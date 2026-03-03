@extends('layouts/layoutMaster')

@section('title', 'PhenixSpot - Dashboard Pro')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
<style>
:root {
  --dash-bg: #f8fafc;
  --dash-card-bg: #ffffff;
  --dash-accent: #7367f0;
  --dash-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.05);
}
body { background-color: var(--dash-bg); }
.card-analytics { border: none; border-radius: 20px; box-shadow: var(--dash-shadow); background: var(--dash-card-bg); transition: transform 0.2s ease; }
.kpi-label { font-size: 0.85rem; color: #64748b; font-weight: 500; }
.kpi-value { font-size: 1.6rem; font-weight: 700; color: #1e293b; letter-spacing: -0.5px; }
.icon-wrapper { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
.bg-light-primary { background: #f0eeff; color: #7367f0; }
.bg-light-success { background: #e8fadf; color: #28c76f; }
.bg-light-info { background: #e0f9fc; color: #00cfe8; }
.bg-light-warning { background: #fff4e6; color: #ff9f43; }
.table-modern thead th { background: #f8fafc; font-weight: 600; color: #475569; padding: 1.2rem 1rem; border: none; }
.table-modern tbody td { padding: 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.source-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.filter-bar { background: #fff; padding: 0.75rem 1.5rem; border-radius: 15px; box-shadow: var(--dash-shadow); margin-bottom: 2rem; }
.chart-filter-select { border: none; font-size: 0.8rem; font-weight: 600; color: #7367f0; cursor: pointer; background: #f0eeff; padding: 4px 10px; border-radius: 8px; }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Main Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
      <h4 class="fw-bold mb-1">Tableau de bord <span class="text-primary">Analytics</span></h4>
      <p class="text-muted mb-0">Résumé des performances en temps réel.</p>
    </div>

    <div class="d-flex gap-2 filter-bar align-items-center">
      @if(!$isAdmin)
        <div class="d-flex align-items-center gap-2 border-end pe-2">
          <i class="ti tabler-router text-muted"></i>
          <select id="routerFilterGlobal" class="form-select border-0 bg-transparent fw-semibold" style="width: auto;" onchange="loadStats()">
            <option value="">Tous les routeurs</option>
            @foreach($routers as $r) <option value="{{$r->id}}">{{$r->name}}</option> @endforeach
          </select>
        </div>
        <div class="d-flex align-items-center gap-2">
          <i class="ti tabler-filter text-muted"></i>
          <select id="saleTypeFilterGlobal" class="form-select border-0 bg-transparent fw-semibold" style="width: auto;" onchange="loadStats()">
            <option value="all">Toutes sources</option>
            <option value="manual">Vente Manuelle</option>
            <option value="online">Vente en Ligne</option>
          </select>
        </div>
      @endif
      <button class="btn btn-icon btn-primary rounded-circle ms-2" onclick="loadStats()">
        <i class="ti tabler-refresh"></i>
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-4 mb-4" id="kpi-container"></div>

  <!-- Charts Row -->
  <div class="row g-4 mb-4">

    @if($isAdmin)
      <!-- ADMIN: 2 charts alignés (gauche/droite) -->
      <div class="col-lg-6">
        <div class="card card-analytics h-100">
          <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <div>
              <h5 class="card-title mb-0 fw-bold">Évolution des revenus</h5>
              <small class="text-muted">Analyse temporelle des encaissements</small>
            </div>
            <div class="d-flex gap-2">
              <select id="evolutionPeriod" class="chart-filter-select" onchange="loadStats()">
                <option value="week">Semaine</option>
                <option value="month">Mois</option>
                <option value="day">Aujourd'hui</option>
              </select>
            </div>
          </div>
          <div class="card-body">
            <div id="evolutionChart" style="min-height: 350px;"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card card-analytics h-100">
          <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <div>
              <h5 class="card-title mb-0 fw-bold">Revenus Plateforme (Commissions + Frais services)</h5>
              <small class="text-muted">Filtrer par jour/semaine/mois</small>
            </div>
            <div class="d-flex gap-2">
              <select id="feesTrendPeriod" class="chart-filter-select" onchange="loadStats()">
                <option value="week">Semaine</option>
                <option value="month">Mois</option>
                <option value="day">Aujourd'hui</option>
              </select>
            </div>
          </div>
          <div class="card-body">
            <div id="feesTrendChart" style="min-height: 350px;"></div>
          </div>
        </div>
      </div>

    @else
      <!-- ✅ USER: 3 colonnes -> Evolution (6) + Sources (3) + Top Zone (3) -->
      <div class="col-lg-6">
        <div class="card card-analytics h-100">
          <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <div>
              <h5 class="card-title mb-0 fw-bold">Évolution des revenus</h5>
              <small class="text-muted">Analyse temporelle des encaissements</small>
            </div>
            <div class="d-flex gap-2">
              <select id="evolutionPeriod" class="chart-filter-select" onchange="loadStats()">
                <option value="week">Semaine</option>
                <option value="month">Mois</option>
                <option value="day">Aujourd'hui</option>
              </select>
            </div>
          </div>
          <div class="card-body">
            <div id="evolutionChart" style="min-height: 350px;"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-3">
        <div class="card card-analytics h-100">
          <div class="card-header">
            <h5 class="card-title mb-0 fw-bold">Sources de revenus</h5>
            <small class="text-muted">Manuel vs En ligne</small>
          </div>
          <div class="card-body d-flex flex-column align-items-center justify-content-center">
            <div id="sourceDistributionChart"></div>
            <div id="sourceLegend" class="mt-4 w-100"></div>
          </div>
        </div>
      </div>

      <!-- ✅ NEW: Top Zone -->
      <div class="col-lg-3">
        <div class="card card-analytics h-100">
          <div class="card-header">
            <h5 class="card-title mb-0 fw-bold">Top Zone</h5>
            <small class="text-muted">Zone la plus performante</small>
          </div>

          <div class="card-body d-flex flex-column justify-content-center">
            <div class="mb-2">
              <div class="text-muted small">Zone</div>
              <div class="fw-bold" style="font-size:1.05rem;" id="topZoneName">—</div>
            </div>

            <div class="mb-3">
              <div class="text-muted small">Revenus (net)</div>
              <div class="fw-bold" style="font-size:1.25rem;" id="topZoneAmount">0</div>
            </div>

            <div class="mb-1 d-flex justify-content-between align-items-center">
              <div class="text-muted small">Part</div>
              <div class="fw-semibold small" id="topZoneShare">0%</div>
            </div>
            <div class="progress" style="height:10px; border-radius:999px;">
              <div class="progress-bar" role="progressbar" style="width:0%;" id="topZoneProgress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="text-muted small mt-3" id="topZoneHint">
              Basé sur la période sélectionnée.
            </div>
          </div>
        </div>
      </div>
    @endif

  </div>

  @if($isAdmin)
    <!-- ✅ NEW: Top Partenaires (Top 5) sous les charts admin -->
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card card-analytics">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">Top partenaires</h5>
            <span class="badge bg-label-secondary">Top 5 (période)</span>
          </div>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead>
              <tr>
                <th>Partenaire</th>
                <th class="text-end">Montant net (XOF)</th>
              </tr>
              </thead>
              <tbody id="top-partners-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Table ADMIN (last 5 fees) -->
    <div class="row g-4">
      <div class="col-12">
        <div class="card card-analytics">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">5 dernières lignes (Commissions & Frais services)</h5>
            <span class="badge bg-label-secondary">Top 5 récents</span>
          </div>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead>
              <tr>
                <th>Référence</th>
                <th>Type</th>
                <th>Montant (XOF)</th>
                <th>Date</th>
              </tr>
              </thead>
              <tbody id="admin-fees-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- Bottom Row -->
  <div class="row g-4">
    @if(!$isAdmin)
      <div class="col-xl-8">
        <div class="card card-analytics">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">Dernières activités</h5>
            <span class="badge bg-label-secondary">Top 5 récents</span>
          </div>
          <div class="table-responsive">
            <table class="table table-modern">
              <thead>
              <tr>
                <th>Référence / Routeur</th>
                <th>Source</th>
                <th>Net (XOF)</th>
                <th>Date</th>
              </tr>
              </thead>
              <tbody id="tx-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card card-analytics h-100">
          <div class="card-header">
            <h5 class="card-title mb-0 fw-bold">Ventes par Routeur</h5>
            <small class="text-muted">Top performance</small>
          </div>
          <div class="card-body">
            <div id="routerPerformanceChart"></div>
            <div id="routerPerfList" class="mt-4"></div>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
<script>
document.addEventListener('DOMContentLoaded', function() {
  const isAdmin = @json($isAdmin);
  const formatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' });
  const withdrawalsUrl = @json(url('/admin/withdrawals'));
  let chartEvolution, chartSource, chartRouter, chartFeesTrend;

  window.loadStats = async () => {
    const params = new URLSearchParams();

    params.append('period', document.getElementById('evolutionPeriod')?.value || 'month');

    if (isAdmin) {
      params.append('trend_period', document.getElementById('feesTrendPeriod')?.value || 'week');
    } else {
      params.append('router_id', document.getElementById('routerFilterGlobal').value);
      params.append('sale_type', document.getElementById('saleTypeFilterGlobal').value);
    }

    try {
      const res = await fetch(`{{ route('dashboard.stats') }}?${params}`);
      const data = await res.json();
      updateUI(data);
    } catch (e) { console.error("Erreur stats:", e); }
  };

  function updateUI(data) {
    // 1. KPIs
    const kContainer = document.getElementById('kpi-container');
    const k = data.kpis;

    let kpis = isAdmin ? [
      { label: 'Revenu Réseau', val: formatter.format(k.revenue), icon: 'tabler-coin', color: 'primary' },
      { label: 'Utilisateurs', val: k.users, icon: 'tabler-users', color: 'info' },
      { label: 'Routeurs', val: k.routers_total ?? 0, icon: 'tabler-router', color: 'info' },
      { label: 'Forfaits souscrits', val: k.subscriptions_count ?? 0, icon: 'tabler-crown', color: 'success' },

      { label: 'Fees Plateforme', val: formatter.format(k.fees_total ?? 0), icon: 'tabler-receipt-tax', color: 'warning' },
      { label: 'Commissions ventes', val: formatter.format(k.commission_total ?? 0), icon: 'tabler-percentage', color: 'primary' },
      { label: 'Frais retraits (2.5%)', val: formatter.format(k.withdraw_provider_fee ?? 0), icon: 'tabler-wallet', color: 'info' },
      { label: 'Total retraits (montant)', val: formatter.format(k.withdrawals_total_amount ?? 0), icon: 'tabler-cash', color: 'warning' },

      { label: 'En ligne', val: k.vouchers_online, icon: 'tabler-broadcast', color: 'success' },

      { label: 'Retraits (pending)', val: k.pending_withdrawals, icon: 'tabler-hourglass', color: 'danger', href: withdrawalsUrl }
    ] : [
      { label: 'Ventes Nettes', val: formatter.format(k.total_sales), icon: 'tabler-chart-line', color: 'primary' },
      { label: 'Vouchers Neufs', val: k.vouchers_unused, icon: 'tabler-package', color: 'info' },
      { label: 'Utilisés', val: k.vouchers_used, icon: 'tabler-ticket', color: 'success' },
      { label: 'En Ligne', val: k.vouchers_online, icon: 'tabler-wifi', color: 'warning' }
    ];

    kContainer.innerHTML = kpis.map(x => {
      const cardInner = `
        <div class="card card-analytics p-3">
          <div class="d-flex align-items-center">
            <div class="icon-wrapper bg-light-${x.color} me-3"><i class="ti ${x.icon}"></i></div>
            <div>
              <div class="kpi-label">${x.label}</div>
              <div class="kpi-value">${x.val}</div>
            </div>
          </div>
        </div>
      `;

      return `
        <div class="col-6 col-md-3">
          ${isAdmin && x.href
            ? `<a href="${x.href}" class="text-decoration-none d-block">${cardInner}</a>`
            : cardInner
          }
        </div>
      `;
    }).join('');

    // 2. Evolution Chart
    const evol = isAdmin ? data.charts.revenue : data.charts.sales_evolution;
    if(chartEvolution) chartEvolution.destroy();
    chartEvolution = new ApexCharts(document.querySelector("#evolutionChart"), {
      series: [{ name: 'Revenu', data: evol.series }],
      chart: { type: 'area', height: 350, toolbar: { show: false } },
      colors: ['#7367f0'],
      stroke: { curve: 'smooth', width: 2 },
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 } },
      xaxis: { categories: evol.labels },
      yaxis: { labels: { formatter: v => v >= 1000 ? (v/1000).toFixed(1) + 'k' : v } }
    });
    chartEvolution.render();

    if (isAdmin) {
      // 3. Fees Trend Chart (admin)
      const ft = data.charts.fees_trend || { labels: [], series: [] };
      if (chartFeesTrend) chartFeesTrend.destroy();
      chartFeesTrend = new ApexCharts(document.querySelector("#feesTrendChart"), {
        series: [{ name: 'Fees Plateforme', data: ft.series }],
        chart: { type: 'bar', height: 350, toolbar: { show: false } },
        colors: ['#7367f0'],
        plotOptions: { bar: { borderRadius: 10, columnWidth: '45%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: ft.labels },
        yaxis: { labels: { formatter: v => v >= 1000 ? (v/1000).toFixed(1) + 'k' : v } }
      });
      chartFeesTrend.render();

      // ✅ NEW: Table top partenaires
      const topBody = document.getElementById('top-partners-tbody');
      const topPartners = data.top_partners || [];
      topBody.innerHTML = topPartners.length
        ? topPartners.map(p => `
          <tr>
            <td><div class="fw-semibold text-primary">${p.partner}</div></td>
            <td class="text-end fw-bold">${formatter.format(p.amount)}</td>
          </tr>
        `).join('')
        : `<tr><td colspan="2" class="text-center text-muted py-4">Aucune donnée sur la période.</td></tr>`;

      // 4. Table admin: last 5 fees
      const tbody = document.getElementById('admin-fees-tbody');
      const fees = data.latest_fees || [];
      tbody.innerHTML = fees.map(t => `
        <tr>
          <td><div class="fw-bold text-primary">${t.reference}</div></td>
          <td>
            <span class="badge ${t.type === 'commission' ? 'bg-label-info' : 'bg-label-warning'}">
              ${t.type === 'commission' ? 'Commission' : 'Frais service'}
            </span>
          </td>
          <td class="fw-bold">${formatter.format(t.amount)}</td>
          <td class="small text-muted">${t.transacted_at}</td>
        </tr>
      `).join('');

      return;
    }

    if(!isAdmin) {
      // 3. Source Distribution
      const sType = data.charts.sales_type_evolution.series;
      const mTot = sType.find(s => s.name === 'Manuel')?.data.reduce((a,b)=>a+b,0) || 0;
      const oTot = sType.find(s => s.name === 'En ligne')?.data.reduce((a,b)=>a+b,0) || 0;
      if(chartSource) chartSource.destroy();
      chartSource = new ApexCharts(document.querySelector("#sourceDistributionChart"), {
        series: [mTot, oTot],
        labels: ['Manuel', 'En ligne'],
        chart: { type: 'donut', height: 250 },
        colors: ['#7367f0', '#00cfe8'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '75%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => formatter.format(mTot+oTot) } } } } }
      });
      chartSource.render();

      // ✅ NEW (USER): Top Zone widget
      const tz = (data.widgets && data.widgets.top_zone) ? data.widgets.top_zone : null;
      const nameEl = document.getElementById('topZoneName');
      const amountEl = document.getElementById('topZoneAmount');
      const shareEl = document.getElementById('topZoneShare');
      const progEl = document.getElementById('topZoneProgress');
      const hintEl = document.getElementById('topZoneHint');

      if (nameEl && amountEl && shareEl && progEl) {
        const tzName = tz?.name ?? '—';
        const tzAmt = Number(tz?.amount ?? 0);
        const tzShare = Number(tz?.share ?? 0);

        nameEl.textContent = tzName;
        amountEl.textContent = formatter.format(tzAmt);
        shareEl.textContent = `${tzShare}%`;

        progEl.style.width = `${Math.max(0, Math.min(100, tzShare))}%`;
        progEl.setAttribute('aria-valuenow', `${Math.max(0, Math.min(100, tzShare))}`);

        if (hintEl) {
          hintEl.textContent = 'Basé sur la période sélectionnée.';
        }
      }

      // 4. Router Performance
      const rPerf = data.charts.router_performance;
      if(chartRouter) chartRouter.destroy();
      chartRouter = new ApexCharts(document.querySelector("#routerPerformanceChart"), {
        series: rPerf.series,
        labels: rPerf.labels,
        chart: { type: 'pie', height: 200 },
        colors: ['#7367f0', '#28c76f', '#ff9f43', '#00cfe8', '#ea5455'],
        legend: { show: false },
        dataLabels: { enabled: false }
      });
      chartRouter.render();

      document.getElementById('routerPerfList').innerHTML = rPerf.labels.map((l, i) => `
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted"><i class="ti tabler-router me-1"></i> ${l}</span>
          <span class="fw-bold">${formatter.format(rPerf.series[i])}</span>
        </div>
      `).join('');

      // 5. Table (Top 5)
      document.getElementById('tx-tbody').innerHTML = data.latest_transactions.map(t => `
        <tr>
          <td><div class="fw-bold text-primary">${t.reference}</div><small class="text-muted">${t.router_name}</small></td>
          <td><span class="badge ${t.sale_type.includes('online') ? 'bg-label-info' : 'bg-label-primary'}">${t.sale_type.replace('_',' ')}</span></td>
          <td class="fw-bold text-success">${formatter.format(t.net_amount)}</td>
          <td class="small text-muted">${t.transacted_at}</td>
        </tr>
      `).join('');
    }
  }

  loadStats();
});
</script>
@endsection