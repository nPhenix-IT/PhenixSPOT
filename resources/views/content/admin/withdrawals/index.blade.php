@extends('layouts/layoutMaster')
@section('title', 'Pilotage des Retraits')
@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Retraits & Encaissements</h4>

<div class="row g-4 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Retraits approuvés (mois)</small><h4>{{ number_format($adminKpis['totalApprovedWithdrawal'] ?? 0, 0, ',', ' ') }} FCFA</h4></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Retraits cumulés (total débité)</small><h4>{{ number_format($adminKpis['totalWithdrawalDebitedAllUsers'] ?? 0, 0, ',', ' ') }} FCFA</h4></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Frais retraits cumulés</small><h4>{{ number_format($adminKpis['withdrawFeesTotal'] ?? 0, 0, ',', ' ') }} FCFA</h4></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Frais ventes cumulés</small><h4>{{ number_format($adminKpis['salesFeesTotal'] ?? 0, 0, ',', ' ') }} FCFA</h4></div></div></div>
</div>
<div class="row g-4 align-items-start">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Demandes de retrait</h5>
        <input id="adminWithdrawalSearch" class="form-control form-control-sm w-auto" placeholder="Rechercher...">
      </div>
      <div class="table-responsive">
        <table class="table table-striped" id="adminWithdrawalTable">
          <thead><tr><th>Utilisateur</th><th>Montant</th><th>Frais</th><th>Total</th><th>Méthode</th><th>Statut</th><th>Commentaire</th><th>Actions</th></tr></thead>
          <tbody>
          @foreach($requests as $request)
            @php
              $details = is_array($request->payment_details) ? $request->payment_details : [];
              $fee = (float) ($details['fee_amount'] ?? round(((float)$request->amount * ($withdrawalFeePercent ?? 5))/100,0));
              $total = (float) ($details['total_debited'] ?? ((float)$request->amount + $fee));
              $method = $request->withdraw_mode ?: ($details['withdraw_mode'] ?? $details['method'] ?? 'N/A');
              $phone = $request->phone_number ?: ($details['phone'] ?? 'N/A');
              $statusClass = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$request->status] ?? 'secondary';
            @endphp
            <tr data-withdrawal-id="{{ $request->id }}">
              <td><strong>{{ $request->user->name }}</strong><br><small>{{ $request->user->email }}</small><br><small>{{ strtoupper($request->country_code ?? 'ci') }} - {{ $phone }}</small></td>
              <td>{{ number_format($request->amount, 0, ',', ' ') }}</td>
              <td class="js-fee">{{ number_format($fee, 0, ',', ' ') }}</td>
              <td class="js-total"><strong>{{ number_format($total, 0, ',', ' ') }}</strong></td>
              <td>{{ $method }}</td>
              <td class="js-status"><span class="badge bg-label-{{ $statusClass }}">{{ ucfirst($request->status) }}</span></td>
              <td class="js-reason">{{ $request->rejection_reason ?: '-' }}</td>
              <td>
                @if($request->status === 'pending')
                  <button class="btn btn-sm btn-success mb-1 js-approve" data-id="{{ $request->id }}">Approuver</button>
                  <button class="btn btn-sm btn-danger js-reject" data-id="{{ $request->id }}">Rejeter</button>
                @else
                  Traitée
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted" id="adminWithdrawalPagerInfo"></small>
        <div class="btn-group btn-group-sm"><button class="btn btn-label-secondary" id="adminWithdrawalPrev">Précédent</button><button class="btn btn-label-secondary" id="adminWithdrawalNext">Suivant</button></div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Paiements entrants (frais 2%)</h5>
        <input id="adminIncomingSearch" class="form-control form-control-sm w-auto" placeholder="Rechercher...">
      </div>
      <div class="table-responsive">
        <table class="table table-striped" id="adminIncomingTable">
          <thead><tr><th>Date</th><th>Utilisateur</th><th>Transaction crédit</th><th>Frais (2%)</th></tr></thead>
          <tbody>
            @foreach($incomingTransactions as $tx)
              <tr>
                <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $tx->wallet?->user?->name ?? 'N/A' }}</td>
                <td>{{ number_format($tx->amount, 0, ',', ' ') }} FCFA</td>
                <td><span class="badge bg-label-info">{{ number_format($tx->platform_fee_amount ?? 0, 0, ',', ' ') }} FCFA</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted" id="adminIncomingPagerInfo"></small>
        <div class="btn-group btn-group-sm"><button class="btn btn-label-secondary" id="adminIncomingPrev">Précédent</button><button class="btn btn-label-secondary" id="adminIncomingNext">Suivant</button></div>
      </div>
    </div>
  </div>

