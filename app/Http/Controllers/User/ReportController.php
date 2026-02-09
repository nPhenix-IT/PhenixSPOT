<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Models\Router;
use App\Models\Profile;
use App\Exports\SalesReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Gère l'affichage du Dashboard avec filtrage par POST pour garder l'URL propre.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Récupération des filtres
        $routerId = $request->input('router_id');
        $period = $request->input('period', 'month');
        $dateRange = $request->input('date_range');

        // Résolution de la plage de dates (Calendrier ou Période rapide)
        [$startDate, $endDate] = $this->resolveDates($period, $dateRange);

        // Liste des routeurs pour le filtre
        $routers = Router::where('user_id', $user->id)->get();

        // Requête de base pour les transactions complétées
        $query = PendingTransaction::query()
            ->where('pending_transactions.user_id', $user->id)
            ->where('pending_transactions.status', 'completed')
            ->whereBetween('pending_transactions.created_at', [$startDate, $endDate]);
        
        if ($routerId) {
            $query->where('pending_transactions.router_id', $routerId);
        }

        // 1. KPI Totaux
        $totals = [
            'sales' => (clone $query)->count(),
            'amount' => (clone $query)->sum('total_price'),
        ];

        // 2. Tendance pour ApexCharts
        $diffDays = $startDate->diffInDays($endDate);
        $groupFormat = $diffDays <= 1 ? '%H:00' : ($diffDays > 365 ? '%Y-%m' : '%Y-%m-%d');

        $salesTrend = (clone $query)
            ->select([
                DB::raw("DATE_FORMAT(created_at, '$groupFormat') as date_label"),
                DB::raw('SUM(total_price) as amount')
            ])
            ->groupBy('date_label')
            ->orderBy('created_at', 'ASC')
            ->get();

        // 3. Performance par Profil (Donut)
        $profileStats = (clone $query)
            ->leftJoin('profiles', 'pending_transactions.profile_id', '=', 'profiles.id')
            ->select([
                DB::raw('COALESCE(profiles.name, "Inconnu") as label'),
                DB::raw('SUM(total_price) as value')
            ])
            ->groupBy('label')
            ->get();

        // 4. Performance par Routeur (Tableau)
        $routerStats = (clone $query)
            ->leftJoin('routers', 'pending_transactions.router_id', '=', 'routers.id')
            ->select([
                DB::raw('COALESCE(routers.name, "Non assigné") as label'),
                DB::raw('COUNT(pending_transactions.id) as sales_count'),
                DB::raw('SUM(total_price) as total_revenue')
            ])
            ->groupBy('label')
            ->orderByDesc('total_revenue')
            ->get();

        return view('content.reports.index', compact(
            'totals', 'salesTrend', 'profileStats', 'routerStats', 
            'routers', 'routerId', 'period', 'dateRange'
        ));
    }

    /**
     * Export Excel filtré
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'month');
        $routerId = $request->input('router_id');
        $dateRange = $request->input('date_range');

        [$startDate, $endDate] = $this->resolveDates($period, $dateRange);

        $query = PendingTransaction::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($routerId) {
            $query->where('router_id', $routerId);
        }

        return Excel::download(new SalesReportExport($query), 'ventes_hotspot_' . now()->format('d_m_Y') . '.xlsx');
    }

    /**
     * Export PDF filtré
     */
    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'month');
        $routerId = $request->input('router_id');
        $dateRange = $request->input('date_range');

        [$startDate, $endDate] = $this->resolveDates($period, $dateRange);

        $query = PendingTransaction::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['router', 'profile']);

        if ($routerId) {
            $query->where('router_id', $routerId);
        }

        $sales = $query->orderByDesc('created_at')->get();
        $totals = [
            'sales' => $sales->count(),
            'amount' => $sales->sum('total_price'),
        ];

        $pdf = Pdf::loadView('content.reports.export_pdf', compact('sales', 'totals', 'period'));
        return $pdf->download('rapport_ventes_' . now()->format('d_m_Y') . '.pdf');
    }

    /**
     * Méthode unique de résolution des dates pour index et exports
     */
    private function resolveDates($period, $dateRange)
    {
        if ($dateRange && str_contains($dateRange, ' to ')) {
            $parts = explode(' to ', $dateRange);
            return [
                Carbon::parse($parts[0])->startOfDay(),
                Carbon::parse($parts[1])->endOfDay()
            ];
        }

        $start = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        return [$start, now()->endOfDay()];
    }
}