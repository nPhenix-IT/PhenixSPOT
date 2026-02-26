@extends('layouts/layoutMaster')
@section('title', 'Mon Portefeuille')
@section('content')
@php
  $countryCode = strtolower($countryCode ?? (auth()->user()->country_code ?? 'ci'));
@endphp
      
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Finance /</span> Wallet & Retraits</h4>

<div class="row g-4 mb-4">
  <div class="col-md-5">
    <div class="card border-primary">
      <div class="card-body">
        <small class="text-muted">Solde actuel</small>
        <h2 class="mb-1 text-primary">{{ number_format($wallet->balance, 0, ',', ' ') }} FCFA</h2>
        <small class="text-muted d-block mb-3">Pays: {{ strtoupper($countryCode) }} • Frais retrait: {{ number_format($withdrawFeePercent ?? 5, 2, ',', ' ') }}%</small>
        <img
          src="https://images.unsplash.com/photo-1579621970795-87facc2f976d?auto=format&fit=crop&w=1200&q=80"
          alt="Illustration portefeuille"
          class="img-fluid rounded-3 border mb-3"
          style="max-height: 180px; width: 100%; object-fit: cover;"
          loading="lazy"
        >
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#withdrawModal">Demander un retrait</button>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="row g-3">
      <div class="col-md-6"><div class="card h-100"><div class="card-body"><h6>Retraits par mois</h6><div id="withdrawApexChart" style="height:220px;"></div></div></div></div>
      <div class="col-md-6"><div class="card h-100"><div class="card-body"><h6>Revenus vs Retraits</h6><canvas id="incomeChart" height="220"></canvas></div></div></div>
    </div>
  </div>
</div>

