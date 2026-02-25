@extends('layouts/layoutMaster')
@section('title', 'Statut du Paiement')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
<style>
  :root {
    --primary: #3b82f6;
    --text: #0f172a;
    --text-muted: #6b7280;
  }

  .sale-shell {
    min-height: 100vh;
    padding: 32px 0;
    background: radial-gradient(circle at top, rgba(59, 130, 246, 0.12), transparent 55%);
  }

  .sale-card {
    background: white;
    border-radius: 24px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
    padding: 28px;
    text-align: center;
  }

  .sale-title {
    font-size: clamp(1.6rem, 4vw, 2.2rem);
    font-weight: 800;
    color: var(--text);
  }

  .sale-subtitle {
    color: #475569;
    margin: 14px auto 0;
    max-width: 560px;
    font-size: 0.98rem;
    line-height: 1.6;
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.15);
    padding: 10px 16px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
  }

  .voucher-chip {
    cursor: pointer;
    user-select: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .voucher-chip:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
  }
</style>
@endsection

@section('content')
@php($sellerPhoneSafe = $sellerPhone ?? null)
<div class="sale-shell">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="sale-card">
          @if (!empty($voucherCode))
            <h2 class="sale-title">Paiement confirmé ✅</h2>
            <p class="sale-subtitle">Voici votre code d'accès. Copiez-le et conservez-le précieusement.</p>
            <div class="d-inline-flex align-items-center gap-2 px-4 py-2 border rounded bg-light voucher-chip mt-4" id="voucherCode" data-code="{{ $voucherCode }}">
              <strong class="fs-4">{{ $voucherCode }}</strong>
            </div>
            <p class="mt-3 mb-4 text-muted">Cliquez sur le code pour le copier.</p>
            <a href="{{ $loginUrl }}" class="btn btn-primary">
              Me connecter automatiquement
            </a>
          @else
            <h2 class="sale-title">Paiement en cours de traitement</h2>
            <p class="sale-subtitle">Votre transaction est en cours de validation. Vous recevrez une confirmation sous peu.</p>
           <p class="mt-3 text-muted mb-3">Si votre code tarde à s'afficher, vous pouvez vérifier manuellement ou contacter le vendeur.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
              <button type="button" class="btn btn-outline-primary" id="manualCheckBtn">Vérifier manuellement</button>
            </div>
            <p class="mt-3 mb-0 text-muted">
              Contact vendeur:
              <a href="tel:{{ $sellerPhoneSafe ?? '' }}" class="fw-semibold">
                {{ $sellerPhoneSafe ?: 'Indisponible' }}
              </a>
            </p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  const isPending = @json($isPending ?? false);
  const transactionId = @json($transactionId ?? null);
  const manualCheckBtn = document.getElementById('manualCheckBtn');

  if (manualCheckBtn) {
    manualCheckBtn.addEventListener('click', () => {
      const url = new URL(window.location.href);
      if (transactionId) {
        url.searchParams.set('transaction_id', transactionId);
      }
      window.location.replace(url.toString());
    });
  }

  if (isPending && transactionId) {
    const refreshDelayMs = 5000;
    setTimeout(() => {
      const url = new URL(window.location.href);
      url.searchParams.set('transaction_id', transactionId);
      window.location.replace(url.toString());
    }, refreshDelayMs);
  }

  const voucherChip = document.getElementById('voucherCode');
  if (voucherChip) {
    voucherChip.addEventListener('click', async () => {
      const code = voucherChip.dataset.code;
      try {
        await navigator.clipboard.writeText(code);
        voucherChip.classList.add('bg-success', 'text-white');
        setTimeout(() => {
          voucherChip.classList.remove('bg-success', 'text-white');
        }, 1200);
      } catch (error) {
        const selection = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(voucherChip);
        selection.removeAllRanges();
        selection.addRange(range);
      }
    });
  }
</script>
@endsection
