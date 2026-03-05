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
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Affiche la vue principale du dashboard.
     */
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

    /**
     * Point d'entrée API pour récupérer les statistiques dynamiques.
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();

        return response()->json(
            $user->hasRole(['Super-admin', 'Admin'])
                ? $this->getAdminStats($request)
                : $this->getUserStats($user, $request)
        );
    }

    /**
     * Logique spécifique pour les utilisateurs (Partenaires).
     */
    private function getUserStats(User $user, Request $request): array
    {
        $routerId = $request->integer('router_id');
        $saleTypeFilter = (string) $request->input('sale_type', 'all');
        $period = (string) $request->input('period', 'month'); // day, week, month, year

        // Calcul des revenus globaux pour les KPIs
        $manualNet = (float) Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->where('vouchers.user_id', $user->id)
            ->where('vouchers.source', 'manual_generation')
            ->where('vouchers.status', 'used')
            ->when($routerId, fn($q) => $q->where('vouchers.activated_router_id', $routerId))
            ->sum('profiles.price');

        $onlineNet = (float) PendingTransaction::query()
            ->where('pending_transactions.user_id', $user->id)
            ->where('pending_transactions.status', 'completed')
            ->when($routerId, fn($q) => $q->where('pending_transactions.router_id', $routerId))
            ->selectRaw('SUM(total_price - COALESCE(commission_amount, 0)) as net')
            ->value('net') ?? 0;

        // Construction des données pour les graphiques
        $salesEvolution = $this->buildSalesEvolution($user->id, $routerId, $period);
        $salesTypeEvolution = $this->buildSalesTypeEvolution($user->id, $saleTypeFilter);
        $routerPerformance = $this->buildRouterPerformance($user->id);

        // ✅ NEW (USER): widget Top Zone + dimension revenus par zone routeur
        $topZone = $this->buildUserTopZone($user->id, $routerId, $period);
        $zoneBreakdown = $this->buildUserZonesBreakdown($user->id, $routerId, $period);

        return [
            'role' => 'user',
            'kpis' => [
                'total_sales' => round($manualNet + $onlineNet, 2),
                'vouchers_unused' => Voucher::where('user_id', $user->id)->where('status', 'new')->count(),
                'vouchers_used' => Voucher::where('user_id', $user->id)->where('status', 'used')->count(),
                'vouchers_online' => $this->countOnlineVouchersForUser($user->id),
            ],
            'charts' => [
                'sales_evolution' => $salesEvolution,
                'sales_type_evolution' => $salesTypeEvolution,
                'router_performance' => $routerPerformance,
            ],
            // ✅ NEW (USER)
            'widgets' => [
                'top_zone' => $topZone,
                'zones_breakdown' => $zoneBreakdown,
            ],
            'latest_transactions' => $this->buildLatestTransactions($user->id, 5),
        ];
    }
    
    private function countOnlineVouchersForUser(int $userId): int
    {
        return (int) DB::table('radacct')
            ->whereNull('radacct.acctstoptime')
            ->where(function ($query) {
                $query->where('radacct.acctupdatetime', '>', now()->subDay())
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('radacct.acctupdatetime')
                            ->where('radacct.acctstarttime', '>', now()->subDay());
                    });
            })
            ->whereExists(function ($query) use ($userId) {
                $query->selectRaw('1')
                    ->from('vouchers')
                    ->where('vouchers.user_id', $userId)
                    ->whereRaw('LOWER(TRIM(vouchers.code)) = LOWER(TRIM(radacct.username))');
            })
            ->selectRaw('COUNT(DISTINCT LOWER(TRIM(radacct.username))) as aggregate')
            ->value('aggregate');
    }

    /**
     * Construit les données du graphique "Ventes par Routeur".
     */
    private function buildRouterPerformance(int $userId): array
    {
        // 1. Ventes Manuelles groupées par routeur
        $manual = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->join('routers', 'routers.id', '=', 'vouchers.activated_router_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.status', 'used')
            ->select('routers.name', DB::raw('SUM(profiles.price) as total'))
            ->groupBy('routers.name')
            ->get();

        // 2. Ventes Online groupées par routeur
        $online = PendingTransaction::query()
            ->join('routers', 'routers.id', '=', 'pending_transactions.router_id')
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->select('routers.name', DB::raw('SUM(total_price - COALESCE(commission_amount, 0)) as total'))
            ->groupBy('routers.name')
            ->get();

        // Fusion des résultats
        $merged = [];
        foreach ($manual as $m) { $merged[$m->name] = (float) $m->total; }
        foreach ($online as $o) {
            $merged[$o->name] = ($merged[$o->name] ?? 0) + (float) $o->total;
        }

        arsort($merged);

        return [
            'labels' => array_keys($merged),
            'series' => array_values($merged)
        ];
    }

    /**
     * Construit l'évolution des revenus selon la période choisie (Jour/Semaine/Mois/Année).
     */
    private function buildSalesEvolution(int $userId, ?int $routerId, string $period): array
    {
        $daysCount = match($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 14
        };

        $startDate = now()->subDays($daysCount - 1)->startOfDay();

        // Données Manuelles
        $manual = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.source', 'manual_generation')
            ->where('vouchers.status', 'used')
            ->where('vouchers.used_at', '>=', $startDate)
            ->when($routerId, fn($q) => $q->where('vouchers.activated_router_id', $routerId))
            ->selectRaw('DATE(vouchers.used_at) as day, SUM(profiles.price) as total')
            ->groupBy('day')->pluck('total', 'day');

        // Données Online
        $online = PendingTransaction::query()
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->where('pending_transactions.created_at', '>=', $startDate)
            ->when($routerId, fn($q) => $q->where('pending_transactions.router_id', $routerId))
            ->selectRaw('DATE(pending_transactions.created_at) as day, SUM(total_price - COALESCE(commission_amount, 0)) as total')
            ->groupBy('day')->pluck('total', 'day');

        $labels = []; $series = [];
        for ($i = $daysCount - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
            $series[] = (float)(($manual[$key] ?? 0) + ($online[$key] ?? 0));
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /**
     * Construit la répartition des types de ventes (Manuel vs Online).
     */
    private function buildSalesTypeEvolution(int $userId, string $saleTypeFilter): array
    {
        $startDate = now()->subDays(13)->startOfDay();

        $manual = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.source', 'manual_generation')
            ->where('vouchers.status', 'used')
            ->where('vouchers.used_at', '>=', $startDate)
            ->selectRaw('DATE(vouchers.used_at) as day, SUM(profiles.price) as total')
            ->groupBy('day')->pluck('total', 'day');

        $online = PendingTransaction::query()
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->where('pending_transactions.created_at', '>=', $startDate)
            ->selectRaw('DATE(pending_transactions.created_at) as day, SUM(total_price - COALESCE(commission_amount, 0)) as total')
            ->groupBy('day')->pluck('total', 'day');

        $labels = []; $mS = []; $oS = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i); $k = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
            $mS[] = (float)($manual[$k] ?? 0);
            $oS[] = (float)($online[$k] ?? 0);
        }

        $series = match ($saleTypeFilter) {
            'manual' => [['name' => 'Manuel', 'data' => $mS]],
            'online' => [['name' => 'En ligne', 'data' => $oS]],
            default => [['name' => 'Manuel', 'data' => $mS], ['name' => 'En ligne', 'data' => $oS]]
        };

        return ['labels' => $labels, 'series' => $series];
    }

    /**
     * Récupère les 5 dernières activités (Vouchers utilisés et Ventes online).
     */
    private function buildLatestTransactions(int $userId, int $limit): array
    {
        $m = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->leftJoin('routers', 'routers.id', '=', 'vouchers.activated_router_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.status', 'used')
            ->selectRaw("vouchers.code as reference, vouchers.source as sale_type, profiles.price as net_amount, vouchers.used_at as transacted_at, routers.name as router_name")
            ->orderByDesc('vouchers.used_at')
            ->limit($limit)
            ->get();

        $o = PendingTransaction::query()
            ->leftJoin('routers', 'routers.id', '=', 'pending_transactions.router_id')
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->selectRaw("pending_transactions.transaction_id as reference, 'online_sale' as sale_type, (total_price - COALESCE(commission_amount,0)) as net_amount, pending_transactions.created_at as transacted_at, routers.name as router_name")
            ->orderByDesc('pending_transactions.created_at')
            ->limit($limit)
            ->get();

        return $m->concat($o)
            ->sortByDesc('transacted_at')
            ->take($limit)
            ->values()
            ->map(fn($r) => [
                'reference' => $r->reference,
                'sale_type' => $r->sale_type,
                'net_amount' => (float)$r->net_amount,
                'transacted_at' => \Carbon\Carbon::parse($r->transacted_at)->format('d/m/Y H:i'),
                'router_name' => $r->router_name ?? 'N/A'
            ])->all();
    }

    /**
     * ✅ NEW (USER)
     * Widget "Top Zone" sur la période choisie.
     * - Si la table routers contient une colonne zone/area/city/location/site => on groupe dessus
     * - Sinon fallback: on groupe par routers.name (ça reste utile, même si le label est "Top Zone")
     */
    private function buildUserTopZone(int $userId, ?int $routerId, string $period): array
    {
        $daysCount = match($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 14
        };
        $startDate = now()->subDays($daysCount - 1)->startOfDay();

        // Choix de la "colonne zone" si elle existe
        $zoneCol = null;
        $candidates = ['zone', 'area', 'city', 'location', 'site'];
        foreach ($candidates as $c) {
            if (Schema::hasColumn('routers', $c)) { $zoneCol = $c; break; }
        }

        // Si aucune colonne "zone", fallback sur le nom routeur
        $groupSelect = $zoneCol ? "routers.$zoneCol" : "routers.name";
        $groupLabel = $zoneCol ? $zoneCol : 'router_name';

        // MANUEL (used) -> sum profiles.price
        $manualRows = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->join('routers', 'routers.id', '=', 'vouchers.activated_router_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.status', 'used')
            ->where('vouchers.used_at', '>=', $startDate)
            ->when($routerId, fn($q) => $q->where('vouchers.activated_router_id', $routerId))
            ->selectRaw("$groupSelect as grp, SUM(profiles.price) as total")
            ->groupBy('grp')
            ->get();

        // ONLINE (completed) -> sum net
        $onlineRows = PendingTransaction::query()
            ->join('routers', 'routers.id', '=', 'pending_transactions.router_id')
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->where('pending_transactions.created_at', '>=', $startDate)
            ->when($routerId, fn($q) => $q->where('pending_transactions.router_id', $routerId))
            ->selectRaw("$groupSelect as grp, SUM(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount,0)) as total")
            ->groupBy('grp')
            ->get();

        // Merge
        $merged = [];
        foreach ($manualRows as $r) {
            $key = (string)($r->grp ?? 'N/A');
            $merged[$key] = (float)($merged[$key] ?? 0) + (float)($r->total ?? 0);
        }
        foreach ($onlineRows as $r) {
            $key = (string)($r->grp ?? 'N/A');
            $merged[$key] = (float)($merged[$key] ?? 0) + (float)($r->total ?? 0);
        }

        if (empty($merged)) {
            return [
                'label' => $groupLabel,
                'name' => '—',
                'amount' => 0.0,
                'share' => 0.0,
            ];
        }

        arsort($merged);
        $topName = array_key_first($merged);
        $topAmount = (float)($merged[$topName] ?? 0);
        $total = array_sum($merged);
        $share = $total > 0 ? round(($topAmount / $total) * 100, 1) : 0.0;

        return [
            'label' => $groupLabel,
            'name' => $topName ?: 'N/A',
            'amount' => round($topAmount, 2),
            'share' => $share,
        ];
    }
    
    /**
     * Dimension analytique "par zone routeur" pour la période sélectionnée.
     */
    private function buildUserZonesBreakdown(int $userId, ?int $routerId, string $period, int $limit = 5): array
    {
        $daysCount = match($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 14
        };
        $startDate = now()->subDays($daysCount - 1)->startOfDay();

        $zoneCol = null;
        foreach (['zone', 'area', 'city', 'location', 'site'] as $candidate) {
            if (Schema::hasColumn('routers', $candidate)) {
                $zoneCol = $candidate;
                break;
            }
        }

        $groupSelect = $zoneCol ? "routers.$zoneCol" : 'routers.name';

        $manualRows = Voucher::query()
            ->join('profiles', 'profiles.id', '=', 'vouchers.profile_id')
            ->join('routers', 'routers.id', '=', 'vouchers.activated_router_id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.status', 'used')
            ->where('vouchers.used_at', '>=', $startDate)
            ->when($routerId, fn($q) => $q->where('vouchers.activated_router_id', $routerId))
            ->selectRaw("$groupSelect as grp, SUM(profiles.price) as total")
            ->groupBy('grp')
            ->get();

        $onlineRows = PendingTransaction::query()
            ->join('routers', 'routers.id', '=', 'pending_transactions.router_id')
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->where('pending_transactions.created_at', '>=', $startDate)
            ->when($routerId, fn($q) => $q->where('pending_transactions.router_id', $routerId))
            ->selectRaw("$groupSelect as grp, SUM(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount,0)) as total")
            ->groupBy('grp')
            ->get();

        $merged = [];
        foreach ($manualRows as $row) {
            $key = (string)($row->grp ?? 'N/A');
            $merged[$key] = (float)($merged[$key] ?? 0) + (float)($row->total ?? 0);
        }
        foreach ($onlineRows as $row) {
            $key = (string)($row->grp ?? 'N/A');
            $merged[$key] = (float)($merged[$key] ?? 0) + (float)($row->total ?? 0);
        }

        if (empty($merged)) {
            return [];
        }

        arsort($merged);
        $total = array_sum($merged);

        $top = array_slice($merged, 0, $limit, true);

        return collect($top)->map(fn($amount, $name) => [
            'name' => $name,
            'amount' => round((float)$amount, 2),
            'share' => $total > 0 ? round(((float)$amount / $total) * 100, 1) : 0.0,
        ])->values()->all();
    }

    /**
     * ===== ADMIN =====
     * Statistiques Administrateur (KPIs + charts + tables)
     */
    private function getAdminStats(Request $request): array
    {
        $period = (string)$request->input('period', 'month'); // day|week|month|year
        $trendPeriod = (string)$request->input('trend_period', 'month'); // day|week|month|year

        // KPI: Revenu réseau (net ventes online)
        $start = match($period){
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };

        $revenueNet = (float) PendingTransaction::where('status', 'completed')
            ->where('created_at', '>=', $start)
            ->selectRaw('SUM(total_price - COALESCE(commission_amount,0)) as net')
            ->value('net');

        // KPI: cumul forfaits souscrits (count/amount)
        [$subscriptionsCount, $subscriptionsAmount] = $this->adminSubscriptionsTotals();

        // KPI: cumul commissions ventes online
        $commissionTotal = 0.0;
        if (Schema::hasTable('pending_transactions') && Schema::hasColumn('pending_transactions', 'commission_amount')) {
            $commissionTotal = (float) PendingTransaction::query()
                ->where('status', 'completed')
                ->where('created_at', '>=', $start)
                ->sum(DB::raw('COALESCE(commission_amount,0)'));
        }

        // KPI: 2.5% frais retrait (provider) (sur withdrawals completed)
        $withdrawProviderFee = $this->adminWithdrawalProviderFeeTotal($start);

        // ✅ NEW: total retraits montant (withdrawals completed)
        $withdrawalsTotalAmount = $this->adminWithdrawalTotalAmount($start);

        $feesTotal = (float) ($commissionTotal + $withdrawProviderFee);

        // ✅ total routeurs (widget admin)
        $routersTotal = (int) Router::count();

        // Charts
        $revenueEvolution = $this->buildAdminRevenueEvolution($period); // chart 1
        $platformFeesTrend = $this->buildAdminPlatformFeesTrend($trendPeriod); // chart 2

        // Table: 5 dernières transactions "fees"
        $latestFees = $this->buildAdminLatestFees(5);

        // ✅ NEW: Top partenaires (Top 5) sur la période
        $topPartners = $this->buildAdminTopPartners($start, 5);

        return [
            'role' => 'admin',
            'kpis' => [
                'revenue' => round($revenueNet, 2),
                'users' => User::count(),
                'vouchers_online' => DB::table('radacct')->whereNull('acctstoptime')->count(),
                'pending_withdrawals' => WithdrawalRequest::where('status','pending')->count(),

                'subscriptions_count' => (int)$subscriptionsCount,
                'subscriptions_amount' => round((float)$subscriptionsAmount, 2),
                'commission_total' => round((float)$commissionTotal, 2),
                'withdraw_provider_fee' => round((float)$withdrawProviderFee, 2),
                'fees_total' => round((float)$feesTotal, 2),

                'routers_total' => $routersTotal,

                // ✅ NEW
                'withdrawals_total_amount' => round((float)$withdrawalsTotalAmount, 2),
            ],
            'charts' => [
                'revenue' => $revenueEvolution,
                'fees_trend' => $platformFeesTrend,
            ],
            'latest_fees' => $latestFees,

            // ✅ NEW
            'top_partners' => $topPartners,
        ];
    }

    private function adminSubscriptionsTotals(): array
    {
        $count = 0;
        $amount = 0.0;

        if (Schema::hasTable('subscriptions')) {
            $count = (int) DB::table('subscriptions')->count();

            $col = null;
            foreach (['amount', 'price', 'total', 'monthly_price'] as $c) {
                if (Schema::hasColumn('subscriptions', $c)) { $col = $c; break; }
            }
            if ($col) {
                $amount = (float) DB::table('subscriptions')->sum(DB::raw("COALESCE($col,0)"));
            }
            return [$count, $amount];
        }

        foreach (['user_subscriptions', 'plan_subscriptions'] as $t) {
            if (Schema::hasTable($t)) {
                $count = (int) DB::table($t)->count();

                $col = null;
                foreach (['amount', 'price', 'total'] as $c) {
                    if (Schema::hasColumn($t, $c)) { $col = $c; break; }
                }
                if ($col) {
                    $amount = (float) DB::table($t)->sum(DB::raw("COALESCE($col,0)"));
                }
                return [$count, $amount];
            }
        }

        return [$count, $amount];
    }

    private function adminWithdrawalProviderFeeTotal($start): float
    {
        if (!Schema::hasTable('withdrawal_requests')) return 0.0;

        $q = WithdrawalRequest::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $start);

        if (Schema::hasColumn('withdrawal_requests', 'fee_amount')) {
            return (float) $q->sum(DB::raw('COALESCE(fee_amount,0)'));
        }

        if (Schema::hasColumn('withdrawal_requests', 'amount')) {
            return (float) $q->sum(DB::raw('COALESCE(amount,0) * 0.025'));
        }

        return 0.0;
    }

    private function adminWithdrawalTotalAmount($start): float
    {
        if (!Schema::hasTable('withdrawal_requests')) return 0.0;
        if (!Schema::hasColumn('withdrawal_requests', 'amount')) return 0.0;

        return (float) WithdrawalRequest::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $start)
            ->sum(DB::raw('COALESCE(amount,0)'));
    }

    private function buildAdminTopPartners($start, int $limit = 5): array
    {
        if (!Schema::hasTable('pending_transactions')) return [];

        $rows = PendingTransaction::query()
            ->join('users', 'users.id', '=', 'pending_transactions.user_id')
            ->where('pending_transactions.status', 'completed')
            ->where('pending_transactions.created_at', '>=', $start)
            ->selectRaw("
                pending_transactions.user_id as user_id,
                COALESCE(users.name, users.email) as partner_name,
                SUM(pending_transactions.total_price - COALESCE(pending_transactions.commission_amount,0)) as amount
            ")
            ->groupBy('pending_transactions.user_id', 'partner_name')
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();

        return $rows->map(fn($r) => [
            'partner' => (string)($r->partner_name ?? ('User #' . $r->user_id)),
            'amount' => (float)($r->amount ?? 0),
        ])->all();
    }

    private function buildAdminRevenueEvolution(string $period): array
    {
        $daysCount = match($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 14
        };

        $startDate = now()->subDays($daysCount - 1)->startOfDay();

        $onlineNet = PendingTransaction::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as day, SUM(total_price - COALESCE(commission_amount,0)) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = []; $series = [];
        for ($i = $daysCount - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
            $series[] = (float)($onlineNet[$key] ?? 0);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    private function buildAdminPlatformFeesTrend(string $period): array
    {
        $daysCount = match($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 14
        };

        $startDate = now()->subDays($daysCount - 1)->startOfDay();

        $comm = [];
        if (Schema::hasTable('pending_transactions') && Schema::hasColumn('pending_transactions', 'commission_amount')) {
            $comm = PendingTransaction::query()
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as day, SUM(COALESCE(commission_amount,0)) as total')
                ->groupBy('day')
                ->pluck('total', 'day')
                ->toArray();
        }

        $wfee = [];
        if (Schema::hasTable('withdrawal_requests')) {
            if (Schema::hasColumn('withdrawal_requests', 'fee_amount')) {
                $wfee = WithdrawalRequest::query()
                    ->where('status', 'completed')
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as day, SUM(COALESCE(fee_amount,0)) as total')
                    ->groupBy('day')
                    ->pluck('total', 'day')
                    ->toArray();
            } elseif (Schema::hasColumn('withdrawal_requests', 'amount')) {
                $wfee = WithdrawalRequest::query()
                    ->where('status', 'completed')
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as day, SUM(COALESCE(amount,0) * 0.025) as total')
                    ->groupBy('day')
                    ->pluck('total', 'day')
                    ->toArray();
            }
        }

        $labels = []; $series = [];
        for ($i = $daysCount - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
            $series[] = (float)(($comm[$key] ?? 0) + ($wfee[$key] ?? 0));
        }

        return ['labels' => $labels, 'series' => $series];
    }

    private function buildAdminLatestFees(int $limit = 5): array
    {
        $rows = collect();

        if (Schema::hasTable('pending_transactions')) {
            $q = PendingTransaction::query()
                ->where('status', 'completed')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(['transaction_id', 'commission_amount', 'created_at']);

            foreach ($q as $r) {
                $amt = (float)($r->commission_amount ?? 0);
                if ($amt <= 0) continue;
                $rows->push([
                    'type' => 'commission',
                    'reference' => (string)($r->transaction_id ?? 'N/A'),
                    'amount' => $amt,
                    'created_at' => $r->created_at,
                ]);
            }
        }

        if (Schema::hasTable('withdrawal_requests')) {
            $q = WithdrawalRequest::query()
                ->where('status', 'completed')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            foreach ($q as $r) {
                $fee = 0.0;
                if (isset($r->fee_amount)) {
                    $fee = (float)($r->fee_amount ?? 0);
                } elseif (isset($r->amount)) {
                    $fee = (float)($r->amount ?? 0) * 0.025;
                }
                if ($fee <= 0) continue;

                $rows->push([
                    'type' => 'withdraw_fee',
                    'reference' => (string)($r->reference ?? $r->id ?? 'N/A'),
                    'amount' => $fee,
                    'created_at' => $r->created_at,
                ]);
            }
        }

        return $rows
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->map(fn($r) => [
                'type' => $r['type'],
                'reference' => $r['reference'],
                'amount' => (float)$r['amount'],
                'transacted_at' => \Carbon\Carbon::parse($r['created_at'])->format('d/m/Y H:i'),
            ])
            ->all();
    }
}