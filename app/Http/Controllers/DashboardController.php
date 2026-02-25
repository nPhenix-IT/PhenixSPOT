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
        
        if ($user->hasRole('User')) {
            $routers = Router::where('user_id', $user->id)
                ->select('name', 'latitude', 'longitude', 'status')
                ->get();
        }

        return view('content.dashboard.index', compact('routers'));
    }

    public function getStats(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'month');

        if ($user->hasRole(['Super-admin', 'Admin'])) {
            return response()->json($this->getAdminStats($period));
        } else {
            return response()->json($this->getUserStats($user));
        }
    }

    private function getAdminStats($period)
    {
        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Données basées sur l'image 1
        $revenue = PendingTransaction::where('status', 'completed')->where('created_at', '>=', $startDate)->sum('total_price');
        $newLeads = 230; // Exemple statique ou calculé depuis votre DB

        // Simulation de données pour les graphiques (ApexCharts)
        $revenueSeries = [30, 40, 35, 50, 49, 60, 70, 91, 125]; 
        $revenueLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];

        return [
            'role' => 'admin',
            'kpis' => [
                'revenue' => number_format($revenue, 0, ',', ' '),
                'leads' => $newLeads,
                'routers_active' => Router::where('status', 'active')->count(),
                'hotspot_conn' => 542,
                'pppoe_active' => 179,
                'vpn_accounts' => 82,
            ],
            'charts' => [
                'revenueSeries' => $revenueSeries,
                'revenueLabels' => $revenueLabels,
                'leadsGrowth' => 22.4,
            ]
        ];
    }

    private function getUserStats($user)
    {
        $vouchers = Voucher::where('user_id', $user->id);
        
        return [
            'role' => 'user',
            'kpis' => [
                'routers' => Router::where('user_id', $user->id)->count(),
                'hotspot_conn' => 48,
                'pppoe_active' => 27,
                'vpn_accounts' => 5,
                'revenue_today' => 168000,
            ],
            'charts' => [
                'activityLabels' => ['12 AM', '4 AM', '8 AM', '12 PM', '4 PM', '8 PM'],
                'activityData' => [10, 15, 8, 45, 30, 55],
                'voucherDistribution' => [
                    (clone $vouchers)->where('status', 'used')->count() ?: 321, // fallback démo
                    (clone $vouchers)->where('status', 'new')->count() ?: 150,
                    (clone $vouchers)->where('status', 'disabled')->count() ?: 45
                ]
            ]
        ];
    }
}