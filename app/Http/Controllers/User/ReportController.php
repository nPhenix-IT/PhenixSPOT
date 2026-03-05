<?php

namespace App\Http\Controllers\User;

use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Models\Router;
use App\Models\Voucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Affiche la page rapports avec dataset consolidé (online + vouchers utilisés).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->isMethod('post')) {
            Session::put('reports.filters', [
                'router_id' => $request->input('router_id'),
                'period' => $request->input('period', 'month'),
                'date_range' => $request->input('date_range'),
            ]);

            return redirect()->route('user.reports.index');
        }

        $savedFilters = Session::get('reports.filters', []);
        $routerId = $savedFilters['router_id'] ?? null;
        $period = $savedFilters['period'] ?? 'month';
        $dateRange = $savedFilters['date_range'] ?? null;

        [$startDate, $endDate] = $this->resolveDates($period, $dateRange);

        $routers = Router::where('user_id', $user->id)->get();
        $selectedRouter = $routerId ? $routers->firstWhere('id', (int) $routerId) : null;
        $selectedRouterName = $selectedRouter?->name;

        $sales = $this->buildSalesDataset((int) $user->id, $startDate, $endDate, $routerId, $selectedRouterName);

        $totals = [
            'sales' => $sales->count(),
            'amount' => (float) $sales->sum('amount'),
        ];

        $diffDays = $startDate->diffInDays($endDate);
        $groupFormat = $diffDays <= 1 ? 'Y-m-d H:00' : ($diffDays > 365 ? 'Y-m' : 'Y-m-d');

        $salesTrend = $sales
            ->groupBy(fn (array $row) => Carbon::parse($row['date'])->format($groupFormat))
            ->map(fn (Collection $rows, string $label) => (object) [
                'date_label' => $label,
                'amount' => (float) $rows->sum('amount'),
            ])
            ->sortBy('date_label')
            ->values();

        $profileStats = $sales
            ->groupBy('profile_label')
            ->map(fn (Collection $rows, string $label) => (object) [
                'label' => $label,
                'value' => (float) $rows->sum('amount'),
            ])
            ->sortByDesc('value')
            ->values();

        $maxRevenue = max(1, (float) $sales->groupBy('router_label')->map(fn ($rows) => $rows->sum('amount'))->max());

        $routerStats = $sales
            ->groupBy('router_label')
            ->map(fn (Collection $rows, string $label) => (object) [
                'label' => $label,
                'sales_count' => $rows->count(),
                'total_revenue' => (float) $rows->sum('amount'),
                'progress' => round(((float) $rows->sum('amount') / $maxRevenue) * 100, 1),
            ])
            ->sortByDesc('total_revenue')
            ->values();

        return view('content.reports.index', compact(
            'totals',
            'salesTrend',
            'profileStats',
            'routerStats',
            'routers',
            'routerId',
            'period',
            'dateRange'
        ));
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $filters = Session::get('reports.filters', []);
        $period = $request->input('period', $filters['period'] ?? 'month');
        $routerId = $request->input('router_id', $filters['router_id'] ?? null);
        $dateRange = $request->input('date_range', $filters['date_range'] ?? null);

        [$startDate, $endDate] = $this->resolveDates($period, $dateRange);
        $selectedRouterName = null;
        if ($routerId) {
            $selectedRouterName = Router::where('user_id', $user->id)->where('id', (int) $routerId)->value('name');
        }

        $sales = $this->buildSalesDataset((int) $user->id, $startDate, $endDate, $routerId, $selectedRouterName);

        return Excel::download(new SalesReportExport($sales), 'ventes_hotspot_' . now()->format('d_m_Y') . '.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $filters = Session::get('reports.filters', []);
        $period = $request->input('period', $filters['period'] ?? 'month');
        $routerId = $request->input('router_id', $filters['router_id'] ?? null);
        $dateRange = $request->input('date_range', $filters['date_range'] ?? null);

        [$startDate, $endDate] = $this->resolveDates($period, $dateRange);
        $selectedRouterName = null;
        if ($routerId) {
            $selectedRouterName = Router::where('user_id', $user->id)->where('id', (int) $routerId)->value('name');
        }

        $sales = $this->buildSalesDataset((int) $user->id, $startDate, $endDate, $routerId, $selectedRouterName)
            ->sortByDesc('date')
            ->values();

        $totals = [
            'sales' => $sales->count(),
            'amount' => (float) $sales->sum('amount'),
        ];

        $pdf = Pdf::loadView('content.reports.export_pdf', compact('sales', 'totals', 'period'));

        return $pdf->download('rapport_ventes_' . now()->format('d_m_Y') . '.pdf');
    }

    private function resolveDates($period, $dateRange): array
    {
        if ($dateRange && str_contains($dateRange, ' to ')) {
            $parts = explode(' to ', $dateRange);

            return [
                Carbon::parse($parts[0])->startOfDay(),
                Carbon::parse($parts[1])->endOfDay(),
            ];
        }

        $start = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        return [$start, now()->endOfDay()];
    }

    private function buildSalesDataset(int $userId, Carbon $startDate, Carbon $endDate, $routerId = null, ?string $selectedRouterName = null): Collection
    {
        $online = PendingTransaction::query()
            ->leftJoin('routers', 'pending_transactions.router_id', '=', 'routers.id')
            ->leftJoin('profiles', 'pending_transactions.profile_id', '=', 'profiles.id')
            ->where('pending_transactions.user_id', $userId)
            ->where('pending_transactions.status', 'completed')
            ->whereBetween('pending_transactions.created_at', [$startDate, $endDate])
            ->when($routerId, fn ($q) => $q->where('pending_transactions.router_id', $routerId))
            ->select([
                'pending_transactions.created_at as date',
                DB::raw('COALESCE(pending_transactions.total_price, 0) as amount'),
                DB::raw('COALESCE(routers.name, "Non assigné") as router_label'),
                DB::raw('COALESCE(profiles.name, "Profil inconnu") as profile_label'),
                DB::raw('COALESCE(pending_transactions.customer_number, "-") as customer'),
                DB::raw("'online' as source"),
            ])
            ->get()
            ->map(fn ($row) => [
                'date' => Carbon::parse($row->date),
                'amount' => (float) $row->amount,
                'router_label' => (string) (($row->router_label === 'Non assigné' && $selectedRouterName) ? $selectedRouterName : $row->router_label),
                'profile_label' => (string) $row->profile_label,
                'customer' => (string) $row->customer,
                'source' => (string) $row->source,
            ]);

        $manual = Voucher::query()
            ->leftJoin('routers', 'vouchers.activated_router_id', '=', 'routers.id')
            ->leftJoin('profiles', 'vouchers.profile_id', '=', 'profiles.id')
            ->where('vouchers.user_id', $userId)
            ->where('vouchers.status', 'used')
            ->whereBetween('vouchers.used_at', [$startDate, $endDate])
            ->when($routerId, fn ($q) => $q->where('vouchers.activated_router_id', $routerId))
            ->select([
                'vouchers.used_at as date',
                DB::raw('COALESCE(profiles.price, 0) as amount'),
                DB::raw('COALESCE(routers.name, "Non assigné") as router_label'),
                DB::raw('COALESCE(profiles.name, "Profil inconnu") as profile_label'),
                DB::raw('COALESCE(vouchers.code, "-") as customer'),
                DB::raw("'voucher' as source"),
            ])
            ->get()
            ->map(fn ($row) => [
                'date' => Carbon::parse($row->date),
                'amount' => (float) $row->amount,
                'router_label' => (string) (($row->router_label === 'Non assigné' && $selectedRouterName) ? $selectedRouterName : $row->router_label),
                'profile_label' => (string) $row->profile_label,
                'customer' => (string) $row->customer,
                'source' => (string) $row->source,
            ]);

        return $online->concat($manual)->sortBy('date')->values();
    }
}