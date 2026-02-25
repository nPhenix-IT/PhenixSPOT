<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Models\Voucher;
use App\Models\Radcheck;
use App\Models\Radusergroup;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Profile;
use App\Services\KingSmsService;
use App\Services\MoneyFusionService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Gère la notification webhook de Money Fusion.
     * C'est la méthode la plus fiable pour confirmer un paiement.
     */
    public function webhook(Request $request)
    {
        // Enregistrer la requête entrante pour le débogage
        Log::info('Webhook Money Fusion reçu :', $request->all());

        $data = $request->all();
        $event = $data['event'] ?? null;
        $personalInfo = $data['personal_Info'][0] ?? [];
        $transactionId = $personalInfo['orderId']
            ?? $personalInfo['transaction_id']
            ?? $data['transaction_id']
            ?? null;
        $tokenPay = $data['tokenPay'] ?? null;

        // On ne traite que les événements de paiement complété et si on a notre ID de transaction
        if ($event === 'payin.session.completed') {
            $pendingTransaction = $transactionId
                ? PendingTransaction::where('transaction_id', $transactionId)->first()
                : null;
            if (!$pendingTransaction && $tokenPay) {
                $pendingTransaction = PendingTransaction::where('payment_token', $tokenPay)->first();
            }

            // Vérifier si la transaction existe et est bien en attente pour éviter les doublons
            if ($pendingTransaction && $pendingTransaction->status === 'pending') {
                $this->processCompletedPayment($pendingTransaction);
            }
        }

        // Toujours retourner une réponse 200 OK pour que le webhook ne soit pas renvoyé
        return response()->json(['status' => 'success']);
    }

    /**
     * Gère le retour du client après le paiement.
     */
    public function callback(Request $request)
    {
        // Cette page est une simple confirmation pour le client.
        // La logique métier est gérée par le webhook pour plus de sécurité.
        $pageConfigs = ['myLayout' => 'blank'];
        $transactionId = $request->query('transaction_id');
        $pendingTransaction = $transactionId
            ? PendingTransaction::where('transaction_id', $transactionId)->first()
            : null;
        if ($pendingTransaction && $pendingTransaction->status === 'pending' && $pendingTransaction->payment_token) {
            $moneyFusion = app(MoneyFusionService::class);
            $statusData = $moneyFusion->checkStatus($pendingTransaction->payment_token);
            if (!empty($statusData) && $moneyFusion->isPaid($statusData)) {
                $this->processCompletedPayment($pendingTransaction);
            }
        }
        $voucherCode = $pendingTransaction?->voucher_code;
        $user = $pendingTransaction ? User::find($pendingTransaction->user_id) : null;
        $dns = $user?->salePageSetting?->login_dns;
        if ($dns) {
            $loginUrl = str_starts_with($dns, 'http://') || str_starts_with($dns, 'https://')
                ? $dns
                : 'http://' . $dns. '/login?username=' .$voucherCode . '&password=' .$voucherCode;
        } else {
            $loginUrl = $pendingTransaction?->login_url
                ?: ($user ? route('public.sale.show', $user->slug) : url('/'));
        }

        return view('content.public.payment_status', compact('pageConfigs', 'voucherCode', 'loginUrl'));
    }

    private function processCompletedPayment(PendingTransaction $pendingTransaction): void
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
        $walletBalance = $user->wallet->balance;
        if ($user && $user->sms_enabled && $pendingTransaction->customer_number && $code && $profile) {
            $message = "Votre code WiFi est: {$code}. Pass: {$profile->name}.";
            $smsSender = $user->sms_sender ?: null;
            app(KingSmsService::class)->sendSms($pendingTransaction->customer_number, $message, $smsSender);
        }

        if ($user && $user->telegram_bot_token && $user->telegram_chat_id && $code && $profile) {
            $telegramMessage = "🛒 <b>Nouvelle vente - e-Ticket</b>\n\n";
            $telegramMessage .= "Pass: {$profile->name}\n";
            $telegramMessage .= "Code: {$code}\n";
            $telegramMessage .= "Montant: " . number_format($profile->price, 0, ',', ' ') . " FCFA\n";
            // if ($pendingTransaction->customer_number) {
                $telegramMessage .= "Client: {$pendingTransaction->customer_number}\n\n";
            // }
            if ($walletBalance !== null) {
                $telegramMessage .= "👛 <b>Solde Actuel</b>: " . number_format($walletBalance, 0, ',', ' ') . " FCFA\n";
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