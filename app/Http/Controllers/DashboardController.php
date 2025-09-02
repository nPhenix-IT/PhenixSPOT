<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\WithdrawalRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $data = [];

        if ($user->hasRole(['Super-admin', 'Admin'])) {
            // Données pour l'administrateur
            $data['total_users'] = User::role('User')->count();
            $data['pending_withdrawals'] = WithdrawalRequest::where('status', 'pending')->count();
            // D'autres statistiques globales peuvent être ajoutées ici
            $wallet = $user->wallet()->firstOrCreate(['user_id' => $user->id]);
            $data['wallet_balance'] = $wallet->balance;
            $data['router_count'] = $user->routers()->count();
            $data['active_vouchers'] = $user->vouchers()->where('is_active', true)->where('status', 'new')->count();
            $data['latest_transactions'] = $wallet->transactions()->latest()->take(5)->get();
        } else {
            // Données pour l'utilisateur standard
            $wallet = $user->wallet()->firstOrCreate(['user_id' => $user->id]);
            $data['wallet_balance'] = $wallet->balance;
            $data['router_count'] = $user->routers()->count();
            $data['active_vouchers'] = $user->vouchers()->where('is_active', true)->where('status', 'new')->count();
            $data['latest_transactions'] = $wallet->transactions()->latest()->take(5)->get();
        }

        return view('content.dashboard.index', compact('data'));
    }
}