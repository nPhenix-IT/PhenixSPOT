@extends('layouts/layoutMaster')

@section('title', 'Analytics Pro')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/select2/select2.scss'
])
<style>
    :root { --primary-gradient: linear-gradient(135deg, #7367f0 0%, #a8aaae 100%); }
    .card-kpi { border: none; border-radius: 15px; overflow: hidden; position: relative; }
    .card-kpi .card-body { position: relative; z-index: 2; }
    .kpi-icon-bg { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1; transform: rotate(-15deg); }
    .filter-bar { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
    .dark-style .filter-bar { background: #2f3349; }
    .chart-container { min-height: 400px; }
    .badge-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h3 class="fw-bold mb-1 text-primary">Tableau de Bord Analytique</h3>
            <p class="text-muted mb-0">Analyse de rentabilité et flux transactionnels.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="btn-group">
                <button type="button" class="btn btn-label-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="ti tabler-download me-1"></i> Exporter
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('user.reports.export-excel', ['period' => $period]) }}"><i class="ti tabler-file-spreadsheet me-2"></i>Format Excel</a></li>
                    <li><a class="dropdown-item" href="{{ route('user.reports.export-pdf', ['period' => $period]) }}"><i class="ti tabler-file-description me-2"></i>Format PDF</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Filtres Modernes (POST) -->
    <div class="filter-bar mb-4">
        <form method="POST" action="{{ route('user.reports.index') }}" id="mainFilterForm" class="row g-3 align-items-end">
            @csrf
            <div class="col-lg-3">
                <label class="form-label fw-bold small text-uppercase">Intervalle Rapide</label>
                <select name="period" class="form-select select2" data-allow-clear="false">
                    <option value="day" {{ $period == 'day' ? 'selected' : '' }}>Aujourd'hui</option>
                    <option value="week" {{ $period == 'week' ? 'selected' : '' }}>7 derniers jours</option>
                    <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Ce mois-ci</option>
                    <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Cette année</option>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label fw-bold small text-uppercase">Dates Personnalisées</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ti tabler-calendar-event"></i></span>
                    <input type="text" name="date_range" value="{{ $dateRange }}" class="form-control flatpickr-range" placeholder="Sélectionner l'intervalle">
                </div>
            </div>
            <div class="col-lg-3">
                <label class="form-label fw-bold small text-uppercase">Source / Routeur</label>
                <select name="router_id" class="form-select select2">
                    <option value="">Tous les équipements</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}" {{ $routerId == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 shadow-sm">
                    <i class="ti tabler-analyze me-2"></i> Analyser
                </button>
                <a href="{{ route('user.reports.index') }}" class="btn btn-label-secondary shadow-sm" title="Réinitialiser">
                    <i class="ti tabler-refresh"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- KPIs -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-kpi bg-primary shadow-lg">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar bg-white bg-opacity-20 p-2 rounded me-3">
                            <i class="ti tabler-cash fs-3 text-white"></i>
                        </div>
                        <h6 class="text-white mb-0 opacity-75">Revenus Totaux</h6>
                    </div>
                    <h2 class="text-white fw-bold mb-1">{{ number_format($totals['amount'], 0, ',', ' ') }} <small class="fs-6">FCFA</small></h2>
                    <p class="text-white opacity-50 mb-0 small">Basé sur {{ $totals['sales'] }} transactions</p>
                    <i class="ti tabler-currency-dollar kpi-icon-bg text-white"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-kpi bg-white border shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar bg-label-info p-2 rounded me-3">
                            <i class="ti tabler-ticket fs-3"></i>
                        </div>
                        <h6 class="text-muted mb-0">Tickets Vendus</h6>
                    </div>
                    <h2 class="fw-bold mb-1 text-dark">{{ number_format($totals['sales']) }}</h2>
                    <p class="text-muted mb-0 small">Volume d'activité réseau</p>
                    <i class="ti tabler-smart-home kpi-icon-bg"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-kpi bg-white border shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar bg-label-success p-2 rounded me-3">
                            <i class="ti tabler-chart-arrows fs-3"></i>
                        </div>
                        <h6 class="text-muted mb-0">Moyenne par Client</h6>
                    </div>
                    <h2 class="fw-bold mb-1 text-dark">{{ $totals['sales'] > 0 ? number_format($totals['amount'] / $totals['sales'], 0, ',', ' ') : 0 }} <small class="fs-6">FCFA</small></h2>
                    <p class="text-muted mb-0 small">Valeur moyenne du panier</p>
                    <i class="ti tabler-users kpi-icon-bg"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Courbe de Performance Financière</h5>
                    <div class="badge bg-label-primary">Temps Réel</div>
                </div>
                <div class="card-body pt-4">
                    <div id="mainRevenueChart" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Donut Chart -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Répartition par Profil</h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div id="profileDonutChart"></div>
                    <div class="mt-4">
                        @foreach($profileStats->take(3) as $ps)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <span class="badge-dot" style="background: #7367f0;"></span>
                                <span class="text-muted small">{{ $ps->label }}</span>
                            </div>
                            <span class="fw-bold small">{{ number_format($ps->value, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Table des Routeurs -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-label-secondary border-bottom">
                    <h5 class="mb-0 text-dark">Classement de Rentabilité des Routeurs</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr class="text-uppercase small fw-bold">
                                <th class="py-3">Équipement</th>
                                <th class="py-3 text-center">Volume Ventes</th>
                                <th class="py-3 text-center">Progression</th>
                                <th class="py-3 text-end">Revenus Générés</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($routerStats as $rs)
                            <tr>
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-label-secondary me-3">
                                            <i class="ti tabler-router"></i>
                                        </div>
                                        <span class="fw-bold">{{ $rs->label }}</span>
                                    </div>
                                </td>
                                <td class="text-center"><span class="badge bg-label-info">{{ $rs->sales_count }}</span></td>
                                <td class="text-center">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: 75%"></div>
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-primary">{{ number_format($rs->total_revenue, 0, ',', ' ') }} <small>FCFA</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/select2/select2.js'
])

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du Date Range Picker
    flatpickr(".flatpickr-range", {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: "fr",
        onClose: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                // Optionnel: soumission auto après sélection d'un intervalle complet
                // document.getElementById('mainFilterForm').submit();
            }
        }
    });

    // Configuration ApexCharts - Main Area
    const trendData = @json($salesTrend);
    const mainChartOptions = {
        series: [{
            name: 'Revenus',
            data: trendData.map(d => parseFloat(d.amount))
        }],
        chart: {
            type: 'area',
            height: 400,
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 800 }
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 4, colors: ['#7367f0'] },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0,
                stops: [0, 95, 100],
                colorStops: [
                    { offset: 0, color: '#7367f0', opacity: 0.4 },
                    { offset: 100, color: '#7367f0', opacity: 0 }
                ]
            }
        },
        grid: { show: true, borderColor: '#f1f1f1', strokeDashArray: 5 },
        xaxis: {
            categories: trendData.map(d => d.date_label),
            axisBorder: { show: false },
            labels: { style: { colors: '#a8aaae' } }
        },
        yaxis: {
            labels: { 
                formatter: (val) => val.toLocaleString() + ' F',
                style: { colors: '#a8aaae' } 
            }
        },
        tooltip: {
            theme: 'dark',
            x: { show: true },
            y: { formatter: (val) => val.toLocaleString() + ' FCFA' }
        },
        colors: ['#7367f0']
    };
    new ApexCharts(document.querySelector("#mainRevenueChart"), mainChartOptions).render();

    // Configuration Donut - Profils
    const profileData = @json($profileStats);
    const donutOptions = {
        series: profileData.map(d => parseFloat(d.value)),
        labels: profileData.map(d => d.label),
        chart: { type: 'donut', height: 300 },
        colors: ['#7367f0', '#28c76f', '#ff9f43', '#00cfe8', '#ea5455'],
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Revenu Total',
                            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString() + ' F'
                        }
                    }
                }
            }
        },
        legend: { show: false },
        dataLabels: { enabled: false }
    };
    new ApexCharts(document.querySelector("#profileDonutChart"), donutOptions).render();
});
</script>
@endsection