<div class="row g-4 align-items-start">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Historique des retraits</h5>
        <input id="withdrawSearch" class="form-control form-control-sm w-auto" placeholder="Rechercher...">
      </div>
      <div class="table-responsive">
        <table class="table table-striped" id="withdrawTable">
          <thead><tr><th>Date</th><th>Montant</th><th>Frais</th><th>Total débité</th><th>Méthode</th><th>Statut</th><th>Commentaire</th></tr></thead>
          <tbody>
            @foreach($withdrawals as $withdrawal)
              @php
                $details = is_array($withdrawal->payment_details) ? $withdrawal->payment_details : [];
                $fee = (float) ($details['fee_amount'] ?? 0);
                $total = (float) ($details['total_debited'] ?? ((float) $withdrawal->amount + $fee));
                $label = $details['method_label'] ?? $withdrawal->withdraw_mode ?? 'N/A';
                $statusClass = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$withdrawal->status] ?? 'secondary';
              @endphp
              <tr>
                <td>{{ $withdrawal->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ number_format($withdrawal->amount, 0, ',', ' ') }}</td>
                <td>{{ number_format($fee, 0, ',', ' ') }}</td>
                <td>{{ number_format($total, 0, ',', ' ') }}</td>
                <td>{{ $label }}</td>
                <td><span class="badge bg-label-{{ $statusClass }}" data-status="{{ $withdrawal->status }}">{{ ucfirst($withdrawal->status) }}</span></td>
                <td class="rejection-reason">{{ $withdrawal->rejection_reason ?: '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted" id="withdrawPagerInfo"></small>
        <div class="btn-group btn-group-sm"><button class="btn btn-label-secondary" id="withdrawPrev">Précédent</button><button class="btn btn-label-secondary" id="withdrawNext">Suivant</button></div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Historique paiements entrants</h5>
        <input id="incomingSearch" class="form-control form-control-sm w-auto" placeholder="Rechercher...">
      </div>
      <div class="table-responsive">
        <table class="table table-striped" id="incomingTable">
          <thead><tr><th>Date</th><th>Description</th><th>Montant crédité</th></tr></thead>
          <tbody>
            @foreach($incomingTransactions as $transaction)
              <tr>
                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $transaction->description }}</td>
                <td><span class="badge bg-label-success">+{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted" id="incomingPagerInfo"></small>
        <div class="btn-group btn-group-sm"><button class="btn btn-label-secondary" id="incomingPrev">Précédent</button><button class="btn btn-label-secondary" id="incomingNext">Suivant</button></div>
      </div>
    </div>
  </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Nouvelle demande de retrait</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form action="{{ route('user.wallet.withdraw') }}" method="POST" id="withdrawForm">
        @csrf
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Montant à retirer (Minimum 5000 FCFA)</label><input type="number" name="amount" id="withdrawAmount" class="form-control" min="5000" max="{{ $wallet->balance }}" required></div>
          <div class="mb-3"><label class="form-label">Moyen de retrait</label><select name="payment_method" class="form-select" required>@foreach(($withdrawOptions ?? []) as $mode => $label)<option value="{{ $mode }}">{{ $label }} ({{ $mode }})</option>@endforeach</select></div>
          <div class="mb-3"><label class="form-label">Numéro mobile money</label><input type="text" name="phone_number" class="form-control" required></div>
          <div class="border rounded p-3 bg-light">
            <div class="d-flex justify-content-between"><span>Solde actuel</span><strong>{{ number_format($wallet->balance, 0, ',', ' ') }} FCFA</strong></div>
            <div class="d-flex justify-content-between"><span>Montant à retirer</span><strong id="calcRequested">0 FCFA</strong></div>
            <div class="d-flex justify-content-between"><span>Frais ({{ number_format($withdrawFeePercent ?? 5, 2, ',', ' ') }}%)</span><strong id="calcFee">0 FCFA</strong></div>
            <div class="d-flex justify-content-between border-top pt-2 mt-2"><span>Total débité</span><strong id="calcTotal">0 FCFA</strong></div>
            <div class="d-flex justify-content-between"><span>Solde restant</span><strong id="calcRemaining">{{ number_format($wallet->balance, 0, ',', ' ') }} FCFA</strong></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-primary">Soumettre la demande</button></div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
  const perPage = 5;
  const feePercent = Number(@json($withdrawFeePercent ?? 5));
  const currentBalance = Number(@json((float) ($wallet->balance ?? 0)));
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const chartData = @json($incomeVsWithdrawal ?? ['months'=>[], 'credits'=>[], 'withdrawals'=>[]]);
  const swal = window.Swal;

  const toast = (icon, title) => {
    if (!swal) return;
    swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:3500,icon,title});
  };

  @if(session('success'))
    toast('success', @json(session('success')));
  @endif
  @if(session('error'))
    toast('error', @json(session('error')));
  @endif

  const fmt = (n) => new Intl.NumberFormat('fr-FR').format(Math.max(0, Math.round(n || 0))) + ' FCFA';

  function paginateTable({tableId, searchId, prevId, nextId, infoId}) {
    const table = document.getElementById(tableId);
    const search = document.getElementById(searchId);
    const prev = document.getElementById(prevId);
    const next = document.getElementById(nextId);
    const info = document.getElementById(infoId);
    if (!table) return;

    let page = 1;
    let filtered = [];
    const rows = Array.from(table.querySelectorAll('tbody tr'));

    function computeFilter() {
      const q = (search?.value || '').toLowerCase();
      filtered = rows.filter(r => r.innerText.toLowerCase().includes(q));
      page = 1;
      render();
    }

    function render() {
      const total = filtered.length || 0;
      const maxPage = Math.max(1, Math.ceil(total / perPage));
      page = Math.min(page, maxPage);
      rows.forEach(r => r.style.display = 'none');
      filtered.slice((page - 1) * perPage, page * perPage).forEach(r => r.style.display = '');
      if (info) info.textContent = `Page ${page}/${maxPage} • ${total} ligne(s)`;
      if (prev) prev.disabled = page <= 1;
      if (next) next.disabled = page >= maxPage;
    }

    search?.addEventListener('input', computeFilter);
    prev?.addEventListener('click', () => { page--; render(); });
    next?.addEventListener('click', () => { page++; render(); });
    filtered = rows;
    render();
    return {refresh: computeFilter, rows};
  }

  const withdrawPager = paginateTable({tableId:'withdrawTable', searchId:'withdrawSearch', prevId:'withdrawPrev', nextId:'withdrawNext', infoId:'withdrawPagerInfo'});
  paginateTable({tableId:'incomingTable', searchId:'incomingSearch', prevId:'incomingPrev', nextId:'incomingNext', infoId:'incomingPagerInfo'});

  const amountInput = document.getElementById('withdrawAmount');
  function recalc() {
    const requested = Number(amountInput?.value || 0);
    const fee = Math.round((requested * feePercent) / 100);
    const total = requested + fee;
    const remaining = currentBalance - total;
    document.getElementById('calcRequested').textContent = fmt(requested);
    document.getElementById('calcFee').textContent = fmt(fee);
    document.getElementById('calcTotal').textContent = fmt(total);
    document.getElementById('calcRemaining').textContent = fmt(remaining);
    document.getElementById('calcRemaining').classList.toggle('text-danger', remaining < 0);
  }
  amountInput?.addEventListener('input', recalc);
  recalc();

  document.getElementById('withdrawForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.currentTarget;
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf || '',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        body: new FormData(form)
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Échec de soumission');

      const w = data.withdrawal;
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${w.created_at}</td><td>${new Intl.NumberFormat('fr-FR').format(w.amount)}</td><td>${new Intl.NumberFormat('fr-FR').format(w.fee_amount)}</td><td>${new Intl.NumberFormat('fr-FR').format(w.total_debited)}</td><td>${w.method_label}</td><td><span class="badge bg-label-warning" data-status="pending">Pending</span></td><td class="rejection-reason">-</td>`;
      const tbody = document.querySelector('#withdrawTable tbody');
      tbody.prepend(tr);
      withdrawPager?.rows.unshift(tr);
      withdrawPager?.refresh();

      const modalEl = document.getElementById('withdrawModal');
      const modal = window.bootstrap?.Modal?.getInstance(modalEl) || window.bootstrap?.Modal?.getOrCreateInstance(modalEl);
      modal?.hide();
      form.reset();
      recalc();

      if (swal) {
        await swal.fire({icon:'success', title:'Demande envoyée', text: 'Vous recevrez vos fonds dès validation par l\'administration.'});
      }
      toast('success', data.message || 'Demande enregistrée');
    } catch (err) {
      const message = String(err.message || err);
      if (swal) swal.fire({icon:'error', title:'Erreur', text:message});
      toast('error', message);
    } finally {
      submitBtn.disabled = false;
    }
  });

  if (window.ApexCharts && document.querySelector('#withdrawApexChart')) {
    const apex = new ApexCharts(document.querySelector('#withdrawApexChart'), {
      chart: {type:'line', height:220, toolbar:{show:false}},
      series: [{name:'Retraits', data: chartData.withdrawals || []}],
      xaxis: {categories: chartData.months || []},
      stroke: {curve:'smooth'},
      colors: ['#ff9f43']
    });
    apex.render();
  }

  if (window.Chart && document.getElementById('incomeChart')) {
    new Chart(document.getElementById('incomeChart'), {
      type: 'bar',
      data: {
        labels: chartData.months || [],
        datasets: [
          {label:'Entrants', data: chartData.credits || [], backgroundColor:'#28c76f'},
          {label:'Retraits', data: chartData.withdrawals || [], backgroundColor:'#ea5455'}
        ]
      },
      options: {responsive:true, plugins:{legend:{position:'bottom'}}}
    });
  }
})();
</script>
@endsection