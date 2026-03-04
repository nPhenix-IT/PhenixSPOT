@extends('layouts/layoutMaster')

@section('title', 'Permissions d\'accès')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-admin-access-permissions.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration / RBAC /</span> Permissions</h4>

<div class="card mb-4 border-0 shadow-sm bg-label-primary">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-1">Permissions métier</h5>
      <small>Gérez finement les autorisations assignables par rôle.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPermissionModal">Nouvelle permission</button>
  </div>
</div>

<div class="card">
  <div class="card-datatable table-responsive">
    <table class="table" id="permissionsTable">
      <thead>
      <tr>
        <th>Nom</th>
        <th>Assignée à</th>
        <th>Core</th>
        <th>Créée le</th>
        <th>Actions</th>
      </tr>
      </thead>
    </table>
  </div>
</div>

<div class="modal fade" id="addPermissionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Ajouter permission</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="addPermissionForm" action="{{ route('admin.access.permissions.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <label class="form-label">Nom permission</label>
          <input type="text" name="name" class="form-control" placeholder="users.impersonate" required>
          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="is_core" value="1" id="corePermission">
            <label class="form-check-label" for="corePermission">Set as core permission</label>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary" type="submit">Créer</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editPermissionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Modifier permission</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="editPermissionForm" method="POST">
        @csrf @method('PUT')
        <div class="modal-body">
          <div class="alert alert-warning">Modifier une permission peut impacter les accès.</div>
          <label class="form-label">Nom permission</label>
          <input type="text" name="name" class="form-control" required>
          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="is_core" value="1" id="editCorePermission">
            <label class="form-check-label" for="editCorePermission">Set as core permission</label>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Annuler</button><button class="btn btn-primary" type="submit">Mettre à jour</button></div>
      </form>
    </div>
  </div>
</div>
@endsection
