<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\PendingTransaction;
use App\Models\WithdrawalRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $routers = [];
        
        // Pour l'utilisateur standard, on précharge les positions des routeurs pour la carte
        if ($user->hasRole('User')) {
            $routers = Router::where('user_id', $user->id)
                ->select('name', 'latitude', 'longitude', 'status')
                ->get();
        }

        return view('content.dashboard.index', compact('routers'));
    }

    /**
     * Endpoint AJAX pour récupérer les statistiques filtrées
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'month');
        $data = [];

        if ($user->hasRole(['Super-admin', 'Admin'])) {
            $data = $this->getAdminStats($period);
        } else {
            $data = $this->getUserStats($user);
        }

        return response()->json($data);
    }

    private function getAdminStats($period)
    {
        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Revenue stats
        $revenue = PendingTransaction::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('total_price');

        $totalUsers = User::role('User')->count();

        // Courbe d'inscription des utilisateurs (ApexCharts)
        $userGrowth = User::role('User')
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->take(12)
            ->get();

        return [
            'role' => 'admin',
            'kpis' => [
                'revenue' => number_format($revenue, 0, ',', ' ') . ' FCFA',
                'total_users' => $totalUsers,
                'pending_withdrawals' => WithdrawalRequest::where('status', 'pending')->count(),
            ],
            'charts' => [
                'userGrowthLabels' => $userGrowth->pluck('month'),
                'userGrowthData' => $userGrowth->pluck('count'),
            ]
        ];
    }

    private function getUserStats($user)
    {
        $vouchers = Voucher::where('user_id', $user->id);
        
        return [
            'role' => 'user',
            'kpis' => [
                'total_routers' => Router::where('user_id', $user->id)->count(),
                'vouchers_available' => (clone $vouchers)->where('status', 'new')->count(),
                'vouchers_used' => (clone $vouchers)->where('status', 'used')->count(),
                'vouchers_disabled' => (clone $vouchers)->where('status', 'disabled')->count(),
                'vouchers_connected' => (clone $vouchers)->where('is_active', true)->count(), // Hypothèse : is_active = connecté
            ],
            'charts' => [
                'voucherDistribution' => [
                    (clone $vouchers)->where('status', 'used')->count(),
                    (clone $vouchers)->where('status', 'new')->count(),
                    (clone $vouchers)->where('status', 'disabled')->count()
                ]
            ]
        ];
    }
}