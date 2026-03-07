<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SmsPackage;
use App\Models\SmsRechargeTransaction;
use App\Models\Transaction;
use App\Services\MoneyFusionService;
use App\Services\SmsCreditService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmsRechargeController extends Controller
{
    public function buyWithWallet(Request $request, SmsPackage $smsPackage)
    {
        $user = Auth::user();

        if (!$smsPackage->is_active) {
            return back()->with('error', 'Ce pack SMS est indisponible.');
        }

        $wallet = $user->wallet;
        if (!$wallet) {
            return back()->with('error', 'Portefeuille introuvable.');
        }

        $amount = (float) $smsPackage->price_fcfa;

        try {
            DB::transaction(function () use ($user, $wallet, $smsPackage, $amount) {
                $lockedWallet = $wallet->newQuery()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

                if ((float) $lockedWallet->balance < $amount) {
                    throw new \RuntimeException('Solde wallet insuffisant pour acheter ce pack SMS.');
                }

                $lockedWallet->balance = (float) $lockedWallet->balance - $amount;
                $lockedWallet->save();

                Transaction::create([
                    'wallet_id' => $lockedWallet->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'description' => 'Achat pack SMS: ' . $smsPackage->name,
                ]);

                $recharge = SmsRechargeTransaction::create([
                    'user_id' => $user->id,
                    'sms_package_id' => $smsPackage->id,
                    'transaction_id' => 'SMSW-' . strtoupper(Str::random(10)),
                    'payment_method' => 'wallet',
                    'status' => 'completed',
                    'amount_fcfa' => $amount,
                    'credits' => (int) $smsPackage->credits,
                ]);

                app(SmsCreditService::class)->creditFromPackage(
                    $user,
                    $smsPackage->id,
                    (int) $smsPackage->credits,
                    $amount,
                    'wallet',
                    ['recharge_transaction_id' => $recharge->id]
                );
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->notifyTelegramRecharge($user, $smsPackage, $amount, 'wallet');

        return back()->with('success', 'Pack SMS acheté via wallet et crédits ajoutés.');
    }

    public function initiateMoneyFusion(Request $request, SmsPackage $smsPackage)
    {
        $user = Auth::user();

        if (!$smsPackage->is_active) {
            return back()->with('error', 'Ce pack SMS est indisponible.');
        }

        $transactionId = 'SMSM-' . strtoupper(Str::random(12));

        $customerNumber = preg_replace('/\D+/', '', (string) ($request->input('customer_number') ?: $user->phone_number));
        if (empty($customerNumber) || strlen($customerNumber) < 8) {
            return back()->with('error', 'Veuillez renseigner un numéro de téléphone valide dans votre profil pour payer via MoneyFusion.');
        }

        $baseAmount = (float) $smsPackage->price_fcfa;
        $moneyfusionPayinPercent = (float) config('fees.moneyfusion_payin_percent', 3);
        $feeAmount = round(($baseAmount * $moneyfusionPayinPercent) / 100, 0);
        $totalPayable = $baseAmount + $feeAmount;

        $recharge = SmsRechargeTransaction::create([
            'user_id' => $user->id,
            'sms_package_id' => $smsPackage->id,
            'transaction_id' => $transactionId,
            'payment_method' => 'moneyfusion',
            'status' => 'pending',
            'amount_fcfa' => $totalPayable,
            'credits' => (int) $smsPackage->credits,
            'meta' => [
                'base_amount_fcfa' => $baseAmount,
                'fee_amount_fcfa' => $feeAmount,
                'fee_percent' => $moneyfusionPayinPercent,
                'total_payable_fcfa' => $totalPayable,
            ],
        ]);

        $moneyFusion = app(MoneyFusionService::class);

        try {
            $paymentData = $moneyFusion->initiateSimplePayment(
                $user,
                (int) $totalPayable,
                $transactionId,
                route('user.sms-recharges.callback', ['transaction_id' => $transactionId]),
                route('user.sms-recharges.webhook'),
                'Recharge SMS - ' . $smsPackage->name,
                $customerNumber,
                $user->name
            );
        } catch (\Exception $e) {
            $recharge->update(['status' => 'failed', 'meta' => ['error' => $e->getMessage()]]);
            return back()->with('error', 'Impossible de lancer le paiement MoneyFusion: ' . $e->getMessage());
        }

        $paymentUrl = $paymentData['url']
            ?? $paymentData['payment_url']
            ?? $paymentData['redirect_url']
            ?? data_get($paymentData, 'data.url');

        $paymentToken = $paymentData['tokenPay']
            ?? $paymentData['token']
            ?? data_get($paymentData, 'data.tokenPay');

        $recharge->update([
            'payment_token' => $paymentToken,
            'meta' => array_merge((array) ($recharge->meta ?? []), [
                'payment_data' => $paymentData,
                'base_amount_fcfa' => $baseAmount,
                'fee_amount_fcfa' => $feeAmount,
                'fee_percent' => $moneyfusionPayinPercent,
                'total_payable_fcfa' => $totalPayable,
            ]),
        ]);

        if (!$paymentUrl) {
            return back()->with('error', 'MoneyFusion n\'a pas retourné d\'URL de paiement.');
        }

        return redirect()->away($paymentUrl);
    }

    public function callback(Request $request)
    {
        $user = Auth::user();
        $transactionId = $request->query('transaction_id');
        $token = $request->query('tokenPay') ?: $request->query('token');

        $recharge = SmsRechargeTransaction::where('user_id', $user->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$recharge) {
            return redirect()->route('user.sms-history.index')->with('error', 'Transaction de recharge introuvable.');
        }

        if ($recharge->status === 'completed') {
            return redirect()->route('user.sms-history.index')->with('success', 'Recharge SMS déjà validée.');
        }

        if (!$recharge->payment_token && $token) {
            $recharge->update(['payment_token' => $token]);
        }

        if (!$recharge->payment_token) {
            return redirect()->route('user.sms-history.index')->with('error', 'Token de paiement manquant.');
        }

        $moneyFusion = app(MoneyFusionService::class);
        $statusData = $moneyFusion->checkStatus($recharge->payment_token);

        if ($this->isPaid($statusData, $moneyFusion)) {
            $this->completeRecharge($recharge);
            return redirect()->route('user.sms-history.index')->with('success', 'Recharge SMS effectuée avec succès.');
        }

        return redirect()->route('user.sms-history.index')->with('error', 'Le paiement MoneyFusion n\'est pas encore confirmé.');
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();

        $transactionId = data_get($payload, 'personal_Info.0.orderId')
            ?? data_get($payload, 'transaction_id');

        $token = data_get($payload, 'tokenPay') ?? data_get($payload, 'token');

        $recharge = SmsRechargeTransaction::where('transaction_id', $transactionId)->first();

        if (!$recharge && $token) {
            $recharge = SmsRechargeTransaction::where('payment_token', $token)->first();
        }

        if ($recharge && $recharge->status === 'pending') {
            if (!$recharge->payment_token && $token) {
                $recharge->update(['payment_token' => $token]);
            }

            if ($this->isWebhookPaid($payload)) {
                $this->completeRecharge($recharge);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function completeRecharge(SmsRechargeTransaction $recharge): void
    {
        $justCompleted = false;
        $rechargePayload = null;

        DB::transaction(function () use ($recharge, &$justCompleted, &$rechargePayload) {
            $locked = SmsRechargeTransaction::whereKey($recharge->id)->lockForUpdate()->first();
            if (!$locked || $locked->status === 'completed') {
                return;
            }

            $locked->status = 'completed';
            $locked->save();

            app(SmsCreditService::class)->creditFromPackage(
                $locked->user,
                $locked->sms_package_id,
                (int) $locked->credits,
                (float) $locked->amount_fcfa,
                (string) $locked->payment_method,
                ['recharge_transaction_id' => $locked->id, 'transaction_id' => $locked->transaction_id]
            );

            $justCompleted = true;
            $rechargePayload = $locked->loadMissing('package', 'user');
        });

        if ($justCompleted && $rechargePayload) {
            $this->notifyTelegramRecharge(
                $rechargePayload->user,
                $rechargePayload->package,
                (float) $rechargePayload->amount_fcfa,
                (string) $rechargePayload->payment_method
            );
        }
    }

    private function isPaid(array $statusData, MoneyFusionService $moneyFusion): bool
    {
        if ($moneyFusion->isPaid($statusData)) {
            return true;
        }

        $status = strtolower((string) (data_get($statusData, 'data.statut') ?? data_get($statusData, 'statut') ?? ''));
        return in_array($status, ['paid', 'success', 'successful', 'completed'], true);
    }


    private function notifyTelegramRecharge($user, $smsPackage, float $amount, string $paymentMethod): void
    {
        if (!$user || !$user->telegram_bot_token || !$user->telegram_chat_id || !$smsPackage) {
            return;
        }

        $user->refresh();
        $message = "✅ <b>PhenixSPOT | Recharge SMS réussie</b>\n\n";
        $message .= "📦 Pack: <b>{$smsPackage->name}</b>\n";
        $message .= "🔢 Crédits ajoutés: <b>" . number_format((int) $smsPackage->credits, 0, ',', ' ') . " SMS</b>\n";
        $message .= "💳 Montant: <b>" . number_format($amount, 0, ',', ' ') . " FCFA</b>\n";
        $message .= "🏦 Méthode: <b>" . strtoupper($paymentMethod) . "</b>\n\n";
        $message .= "📊 Solde crédit SMS: <b>" . number_format((float) $user->sms_credit_balance, 0, ',', ' ') . " SMS</b>";

        app(TelegramService::class)->sendMessage(
            $user->telegram_bot_token,
            $user->telegram_chat_id,
            $message
        );
    }

    private function isWebhookPaid(array $payload): bool
    {
        $event = strtolower((string) ($payload['event'] ?? ''));
        if (in_array($event, ['payin.session.completed', 'payment.completed', 'payment.success'], true)) {
            return true;
        }

        $status = strtolower((string) (data_get($payload, 'data.statut') ?? data_get($payload, 'statut') ?? ''));
        return in_array($status, ['paid', 'success', 'successful', 'completed'], true);
    }
}
