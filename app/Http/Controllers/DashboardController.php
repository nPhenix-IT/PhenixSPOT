<?php

namespace App\Http\Controllers;

use App\Models\PendingTransaction;
use App\Models\Router;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('content.dashboard.index', [
            'isAdmin' => $user->hasRole(['Super-admin', 'Admin']),
            'routers' => Router::query()
                ->when(
                    !$user->hasRole(['Super-admin', 'Admin']),
                    fn ($q) => $q->where('routers.user_id', $user->id)
                )
                ->select('routers.id', 'routers.name')
                ->orderBy('routers.name')
                ->get(),
        ]);
    }

    public function getStats(Request $request)
    {
        $user = Auth::user();

        return response()->json(
            $user->hasRole(['Super-admin', 'Admin'])
                ? $this->getAdminStats($request)
                : $this->getUserStats($user, $request)
        );
    }

    private function getUserStats(User $user, Request $request): array
    {
        $routerId = $request->integer('router_id');
        $saleTypeFilter = (string) $request->input('sale_type', 'all');

        /**
         * ✅ IMPORTANT:
         * Dès qu'il y a JOIN, on qualifie les colonnes:
         * vouchers.user_id, vouchers.status, vouchers.source, etc.
         */
        $manualUsed = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->where('vouchers.user_id', $user->id)
            ->where('vouchers.source', 'manual_generation')
            ->where('vouchers.status', 'used');

        $onlineCompleted = PendingTransaction::query()
            ->where('pending_transactions.user_id', $user->id)
            ->where('pending_transactions.status', 'completed');

        $manualNet = (float) ((clone $manualUsed)->sum('profiles.price') ?? 0);

        $onlineNet = (float) (
            (clone $onlineCompleted)
                ->selectRaw('COALESCE(SUM(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount, 0)), 0) as net')
                ->value('net') ?? 0
        );

        $vouchersQuery = Voucher::query()
            ->where('vouchers.user_id', $user->id);

        $connectedCount = DB::table('radacct')
            ->join('vouchers', 'vouchers.code', '=', 'radacct.username')
            ->where('vouchers.user_id', $user->id)
            ->whereNull('radacct.acctstoptime')
            ->distinct('radacct.username')
            ->count('radacct.username');

        $salesEvolution = $this->buildSalesEvolution($user->id, $routerId);
        $salesTypeEvolution = $this->buildSalesTypeEvolution($user->id, $saleTypeFilter);

        $latestTransactions = $this->buildLatestVoucherTransactions($user->id);

        return [
            'role' => 'user',
            'kpis' => [
                'total_sales' => round($manualNet + $onlineNet, 2),
                'routers_count' => Router::query()->where('routers.user_id', $user->id)->count(),

                'vouchers_total' => (clone $vouchersQuery)->count(),
                'vouchers_unused' => (clone $vouchersQuery)->where('vouchers.status', 'new')->count(),
                'vouchers_used' => (clone $vouchersQuery)->where('vouchers.status', 'used')->count(),

                'vouchers_online' => $connectedCount,
            ],
            'charts' => [
                'sales_evolution' => $salesEvolution,
                'sales_type_evolution' => $salesTypeEvolution,
            ],
            'latest_transactions' => $latestTransactions,
        ];
    }

    private function getAdminStats(Request $request): array
    {
        $period = (string) $request->input('period', 'month');

        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $completed = PendingTransaction::query()
            ->where('pending_transactions.status', 'completed');

        $revenue = (float) (
            (clone $completed)
                ->where('pending_transactions.created_at', '>=', $startDate)
                ->selectRaw('COALESCE(SUM(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount, 0)), 0) as net')
                ->value('net') ?? 0
        );

        $adminChartData = (clone $completed)
            ->where('pending_transactions.created_at', '>=', $startDate)
            ->selectRaw('DATE(pending_transactions.created_at) as day, COALESCE(SUM(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount,0)),0) as net')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('net', 'day');

        $span = min(30, now()->diffInDays($startDate));
        $days = collect(range(0, $span))
            ->map(fn ($i) => now()->copy()->subDays($span - $i));

        return [
            'role' => 'admin',
            'kpis' => [
                'revenue' => round($revenue, 2),
                'users' => User::count(),
                'routers' => Router::count(),
                'vouchers_total' => Voucher::count(),
                'vouchers_used' => Voucher::where('status', 'used')->count(),
                'vouchers_unused' => Voucher::where('status', 'new')->count(),
                'vouchers_online' => DB::table('radacct')->whereNull('acctstoptime')->distinct('username')->count('username'),
                'pending_withdrawals' => WithdrawalRequest::where('status', 'pending')->count(),
            ],
            'charts' => [
                'revenue' => [
                    'labels' => $days->map(fn ($d) => $d->format('d/m'))->values(),
                    'series' => $days->map(fn ($d) => (float) ($adminChartData[$d->format('Y-m-d')] ?? 0))->values(),
                ],
            ],
        ];
    }

    private function buildSalesEvolution(int $userId, ?int $routerId): array
    {
        $startDate = now()->subDays(13)->startOfDay();

        $manual = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.source', 'manual_generation')
            ->where('vouchers.status', 'used')
            ->where('vouchers.used_at', '>=', $startDate)
            ->when($routerId, fn ($q) => $q->where('vouchers.activated_router_id', $routerId))
            ->selectRaw('DATE(vouchers.used_at) as day, COALESCE(SUM(profiles.price),0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $online = PendingTransaction::query()
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->where('pending_transactions.created_at', '>=', $startDate)
            ->when($routerId, fn ($q) => $q->where('pending_transactions.router_id', $routerId))
            ->selectRaw('DATE(pending_transactions.created_at) as day, COALESCE(SUM(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount,0)), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $series = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = now()->copy()->subDays($i);
            $key = $d->format('Y-m-d');

            $labels[] = $d->format('d/m');
            $series[] = (float) (($manual[$key] ?? 0) + ($online[$key] ?? 0));
        }

        return ['labels' => $labels, 'series' => $series];
    }

    private function buildSalesTypeEvolution(int $userId, string $saleTypeFilter): array
    {
        $startDate = now()->subDays(13)->startOfDay();

        $manual = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.source', 'manual_generation')
            ->where('vouchers.status', 'used')
            ->where('vouchers.used_at', '>=', $startDate)
            ->selectRaw('DATE(vouchers.used_at) as day, COALESCE(SUM(profiles.price), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $online = PendingTransaction::query()
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->where('pending_transactions.created_at', '>=', $startDate)
            ->selectRaw('DATE(pending_transactions.created_at) as day, COALESCE(SUM(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount,0)), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $manualSeries = [];
        $onlineSeries = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = now()->copy()->subDays($i);
            $key = $d->format('Y-m-d');

            $labels[] = $d->format('d/m');
            $manualSeries[] = (float) ($manual[$key] ?? 0);
            $onlineSeries[] = (float) ($online[$key] ?? 0);
        }

        $series = match ($saleTypeFilter) {
            'manual' => [['name' => 'Vente manuelle', 'data' => $manualSeries]],
            'online' => [['name' => 'Vente en ligne', 'data' => $onlineSeries]],
            default => [
                ['name' => 'Vente manuelle', 'data' => $manualSeries],
                ['name' => 'Vente en ligne', 'data' => $onlineSeries],
            ],
        };

        return ['labels' => $labels, 'series' => $series];
    }

    private function buildLatestVoucherTransactions(int $userId): array
    {
        $manualRows = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->leftJoin('routers', 'routers.id', '=', 'vouchers.activated_router_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.status', 'used')
            ->selectRaw("
                vouchers.code as reference,
                vouchers.source as sale_type,
                COALESCE(profiles.price,0) as gross_amount,
                0 as fee_amount,
                COALESCE(profiles.price,0) as net_amount,
                vouchers.used_at as transacted_at,
                COALESCE(routers.name, 'N/A') as router_name
            ")
            ->orderByDesc('vouchers.used_at')
            ->limit(10)
            ->get();

        $onlineRows = PendingTransaction::query()
            ->leftJoin('routers', 'routers.id', '=', 'pending_transactions.router_id')
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->selectRaw("
                pending_transactions.transaction_id as reference,
                'online_sale' as sale_type,
                COALESCE(pending_transactions.total_price,0) as gross_amount,
                COALESCE(pending_transactions.commission_amount,0) as fee_amount,
                COALESCE(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount,0),0) as net_amount,
                pending_transactions.created_at as transacted_at,
                COALESCE(routers.name, 'N/A') as router_name
            ")
            ->orderByDesc('pending_transactions.created_at')
            ->limit(10)
            ->get();

        return $manualRows
            ->concat($onlineRows)
            ->sortByDesc('transacted_at')
            ->take(10)
            ->values()
            ->map(fn ($row) => [
                'reference' => $row->reference,
                'sale_type' => $row->sale_type,
                'gross_amount' => (float) $row->gross_amount,
                'fee_amount' => (float) $row->fee_amount,
                'net_amount' => (float) $row->net_amount,
                'transacted_at' => $row->transacted_at
                    ? \Carbon\Carbon::parse($row->transacted_at)->format('d/m/Y H:i')
                    : null,
                'router_name' => $row->router_name,
            ])
            ->all();
    }
}