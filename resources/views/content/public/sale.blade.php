@php
$configData = Helper::appClasses();
$customizerHidden = true;
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Acheter un forfait WiFi')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
<style>
  :root {
    --primary: {{ optional($settings)->primary_color ?? '#3b82f6' }};
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
  }

  .sale-title {
    font-size: clamp(1.5rem, 4vw, 2.1rem);
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
  }

  .pricing-title {
    margin-top: 26px;
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
  }

  .pricing-title::before,
  .pricing-title::after {
    content: '';
    width: 42px;
    height: 1px;
    background: rgba(15, 23, 42, 0.15);
  }

  .pricing-area {
    margin-top: 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
  }

  .pricing-area > div {
    flex: 0 0 calc(50% - 12px);
    min-width: 160px;
  }

  .price-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .price-badge {
    padding: 12px;
    border-radius: 16px;
    transition: all 0.3s;
    cursor: pointer;
    color: white;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.15);
    text-decoration: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
    border: 2px solid transparent;
    height: 100%;
  }

  .price-badge:hover {
    transform: translateY(-3px) scale(1.01);
  }

  .price-input:checked + .price-badge {
    border-color: rgba(255, 255, 255, 0.7);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
  }

  .badge-blue { background: linear-gradient(135deg, #60a5fa, #3b82f6); }
  .badge-purple { background: linear-gradient(135deg, #a78bfa, #8b5cf6); }
  .badge-pink { background: linear-gradient(135deg, #f472b6, #ec4899); }
  .badge-emerald { background: linear-gradient(135deg, #34d399, #10b981); }

  .badge-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
  }

  .badge-time { font-size: 11px; opacity: 0.9; text-transform: uppercase; font-weight: 700; letter-spacing: 0.3px; }
  .badge-val { font-size: 15px; font-weight: 800; }
  .badge-list {
    margin: 0;
    padding-left: 16px;
    font-size: 11px;
    opacity: 0.9;
    line-height: 1.4;
  }
  .badge-footer {
    margin-top: auto;
    display: flex;
    justify-content: flex-end;
  }

  .badge-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.22);
    border: 1px solid rgba(255, 255, 255, 0.35);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }

  .customer-card {
    background: #f8fafc;
    border-radius: 18px;
    padding: 16px;
    margin-top: 24px;
    max-width: 260px;
    margin-left: auto;
    margin-right: auto;
  }

  .purchase-actions {
    display: none;
  }

  .purchase-actions.is-visible {
    display: block;
  }

  .purchase-actions-centered {
    display: flex;
    justify-content: center;
  }

  .purchase-actions-centered.is-visible {
    display: flex;
    justify-content: center;
  }
</style>
@endsection

@section('content')
<div class="sale-shell">
  <div class="container">
      <div class="row justify-content-center">
          <div class="col-lg-7 col-md-9">
        <div class="sale-card">
          <div class="text-center">
            <h2 class="sale-title" style="color: {{ optional($settings)->primary_color ?? '#1f2937' }}">
              {{ optional($settings)->title ?: "Forfaits WiFi de {$user->name}" }}
            </h2>
            <p class="sale-subtitle">
              {{ optional($settings)->description ?: 'Sélectionnez un forfait pour vous connecter.' }}
            </p>
          </div>

          @if(session('success'))
            <div class="alert alert-success text-center mt-4"><h4>{{ session('success') }}</h4></div>
          @endif
          @if($errors->any())
            <div class="alert alert-danger mt-4">Veuillez corriger les erreurs ci-dessous.</div>
          @endif

          <form action="{{ route('public.sale.purchase', $user->slug) }}" method="POST" class="mt-4">
            @csrf
            <input type="hidden" name="login_url" value="{{ request('login_url') }}">
            <input type="hidden" name="router_id" value="{{ request('router_id') }}">
            @if($profiles->isNotEmpty())
              @php
                $commissionPayer = optional($settings)->commission_payer ?? 'seller';
                $badgeClasses = ['badge-blue', 'badge-purple', 'badge-pink', 'badge-emerald'];
                $renderProfiles = function ($items, $sectionTitle, $offset = 0) use ($commissionPayer, $commissionPercent, $badgeClasses) {
                  if ($items->isEmpty()) {
                    return;
                  }

                  echo '<div class="pricing-title">' . e($sectionTitle) . '</div>';
                  echo '<div class="pricing-area">';
                  foreach ($items as $index => $profile) {
                    $commissionAmount = round(($profile->price * $commissionPercent) / 100, 2);
                    $displayPrice = $commissionPayer === 'client'
                      ? $profile->price + $commissionAmount
                      : $profile->price;
                    $badgeClass = $badgeClasses[($offset + $index) % count($badgeClasses)];

                    echo view('content.public.partials.sale_profile_badge', [
                      'profile' => $profile,
                      'badgeClass' => $badgeClass,
                      'commissionAmount' => $commissionAmount,
                      'commissionPayer' => $commissionPayer,
                      'displayPrice' => $displayPrice,
                    ])->render();
                  }
                  echo '</div>';
                };
              @endphp

              @php $renderProfiles($hourProfiles, 'PASS HEURES', 0); @endphp
              @php $renderProfiles($dataProfiles, 'PASS DATA', $hourProfiles->count()); @endphp
            @else
              <div class="alert alert-warning mt-4">Aucun forfait disponible.</div>
            @endif
            
            @if($profiles->isNotEmpty())
              <div class="customer-card purchase-actions">
                <input type="hidden" name="customer_name" value="{{ $user->name }}">
                <label class="form-label">Votre numéro</label>
                <input type="text" name="customer_number" class="form-control" placeholder="0700000000" required>
              </div>
              <div class="mt-4 purchase-actions purchase-actions-centered">
                <button id="purchaseSubmitBtn" type="submit" class="btn btn-primary btn-lg"
                  style="background-color: {{ optional($settings)->primary_color ?? '#1f2937' }}; border-color: {{ optional($settings)->primary_color ?? '#1f2937' }};">
                  Payer avec Money Fusion
                </button>
              </div>
            @endif
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const profileInputs = document.querySelectorAll('.price-input');
    const purchaseActions = document.querySelectorAll('.purchase-actions');
    const purchaseSubmitBtn = document.getElementById('purchaseSubmitBtn');

    const updateSubmitButtonText = () => {
      if (!purchaseSubmitBtn) return;
      const selected = document.querySelector('.price-input:checked');
      const isFree = selected ? selected.dataset.isFree === '1' : false;
      purchaseSubmitBtn.textContent = isFree ? 'Obtenir mon code' : 'Payer avec Money Fusion';
    };

    const togglePurchaseActions = () => {
      const hasSelection = Array.from(profileInputs).some(input => input.checked);
      purchaseActions.forEach(section => {
        if (hasSelection) {
          section.classList.add('is-visible');
        } else {
          section.classList.remove('is-visible');
        }
      });
      updateSubmitButtonText();
    };

    profileInputs.forEach(input => {
      input.addEventListener('change', togglePurchaseActions);
      input.addEventListener('click', togglePurchaseActions);
    });

    togglePurchaseActions();
  });
</script>
@endsection