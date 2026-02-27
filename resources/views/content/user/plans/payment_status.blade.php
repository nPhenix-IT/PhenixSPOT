@extends('layouts/layoutMaster')
@section('title', 'Statut du paiement')

@section('content')
<div class="row justify-content-center py-5">
  <div class="col-lg-7">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4 p-md-5 text-center">
        @php
          $isSuccess = $status === 'success';
          $isPending = $status === 'pending';
        @endphp

        <div class="mb-3">
          @if($isSuccess)
            <i class="ti tabler-circle-check text-success" style="font-size:3rem;"></i>
          @elseif($isPending)
            <i class="ti tabler-loader text-warning" style="font-size:3rem;"></i>
          @else
            <i class="ti tabler-circle-x text-danger" style="font-size:3rem;"></i>
          @endif
        </div>

        <h3 class="mb-2">
          @if($isSuccess)
            Abonnement activé ✅
          @elseif($isPending)
            Traitement en cours ⏳
          @else
            Échec de confirmation ❌
          @endif
        </h3>

        <p class="text-muted mb-4">{{ $message }}</p>

        @if($isPending && !empty($transactionId))
          <button id="verify-payment-btn" class="btn btn-primary" data-transaction-id="{{ $transactionId }}" data-verify-url="{{ route('user.plans.payment-verify') }}">
            Vérifier manuellement le paiement
          </button>
          <div class="mt-2 small text-muted">Transaction: {{ $transactionId }}</div>
        @endif

        <div class="mt-4 d-flex justify-content-center gap-2">
          <a href="{{ route('user.plans.index') }}" class="btn btn-label-secondary">Retour aux plans</a>
          <a href="{{ route('dashboard') }}" class="btn btn-success">Aller au dashboard</a>
        </div>
      </div>
    </div>
  </div>
</div>

@if($status === 'pending' && !empty($transactionId))
<script>
(function () {
  const btn = document.getElementById('verify-payment-btn');
  if (!btn) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const swal = window.Swal;

  const toast = (icon, title) => {
    if (!swal) return;
    swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:3200,icon,title});
  };

  btn.addEventListener('click', async () => {
    btn.disabled = true;
    try {
      const fd = new FormData();
      fd.append('transaction_id', btn.dataset.transactionId || '');

      const res = await fetch(btn.dataset.verifyUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: fd,
      });

      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || 'Erreur de vérification.');

      if (data.status === 'success') {
        toast('success', data.message || 'Paiement confirmé.');
        window.location.href = data.redirect || '{{ route('dashboard') }}';
        return;
      }

      toast('info', data.message || 'Paiement en cours.');
    } catch (e) {
      const msg = String(e.message || e);
      if (swal) swal.fire({icon:'error', title:'Erreur', text: msg});
      toast('error', msg);
    } finally {
      btn.disabled = false;
    }
  });
})();
</script>
@endif
@endsection