<div class="row g-4 mt-1 mb-4">
  <div class="col-lg-6"><div class="card"><div class="card-body"><h6>Évolution retraits approuvés</h6><div id="adminApexChart" style="height:260px"></div></div></div></div>
  <div class="col-lg-6"><div class="card"><div class="card-body"><h6>Évolution retraits approuvés</h6><canvas id="adminChartJs" style="height:260px;"></canvas></div></div></div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const swal = window.Swal;
  const perPage = 7;
  const chartData = @json($approvedWithdrawalChart ?? ['months'=>[], 'approved'=>[]]);

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

  function paginateTable({tableId, searchId, prevId, nextId, infoId}) {
    const table = document.getElementById(tableId);
    const search = document.getElementById(searchId);
    const prev = document.getElementById(prevId);
    const next = document.getElementById(nextId);
    const info = document.getElementById(infoId);
    if (!table) return;

    let page = 1;
    const allRows = Array.from(table.querySelectorAll('tbody tr'));
    let filtered = allRows;

    function render() {
      const total = filtered.length;
      const maxPage = Math.max(1, Math.ceil(total / perPage));
      page = Math.min(page, maxPage);
      allRows.forEach(r => r.style.display = 'none');
      filtered.slice((page - 1) * perPage, page * perPage).forEach(r => r.style.display = '');
      if (info) info.textContent = `Page ${page}/${maxPage} • ${total} ligne(s)`;
      if (prev) prev.disabled = page <= 1;
      if (next) next.disabled = page >= maxPage;
    }

    search?.addEventListener('input', function(){
      const q = this.value.toLowerCase();
      filtered = allRows.filter(r => r.innerText.toLowerCase().includes(q));
      page = 1; render();
    });
    prev?.addEventListener('click', () => { page--; render(); });
    next?.addEventListener('click', () => { page++; render(); });
    render();
  }

  paginateTable({tableId:'adminWithdrawalTable', searchId:'adminWithdrawalSearch', prevId:'adminWithdrawalPrev', nextId:'adminWithdrawalNext', infoId:'adminWithdrawalPagerInfo'});
  paginateTable({tableId:'adminIncomingTable', searchId:'adminIncomingSearch', prevId:'adminIncomingPrev', nextId:'adminIncomingNext', infoId:'adminIncomingPagerInfo'});

  if (window.ApexCharts && document.querySelector('#adminApexChart')) {
    const apex = new ApexCharts(document.querySelector('#adminApexChart'), {
      chart:{type:'area',height:260,toolbar:{show:false}},
      series:[{name:'Retraits approuvés', data: chartData.approved || []}],
      xaxis:{categories: chartData.months || []},
      stroke:{curve:'smooth'},
      colors:['#7367f0']
    });
    apex.render();
  }

  if (window.Chart && document.getElementById('adminChartJs')) {
    new Chart(document.getElementById('adminChartJs'), {
      type:'line',
      data:{labels: chartData.months || [], datasets:[{label:'Retraits approuvés', data: chartData.approved || [], borderColor:'#28c76f', backgroundColor:'rgba(40,199,111,.2)', fill:true}]},
      options:{responsive:true, plugins:{legend:{position:'bottom'}}}
    });
  }

  async function postAjax(url, payload = {}) {
    const fd = new FormData();
    Object.entries(payload).forEach(([k,v]) => fd.append(k,v));
    const res = await fetch(url, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
      body: fd,
    });
    const data = await res.json();
    if (!res.ok || !data.success) throw new Error(data.message || 'Erreur serveur');
    return data;
  }

  document.querySelectorAll('.js-approve').forEach(btn => {
    btn.addEventListener('click', async function(){
      const id = this.dataset.id;
      const tr = document.querySelector(`tr[data-withdrawal-id="${id}"]`);
      const totalText = tr?.querySelector('.js-total')?.innerText || '';
      const ok = swal ? await swal.fire({icon:'question', title:'Confirmer', text:`Approuver ce retrait ? Total débité: ${totalText}`, showCancelButton:true, confirmButtonText:'Oui, approuver'}) : {isConfirmed: true};
      if (!ok.isConfirmed) return;

      try {
        const data = await postAjax(`{{ url('admin/withdrawals') }}/${id}/approve`);
        tr.querySelector('.js-status').innerHTML = '<span class="badge bg-label-success">Approved</span>';
        tr.querySelector('td:last-child').innerText = 'Traitée';
        tr.querySelector('.js-fee').innerText = new Intl.NumberFormat('fr-FR').format(data.fee_amount || 0);
        tr.querySelector('.js-total').innerHTML = '<strong>' + new Intl.NumberFormat('fr-FR').format(data.total_debited || 0) + '</strong>';
        toast('success', data.message || 'Retrait approuvé');
      } catch (e) {
        toast('error', String(e.message||e));
      }
    });
  });

  document.querySelectorAll('.js-reject').forEach(btn => {
    btn.addEventListener('click', async function(){
      const id = this.dataset.id;
      const tr = document.querySelector(`tr[data-withdrawal-id="${id}"]`);
      let reason = '';
      if (swal) {
        const result = await swal.fire({
          icon:'warning',
          title:'Rejeter la demande',
          input:'textarea',
          inputLabel:'Raison du rejet',
          inputPlaceholder:'Expliquez la raison...',
          showCancelButton:true,
          confirmButtonText:'Confirmer le rejet',
          inputValidator:(value)=> !value || value.length < 3 ? 'La raison est obligatoire (min 3 caractères).' : undefined,
        });
        if (!result.isConfirmed) return;
        reason = result.value;
      }

      try {
        const data = await postAjax(`{{ url('admin/withdrawals') }}/${id}/reject`, {rejection_reason: reason});
        tr.querySelector('.js-status').innerHTML = '<span class="badge bg-label-danger">Rejected</span>';
        tr.querySelector('.js-reason').innerText = data.rejection_reason || reason;
        tr.querySelector('td:last-child').innerText = 'Traitée';
        toast('success', data.message || 'Retrait rejeté');
      } catch (e) {
        toast('error', String(e.message||e));
      }
    });
  });
})();
</script>
@endsection