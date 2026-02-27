@extends('layouts/layoutMaster')

@section('title', 'PhenixSpot - Dashboard')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
<style>
/*
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

    /* KPI Mini Cards 
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

    /* Table-like lists 
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
    }*/
    :root { --ps-bg:#f4f7fe; --ps-card-shadow:0 10px 30px rgba(160,174,192,.1); --ps-primary:#7367f0; }
    body { background-color:var(--ps-bg); }
    .ps-card{background:#fff;border:none;border-radius:20px;box-shadow:var(--ps-card-shadow);margin-bottom:1.5rem}
    .ps-card-header{padding:1.5rem 1.5rem .5rem;display:flex;justify-content:space-between;align-items:center}
    .ps-title{font-weight:700;color:#1b2559;margin-bottom:0}
    .kpi-icon-box{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:#f4f7fe;color:var(--ps-primary)}
    .trend-badge{font-size:.75rem;padding:.2rem .6rem;border-radius:10px;font-weight:600}
    .trend-up{background:#e6fcf5;color:#05cd99}.trend-down{background:#fff5f5;color:#ee5d50}
    .mini-chart{height:40px!important}.service-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:8px}
    .ps-list-item{display:flex;align-items:center;padding:.75rem 0;border-bottom:1px solid #f4f7fe}
    .ps-list-item:last-child{border-bottom:none}
</style>
@endsection

@section('content')
@php $isAdmin = auth()->user()->hasRole(['Super-admin', 'Admin']); @endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="ps-title fs-3">Dashboard</h4>
        @if($isAdmin)
            <select class="form-select form-select-sm w-auto" id="adminPeriodFilter">
                <option value="day">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month" selected>Ce mois</option>
                <option value="year">Cette année</option>
            </select>
        @else
            <div class="d-flex gap-2 flex-wrap">
                <select class="form-select form-select-sm" id="routerFilter" style="min-width:180px">
                    <option value="">Tous les routeurs</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}">{{ $router->name }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm" id="saleTypeFilter" style="min-width:210px">
                    <option value="all">Tous les types de vente</option>
                    <option value="manual">Vente manuelle voucher</option>
                    <option value="online">Vente en ligne</option>
                </select>
            </div>
        @endif
    </div>
    <div class="row g-3 mb-4" id="topKpis"></div>
    @if($isAdmin)
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card ps-card h-100">
                    <div class="ps-card-header">
                        <div>
                            <small class="text-muted fw-bold">Revenus nets</small>
                            <h2 class="ps-title"><span id="adminRevenueValue">0</span> <small class="fs-6 text-muted">FCFA</small></h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="adminRevenueChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card ps-card h-100">
                    <div class="ps-card-header"><h5 class="ps-title">Suivi système</h5></div>
                    <div class="card-body" id="adminGauges"></div>
                </div>
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card ps-card">
                    <div class="ps-card-header"><h5 class="ps-title">Évolution des ventes (filtre routeur)</h5></div>
                    <div class="card-body"><div id="salesEvolutionChart"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card ps-card h-100">
                    <div class="ps-card-header"><h5 class="ps-title">Vouchers connectés</h5></div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div id="vouchersDonutChart" style="min-height: 200px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card ps-card">
                    <div class="ps-card-header"><h5 class="ps-title">Évolution par type de vente</h5></div>
                    <div class="card-body"><div id="salesTypeChart"></div></div>
                </div>
            </div>
            <div class="col-12">
                <div class="card ps-card">
                    <div class="ps-card-header"><h5 class="ps-title">10 dernières transactions vouchers</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <th>Référence</th><th>Type</th><th>Routeur</th><th>Brut</th><th>Frais</th><th>Net</th><th>Date</th>
                                </tr>
                                </thead>
                                <tbody id="latestTransactionsTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js'
])
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isAdmin = @json($isAdmin);
    const statsUrl = @json(route('dashboard.stats'));
    const money = (n) => `${Number(n || 0).toLocaleString('fr-FR')} FCFA`;

    let salesEvolutionChart = null;
    let salesTypeChart = null;
    let vouchersDonutChart = null;
    let adminRevenueChart = null;

    const notifyError = (message) => {
        if (window.Swal) {
            Swal.fire({icon:'error', title:'Erreur', text:message, toast:true, timer:3500, position:'top-end', showConfirmButton:false});
        } else if (window.toastr) {
            toastr.error(message);
        }
    };
    
    function topCard(label, val, icon, trend = '+') {
        return `<div class="col-xl-3 col-md-6"><div class="card ps-card h-100"><div class="card-body">
                <div class="d-flex align-items-center mb-2"><div class="kpi-icon-box me-3"><i class="ti ${icon} fs-4"></i></div>
                <div><small class="text-muted fw-bold d-block">${label}</small></div></div>
                <div class="d-flex justify-content-between align-items-end"><h3 class="ps-title me-2">${val}</h3><span class="trend-badge ${trend.includes('-') ? 'trend-down':'trend-up'}">${trend}</span></div>
                </div></div></div>`;
    }
    
    async function loadStats() {
        try {
            const params = new URLSearchParams();
            if (isAdmin) {
                params.set('period', document.getElementById('adminPeriodFilter').value);
            } else {
                const routerId = document.getElementById('routerFilter').value;
                const saleType = document.getElementById('saleTypeFilter').value;
                if (routerId) params.set('router_id', routerId);
                params.set('sale_type', saleType);
            }
            
            const res = await fetch(`${statsUrl}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            if (!res.ok) throw new Error('Chargement des statistiques impossible');
            const payload = await res.json();
            if (isAdmin) {
                renderAdmin(payload);
            } else {
                renderUser(payload);
            }
        } catch (e) {
            notifyError(e.message || 'Erreur lors du chargement des données du dashboard.');
        }
    }
    
    function renderUser(payload) {
        const k = payload.kpis;
        document.getElementById('topKpis').innerHTML = [
            topCard('Vente totale', money(k.total_sales), 'tabler-cash'),
            topCard('Nbre de routeurs', k.routers_count, 'tabler-device-laptop'),
            topCard('Total coupons/vouchers', k.vouchers_total, 'tabler-ticket'),
            topCard('Vouchers non utilisés', k.vouchers_unused, 'tabler-ticket-off', '-'),
            topCard('Vouchers utilisés', k.vouchers_used, 'tabler-check'),
            topCard('Vouchers connectés', k.vouchers_online, 'tabler-wifi')
        ].join('');

        if (salesEvolutionChart) salesEvolutionChart.destroy();
        salesEvolutionChart = new ApexCharts(document.querySelector('#salesEvolutionChart'), {
            chart: {height: 260, type: 'area', toolbar: {show:false}},
            dataLabels: {enabled:false}, stroke: {curve:'smooth', width:3}, colors: ['#7367f0'],
            series: [{name: 'Ventes nettes', data: payload.charts.sales_evolution.series}],
            xaxis: {categories: payload.charts.sales_evolution.labels}
        });
        salesEvolutionChart.render();
        
        if (salesTypeChart) salesTypeChart.destroy();
        salesTypeChart = new ApexCharts(document.querySelector('#salesTypeChart'), {
            chart: {height: 260, type: 'line', toolbar: {show:false}},
            dataLabels: {enabled:false}, stroke: {curve:'smooth', width:3}, colors: ['#00cfe8', '#28c76f'],
            series: payload.charts.sales_type_evolution.series,
            xaxis: {categories: payload.charts.sales_type_evolution.labels}
        });
        salesTypeChart.render();
        
        if (vouchersDonutChart) vouchersDonutChart.destroy();
        vouchersDonutChart = new ApexCharts(document.querySelector('#vouchersDonutChart'), {
            chart: {type:'donut', height:260},
            labels: ['Utilisés', 'Non utilisés', 'En ligne'],
            series: [k.vouchers_used, k.vouchers_unused, k.vouchers_online],
            colors: ['#7367f0','#ff9f43','#28c76f'],
            legend: {position:'bottom'}
        });
        vouchersDonutChart.render();

        const tbody = document.getElementById('latestTransactionsTable');
        const rows = payload.latest_transactions || [];
        tbody.innerHTML = rows.length ? rows.map(r => `<tr>
            <td>${r.reference || '-'}</td>
            <td><span class="badge bg-label-primary">${r.sale_type === 'manual_generation' ? 'Manuelle' : 'En ligne'}</span></td>
            <td>${r.router_name || '-'}</td>
            <td>${money(r.gross_amount)}</td>
            <td>${money(r.fee_amount)}</td>
            <td class="text-success fw-bold">${money(r.net_amount)}</td>
            <td>${r.transacted_at || '-'}</td>
        </tr>`).join('') : '<tr><td colspan="7" class="text-center text-muted">Aucune transaction</td></tr>';
    }

    function renderAdmin(payload) {
        const k = payload.kpis;
        document.getElementById('topKpis').innerHTML = [
            topCard('Revenu net', money(k.revenue), 'tabler-currency-dollar'),
            topCard('Utilisateurs', k.users, 'tabler-users'),
            topCard('Routeurs', k.routers, 'tabler-device-laptop'),
            topCard('Vouchers total', k.vouchers_total, 'tabler-ticket'),
            topCard('Vouchers utilisés', k.vouchers_used, 'tabler-check'),
            topCard('Vouchers connectés', k.vouchers_online, 'tabler-wifi'),
            topCard('Retraits en attente', k.pending_withdrawals, 'tabler-hourglass-high', '-')
        ].join('');

        document.getElementById('adminRevenueValue').textContent = Number(k.revenue || 0).toLocaleString('fr-FR');
        if (adminRevenueChart) adminRevenueChart.destroy();
        adminRevenueChart = new ApexCharts(document.querySelector('#adminRevenueChart'), {
            chart: {height:260, type:'bar', toolbar: {show:false}},
            series: [{name:'Revenu net', data: payload.charts.revenue.series}],
            xaxis: {categories: payload.charts.revenue.labels}, colors: ['#7367f0']
        });
        adminRevenueChart.render();

        document.getElementById('adminGauges').innerHTML = `
            <div class="ps-list-item"><span class="service-dot bg-success"></span><span class="me-auto">Vouchers non utilisés</span><strong>${k.vouchers_unused}</strong></div>
            <div class="ps-list-item"><span class="service-dot bg-warning"></span><span class="me-auto">Vouchers utilisés</span><strong>${k.vouchers_used}</strong></div>
            <div class="ps-list-item"><span class="service-dot bg-info"></span><span class="me-auto">Vouchers connectés</span><strong>${k.vouchers_online}</strong></div>
            <div class="ps-list-item"><span class="service-dot bg-danger"></span><span class="me-auto">Retraits en attente</span><strong>${k.pending_withdrawals}</strong></div>`;
    }

    loadStats();
    if (isAdmin) {
        document.getElementById('adminPeriodFilter').addEventListener('change', loadStats);
    } else {
        document.getElementById('routerFilter').addEventListener('change', loadStats);
        document.getElementById('saleTypeFilter').addEventListener('change', loadStats);
    }
});
</script>
@endsection