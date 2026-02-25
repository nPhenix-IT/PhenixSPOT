@php
$configData = Helper::appClasses();
$customizerHidden = true;
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Acheter un forfait WiFi')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/css/intlTelInput.css">
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
    text-align: left;
    font-size: 0.95rem;
    font-weight: 800;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #0f172a;
  }

  .pricing-accordion {
    margin-top: 16px;
  }

  .pricing-accordion .accordion-item {
    border: 1px solid #e2e8f0;
    border-radius: 14px !important;
    overflow: hidden;
    margin-bottom: 12px;
  }

  .pricing-accordion .accordion-button {
    font-weight: 700;
    background: #f8fafc;
    color: #0f172a;
  }

  .pricing-accordion .accordion-button:not(.collapsed) {
    background: #eff6ff;
    color: #1d4ed8;
    box-shadow: none;
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
  
  .selection-summary {
    display: none;
    margin-top: 16px;
    border: 1px solid #dbeafe;
    background: #f8fbff;
    border-radius: 16px;
    padding: 14px;
  }

  .selection-summary.is-visible {
    display: block;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 0.92rem;
    margin-bottom: 6px;
  }

  .summary-total {
    font-weight: 800;
    font-size: 1rem;
    color: #0f172a;
    border-top: 1px dashed #bfdbfe;
    padding-top: 8px;
    margin-top: 4px;
  }

  .summary-note {
    margin-top: 8px;
    color: #475569;
    font-size: 0.8rem;
  }

  .purchase-actions {
    display: none;
  }

  .purchase-actions.is-visible {
    display: block;
  }

  .purchase-actions-centered {
    display: none;
    justify-content: center;
  }

  .purchase-actions-centered.is-visible {
    display: flex;
    justify-content: center;
  }
  
  .iti {
    width: 100%;
  }

  .iti__country-list {
    z-index: 2000;
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
          @if(session('error'))
            <div class="alert alert-danger text-center mt-4">{{ session('error') }}</div>
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
              @endphp

              <div class="accordion pricing-accordion" id="passAccordion">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingHours">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHours" aria-expanded="true" aria-controls="collapseHours">
                      <span class="pricing-title">PASS HEURES</span>
                    </button>
                  </h2>
                  <div id="collapseHours" class="accordion-collapse collapse show" aria-labelledby="headingHours" data-bs-parent="#passAccordion">
                    <div class="accordion-body">
                      @if($hourProfiles->isNotEmpty())
                        <div class="pricing-area">
                          @foreach($hourProfiles as $index => $profile)
                            @php
                              $commissionAmount = round(($profile->price * $commissionPercent) / 100, 2);
                              $displayPrice = $commissionPayer === 'client'
                                ? $profile->price + $commissionAmount
                                : $profile->price;
                              $badgeClass = $badgeClasses[$index % count($badgeClasses)];
                            @endphp
                            @include('content.public.partials.sale_profile_badge', [
                              'profile' => $profile,
                              'badgeClass' => $badgeClass,
                              'commissionAmount' => $commissionAmount,
                              'commissionPayer' => $commissionPayer,
                              'displayPrice' => $displayPrice,
                            ])
                          @endforeach
                        </div>
                      @else
                        <div class="text-muted">Aucun pass heure disponible.</div>
                      @endif
                    </div>
                  </div>
                </div>

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingData">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseData" aria-expanded="false" aria-controls="collapseData">
                      <span class="pricing-title">PASS DATA</span>
                    </button>
                  </h2>
                  <div id="collapseData" class="accordion-collapse collapse" aria-labelledby="headingData" data-bs-parent="#passAccordion">
                    <div class="accordion-body">
                      @if($dataProfiles->isNotEmpty())
                        <div class="pricing-area">
                          @foreach($dataProfiles as $index => $profile)
                            @php
                              $commissionAmount = round(($profile->price * $commissionPercent) / 100, 2);
                              $displayPrice = $commissionPayer === 'client'
                                ? $profile->price + $commissionAmount
                                : $profile->price;
                              $badgeClass = $badgeClasses[($hourProfiles->count() + $index) % count($badgeClasses)];
                            @endphp
                            @include('content.public.partials.sale_profile_badge', [
                              'profile' => $profile,
                              'badgeClass' => $badgeClass,
                              'commissionAmount' => $commissionAmount,
                              'commissionPayer' => $commissionPayer,
                              'displayPrice' => $displayPrice,
                            ])
                          @endforeach
                        </div>
                      @else
                        <div class="text-muted">Aucun pass data disponible.</div>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            @else
              <div class="alert alert-warning mt-4">Aucun forfait disponible.</div>
            @endif
            
            @if($profiles->isNotEmpty())
            <div id="selectionSummary" class="selection-summary">
                <div class="summary-row"><span>Forfait</span><strong id="summaryPack">-</strong></div>
                <div class="summary-row"><span>Prix du code</span><strong id="summaryBase">0 FCFA</strong></div>
                <div class="summary-row"><span>Frais transaction</span><strong id="summaryFee">0 FCFA</strong></div>
                <div class="summary-row summary-total"><span>Total à payer</span><strong id="summaryTotal">0 FCFA</strong></div>
                <div class="summary-note">Paiement sécurisé Money Fusion. Votre code s'affiche immédiatement après confirmation.</div>
              </div>
              <div class="customer-card purchase-actions">
                <input type="hidden" name="customer_name" value="{{ $user->name }}">
                <label class="form-label">Votre numéro</label>
                <input type="tel" id="customer_number" name="customer_number" class="form-control" required>
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
    const loadScript = (src) => new Promise((resolve, reject) => {
      const existing = document.querySelector(`script[src="${src}"]`);
      if (existing) {
        if (window.intlTelInput) {
          resolve();
        } else {
          existing.addEventListener('load', resolve, { once: true });
          existing.addEventListener('error', reject, { once: true });
        }
        return;
      }

      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });

    const profileInputs = document.querySelectorAll('.price-input');
    const purchaseActions = document.querySelectorAll('.purchase-actions');
    const purchaseSubmitBtn = document.getElementById('purchaseSubmitBtn');
    const purchaseActionsCentered = document.querySelector('.purchase-actions-centered');
    const saleForm = document.querySelector('form[action*="/purchase"]');
    const customerNumberInput = document.getElementById('customer_number');
    let itiCustomerNumber = null;
    const selectionSummary = document.getElementById('selectionSummary');
    const summaryPack = document.getElementById('summaryPack');
    const summaryBase = document.getElementById('summaryBase');
    const summaryFee = document.getElementById('summaryFee');
    const summaryTotal = document.getElementById('summaryTotal');

    const formatFcfa = (value) => `${new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0)))} FCFA`;

    const updateSelectionSummary = () => {
      const selected = document.querySelector('.price-input:checked');
      if (!selectionSummary || !selected) {
        selectionSummary?.classList.remove('is-visible');
        return;
      }

      const profileName = selected.dataset.profileName || '-';
      const base = selected.dataset.basePrice;
      const fee = selected.dataset.fee;
      const total = selected.dataset.total;

      summaryPack.textContent = profileName;
      summaryBase.textContent = formatFcfa(base);
      summaryFee.textContent = formatFcfa(fee);
      summaryTotal.textContent = formatFcfa(total);
      selectionSummary.classList.add('is-visible');
    };

    const updateSubmitButtonText = () => {
      if (!purchaseSubmitBtn) return;
      const selected = document.querySelector('.price-input:checked');
      const isFree = selected ? selected.dataset.isFree === '1' : false;
      const total = selected ? selected.dataset.total : 0;
      purchaseSubmitBtn.textContent = isFree ? 'Obtenir mon code' : `Payer ${formatFcfa(total)}`;
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
      updateSelectionSummary();
      updateSubmitButtonText();
    };

    const scrollToPaymentActions = () => {
      const scrollTarget = purchaseActionsCentered || purchaseSubmitBtn;
      if (!scrollTarget) return;

      requestAnimationFrame(() => {
        scrollTarget.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      });
    };

    profileInputs.forEach(input => {
      input.addEventListener('change', togglePurchaseActions);
      input.addEventListener('click', togglePurchaseActions);
      input.addEventListener('change', scrollToPaymentActions);
      input.addEventListener('click', scrollToPaymentActions);
    });

    if (customerNumberInput) {
      loadScript('https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/intlTelInput.min.js')
        .then(() => {
          itiCustomerNumber = window.intlTelInput(customerNumberInput, {
            initialCountry: 'auto',
            preferredCountries: ['ci', 'sn', 'bf', 'ml', 'tg', 'bj', 'fr'],
            separateDialCode: true,
            geoIpLookup: function (callback) {
              fetch('https://ipapi.co/json/')
                .then(response => response.json())
                .then(data => callback((data && data.country_code ? data.country_code : 'CI').toLowerCase()))
                .catch(() => callback('ci'));
            },
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/utils.js'
          });
        })
        .catch(() => {
          // graceful fallback: user can still type the number manually
        });
    }

    if (saleForm) {
      saleForm.addEventListener('submit', () => {
        if (itiCustomerNumber) {
          customerNumberInput.value = itiCustomerNumber.getNumber();
        }
      });
    }

    togglePurchaseActions();
  });
</script>
@endsection