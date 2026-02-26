<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Models\Transaction;
use App\Models\WithdrawalRequest;
use App\Services\MoneyFusionService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class WithdrawalController extends Controller
{
    public function __construct(
        private MoneyFusionService $moneyFusionService,
        private TelegramService $telegramService
    )
    {
    }

    public function index()
    {
        $requests = WithdrawalRequest::with('user')->latest()->limit(500)->get();

        $feePercent = (float) config('fees.platform_markup_percent', 2);
        $incomingTransactions = Transaction::with('wallet.user')
            ->where('type', 'credit')
            ->latest()
            ->limit(500)
            ->get()
            ->map(function ($tx) use ($feePercent) {
                $tx->platform_fee_amount = (float) round(((float) $tx->amount * $feePercent) / 100, 0);
                return $tx;
            });

        $withdrawalFeePercent = (float) config('fees.withdrawal_fee_percent', 5);

        $approvedMonthly = array_fill(1, 12, 0.0);
        $approvedRequests = $requests->where('status', 'approved');
        foreach ($approvedRequests as $req) {
            $m = (int) $req->created_at->format('n');
            $approvedMonthly[$m] += (float) $req->amount;
        }

        $months = collect(range(1, 12))->map(fn ($m) => Carbon::create(null, $m, 1)->locale('fr')->shortMonthName)->values()->all();
        $salesFeesTotal = (float) PendingTransaction::where('status', 'completed')->sum('commission_amount');
        $withdrawFeesTotal = (float) $approvedRequests->sum(function ($req) {
            $details = is_array($req->payment_details) ? $req->payment_details : [];
            return (float) ($details['fee_amount'] ?? 0);
        });

        $totalApprovedWithdrawal = (float) $approvedRequests->sum('amount');
        $totalWithdrawalDebitedAllUsers = (float) $approvedRequests->sum(function ($req) {
            $details = is_array($req->payment_details) ? $req->payment_details : [];
            return (float) ($details['total_debited'] ?? $req->amount);
        });

        $adminKpis = [
            'totalApprovedWithdrawal' => $totalApprovedWithdrawal,
            'totalWithdrawalDebitedAllUsers' => $totalWithdrawalDebitedAllUsers,
            'withdrawFeesTotal' => $withdrawFeesTotal,
            'salesFeesTotal' => $salesFeesTotal,
        ];

        $approvedWithdrawalChart = [
            'months' => $months,
            'approved' => array_values($approvedMonthly),
        ];

        return view('content.admin.withdrawals.index', compact('requests', 'incomingTransactions', 'withdrawalFeePercent', 'feePercent', 'adminKpis', 'approvedWithdrawalChart'));
    }

    public function approve(WithdrawalRequest $withdrawalRequest)
    {
        if ($withdrawalRequest->status !== 'pending') {
            return $this->respondError(request(), 'Cette demande a déjà été traitée.');
        }

        try {
            $result = DB::transaction(function () use ($withdrawalRequest) {
                $lockedRequest = WithdrawalRequest::with('user.wallet')->whereKey($withdrawalRequest->id)->lockForUpdate()->firstOrFail();
                if ($lockedRequest->status !== 'pending') {
                    throw new \RuntimeException('Cette demande a déjà été traitée.');
                }

                $user = $lockedRequest->user;
                $wallet = $user?->wallet;
                if (!$wallet) {
                    throw new \RuntimeException('Portefeuille utilisateur introuvable.');
                }

                $paymentDetails = is_array($lockedRequest->payment_details) ? $lockedRequest->payment_details : [];
                $method = (string) ($lockedRequest->withdraw_mode ?: ($paymentDetails['withdraw_mode'] ?? $paymentDetails['method'] ?? ''));
                $phone = preg_replace('/\s+/', '', (string) ($lockedRequest->phone_number ?: ($paymentDetails['phone'] ?? '')));
                $countryCode = strtolower((string) ($lockedRequest->country_code ?: ($paymentDetails['country_code'] ?? ($user->country_code ?? 'ci'))));

                if ($phone === '') {
                    throw new \RuntimeException('Numéro de retrait manquant sur la demande.');
                }

                $withdrawMode = $method;
                if ($withdrawMode === '' || !str_contains($withdrawMode, '-')) {
                    if (!method_exists($this->moneyFusionService, 'resolveWithdrawMode')) {
                        throw new \RuntimeException('Service MoneyFusion incomplet: resolveWithdrawMode() manquant.');
                    }
                    $withdrawMode = $this->moneyFusionService->resolveWithdrawMode($method, $countryCode);
                }

                $feePercent = (float) config('fees.withdrawal_fee_percent', 5);
                $feeAmount = (float) round(($lockedRequest->amount * $feePercent) / 100, 0);
                $totalDebited = (float) $lockedRequest->amount + $feeAmount;

                if ((float) $wallet->balance < $totalDebited) {
                    throw new \RuntimeException('Solde insuffisant pour valider ce retrait (montant + frais).');
                }

                $transferResponse = $this->moneyFusionService->initiateWithdrawal(
                    countryCode: $countryCode,
                    phone: $phone,
                    amount: $lockedRequest->amount,
                    withdrawMode: $withdrawMode,
                    webhookUrl: route('api.withdrawals.moneyfusion.webhook'),
                );

                $wallet->balance -= $totalDebited;
                $wallet->save();

                Transaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $totalDebited,
                    'description' => 'Retrait approuvé (' . number_format($lockedRequest->amount, 0, ',', ' ') . ' + frais ' . number_format($feeAmount, 0, ',', ' ') . ') - ' . strtoupper($withdrawMode) . ' - ' . ($transferResponse['tokenPay'] ?? 'N/A'),
                ]);

                $lockedRequest->country_code = $countryCode;
                $lockedRequest->withdraw_mode = $withdrawMode;
                $lockedRequest->phone_number = $phone;
                $lockedRequest->payment_details = array_merge($paymentDetails, [
                    'country_code' => $countryCode,
                    'withdraw_mode' => $withdrawMode,
                    'fee_percent' => $feePercent,
                    'fee_amount' => $feeAmount,
                    'total_debited' => $totalDebited,
                    'payout_token' => $transferResponse['tokenPay'] ?? null,
                    'payout_message' => $transferResponse['message'] ?? null,
                    'payout_status' => 'initiated',
                    'approved_at' => now()->toDateTimeString(),
                ]);
                $lockedRequest->status = 'approved';
                $lockedRequest->save();

                if ($user->telegram_bot_token && $user->telegram_chat_id) {
                    $message = "✅ Votre demande de retrait de fonds a ete approuvée. Vous recevrez vos fonds des que l'operateur valide le transfert.\n";
                    $message .= 'Montant: ' . number_format((float) $lockedRequest->amount, 0, ',', ' ') . " FCFA\n";
                    $message .= 'Frais: ' . number_format($feeAmount, 0, ',', ' ') . " FCFA\n";
                    $message .= 'Token: ' . ($transferResponse['tokenPay'] ?? 'N/A');
                    $this->telegramService->sendMessage($user->telegram_bot_token, $user->telegram_chat_id, $message);
                }

                return [
                    'id' => $lockedRequest->id,
                    'status' => $lockedRequest->status,
                    'fee_amount' => $feeAmount,
                    'total_debited' => $totalDebited,
                    'message' => 'Retrait approuvé et transfert initié avec succès.',
                ];
            });

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => true] + $result);
            }

            return redirect()->back()->with('success', $result['message']);
        } catch (Throwable $e) {
            Log::error('Withdrawal approval failed', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'message' => $e->getMessage(),
            ]);

            return $this->respondError(request(), 'Échec du transfert: ' . $e->getMessage());
        }
    }

    public function moneyfusionWebhook(Request $request)
    {
        $payload = $request->all();
        $tokenPay = (string) ($payload['tokenPay'] ?? '');
        $event = (string) ($payload['event'] ?? '');

        if ($tokenPay === '' || $event === '') {
            return response()->json(['ok' => false, 'message' => 'tokenPay/event requis'], 422);
        }
        
        $mappedStatus = $this->moneyFusionService->mapWithdrawalWebhookEvent($event);
        if (!$mappedStatus) {
            return response()->json(['ok' => true, 'message' => 'event ignoré']);
        }

        DB::transaction(function () use ($tokenPay, $mappedStatus, $payload) {
            $withdrawal = WithdrawalRequest::with('user.wallet')->where('payment_details->payout_token', $tokenPay)
                ->lockForUpdate()
                ->first();
                
            if (!$withdrawal) {
                return;
            }
            
            $details = is_array($withdrawal->payment_details) ? $withdrawal->payment_details : [];
            $currentPayoutStatus = (string) ($details['payout_status'] ?? '');
            
            if (in_array($currentPayoutStatus, ['completed', 'cancelled'], true)) {
                return;
            }

            $details['payout_status'] = $mappedStatus;
            $details['webhook_event'] = $event;
            $details['webhook_payload'] = $payload;
            $details['webhook_received_at'] = now()->toDateTimeString();

            if ($mappedStatus === 'cancelled') {
                $wallet = $withdrawal->user?->wallet;
                $refundable = (float) ($details['total_debited'] ?? $withdrawal->amount);

                if ($wallet) {
                    $wallet->balance += $refundable;
                    $wallet->save();
                    
                    Transaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'credit',
                        'amount' => $refundable,
                        'description' => 'Remboursement retrait annulé - ' . $tokenPay,
                    ]);
                }

                $withdrawal->status = 'rejected';
            }

            $withdrawal->payment_details = $details;
            $withdrawal->save();
        });

        return redirect()->back()->with('success', 'La demande de retrait a été approuvée.');
        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, WithdrawalRequest $withdrawalRequest)
    {
        if ($withdrawalRequest->status !== 'pending') {
            return $this->respondError($request, 'Cette demande a déjà été traitée.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:3|max:500',
        ]);

        $details = is_array($withdrawalRequest->payment_details) ? $withdrawalRequest->payment_details : [];
        $details['rejection_reason'] = $validated['rejection_reason'];

        $withdrawalRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'payment_details' => $details,
        ]);

        $user = $withdrawalRequest->user;
        if ($user && $user->telegram_bot_token && $user->telegram_chat_id) {
            $message = "❌ Votre demande de retrait a ete rejetée.\n";
            $message .= 'Raison: ' . $validated['rejection_reason'];
            $this->telegramService->sendMessage($user->telegram_bot_token, $user->telegram_chat_id, $message);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'id' => $withdrawalRequest->id,
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'message' => 'La demande de retrait a été rejetée.',
            ]);
        }

        return redirect()->back()->with('success', 'La demande de retrait a été rejetée.');
    }

    private function respondError(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->back()->with('error', $message);
    }
}