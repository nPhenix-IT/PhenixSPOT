<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $wallet = $user->wallet()->firstOrCreate(['user_id' => $user->id]);

        $countryCode = strtolower((string) ($user->country_code ?? 'ci'));
        $withdrawOptions = $this->getWithdrawModesByCountry($countryCode);
        $withdrawFeePercent = (float) config('fees.withdrawal_fee_percent', 5);

        $incomingTransactions = $wallet->transactions()
            ->where('type', 'credit')
            ->latest()
            ->limit(300)
            ->get();

        $withdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->latest()
            ->limit(300)
            ->get();

        $months = collect(range(1, 12))->map(fn ($m) => Carbon::create(null, $m, 1)->locale('fr')->shortMonthName)->values();
        $creditsByMonth = array_fill(1, 12, 0.0);
        foreach ($incomingTransactions as $tx) {
            $m = (int) $tx->created_at->format('n');
            $creditsByMonth[$m] += (float) $tx->amount;
        }

        $withdrawalsByMonth = array_fill(1, 12, 0.0);
        foreach ($withdrawals as $w) {
            $m = (int) $w->created_at->format('n');
            $details = is_array($w->payment_details) ? $w->payment_details : [];
            $withdrawalsByMonth[$m] += (float) ($details['total_debited'] ?? $w->amount);
        }

        $incomeVsWithdrawal = [
            'months' => $months->all(),
            'credits' => array_values($creditsByMonth),
            'withdrawals' => array_values($withdrawalsByMonth),
        ];

        return view('content.wallet.index', compact(
            'wallet',
            'withdrawOptions',
            'countryCode',
            'withdrawFeePercent',
            'incomingTransactions',
            'withdrawals',
            'incomeVsWithdrawal'
        ));
    }

    public function withdraw(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->wallet;
        
        if (!$wallet) {
            return $this->respondError($request, 'Portefeuille introuvable.');
        }

        $countryCode = strtolower((string) ($user->country_code ?? 'ci'));
        $withdrawOptions = $this->getWithdrawModesByCountry($countryCode);

        if (empty($withdrawOptions)) {
            return $this->respondError($request, 'Aucune méthode de retrait disponible pour votre pays.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:5000',
            'payment_method' => ['required', 'string', Rule::in(array_keys($withdrawOptions))],
            'phone_number' => 'required|string|max:30',
        ]);

        $feePercent = (float) config('fees.withdrawal_fee_percent', 5);
        $requestedAmount = (float) $validated['amount'];
        $feeAmount = (float) round(($requestedAmount * $feePercent) / 100, 0);
        $totalDebited = $requestedAmount + $feeAmount;

        if ((float) $wallet->balance < $totalDebited) {
            return $this->respondError($request, 'Solde insuffisant. Total requis: ' . number_format($totalDebited, 0, ',', ' ') . ' FCFA.');
        }

        $withdrawal = WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount' => $requestedAmount,
            'country_code' => $countryCode,
            'withdraw_mode' => $validated['payment_method'],
            'phone_number' => $validated['phone_number'],
            'payment_details' => [
                'country_code' => $countryCode,
                'method_label' => $withdrawOptions[$validated['payment_method']] ?? $validated['payment_method'],
                'method' => $validated['payment_method'],
                'withdraw_mode' => $validated['payment_method'],
                'phone' => $validated['phone_number'],
                'fee_percent' => $feePercent,
                'fee_amount' => $feeAmount,
                'total_debited' => $totalDebited,
            ],
        ]);

        $message = 'Votre demande a été envoyée. Vous recevrez vos fonds dès validation par l\'administration.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'created_at' => $withdrawal->created_at->format('d/m/Y H:i'),
                    'amount' => (float) $withdrawal->amount,
                    'fee_amount' => $feeAmount,
                    'total_debited' => $totalDebited,
                    'method_label' => $withdrawOptions[$validated['payment_method']] ?? $validated['payment_method'],
                    'status' => $withdrawal->status,
                    'rejection_reason' => $withdrawal->rejection_reason,
                ],
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    private function respondError(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->back()->with('error', $message);
    }

    /**
     * @return array<string, string>
     */
    private function getWithdrawModesByCountry(string $countryCode): array
    {
        $map = [
            'ci' => ['orange-money-ci' => 'Orange Money', 'mtn-ci' => 'MTN Money', 'moov-ci' => 'Moov Money', 'wave-ci' => 'Wave'],
            'sn' => ['orange-money-senegal' => 'Orange Money Sénégal', 'free-money-senegal' => 'Free Money', 'wave-senegal' => 'Wave Sénégal', 'expresso-senegal' => 'Expresso'],
            'bf' => ['orange-money-burkina' => 'Orange Money Burkina', 'moov-burkina-faso' => 'Moov Burkina Faso'],
            'bj' => ['mtn-benin' => 'MTN Bénin', 'moov-benin' => 'Moov Bénin'],
            'tg' => ['t-money-togo' => 'T-Money Togo', 'moov-togo' => 'Moov Togo'],
            'ml' => ['orange-money-mali' => 'Orange Money Mali'],
            'cg' => ['orange-money-mali' => 'Orange Money', 'mtn-cg' => 'MTN Congo'],
            'cm' => ['orange-money-cm' => 'Orange Money Cameroun', 'mtn-cm' => 'MTN Cameroun'],
            'cd' => ['airtel-money-cd' => 'Airtel Money RDC'],
            'ga' => ['airtel-money-ga' => 'Airtel Money Gabon', 'libertis-ga' => 'Libertis'],
            'gh' => ['airtel-money-gh' => 'Airtel Money Ghana', 'mtn-gh' => 'MTN Ghana', 'vodafone-gh' => 'Vodafone Ghana'],
            'gn' => ['orange-gn' => 'Orange Guinée', 'mtn-gn' => 'MTN Guinée'],
            'gw' => ['mtn-gw' => 'MTN Guinée-Bissau'],
            'ke' => ['m-pesa-ke' => 'M-Pesa Kenya'],
            'mr' => ['bankily-mr' => 'Bankily'],
            'ne' => ['airtel-money-ne' => 'Airtel Money Niger', 'mtn-ne' => 'MTN Niger', 'mauritel-ne' => 'Mauritel'],
            'ug' => ['mtn-ug' => 'MTN Uganda'],
            'cf' => ['orange-cf' => 'Orange Centrafrique'],
            'rw' => ['mtn-rw' => 'MTN Rwanda'],
            'sl' => ['africell-sl' => 'Africell', 'orange-sl' => 'Orange Sierra Leone'],
            'tz' => ['airtel-money-tz' => 'Airtel Money Tanzanie', 'm-pesa-tz' => 'M-Pesa Tanzanie', 'tigo-tz' => 'Tigo Tanzanie'],
            'td' => ['airtel-money-td' => 'Airtel Money Tchad', 'moov-td' => 'Moov Tchad'],
            'gm' => ['orange-gm' => 'Orange Gambie'],
            'et' => ['safaricom-et' => 'Safaricom Éthiopie'],
        ];

        return $map[$countryCode] ?? $map['ci'];
    }
}