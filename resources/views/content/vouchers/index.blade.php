@extends('layouts/layoutMaster')
@section('title', 'Gérer les Vouchers')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/dracula.min.css" />
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/htmlmixed/htmlmixed.min.js"></script>
@endsection

@section('page-script')
@vite(['resources/assets/js/app-voucher-list.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Gestion Hotspot /</span> Gérer les Vouchers</h4>

@if(!$hasActiveSubscription)
<div class="alert alert-danger" role="alert">
    <h4 class="alert-heading">Abonnement Inactif !</h4>
    <p>Pour générer et imprimer de nouveaux coupons, veuillez souscrire à une de nos offres.</p>
    <hr><a href="{{ route('user.plans.index') }}" class="btn btn-danger">Voir les offres</a>
</div>
@else
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Utilisation de votre forfait</h5>
        <p>Vous avez créé <strong>{{ $vouchersCount }}</strong> sur les <strong>{{ $limit }}</strong> coupons autorisés.</p>
        <div class="progress"><div class="progress-bar" style="width: {{ $limit > 0 ? ($vouchersCount / $limit) * 100 : 0 }}%;"></div></div>
    </div>
</div>

<div class="card mb-4">
  <h5 class="card-header">Générer de nouveaux vouchers</h5>
  <div class="card-body">
    <form id="generate-vouchers-form" class="row g-3">
      <div class="col-sm-12 col-lg-3"><label class="form-label">Profil</label><select name="profile_id" class="form-select" required><option value="" selected disabled>Sélectionner...</option>@foreach ($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->name }}</option>@endforeach</select></div>
      <div class="col-md-6 col-lg-2"><label class="form-label">Quantité</label><input type="number" name="quantity" class="form-control" value="10" min="1" max="500" required></div>
      <div class="col-md-6 col-lg-2"><label class="form-label">Longueur</label><select name="length" class="form-select" required><option value="4">4</option><option value="6" selected>6</option><option value="8">8</option><option value="10">10</option></select></div>
      <div class="col-md-6 col-lg-3"><label class="form-label">Type de caractères</label><select name="charset" class="form-select" required><option value="A1B2C" selected>Alphanumérique Majuscule</option><option value="a1b2c">Alphanumérique Minuscule</option><option value="ABC">Lettres Majuscules</option><option value="abc">Lettres Minuscules</option></select></div>
      <div class="col-md-6 col-lg-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="icon-base ti tabler-bolt me-1"></i> Générer</button></div>
    </form>
  </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
      <h5 class="mb-0">Liste des vouchers</h5>
      <div class="d-flex gap-2 align-items-center">
        <select id="profile-filter" class="form-select form-select-sm">
            <option value="">Filtrer par profil</option>
            @foreach ($profiles as $profile)
                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
            @endforeach
        </select>
        <button type="button" class="btn btn-secondary" id="print-by-profile-btn" disabled><i class="icon-base ti tabler-printer me-1"></i> Imprimer</button>
        <button type="button" class="btn btn-info" id="edit-template-btn"><i class="icon-base ti tabler-edit me-1"></i> Template</button>
        <button type="button" class="btn btn-danger" id="delete-selected-btn" disabled><i class="icon-base ti tabler-trash me-1"></i> Supprimer</button>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-vouchers table table-striped">
        <thead class="table-light">
          <tr>
            <th><input class="form-check-input" type="checkbox" id="select-all-checkbox"></th>
            <th>Code</th>
            <th>Profil</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
      </table>
    </div>
</div>
@endif

@include('content.vouchers._modals')
@endsection