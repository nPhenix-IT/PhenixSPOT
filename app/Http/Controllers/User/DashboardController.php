<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $wallet = $user->wallet()->firstOrCreate(['user_id' => $user->id]);

        $stats = [
            'balance' => $wallet->balance,
            'routers_count' => $user->routers()->count(),
            'vouchers_sold' => $user->vouchers()->where('status', 'used')->count(),
            'vouchers_remaining' => $user->vouchers()->where('status', 'new')->count(),
        ];

        // Données pour le graphique des ventes (simulation pour les 7 derniers jours)
        $salesData = Transaction::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total')
            ])
            ->pluck('total', 'date');

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d/m');
            $chartData[] = $salesData[$date] ?? 0;
        }

        $salesChart = [
            'labels' => $chartLabels,
            'data' => $chartData,
        ];

        return view('content.dashboard.index', compact('stats', 'salesChart'));
    }
}