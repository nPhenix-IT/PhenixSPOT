<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PendingVpnAccountPayment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MoneyFusionService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanPaymentController extends Controller
{
    public function callback(Request $request)
    {
        $user = Auth::user();
        $transactionId = (string) $request->query('transaction_id', '');
        $callbackToken = (string) ($request->query('tokenPay') ?: $request->query('token') ?: '');

        $pending = $transactionId !== ''
            ? PendingVpnAccountPayment::where('transaction_id', $transactionId)
                ->where('user_id', $user->id)
                ->first()
            : null;

        if (!$pending && $callbackToken !== '') {
            $pending = PendingVpnAccountPayment::where('payment_token', $callbackToken)
                ->where('user_id', $user->id)
                ->first();
        }

        if ($pending && empty($pending->payment_token) && $callbackToken !== '') {
            $pending->update(['payment_token' => $callbackToken]);
            $pending->refresh();
        }

        $status = 'failed';
        $message = 'Transaction introuvable.';

        if ($pending) {
            if ($pending->status === 'completed') {
                $status = 'success';
                $message = 'Votre abonnement a été activé avec succès.';
            } else {
                $finalized = $this->checkAndFinalizePlanPayment($pending);
                if ($finalized) {
                    $status = 'success';
                    $message = 'Votre abonnement a été activé avec succès.';
                } else {
                    $status = 'pending';
                    $message = 'Le paiement est en cours de traitement. Vérifiez à nouveau dans quelques instants.';
                }
            }
        }

        return view('content.user.plans.payment_status', [
            'status' => $status,
            'message' => $message,
            'transactionId' => $transactionId,
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => 'required|string|max:100',
        ]);

        $user = Auth::user();
        $pending = PendingVpnAccountPayment::where('transaction_id', $data['transaction_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$pending) {
            return response()->json(['success' => false, 'status' => 'failed', 'message' => 'Transaction introuvable.'], 404);
        }

        if ($pending->status === 'completed') {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Votre abonnement est déjà actif.',
                'redirect' => route('dashboard'),
            ]);
        }

        if ($this->checkAndFinalizePlanPayment($pending)) {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Paiement confirmé. Abonnement activé.',
                'redirect' => route('dashboard'),
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'message' => 'Paiement toujours en cours de traitement.',
        ]);
    }

    public function webhook(Request $request)
    {
        Log::info('Webhook MoneyFusion abonnement reçu :', $request->all());

        $data = $request->all();
        $event = strtolower((string) ($data['event'] ?? ''));
        $personalInfo = $data['personal_Info'][0] ?? [];

        $transactionId = $personalInfo['orderId']
            ?? $personalInfo['transaction_id']
            ?? $data['transaction_id']
            ?? null;

        $token = $data['tokenPay']
            ?? $data['token']
            ?? data_get($data, 'data.tokenPay')
            ?? data_get($data, 'data.token')
            ?? null;

        if (!$this->isSuccessfulWebhookPayload($data, $event)) {
            return response()->json(['status' => 'ignored']);
        }

        $pending = $transactionId
            ? PendingVpnAccountPayment::where('transaction_id', $transactionId)->first()
            : null;

        if (!$pending && $token) {
            $pending = PendingVpnAccountPayment::where('payment_token', $token)->first();
        }

        if ($pending && empty($pending->payment_token) && $token) {
            $pending->update(['payment_token' => $token]);
            $pending->refresh();
        }

        if ($pending && $pending->status === 'pending') {
            $this->checkAndFinalizePlanPayment($pending, true);
        }

        return response()->json(['status' => 'success']);
    }

    private function checkAndFinalizePlanPayment(PendingVpnAccountPayment $pending, bool $trustedWebhook = false): bool
    {
        if (!$trustedWebhook && empty($pending->payment_token)) {
            return false;
        }

        if (!$trustedWebhook) {
            $moneyFusion = app(MoneyFusionService::class);
            $statusData = $moneyFusion->checkStatus((string) $pending->payment_token);

            if (!$this->isSuccessfulStatusPayload($statusData, $moneyFusion)) {
                return false;
            }
        }

        $notificationPayload = null;

        $finalized = DB::transaction(function () use ($pending, &$notificationPayload) {
            $locked = PendingVpnAccountPayment::whereKey($pending->id)->lockForUpdate()->first();
            if (!$locked) {
                return false;
            }

            if ($locked->status === 'completed') {
                return true;
            }

            $payload = is_array($locked->payload) ? $locked->payload : [];
            $planId = (int) ($payload['plan_id'] ?? 0);
            $duration = (string) ($payload['duration'] ?? 'monthly');

            if ($planId <= 0) {
                return false;
            }

            if (!in_array($duration, ['monthly', 'annually'], true)) {
                $duration = 'monthly';
            }

            Subscription::where('user_id', $locked->user_id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            $subscription = Subscription::create([
                'user_id' => $locked->user_id,
                'plan_id' => $planId,
                'starts_at' => now(),
                'ends_at' => $duration === 'annually' ? now()->addYear() : now()->addMonth(),
                'status' => 'active',
            ]);

            $locked->update(['status' => 'completed']);
            $pending->forceFill(['status' => 'completed']);

            $planName = Plan::whereKey($planId)->value('name') ?? 'Forfait SaaS';
            $notificationPayload = [
                'user_id' => (int) $locked->user_id,
                'amount' => (float) ($payload['total_payable'] ?? $locked->amount ?? 0),
                'reference' => (string) ($locked->transaction_id ?? ''),
                'date' => now(),
                'plan_name' => $planName,
                'expiration' => $subscription->ends_at,
            ];

            return true;
        });

        if ($finalized && is_array($notificationPayload)) {
            $this->sendSubscriptionActivatedTelegram($notificationPayload);
        }

        return $finalized;
    }

    private function sendSubscriptionActivatedTelegram(array $payload): void
    {
        $user = User::find((int) ($payload['user_id'] ?? 0));
        if (!$user || !$user->telegram_bot_token || !$user->telegram_chat_id) {
            return;
        }

        $amount = number_format((float) ($payload['amount'] ?? 0), 0, ',', ' ');
        $reference = (string) ($payload['reference'] ?? '-');
        $date = ($payload['date'] ?? now()) instanceof \DateTimeInterface
            ? ($payload['date'])->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');
        $planName = (string) ($payload['plan_name'] ?? 'Forfait PhenixSPOT');
        $expiration = ($payload['expiration'] ?? now()) instanceof \DateTimeInterface
            ? ($payload['expiration'])->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');

        $message = "🟢 Paiement Validé — Service Activé\n\n";
        $message .= "Votre transaction a été confirmée avec succès et le provisioning automatique a été exécuté sur votre infrastructure.\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━\n";
        $message .= "💳 Détails de la Transaction\n";
        $message .= "• Montant : {$amount} FCFA\n";
        $message .= "• Référence : {$reference}\n";
        $message .= "• Date : {$date}\n";
        $message .= "• Statut : ✅ Confirmé\n";
        $message .= "━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "⚙ Activation du Forfait\n\n";
        $message .= "Le plan {$planName} est désormais actif sur votre compte :\n\n";
        $message .= "• Expiration : {$expiration}\n\n";
        $message .= "🔐 Le service est pleinement opérationnel.\n\n";
        $message .= "Merci de faire confiance à PhenixSpot —\n";
        $message .= "_Votre solution professionnelle de gestion Hotspot & PPPoE._";

        app(TelegramService::class)->sendMessage(
            $user->telegram_bot_token,
            $user->telegram_chat_id,
            $message
        );
    }

    private function isSuccessfulWebhookPayload(array $data, string $event): bool
    {
        if (in_array($event, ['payin.session.completed', 'payment.completed', 'payment.success'], true)) {
            return true;
        }

        $statusCandidates = [
            strtolower((string) ($data['status'] ?? '')),
            strtolower((string) ($data['payment_status'] ?? '')),
            strtolower((string) data_get($data, 'data.status', '')),
            strtolower((string) data_get($data, 'data.statut', '')),
            strtolower((string) ($data['statut'] ?? '')),
        ];

        return collect($statusCandidates)
            ->contains(fn ($value) => in_array($value, ['paid', 'success', 'succeeded', 'completed', 'true', '1'], true));
    }

    private function isSuccessfulStatusPayload(array $statusData, MoneyFusionService $moneyFusion): bool
    {
        if ($moneyFusion->isPaid($statusData)) {
            return true;
        }

        $statusCandidates = [
            strtolower((string) ($statusData['status'] ?? '')),
            strtolower((string) ($statusData['payment_status'] ?? '')),
            strtolower((string) data_get($statusData, 'data.status', '')),
            strtolower((string) data_get($statusData, 'data.statut', '')),
            strtolower((string) ($statusData['statut'] ?? '')),
        ];

        return collect($statusCandidates)
            ->contains(fn ($value) => in_array($value, ['paid', 'success', 'succeeded', 'completed', 'true', '1'], true));
    }
}
