@extends('layouts/layoutMaster')
@section('title', 'Profil Utilisateur')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js',
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  'resources/assets/vendor/libs/moment/moment.js',
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/pages-account-settings-account.js',
  'resources/assets/js/pages-account-settings-security.js',
  'resources/assets/js/app-invoice-list.js',
  'resources/assets/js/modal-edit-cc.js'
])
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Utilisateur /</span> Profil
</h4>

<div class="row">
  <div class="col-md-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'account' ? 'active' : '' }}" href="{{ route('user.profile', 'account') }}"><i class="icon-base ti ti-users icon-sm me-1_5"></i> Compte</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'securite' ? 'active' : '' }}" href="{{ route('user.profile', 'security') }}"><i class="icon-base ti ti-lock icon-sm me-1_5"></i> Sécurité</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'billing' ? 'active' : '' }}" href="{{ route('user.profile', 'billing') }}"><i class="icon-base ti ti-bookmark icon-sm me-1_5"></i> Facturation & Forfaits</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'notifications' ? 'active' : '' }}" href="{{ route('user.profile', 'notifications') }}"><i class="icon-base ti ti-bell icon-sm me-1_5"></i> Notifications</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'connections' ? 'active' : '' }}" href="{{ route('user.profile', 'connections') }}"><i class="icon-base ti ti-link icon-sm me-1_5"></i> Connexions</a>
        </li>
      </ul>
    </div>

    @include('content.user.profile._' . $tab)

  </div>
</div>
@endsection
