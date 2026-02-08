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
              Retour à la page de connexion
            </a>
          @else
            <h2 class="sale-title">Paiement en cours de traitement</h2>
            <p class="sale-subtitle">Votre transaction est en cours de validation. Vous recevrez une confirmation sous peu.</p>
            <p class="mt-3 text-muted">Si votre code n'est pas généré après quelques minutes, veuillez contacter le vendeur.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
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