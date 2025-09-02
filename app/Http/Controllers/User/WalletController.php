<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $wallet = $user->wallet()->firstOrCreate(['user_id' => $user->id]);
        $transactions = $wallet->transactions()->latest()->paginate(10);
        return view('content.wallet.index', compact('wallet', 'transactions'));
    }

    public function withdraw(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        $request->validate([
            'amount' => 'required|numeric|min:5000|max:' . $wallet->balance,
            'payment_method' => 'required|string',
            'phone_number' => 'required|string',
        ]);

        WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'payment_details' => [
                'method' => $request->payment_method,
                'phone' => $request->phone_number,
            ],
        ]);

        return redirect()->back()->with('success', 'Votre demande de retrait a été soumise.');
    }
}