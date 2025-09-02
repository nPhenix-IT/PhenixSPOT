@extends('layouts/layoutMaster')
@section('title', 'Gestion des Profils')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-profile-list.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Gestion Hotspot /</span> Mes Profils</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des profils</h5>
        <button type="button" class="btn btn-primary" id="add-new-profile-btn">
            <i class="icon-base ti tabler-plus me-1"></i> Ajouter un profil
        </button>
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-profiles table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Prix</th>
                    <th>Débit</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@include('content.profiles._add_modal')
@include('content.profiles._edit_modal')
@endsection

