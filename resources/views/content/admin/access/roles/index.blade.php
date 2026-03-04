@extends('layouts/layoutMaster')

@section('title', 'Rôles d\'accès')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('page-style')
<style>
  .permission-panel {
    max-height: 340px;
    overflow: auto;
    background: #fff;
    border: 1px solid #eaecf0;
    border-radius: 0.75rem;
    padding: 0.75rem;
  }

  .permission-item {
    border: 1px solid #eaecf0;
    border-radius: 0.625rem;
    padding: 0.5rem 0.625rem;
    transition: all .2s ease;
    background: #fff;
  }

  .permission-item:hover {
    border-color: #c7d7fe;
    background: #f8faff;
  }

  .permission-toolbar {
    background: #f8fafc;
    border: 1px solid #eaecf0;
    border-radius: 0.75rem;
    padding: 0.65rem;
  }
</style>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-admin-access-roles.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration / RBAC /</span> Rôles</h4>

<div class="card mb-4 bg-label-info border-0 shadow-sm">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-1">Rôles & policies</h5>
      <small>Créez des rôles granulaires et assignez les permissions métier.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">Nouveau rôle</button>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table" id="rolesTable">
      <thead>
      <tr>
        <th>Rôle</th>
        <th>Utilisateurs</th>
        <th>Permissions</th>
        <th>Actions</th>
      </tr>
      </thead>
    </table>
  </div>
</div>

<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title mb-1">Créer un rôle</h5>
          <small class="text-muted">Définissez un rôle précis et sélectionnez ses permissions.</small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addRoleForm" action="{{ route('admin.access.roles.store') }}" method="POST">
        @csrf
        <div class="modal-body pt-3">
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label">Nom du rôle</label>
              <input class="form-control form-control-lg" name="name" placeholder="Ex: Support N1" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Permissions sélectionnées</label>
              <div class="form-control form-control-lg d-flex align-items-center">
                <span class="badge bg-label-primary" id="addRoleSelectedCount">0</span>
                <span class="ms-2 small text-muted">permission(s)</span>
              </div>
            </div>
          </div>

          <div class="permission-toolbar mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div class="input-group" style="max-width: 360px;">
              <span class="input-group-text"><i class="ti tabler-search"></i></span>
              <input type="text" class="form-control js-permission-search" data-target="add" placeholder="Rechercher une permission...">
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-label-primary js-select-all" data-target="add">Tout cocher</button>
              <button type="button" class="btn btn-sm btn-label-secondary js-clear-all" data-target="add">Tout décocher</button>
            </div>
          </div>

          <div class="permission-panel" id="permissionPanelAdd">
            <div class="row g-2" id="addRolePermissions">
              @foreach($permissions as $permission)
                <div class="col-md-4 permission-col" data-name="{{ strtolower($permission->name) }}">
                  <label class="form-check d-flex gap-2 align-items-center permission-item">
                    <input class="form-check-input js-role-permission" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-target="add">
                    <span class="small fw-medium">{{ $permission->name }}</span>
                  </label>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
          <button class="btn btn-primary" type="submit">Créer le rôle</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title mb-1">Modifier rôle</h5>
          <small class="text-muted">Ajustez les permissions et le périmètre de ce rôle.</small>
        </div>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editRoleForm" method="POST">
        @csrf @method('PUT')
        <div class="modal-body pt-3">
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label">Nom du rôle</label>
              <input class="form-control form-control-lg" name="name" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Permissions sélectionnées</label>
              <div class="form-control form-control-lg d-flex align-items-center">
                <span class="badge bg-label-primary" id="editRoleSelectedCount">0</span>
                <span class="ms-2 small text-muted">permission(s)</span>
              </div>
            </div>
          </div>

          <div class="permission-toolbar mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div class="input-group" style="max-width: 360px;">
              <span class="input-group-text"><i class="ti tabler-search"></i></span>
              <input type="text" class="form-control js-permission-search" data-target="edit" placeholder="Rechercher une permission...">
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-label-primary js-select-all" data-target="edit">Tout cocher</button>
              <button type="button" class="btn btn-sm btn-label-secondary js-clear-all" data-target="edit">Tout décocher</button>
            </div>
          </div>

          <div class="permission-panel" id="permissionPanelEdit">
            <div class="row g-2" id="editRolePermissions">
              @foreach($permissions as $permission)
                <div class="col-md-4 permission-col" data-name="{{ strtolower($permission->name) }}">
                  <label class="form-check d-flex gap-2 align-items-center permission-item">
                    <input class="form-check-input js-role-permission" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-target="edit">
                    <span class="small fw-medium">{{ $permission->name }}</span>
                  </label>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Annuler</button>
          <button class="btn btn-primary" type="submit">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
