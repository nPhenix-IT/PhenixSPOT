@extends('layouts/layoutMaster')

@section('title', 'PhenixSpot - Dashboard')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
<style>
    :root {
        --ps-bg: #f4f7fe;
        --ps-card-shadow: 0 10px 30px rgba(160, 174, 192, 0.1);
        --ps-primary: #7367f0;
    }

    body { background-color: var(--ps-bg); }

    .ps-card {
        background: #fff;
        border: none;
        border-radius: 20px;
        box-shadow: var(--ps-card-shadow);
        margin-bottom: 1.5rem;
    }

    .ps-card-header {
        padding: 1.5rem 1.5rem 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .ps-title { font-weight: 700; color: #1b2559; margin-bottom: 0; }

    /* KPI Mini Cards */
    .kpi-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f4f7fe;
        color: var(--ps-primary);
    }

    .trend-badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.6rem;
        border-radius: 10px;
        font-weight: 600;
    }
    .trend-up { background: #e6fcf5; color: #05cd99; }
    .trend-down { background: #fff5f5; color: #ee5d50; }

    .mini-chart { height: 40px !important; }

    /* Table-like lists */
    .ps-list-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f4f7fe;
    }
    .ps-list-item:last-child { border-bottom: none; }
    .ps-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 12px; }

    .service-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    
    .btn-ps-primary {
        background: #e9e3ff;
        color: var(--ps-primary);
        border: none;
        border-radius: 12px;
        padding: 0.5rem 1rem;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
@php $isAdmin = auth()->user()->hasRole(['Super-admin', 'Admin']); @endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="ps-title fs-3">Dashboard</h4>
        @if(!$isAdmin)
            <span class="badge bg-label-success rounded-pill px-3">EN LIGNE <i class="ti tabler-chevron-down ms-1"></i></span>
        @endif
    </div>

    <!-- 4 TOP CARDS (KPIs) -->
    <div class="row g-3 mb-4">
        @php
            $kpis = $isAdmin ? [
                ['label' => 'Routers', 'val' => '24', 'sub' => 'actifs', 'trend' => '+ 4', 'icon' => 'tabler-device-laptop', 'chartId' => 'miniChart1'],
                ['label' => 'Hotspot', 'val' => '542', 'sub' => 'Connexions', 'trend' => '+ 5%', 'icon' => 'tabler-wifi', 'chartId' => 'miniChart2'],
                ['label' => 'PPPoE', 'val' => '179', 'sub' => 'Abonnés actifs', 'trend' => '+ 16', 'icon' => 'tabler-broadcast', 'chartId' => 'miniChart3'],
                ['label' => 'VPN', 'val' => '82', 'sub' => 'Comptes VPN', 'trend' => '- 3.5%', 'icon' => 'tabler-shield-lock', 'chartId' => 'miniChart4']
            ] : [
                ['label' => 'Routers', 'val' => '2', 'sub' => 'Actifs', 'trend' => '+ 4', 'icon' => 'tabler-device-laptop', 'chartId' => 'miniChart1'],
                ['label' => 'Hotspot', 'val' => '48', 'sub' => 'Connexions', 'trend' => '+ 4.3%', 'icon' => 'tabler-wifi', 'chartId' => 'miniChart2'],
                ['label' => 'PPPoE', 'val' => '27', 'sub' => 'Abonnés actifs', 'trend' => '+ 12', 'icon' => 'tabler-broadcast', 'chartId' => 'miniChart3'],
                ['label' => 'VPN', 'val' => '5', 'sub' => 'Comptes VPN', 'trend' => 'Stable', 'icon' => 'tabler-shield-lock', 'chartId' => 'miniChart4']
            ];
        @endphp

        @foreach($kpis as $kpi)
        <div class="col-xl-3 col-md-6">
            <div class="card ps-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="kpi-icon-box me-3">
                            <i class="ti {{ $kpi['icon'] }} fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted fw-bold d-block">{{ $kpi['label'] }}</small>
                            <small class="text-light">{{ $kpi['sub'] }}</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="d-flex align-items-center">
                            <h3 class="ps-title me-2">{{ $kpi['val'] }}</h3>
                            <span class="trend-badge {{ str_contains($kpi['trend'], '-') ? 'trend-down' : 'trend-up' }}">
                                {{ $kpi['trend'] }}
                            </span>
                        </div>
                        <div id="{{ $kpi['chartId'] }}" class="mini-chart w-50"></div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($isAdmin)
    <!-- VUE SUPER ADMIN -->
    <div class="row g-4">
        <!-- Main Revenue Chart -->
        <div class="col-lg-7">
            <div class="card ps-card h-100">
                <div class="ps-card-header">
                    <div>
                        <small class="text-muted fw-bold">Revenus</small>
                        <h2 class="ps-title">1,475,000 <small class="fs-6 text-muted">FCFA</small></h2>
                        <span class="text-success small fw-bold">Aujourd'hui <i class="ti tabler-chevron-right"></i> +17.8%</span>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-2 rounded-pill">
                        <i class="ti tabler-currency-euro text-warning fs-3"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div id="revenueChartMain"></div>
                    <div class="row mt-4 pt-2">
                        <div class="col-4">
                            <div class="d-flex align-items-center mb-1">
                                <span class="service-dot bg-primary"></span><small class="text-muted">Hotspot</small>
                            </div>
                            <p class="mb-0 fw-bold">648,000 <small class="trend-up p-1 rounded">+178%</small></p>
                        </div>
                        <div class="col-4 border-start">
                            <div class="d-flex align-items-center mb-1">
                                <span class="service-dot bg-danger"></span><small class="text-muted">PPPoE</small>
                            </div>
                            <p class="mb-0 fw-bold">439,000 <small class="trend-up p-1 rounded">+16.2%</small></p>
                        </div>
                        <div class="col-4 border-start">
                            <div class="d-flex align-items-center mb-1">
                                <span class="service-dot bg-info"></span><small class="text-muted">VPN</small>
                            </div>
                            <p class="mb-0 fw-bold">388,000 <small class="trend-down p-1 rounded">-3.5%</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leads & Sellers -->
        <div class="col-lg-5">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card ps-card">
                        <div class="ps-card-header align-items-start">
                            <h5 class="ps-title">Leads générés</h5>
                            <span class="trend-up trend-badge">+22.4%</span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <h1 class="ps-title me-2">230</h1>
                                <p class="text-muted mb-0 small">Nouveau(x) leads<br>cette semaine</p>
                            </div>
                            <div id="leadsMiniChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card ps-card">
                        <div class="ps-card-header">
                            <h5 class="ps-title">Top Vendeurs</h5>
                        </div>
                        <div class="card-body">
                            @foreach([['name' => 'Jean K', 'loc' => 'Poutort', 'val' => '113,000'], ['name' => 'Fatou B.', 'loc' => 'Rouvert', 'val' => '34,000']] as $vendeur)
                            <div class="ps-list-item">
                                <img src="https://ui-avatars.com/api/?name={{$vendeur['name']}}&background=random" class="ps-avatar">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold">{{ $vendeur['name'] }}</h6>
                                    <small class="text-muted"><i class="ti tabler-map-pin fs-6"></i> {{ $vendeur['loc'] }}</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-success fw-bold">{{ $vendeur['val'] }} FCFA</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @else
    <!-- VUE UTILISATEUR -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card ps-card">
                <div class="ps-card-header">
                    <h5 class="ps-title">Activité réseau</h5>
                    <button class="btn btn-ps-primary btn-sm"><i class="ti tabler-plus me-1"></i> Nouveau Voucher</button>
                </div>
                <div class="card-body">
                    <div id="userActivityChart"></div>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div id="userDonutChart" style="min-height: 150px"></div>
                                <div class="ms-2">
                                    <h3 class="ps-title">321</h3>
                                    <small class="text-muted">Connexions</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                             <table class="table table-borderless table-sm">
                                <tr><td><span class="service-dot bg-primary"></span> Hotspot</td><td class="fw-bold">64,000 FCFA</td><td class="text-success fw-bold">+25.6%</td></tr>
                                <tr><td><span class="service-dot bg-warning"></span> PPPoE</td><td class="fw-bold">59,000 FCFA</td><td class="text-success fw-bold">+28.4%</td></tr>
                                <tr><td><span class="service-dot bg-info"></span> VPN</td><td class="fw-bold">45,000 FCFA</td><td class="text-success fw-bold">+25.7%</td></tr>
                             </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card ps-card h-100">
                <div class="ps-card-header">
                    <h5 class="ps-title">Revenus</h5>
                </div>
                <div class="card-body">
                    <h2 class="ps-title">168,000 <small class="fs-6 text-muted">FCFA</small></h2>
                    <p class="text-muted small mb-3">Aujourd'hui <span class="text-success fw-bold">+ 25.6%</span></p>
                    <div id="userRevenueMiniChart"></div>
                    
                    <h6 class="mt-4 mb-3 fw-bold">Dépenses</h6>
                    @foreach([['name' => 'MoneyFusion', 'val' => '39,600', 'type' => 'Commission'], ['name' => 'Frais', 'val' => '69,500', 'type' => 'Transaction']] as $exp)
                    <div class="ps-list-item">
                        <div class="kpi-icon-box bg-light me-2" style="width:32px; height:32px;"><i class="ti tabler-wallet fs-6"></i></div>
                        <div class="flex-grow-1"><small class="fw-bold d-block">{{$exp['name']}}</small></div>
                        <div class="text-end"><small class="text-dark fw-bold">{{$exp['val']}} FCFA</small></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isAdmin = @json($isAdmin);
    
    // Config commune pour mini charts
    const miniChartOptions = {
        chart: { type: 'area', height: 40, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 2 },
        fill: { opacity: 0.1 },
        series: [{ data: [15, 40, 20, 50, 35, 60] }],
        colors: ['#7367f0']
    };

    // Render 4 mini charts
    for(let i=1; i<=4; i++) {
        new ApexCharts(document.querySelector(`#miniChart${i}`), miniChartOptions).render();
    }

    if (isAdmin) {
        // ADMIN REVENUE CHART
        new ApexCharts(document.querySelector("#revenueChartMain"), {
            series: [{ name: 'Revenus', data: [30, 40, 35, 50, 49, 60, 70, 91, 125] }],
            chart: { height: 280, type: 'area', toolbar: { show: false } },
            colors: ['#7367f0'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0 } },
            xaxis: { categories: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep'] }
        }).render();

        // LEADS MINI CHART
        new ApexCharts(document.querySelector("#leadsMiniChart"), {
            series: [{ data: [10, 15, 8, 25, 18, 40] }],
            chart: { height: 60, type: 'line', sparkline: { enabled: true } },
            stroke: { curve: 'smooth', width: 3 },
            colors: ['#05cd99']
        }).render();

    } else {
        // USER ACTIVITY CHART (AREA)
        new ApexCharts(document.querySelector("#userActivityChart"), {
            series: [
                { name: 'Traffic', data: [31, 40, 28, 51, 42, 109, 100] },
                { name: 'Users', data: [11, 32, 45, 32, 34, 52, 41] }
            ],
            chart: { height: 250, type: 'area', toolbar: { show: false } },
            colors: ['#7367f0', '#ff9f43'],
            stroke: { curve: 'smooth' },
            xaxis: { categories: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] }
        }).render();

        // USER DONUT
        new ApexCharts(document.querySelector("#userDonutChart"), {
            series: [44, 32, 24],
            chart: { type: 'donut', height: 150 },
            colors: ['#7367f0', '#05cd99', '#ff9f43'],
            legend: { show: false },
            dataLabels: { enabled: false }
        }).render();

        // REVENUE MINI
        new ApexCharts(document.querySelector("#userRevenueMiniChart"), {
            series: [{ data: [5, 10, 8, 15, 20, 25] }],
            chart: { type: 'bar', height: 60, sparkline: { enabled: true } },
            colors: ['#7367f0']
        }).render();
    }
});
</script>
@endsection