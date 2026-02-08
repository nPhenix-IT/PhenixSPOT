<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Models\Profile;
use App\Models\Router;
use App\Exports\SalesReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'month');
        $startDate = $this->resolveStartDate($period);

        $baseQuery = PendingTransaction::query()
            ->where('pending_transactions.user_id', $user->id)
            ->where('pending_transactions.status', 'completed');
        
        if ($startDate) {
            $baseQuery->where('pending_transactions.created_at', '>=', $startDate);
        }

        $profileSales = (clone $baseQuery)
            ->leftJoin('profiles', 'pending_transactions.profile_id', '=', 'profiles.id')
            ->select([
                DB::raw('COALESCE(profiles.name, "Profil inconnu") as label'),
                DB::raw('COUNT(pending_transactions.id) as total_sales'),
                DB::raw('SUM(pending_transactions.total_price) as total_amount'),
            ])
            ->groupBy('label')
            ->orderByDesc('total_sales')
            ->get();

        $routerSales = (clone $baseQuery)
            ->leftJoin('routers', 'pending_transactions.router_id', '=', 'routers.id')
            ->select([
                DB::raw('COALESCE(routers.name, "Non assigné") as label'),
                DB::raw('COUNT(pending_transactions.id) as total_sales'),
                DB::raw('SUM(pending_transactions.total_price) as total_amount'),
            ])
            ->groupBy('label')
            ->orderByDesc('total_sales')
            ->get();

        $totals = [
            'sales' => (clone $baseQuery)->count(),
            'amount' => (clone $baseQuery)->sum('total_price'),
        ];

        return view('content.reports.index', compact('profileSales', 'routerSales', 'totals', 'period'));
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'month');
        $startDate = $this->resolveStartDate($period);

        $query = PendingTransaction::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        return Excel::download(new SalesReportExport($query), 'ventes.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'month');
        $startDate = $this->resolveStartDate($period);

        $query = PendingTransaction::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['router', 'profile']);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $sales = $query->orderByDesc('created_at')->get();
        $totals = [
            'sales' => $sales->count(),
            'amount' => $sales->sum('total_price'),
        ];

        $pdf = Pdf::loadView('content.reports.export_pdf', compact('sales', 'totals', 'period'));
        return $pdf->download('ventes.pdf');
    }

    private function resolveStartDate(string $period): ?\DateTimeInterface
    {
        return match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => null,
        };
    }
}
