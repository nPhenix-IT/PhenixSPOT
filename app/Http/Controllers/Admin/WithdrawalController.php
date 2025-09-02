<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function index()
    {
        $requests = WithdrawalRequest::with('user')->latest()->paginate(15);
        return view('content.admin.withdrawals.index', compact('requests'));
    }

    public function approve(WithdrawalRequest $withdrawalRequest)
    {
        if ($withdrawalRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        DB::transaction(function () use ($withdrawalRequest) {
            $user = $withdrawalRequest->user;
            $wallet = $user->wallet;

            // S'assurer que le solde est suffisant
            if ($wallet->balance < $withdrawalRequest->amount) {
                $withdrawalRequest->update(['status' => 'rejected']);
                // Optionnel: ajouter une note expliquant pourquoi
                return;
            }

            // Débiter le portefeuille
            $wallet->balance -= $withdrawalRequest->amount;
            $wallet->save();

            // Créer une transaction de débit
            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $withdrawalRequest->amount,
                'description' => 'Retrait approuvé',
            ]);

            // Mettre à jour le statut de la demande
            $withdrawalRequest->update(['status' => 'approved']);
        });

        return redirect()->back()->with('success', 'La demande de retrait a été approuvée.');
    }

    public function reject(WithdrawalRequest $withdrawalRequest)
    {
        if ($withdrawalRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $withdrawalRequest->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'La demande de retrait a été rejetée.');
    }
}