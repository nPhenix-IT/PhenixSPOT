@extends('layouts/layoutMaster')
@section('title', 'Forfaits & Tarifs')

@section('page-style')
@vite([
    'resources/assets/vendor/scss/pages/page-pricing.scss',
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
])
@endsection

@section('page-script')
@vite([
    'resources/assets/js/pages-pricing.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
])
@endsection

@section('content')
<div class="card">
  <!-- Header -->
  <div class="pb-4 rounded-top">
    <div class="container py-12 px-xl-10 px-4">
      <h3 class="text-center mb-2 mt-4">Nos Forfaits</h3>
      <p class="text-center mb-0">Choisissez le forfait qui correspond le mieux à vos besoins.</p>
      <div class="d-flex align-items-center justify-content-center flex-wrap gap-1 pt-9 pb-3 mb-50">
        <label class="switch switch-sm ms-sm-12 ps-sm-12 me-0">
          <span class="switch-label text-body fs-6 fw-medium">Mensuel</span>
          <input type="checkbox" class="switch-input price-duration-toggler" />
          <span class="switch-toggle-slider"><span class="switch-on"></span><span class="switch-off"></span></span>
          <span class="switch-label text-body fs-6 fw-medium">Annuel</span>
        </label>
      </div>

      <!-- Cartes des forfaits -->
      <div class="row mx-0 px-lg-12 gy-6">
        @foreach($plans as $plan)
        <div class="col-xl mb-md-0 mb-6">
          <div class="card border rounded shadow-none">
            <div class="card-body pt-12 px-5">
              <h4 class="card-title text-center text-capitalize mb-1">{{ $plan->name }}</h4>
              <p class="text-center mb-5">{{ $plan->description }}</p>
              <div class="text-center h-px-50">
                <div class="d-flex justify-content-center">
                  <sup class="h6 text-body pricing-currency mt-2 mb-0 me-1">FCFA</sup>
                  <h1 class="mb-0 text-primary price-toggle" data-price-monthly="{{ $plan->price_monthly }}" data-price-annually="{{ $plan->price_annually }}">{{ number_format($plan->price_monthly, 0, ',', ' ') }}</h1>
                  <sub class="h6 text-body pricing-duration mt-auto mb-1 ms-1">/mois</sub>
                </div>
              </div>
              <ul class="list-group ps-6 my-5 pt-9">
                @foreach($plan->features as $key => $value)
                  <li class="mb-4">
                    @if(is_bool($value))
                      @if($value) <span class="badge badge-center rounded-pill bg-label-success p-0 me-2"><i class="icon-base ti tabler-check"></i></span> @else <span class="badge badge-center rounded-pill bg-label-danger p-0 me-2"><i class="icon-base ti tabler-x"></i></span> @endif
                    @else
                      <strong>{{ $value }}</strong>
                    @endif
                    {{ ucfirst(str_replace('_', ' ', $key)) }}
                  </li>
                @endforeach
              </ul>
              <a href="{{ route('user.payment', ['plan' => $plan->id, 'duration' => 'monthly']) }}" class="btn btn-primary d-grid w-100 plan-choose-btn">Choisir ce forfait</a>
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
  <!-- /Header -->

  <!-- Tableau de comparaison -->
  <div class="table-responsive border-top">
    <table class="table text-center">
      <thead>
        <tr>
          <th scope="col"><span class="text-body-secondary">Fonctionnalités</span></th>
          @foreach($plans as $plan)
            <th scope="col">{{ $plan->name }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @php
          $allFeatures = ['routers' => 'Nombre de routeurs', 'vpn_accounts' => 'Comptes VPN', 'vouchers' => 'Vouchers/mois', 'coupon_model' => 'Modèle de coupon', 'sales_page' => 'Page de vente'];
        @endphp
        @foreach($allFeatures as $key => $label)
        <tr>
          <td class="text-start"><span class="text-body-secondary">{{ $label }}</span></td>
          @foreach($plans as $plan)
            <td>
              @if(isset($plan->features[$key]))
                @if(is_bool($plan->features[$key]))
                  @if($plan->features[$key])
                    <span class="badge badge-center rounded-pill w-px-20 h-px-20 bg-label-success p-0"><i class="icon-base ti tabler-check"></i></span>
                  @else
                    <span class="badge badge-center rounded-pill w-px-20 h-px-20 bg-label-danger p-0"><i class="icon-base ti tabler-x"></i></span>
                  @endif
                @else
                  {{ $plan->features[$key] }}
                @endif
              @else
                <span class="badge badge-center rounded-pill w-px-20 h-px-20 bg-label-secondary p-0"><i class="icon-base ti tabler-x"></i></span>
              @endif
            </td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection