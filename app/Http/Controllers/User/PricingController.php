<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\PendingVpnAccountPayment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PricingController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();
        return view('content.user.plans.index', compact('plans'));
    }

    public function payment(Plan $plan, string $duration)
    {
        if (!in_array($duration, ['monthly', 'annually'], true)) {
            abort(404);
        }

        $user = Auth::user();
        $wallet = $user->wallet;

        return view('content.user.plans.payment', compact('plan', 'duration', 'wallet'));
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate([
            'coupon_code' => 'required|string',
            'original_price' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $data['coupon_code'])->where('is_active', true)->first();

        if (!$coupon) {
            return response()->json(['error' => 'Code promo invalide ou expiré.'], 404);
        }
        
        $originalPrice = (float) $data['original_price'];
        $discount = 0.0;

        if ($coupon->type === 'fixed') {
            $discount = (float) $coupon->value;
        } elseif ($coupon->type === 'percent') {
            $discount = ($originalPrice * (float) $coupon->value) / 100;
        }

        $finalPrice = max(0, $originalPrice - $discount);

        return response()->json([
            'success' => 'Code promo appliqué !',
            'discount_amount' => number_format($discount, 0, ',', ' '),
            'discount_raw' => round($discount, 2),
            'final_price' => number_format($finalPrice, 0, ',', ' '),
            'final_price_raw' => round($finalPrice, 2),
        ]);
    }

    public function checkout(Request $request, Plan $plan, string $duration)
    {
        if (!in_array($duration, ['monthly', 'annually'], true)) {
            return response()->json(['success' => false, 'message' => 'Durée invalide.'], 422);
        }

        $data = $request->validate([
            'payment_channel' => 'required|in:wallet,moneyfusion',
            'coupon_code' => 'nullable|string',
            'final_price' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();
        $basePrice = $duration === 'annually' ? (float) $plan->price_annually : (float) $plan->price_monthly;
        $discountedPrice = $this->resolveDiscountedPrice($basePrice, $data['coupon_code'] ?? null, $data['final_price'] ?? null);

        $moneyfusionFeePercent = (float) config('fees.moneyfusion_payin_percent', 3);
        $feeAmount = $data['payment_channel'] === 'moneyfusion'
            ? round(($discountedPrice * $moneyfusionFeePercent) / 100, 0)
            : 0.0;

        $totalPayable = $discountedPrice + $feeAmount;

        if ($totalPayable <= 0) {
            $this->activateSubscription($user->id, $plan->id, $duration);

            return response()->json([
                'success' => true,
                'mode' => 'free',
                'message' => 'Abonnement activé avec succès.',
                'redirect' => route('dashboard'),
            ]);
        }

        if ($data['payment_channel'] === 'wallet') {
            return $this->checkoutWithWallet($user->id, $plan, $duration, $discountedPrice, $feeAmount, $totalPayable);
        }

        return $this->checkoutWithMoneyFusion($user->id, $plan, $duration, $discountedPrice, $feeAmount, $totalPayable);
    }

    private function checkoutWithWallet(int $userId, Plan $plan, string $duration, float $subtotal, float $feeAmount, float $totalPayable)
    {
        $reference = 'WALLET-PLAN-' . Str::upper(Str::random(8));
        $notificationPayload = null;

        $result = DB::transaction(function () use ($userId, $plan, $duration, $totalPayable, $reference, &$notificationPayload) {
            $user = User::with('wallet')->lockForUpdate()->findOrFail($userId);
            $wallet = $user->wallet;

            if (!$wallet) {
                throw new \RuntimeException('Portefeuille introuvable.');
            }

            if ((float) $wallet->balance < $totalPayable) {
                throw new \RuntimeException('Solde wallet insuffisant pour ce paiement.');
            }

            $wallet->balance = (float) $wallet->balance - $totalPayable;
            $wallet->save();

            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $totalPayable,
                'description' => sprintf('Souscription plan %s (%s) via wallet [%s]', $plan->name, $duration, $reference),
            ]);

            $subscription = $this->activateSubscription($user->id, $plan->id, $duration);

            $notificationPayload = [
                'user' => $user,
                'amount' => $totalPayable,
                'reference' => $reference,
                'date' => now(),
                'plan_name' => $plan->name,
                'expiration' => $subscription->ends_at,
            ];

            return true;
        });

        if ($result && is_array($notificationPayload)) {
            $this->sendSubscriptionActivatedTelegram(
                $notificationPayload['user'],
                (float) $notificationPayload['amount'],
                (string) $notificationPayload['reference'],
                (string) $notificationPayload['plan_name'],
                $notificationPayload['expiration']
            );
        }

        return response()->json([
            'success' => (bool) $result,
            'mode' => 'wallet',
            'message' => 'Paiement wallet validé. Abonnement activé.',
            'redirect' => route('dashboard'),
            'subtotal' => $subtotal,
            'fee' => $feeAmount,
            'total' => $totalPayable,
        ]);
    }

    private function checkoutWithMoneyFusion(int $userId, Plan $plan, string $duration, float $subtotal, float $feeAmount, float $totalPayable)
    {
        $user = \App\Models\User::findOrFail($userId);
        $transactionId = 'PLAN-' . Str::upper(Str::random(10));

        $pending = PendingVpnAccountPayment::create([
            'transaction_id' => $transactionId,
            'user_id' => $user->id,
            'amount' => (int) round($totalPayable),
            'duration' => $duration === 'annually' ? 12 : 1,
            'payload' => [
                'type' => 'plan_subscription',
                'plan_id' => $plan->id,
                'duration' => $duration,
                'subtotal' => $subtotal,
                'fee_amount' => $feeAmount,
                'total_payable' => $totalPayable,
            ],
            'status' => 'pending',
        ]);

        $moneyFusion = app(\App\Services\MoneyFusionService::class);
        $returnUrl = route('user.plans.payment-callback', ['transaction_id' => $transactionId]);
        $webhookUrl = route('user.plans.payment-webhook');

        try {
            $response = $moneyFusion->initiateSimplePayment(
                $user,
                (int) round($totalPayable),
                $transactionId,
                $returnUrl,
                $webhookUrl,
                'Abonnement SaaS - ' . strtoupper($plan->name)
            );

            $pending->update([
                'payment_token' => $response['tokenPay']
                    ?? $response['token']
                    ?? data_get($response, 'data.tokenPay')
                    ?? data_get($response, 'data.token')
                    ?? null,
            ]);

            $paymentUrl = $response['url']
                ?? $response['payment_url']
                ?? $response['redirect_url']
                ?? (isset($response['tokenPay']) ? 'https://www.pay.moneyfusion.net/pay/' . $response['tokenPay'] : null)
                ?? (isset($response['token']) ? 'https://www.pay.moneyfusion.net/pay/' . $response['token'] : null)
                ?? data_get($response, 'data.url')
                ?? $returnUrl;

            return response()->json([
                'success' => true,
                'mode' => 'moneyfusion',
                'message' => 'Paiement initié. Redirection vers MoneyFusion...',
                'redirect' => $paymentUrl,
            ]);
        } catch (\Throwable $e) {
            $pending->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'initier le paiement MoneyFusion.',
            ], 422);
        }
    }

    private function resolveDiscountedPrice(float $basePrice, ?string $couponCode, $clientFinalPrice): float
    {
        $couponCode = trim((string) $couponCode);
        if ($couponCode === '') {
            return $basePrice;
        }

        $coupon = Coupon::where('code', $couponCode)->where('is_active', true)->first();
        if (!$coupon) {
            return $basePrice;
        }

        if (is_numeric($clientFinalPrice)) {
            return max(0, (float) $clientFinalPrice);
        }

        $discount = 0.0;
        if ($coupon->type === 'fixed') {
            $discount = (float) $coupon->value;
        } elseif ($coupon->type === 'percent') {
            $discount = ($basePrice * (float) $coupon->value) / 100;
        }

        return max(0, $basePrice - $discount);
    }

    private function activateSubscription(int $userId, int $planId, string $duration): Subscription
    {
        if ($planId <= 0) {
            throw new \RuntimeException('Plan invalide.');
        }

        if (!in_array($duration, ['monthly', 'annually'], true)) {
            $duration = 'monthly';
        }

        Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        return Subscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'starts_at' => now(),
            'ends_at' => $duration === 'annually' ? now()->addYear() : now()->addMonth(),
            'status' => 'active',
        ]);
    }

    private function sendSubscriptionActivatedTelegram(User $user, float $amount, string $reference, string $planName, $expiration): void
    {
        if (!$user->telegram_bot_token || !$user->telegram_chat_id) {
            return;
        }

        $formattedAmount = number_format($amount, 0, ',', ' ');
        $formattedDate = now()->format('d/m/Y H:i');
        $formattedExpiration = $expiration instanceof \DateTimeInterface
            ? $expiration->format('d/m/Y H:i')
            : now()->format('d/m/Y H:i');

        $message = "🟢 Paiement Validé — Service Activé\n\n";
        $message .= "Votre transaction a été confirmée avec succès et le provisioning automatique a été exécuté sur votre infrastructure.\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━\n";
        $message .= "💳 Détails de la Transaction\n";
        $message .= "• Montant : {$formattedAmount} FCFA\n";
        $message .= "• Référence : {$reference}\n";
        $message .= "• Date : {$formattedDate}\n";
        $message .= "• Statut : ✅ Confirmé\n";
        $message .= "━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "⚙ Activation du Forfait\n\n";
        $message .= "Le plan {$planName} est désormais actif sur votre compte :\n\n";
        $message .= "• Expiration : {$formattedExpiration}\n\n";
        $message .= "🔐 Le service est pleinement opérationnel.\n\n";
        $message .= "Merci de faire confiance à PhenixSpot —\n";
        $message .= "_Votre solution professionnelle de gestion Hotspot & PPPoE._";

        app(TelegramService::class)->sendMessage(
            $user->telegram_bot_token,
            $user->telegram_chat_id,
            $message
        );
    }

}
