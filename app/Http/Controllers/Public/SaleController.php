<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Models\Profile;
use App\Models\Radcheck;
use App\Models\Radusergroup;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\OnsiteSaleWallet; // ✅ AJOUT
use App\Services\KingSmsService;
use App\Services\MoneyFusionService;
use App\Services\SmsCreditService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
  public function show($slug)
  {
    $user = User::where('slug', $slug)->firstOrFail();
    $profiles = $user->profiles()->get();
    $dataProfiles = $profiles->where('limit_type', 'data')->values();
    $hourProfiles = $profiles->where('limit_type', '!=', 'data')->values();
    $settings = $user->salePageSetting;
    $commissionPercent = $settings->commission_percent ?? config('fees.sales_commission_percent');
    $pageConfigs = ['myLayout' => 'blank'];

    return view('content.public.sale', compact(
      'user',
      'profiles',
      'dataProfiles',
      'hourProfiles',
      'pageConfigs',
      'settings',
      'commissionPercent'
    ));
  }

  public function purchase(Request $request, $slug)
  {
    $request->validate([
      'profile_id' => 'required|exists:profiles,id',
      'customer_name' => 'required|string',
      'customer_number' => 'required|string',
      'login_url' => 'nullable|url',
      'router_id' => 'nullable|exists:routers,id',
    ]);

    $user = User::where('slug', $slug)->firstOrFail();
    $profile = $user->profiles()
      ->whereKey($request->profile_id)
      ->where('price', '>=', 200)
      ->firstOrFail();

    $transactionId = 'TXN-' . strtoupper(Str::random(12));
    $settings = $user->salePageSetting;
    $commissionPercent = $settings->commission_percent ?? config('fees.sales_commission_percent');

    // ✅ NOUVEAU: on accepte "split" (50/50) sans casser les autres valeurs
    $commissionPayer = $settings->commission_payer ?? 'seller';

    $commissionAmount = round(($profile->price * $commissionPercent) / 100, 2);

    // ✅ NOUVEAU: calcul du total selon scénario
    // - seller: client paie prix affiché (price)
    // - client: client paie price + fee
    // - split: client paie price + (fee/2)
    $halfFee = round($commissionAmount / 2, 2);
    $totalPrice = $commissionPayer === 'client'
      ? $profile->price + $commissionAmount
      : ($commissionPayer === 'split'
        ? $profile->price + $halfFee
        : $profile->price);

    $loginUrl = $request->input('login_url');

    $pendingTransaction = PendingTransaction::create([
      'transaction_id' => $transactionId,
      'user_id' => $user->id,
      'router_id' => $request->input('router_id'),
      'profile_id' => $profile->id,
      'customer_name' => $request->customer_name,
      'customer_number' => $request->customer_number,
      'login_url' => $loginUrl,
      'commission_payer' => $commissionPayer,
      // ✅ IMPORTANT: on conserve commission_amount = frais COMPLETS (utile pour crédit vendeur)
      'commission_amount' => $commissionAmount,
      'total_price' => $totalPrice,
      'status' => 'pending', // ✅ AJOUT ICI
    ]);

    if ((float) $totalPrice <= 0) {
      $this->completeFreePurchase($pendingTransaction);
      return redirect()->route('public.payment.callback', ['transaction_id' => $transactionId]);
    }

    $moneyFusion = app(MoneyFusionService::class);
    try {
      $paymentData = $moneyFusion->initiatePayment(
        $user,
        $profile,
        $totalPrice,
        $transactionId,
        route('public.payment.callback', ['transaction_id' => $transactionId]),
        route('public.payment.webhook'),
        $request->customer_name,
        $request->customer_number
      );
    } catch (\Exception $exception) {
      return back()->with('error', 'Le service de paiement est indisponible.');
    }
    $paymentUrl = $paymentData['url']
      ?? $paymentData['payment_url']
      ?? $paymentData['redirect_url']
      ?? data_get($paymentData, 'data.url')
      ?? null;

    $paymentToken = $paymentData['tokenPay']
      ?? $paymentData['token']
      ?? data_get($paymentData, 'data.tokenPay')
      ?? null;

    $pendingTransaction->update(['payment_token' => $paymentToken]);

    if (empty($paymentUrl)) {
      return back()->with('error', 'Impossible d\'ouvrir la page Money Fusion. Veuillez réessayer.');
    }

    return redirect()->away($paymentUrl);
  }

  private function completeFreePurchase(PendingTransaction $pendingTransaction): void
  {
    $profile = null;
    $code = null;

    DB::transaction(function () use ($pendingTransaction, &$profile, &$code) {
      $user = User::find($pendingTransaction->user_id);
      $profile = Profile::find($pendingTransaction->profile_id);

      $wallet = $user->wallet;

      // ✅ NOUVEAU: gestion du split 50/50
      $creditAmount = (float) $profile->price;

      if ($pendingTransaction->commission_payer === 'seller') {
        $creditAmount = max(0, (float) $profile->price - (float) $pendingTransaction->commission_amount);
      } elseif ($pendingTransaction->commission_payer === 'split') {
        $fullFee = (float) ($pendingTransaction->commission_amount ?? 0);
        $customerFee = max(0, (float) ($pendingTransaction->total_price ?? 0) - (float) $profile->price);
        $sellerFee = max(0, $fullFee - $customerFee);
        $creditAmount = max(0, (float) $profile->price - $sellerFee);
      }

      $code = $this->generateVoucherCode();
      Voucher::create([
        'user_id' => $user->id,
        'profile_id' => $profile->id,
        'code' => $code,
        'source' => 'public_sale',
        'wallet_credited_at' => now(),
      ]);

      Radcheck::create(['username' => $code, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $code]);
      Radusergroup::create(['username' => $code, 'groupname' => $profile->name]);

      $wallet->balance += $creditAmount;
      $wallet->save();
      $walletBalance = $wallet->balance;

      Transaction::create([
        'wallet_id' => $wallet->id,
        'type' => 'credit',
        'amount' => $creditAmount,
        'description' => 'Vente du voucher ' . $code,
      ]);

      $pendingTransaction->update([
        'status' => 'completed',
        'voucher_code' => $code,
      ]);
    });

    $user = User::find($pendingTransaction->user_id);
    $wallet = $user->wallet;
    $walletBalance = $wallet->balance;

    // ✅ AJOUT: solde vente physique (onsite)
    $onsiteBalance = (int) OnsiteSaleWallet::where('user_id', $user->id)
      ->where('type', 'credit')
      ->sum('amount');

    if ($user && $user->sms_enabled && $pendingTransaction->customer_number && $code && $profile) {
      $message = "Votre code WiFi est: {$code}. Pass: {$profile->name}.";
      $smsCredit = app(SmsCreditService::class);
      $smsSender = $smsCredit->getSenderNameFor($user);
      $allowed = $smsCredit->debitAndLog($user, [
        'recipient' => $pendingTransaction->customer_number,
        'sender_name' => $smsSender,
        'message' => $message,
        'context' => 'voucher_sale',
        'meta' => ['transaction_id' => $pendingTransaction->transaction_id],
      ]);

      if ($allowed) {
        $sent = app(KingSmsService::class)->sendSms($pendingTransaction->customer_number, $message, $smsSender);
        $smsCredit->markLastDeliveryStatus($user, $pendingTransaction->customer_number, $message, $sent);
      }
    }

    if ($user && $user->telegram_bot_token && $user->telegram_chat_id && $code && $profile) {
      $salePrice = (float) $profile->price;

      $total = (int) $onsiteBalance + (int) $walletBalance;
      $fullFee = (float) ($pendingTransaction->commission_amount ?? 0);
      $commissionPercentFromEnv = (float) config('fees.sales_commission_percent', 0);

      if ($pendingTransaction->commission_payer === 'seller') {
        $creditedAmount = max(0, $salePrice - $fullFee);
      } elseif ($pendingTransaction->commission_payer === 'split') {
        $customerFee = max(0, (float) ($pendingTransaction->total_price ?? 0) - $salePrice);
        $sellerFee = max(0, $fullFee - $customerFee);
        $creditedAmount = max(0, $salePrice - $sellerFee);
      } else {
        $creditedAmount = $salePrice;
      }

      $telegramMessage = "✅ <b>PhenixSpot | Vente confirmée</b>\n";
      $telegramMessage .= "Type: <b>e-Ticket (Vente en ligne)</b>\n\n";

      $telegramMessage .= "🎫 Pass: <b>{$profile->name}</b>\n";
      $telegramMessage .= "🔑 Code: <code>{$code}</code>\n";
      $telegramMessage .= "👤 Client: <b>" . ($pendingTransaction->customer_number ?: 'N/A') . "</b>\n\n";

      if ($pendingTransaction->commission_payer === 'seller') {
        $telegramMessage .= "🏷 Prix de vente: <b>" . number_format($salePrice, 0, ',', ' ') . " FCFA</b>\n";
        $telegramMessage .= "🧾 Frais: <b>" . rtrim(rtrim(number_format($commissionPercentFromEnv, 2, '.', ''), '0'), '.') . "%</b>\n";
        $telegramMessage .= "💵 Montant crédité: <b>" . number_format($creditedAmount, 0, ',', ' ') . " FCFA</b>\n\n";
      } elseif ($pendingTransaction->commission_payer === 'split') {
        $customerFee = max(0, (float) ($pendingTransaction->total_price ?? 0) - $salePrice);
        $sellerFee = max(0, $fullFee - $customerFee);

        $telegramMessage .= "🏷 Prix du code: <b>" . number_format($salePrice, 0, ',', ' ') . " FCFA</b>\n";
        $telegramMessage .= "🧾 Frais: <b>" . rtrim(rtrim(number_format($commissionPercentFromEnv, 2, '.', ''), '0'), '.') . "%</b>\n";
        $telegramMessage .= "👤 Frais (client): <b>" . number_format($customerFee, 0, ',', ' ') . " FCFA</b>\n";
        $telegramMessage .= "🧑‍💼 Frais (vendeur): <b>" . number_format($sellerFee, 0, ',', ' ') . " FCFA</b>\n";
        $telegramMessage .= "💵 Montant crédité: <b>" . number_format($creditedAmount, 0, ',', ' ') . " FCFA</b>\n\n";
      } else {
        $telegramMessage .= "💵 Montant crédité: <b>" . number_format($creditedAmount, 0, ',', ' ') . " FCFA</b>\n\n";
      }

      // Section soldes (toujours les 2)
      $telegramMessage .= "📊 <b>Soldes</b>\n";
      $telegramMessage .= "🏪 Vente Physique: <b>" . number_format((int) $onsiteBalance, 0, ',', ' ') . " FCFA</b>\n";
      $telegramMessage .= "🌐 Vente en ligne: <b>" . number_format((int) $walletBalance, 0, ',', ' ') . " FCFA</b>\n\n";
      $telegramMessage .= "🧮 Total (Physique + En ligne): <b>" . number_format($total, 0, ',', ' ') . " FCFA</b>\n";

      app(TelegramService::class)->sendMessage(
        $user->telegram_bot_token,
        $user->telegram_chat_id,
        $telegramMessage
      );
    }
  }

  private function generateVoucherCode(int $length = 6): string
  {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
      $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
  }
}