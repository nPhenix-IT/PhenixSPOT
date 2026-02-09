@extends('layouts/layoutMaster')

@section('title', 'Tableau de Bord Analytique')

@section('vendor-style')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<style>
    #routerMap { height: 400px; border-radius: 12px; z-index: 1; }
    .stat-card { transition: all 0.3s ease; }
    .stat-card:hover { transform: translateY(-5px); }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Tableau de Bord Analytique</h4>
        @if(auth()->user()->hasRole(['Super-admin', 'Admin']))
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm w-auto" id="adminPeriodFilter">
                <option value="day">Aujourd'hui</option>
                <option value="week">Cette Semaine</option>
                <option value="month" selected>Ce Mois</option>
                <option value="year">Cette Année</option>
            </select>
        </div>
        @endif
    </div>

    <!-- Container Dynamique AJAX -->
    <div id="dashboardContent">
        @if(auth()->user()->hasRole(['Super-admin', 'Admin']))
            <!-- VUE ADMIN -->
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card stat-card bg-label-primary">
                        <div class="card-body">
                            <h6 class="mb-1">Chiffre d'Affaires</h6>
                            <h3 class="mb-0 fw-bold" id="kpi-revenue">...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-label-success">
                        <div class="card-body">
                            <h6 class="mb-1">Total Utilisateurs</h6>
                            <h3 class="mb-0 fw-bold" id="kpi-total-users">...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-label-warning">
                        <div class="card-body">
                            <h6 class="mb-1">Retraits en Attente</h6>
                            <h3 class="mb-0 fw-bold" id="kpi-pending-withdrawals">...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-4">
                    <div class="card">
                        <div class="card-header"><h5>Croissance des Utilisateurs Hotspot</h5></div>
                        <div class="card-body">
                            <div id="adminGrowthChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- VUE USER -->
            <div class="row g-4">
                <div class="col-md-2 col-6">
                    <div class="card text-center stat-card p-3 shadow-none border">
                        <small class="text-muted">Routeurs</small>
                        <h4 class="mb-0" id="kpi-routers">0</h4>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card text-center stat-card p-3 shadow-none border">
                        <small class="text-success">Vouchers Dispo</small>
                        <h4 class="mb-0" id="kpi-vouchers-available">0</h4>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card text-center stat-card p-3 shadow-none border">
                        <small class="text-primary">Connectés</small>
                        <h4 class="mb-0" id="kpi-vouchers-connected">0</h4>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                     <div class="card border-0 shadow-sm">
                        <div class="card-body p-2">
                             <div id="routerMap"></div>
                        </div>
                     </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header text-center"><h5>Distribution des Vouchers</h5></div>
                        <div class="card-body">
                            <div id="userVoucherChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('vendor-script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark-style');
    const role = @json(auth()->user()->roles->pluck('name')[0]);
    let growthChart, voucherChart;

    // Initialisation de la carte pour l'utilisateur
    if (role === 'User') {
        const routers = @json($routers);
        const map = L.map('routerMap').setView([5.348, -4.03], 12); // Default Ivory Coast
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        routers.forEach(router => {
            if (router.latitude && router.longitude) {
                L.marker([router.latitude, router.longitude])
                 .addTo(map)
                 .bindPopup(`<b>${router.name}</b><br>Statut: ${router.status}`);
            }
        });
    }

    // Fonction de chargement des statistiques via AJAX
    function fetchStats(period = 'month') {
        fetch(`/dashboard/stats?period=${period}`)
            .then(res => res.json())
            .then(data => {
                if (data.role === 'admin') {
                    document.getElementById('kpi-revenue').innerText = data.kpis.revenue;
                    document.getElementById('kpi-total-users').innerText = data.kpis.total_users;
                    document.getElementById('kpi-pending-withdrawals').innerText = data.kpis.pending_withdrawals;
                    updateAdminChart(data.charts);
                } else {
                    document.getElementById('kpi-routers').innerText = data.kpis.total_routers;
                    document.getElementById('kpi-vouchers-available').innerText = data.kpis.vouchers_available;
                    document.getElementById('kpi-vouchers-connected').innerText = data.kpis.vouchers_connected;
                    updateUserChart(data.charts);
                }
            });
    }

    function updateAdminChart(charts) {
        const options = {
            series: [{ name: 'Nouveaux Utilisateurs', data: charts.userGrowthData }],
            chart: { height: 350, type: 'area', toolbar: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth' },
            xaxis: { categories: charts.userGrowthLabels },
            colors: ['#7367f0']
        };
        if (growthChart) growthChart.destroy();
        growthChart = new ApexCharts(document.querySelector("#adminGrowthChart"), options);
        growthChart.render();
    }

    function updateUserChart(charts) {
        const options = {
            series: charts.voucherDistribution,
            chart: { type: 'donut', height: 350 },
            labels: ['Utilisés', 'Disponibles', 'Désactivés'],
            colors: ['#7367f0', '#28c76f', '#ea5455'],
            legend: { position: 'bottom' }
        };
        if (voucherChart) voucherChart.destroy();
        voucherChart = new ApexCharts(document.querySelector("#userVoucherChart"), options);
        voucherChart.render();
    }

    // Event Listener pour le filtre Admin
    const filter = document.getElementById('adminPeriodFilter');
    if (filter) {
        filter.addEventListener('change', (e) => fetchStats(e.target.value));
    }

    // Chargement initial
    fetchStats();
});
</script>
@endsection