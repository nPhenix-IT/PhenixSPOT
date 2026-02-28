@extends('layouts/layoutMaster')
@section('title', 'Forfaits & Tarifs SaaS')

@section('page-style')
@vite([
    'resources/assets/vendor/scss/pages/page-pricing.scss'
])
<style>
  .plan-card {
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    overflow: hidden;
    height: 100%;
  }

  .plan-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.4px;
  }

  .plan-starter { background: #dcfce7; color: #166534; }
  .plan-pro { background: #fef9c3; color: #854d0e; }
  .plan-isp { background: #dbeafe; color: #1e40af; }

  .plan-price {
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1;
  }

  .plan-list li {
    margin-bottom: 10px;
    font-size: 0.92rem;
  }

  .plan-list i {
    color: #16a34a;
    margin-right: 6px;
  }

  .plan-footer-note {
    color: #475569;
    font-size: 0.83rem;
  }

  .comparison-shell {
    margin-top: 28px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
  }

  .comparison-shell .table {
    margin-bottom: 0;
  }

  .comparison-shell thead th {
    background: #f8fafc;
    font-weight: 700;
    color: #0f172a;
    border-bottom: 1px solid #e2e8f0;
  }

  .comparison-feature {
    font-weight: 600;
    color: #334155;
    background: #fcfdff;
    white-space: nowrap;
  }

  .comparison-value {
    font-weight: 600;
    color: #0f172a;
  }

  .comparison-check {
    color: #16a34a;
    font-size: 1rem;
  }

  .comparison-x {
    color: #ef4444;
    font-size: 1rem;
  }
</style>
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-pricing.js'])
@endsection

@section('content')
@php
  use Illuminate\Support\Str;

  $orderedPlans = $plans->sortBy('price_monthly')->values();
  $labels = ['starter' => 'PLAN STARTER', 'pro' => 'PLAN PRO', 'isp' => 'PLAN ISP'];
  $classes = ['starter' => 'plan-starter', 'pro' => 'plan-pro', 'isp' => 'plan-isp'];

  $resolveTier = function ($plan, $index) {
      $slug = strtolower($plan->slug ?? '');
      $name = strtolower($plan->name ?? '');
      if (Str::contains($slug, 'starter') || Str::contains($name, 'starter')) return 'starter';
      if (Str::contains($slug, 'pro') || Str::contains($name, 'pro')) return 'pro';
      if (Str::contains($slug, 'isp') || Str::contains($name, 'isp')) return 'isp';
      return ['starter', 'pro', 'isp'][$index] ?? 'pro';
  };

  $formatLimit = function ($value) {
      if ($value === null) return '—';
      $stringValue = strtolower((string) $value);
      if (in_array($stringValue, ['-1', 'illimite', 'illimité', 'unlimited', 'infini', '∞'])) {
          return 'Illimité';
      }
      if (is_numeric($value) && (int) $value < 0) {
          return 'Illimité';
      }
      return is_numeric($value) ? number_format((int) $value, 0, ',', ' ') : (string) $value;
  };

  $planValueByTier = [];
  foreach ($orderedPlans as $index => $plan) {
      $planValueByTier[$resolveTier($plan, $index)] = $plan;
  }

  $comparisonRows = [
    ['label' => 'Prix mensuel', 'type' => 'price_monthly'],
    ['label' => 'Routeurs', 'feature' => 'routers'],
    ['label' => 'Comptes VPN', 'feature' => 'vpn_accounts'],
    ['label' => 'Utilisateurs actifs', 'feature' => 'active_users'],
    ['label' => 'PPPoE', 'feature' => 'pppoe', 'type' => 'bool'],
    ['label' => 'Hotspot', 'feature' => 'hotspot', 'type' => 'bool'],
    ['label' => 'Vouchers', 'feature' => 'vouchers', 'type' => 'bool'],
    ['label' => 'Page de vente publique', 'feature' => 'sales_page', 'type' => 'bool'],
    ['label' => 'Rapports avancés', 'feature' => 'advanced_reports', 'type' => 'bool'],
    ['label' => 'Support', 'feature' => 'support_level'],
  ];
@endphp

<div class="card">
    <div class="card-body p-4 p-md-5">
    <h3 class="text-center mb-2">Plans SaaS ISP</h3>
    <p class="text-center text-muted mb-4">Comptes VPN alignés sur le nombre de routeurs pour une croissance propre et scalable.</p>
    <div class="d-flex align-items-center justify-content-center gap-1 mb-4">
      <label class="switch switch-sm">
        <span class="switch-label text-body fs-6 fw-medium">Mensuel</span>
        <input type="checkbox" class="switch-input price-duration-toggler" />
        <span class="switch-toggle-slider"><span class="switch-on"></span><span class="switch-off"></span></span>
        <span class="switch-label text-body fs-6 fw-medium">Annuel</span>
      </label>
    </div>

    <div class="row g-4">
      @foreach($orderedPlans as $index => $plan)
        @php
          $tier = $resolveTier($plan, $index);
          $features = $plan->features ?? [];
          $routers = $formatLimit($features['routers'] ?? null);
          $vpnAccounts = $formatLimit($features['vpn_accounts'] ?? null);
          $activeUsers = $formatLimit($features['active_users'] ?? $features['users'] ?? null);
          $supportsPppoe = (bool) ($features['pppoe'] ?? false);
          $supportLevel = $features['support_level'] ?? ($tier === 'starter' ? 'Standard' : 'Prioritaire');
        @endphp
        <div class="col-lg-4">
          <div class="plan-card card shadow-none">
            <div class="card-body p-4 d-flex flex-column">
              <div class="text-center mb-3">
                <span class="plan-badge {{ $classes[$tier] ?? 'plan-pro' }}">{{ $labels[$tier] ?? strtoupper($plan->name) }}</span>
                <h4 class="mt-3 mb-1">{{ strtoupper($plan->name) }}</h4>
                <div class="d-flex justify-content-center align-items-end gap-1">
                  <span class="plan-price text-primary price-toggle" data-price-monthly="{{ $plan->price_monthly }}" data-price-annually="{{ $plan->price_annually }}">{{ number_format($plan->price_monthly, 0, ',', ' ') }}</span>
                  <span class="text-muted mb-1">FCFA <span class="pricing-duration">/mois</span></span>
                </div>
              </div>
              <ul class="list-unstyled plan-list flex-grow-1">
                <li><i class="icon-base ti tabler-check"></i><strong>{{ $routers }}</strong> routeur(s)</li>
                <li><i class="icon-base ti tabler-check"></i><strong>{{ $vpnAccounts }}</strong> compte(s) VPN</li>
                <li><i class="icon-base ti tabler-check"></i><strong>{{ $activeUsers }}</strong> utilisateurs actifs</li>
                <li><i class="icon-base ti tabler-check"></i>{{ $supportsPppoe ? 'PPPoE + Hotspot + Vouchers' : 'Hotspot + Vouchers' }}</li>
                <li><i class="icon-base ti tabler-check"></i>Page de vente publique</li>
                <li><i class="icon-base ti tabler-check"></i>Rapports avancés</li>
                <li><i class="icon-base ti tabler-check"></i>Support {{ strtolower($supportLevel) }}</li>
              </ul>
              <a href="{{ route('user.payment', ['plan' => $plan->id, 'duration' => 'monthly']) }}" class="btn btn-primary w-100 plan-choose-btn">Choisir ce plan</a>
            </div>
          </div>
        </div>
        @endforeach
    </div>

    <div class="alert alert-info mt-4 mb-0">
      <strong>Upgrade intelligent :</strong> routeur supplémentaire +3 000 FCFA/mois, compte VPN supplémentaire +500 FCFA/mois.
    </div>

    <div class="accordion mt-4" id="plansComparisonAccordion">
      <div class="accordion-item border rounded-3">
        <h2 class="accordion-header" id="headingComparison">
          <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseComparison" aria-expanded="true" aria-controls="collapseComparison">
            Tableau comparatif des forfaits
          </button>
        </h2>
        <div id="collapseComparison" class="accordion-collapse collapse show" aria-labelledby="headingComparison" data-bs-parent="#plansComparisonAccordion">
          <div class="accordion-body p-0">
            <div class="comparison-shell mt-0 border-0 rounded-0">
              <div class="table-responsive">
                <table class="table align-middle text-center">
                  <thead>
                    <tr>
                      <th class="text-start">Comparatif des plans</th>
                      <th>STARTER</th>
                      <th>PRO</th>
                      <th>ISP</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($comparisonRows as $row)
                      <tr>
                        <td class="comparison-feature text-start">{{ $row['label'] }}</td>
                        @foreach(['starter', 'pro', 'isp'] as $tier)
                          @php
                            $plan = $planValueByTier[$tier] ?? null;
                            $features = $plan?->features ?? [];
                            $type = $row['type'] ?? 'text';

                            if (!$plan) {
                              $display = '—';
                            } elseif ($type === 'price_monthly') {
                              $display = number_format((float) $plan->price_monthly, 0, ',', ' ') . ' FCFA';
                            } elseif ($type === 'bool') {
                              $display = !empty($features[$row['feature']]);
                            } else {
                              $display = $formatLimit($features[$row['feature']] ?? null);
                            }
                          @endphp

                          <td class="comparison-value">
                            @if(($row['type'] ?? null) === 'bool')
                              @if($display)
                                <i class="icon-base ti tabler-check comparison-check"></i>
                              @else
                                <i class="icon-base ti tabler-x comparison-x"></i>
                              @endif
                            @else
                              {{ $display }}
                            @endif
                          </td>
                        @endforeach
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-4">
        <div class="card shadow-none border">
          <div class="card-body">
            <h6 class="mb-1">Paiement sécurisé</h6>
            <small class="text-muted">Payez via Wallet interne ou MoneyFusion selon votre préférence.</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card shadow-none border">
          <div class="card-body">
            <h6 class="mb-1">Activation rapide</h6>
            <small class="text-muted">Après confirmation de paiement, votre plan est activé automatiquement.</small>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card shadow-none border">
          <div class="card-body">
            <h6 class="mb-1">Historique transparent</h6>
            <small class="text-muted">Toutes les transactions apparaissent dans votre wallet et vos notifications.</small>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
  </div>
@endsection