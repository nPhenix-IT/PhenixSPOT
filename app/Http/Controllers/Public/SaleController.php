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
use App\Services\KingSmsService;
use App\Services\MoneyFusionService;
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
        $commissionPayer = $settings->commission_payer ?? 'seller';
        $commissionAmount = round(($profile->price * $commissionPercent) / 100, 2);
        $totalPrice = $commissionPayer === 'client'
            ? $profile->price + $commissionAmount
            : $profile->price;

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
            $creditAmount = $profile->price;
            if ($pendingTransaction->commission_payer === 'seller') {
                $creditAmount = max(0, $profile->price - $pendingTransaction->commission_amount);
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
        if ($user && $user->sms_enabled && $pendingTransaction->customer_number && $code && $profile) {
            $message = "Votre code WiFi est: {$code}. Pass: {$profile->name}.";
            $smsSender = $user->sms_sender ?: null;
            app(KingSmsService::class)->sendSms($pendingTransaction->customer_number, $message, $smsSender);
        }

        if ($user && $user->telegram_bot_token && $user->telegram_chat_id && $code && $profile) {
            $salePrice = (float) $profile->price;
            $commissionAmount = (float) ($pendingTransaction->commission_amount ?? 0);
            $commissionPercentFromEnv = (float) config('fees.sales_commission_percent', 0);
            $creditedAmount = $pendingTransaction->commission_payer === 'seller'
                ? max(0, $salePrice - $commissionAmount)
                : $salePrice;

            $telegramMessage = "🛒 <b>Nouvelle vente | e-Ticket</b>\n\n";
            $telegramMessage .= "Pass: {$profile->name}\n";
            $telegramMessage .= "Code: {$code}\n";
            $telegramMessage .= "Client: " . ($pendingTransaction->customer_number ?: 'N/A') . "\n\n";

            if ($pendingTransaction->commission_payer === 'seller') {
                $telegramMessage .= "Prix de vente : " . number_format($salePrice, 0, ',', ' ') . " FCFA\n";
                $telegramMessage .= "Frais: " . rtrim(rtrim(number_format($commissionPercentFromEnv, 2, '.', ''), '0'), '.') . "%\n";
                $telegramMessage .= "Montant credité: " . number_format($creditedAmount, 0, ',', ' ') . " FCFA\n\n";
            } else {
                $telegramMessage .= "Montant credité: " . number_format($creditedAmount, 0, ',', ' ') . " FCFA\n\n";
            }
            
            if ($walletBalance !== null) {
                $telegramMessage .= "👛 <b>Solde Actuel: " . number_format($walletBalance, 0, ',', ' ') . " FCFA<b>\n";
            }

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