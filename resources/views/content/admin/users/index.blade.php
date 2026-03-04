@extends('layouts/layoutMaster')

@section('title', 'Gestion des utilisateurs')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('page-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/css/intlTelInput.css">
<style>.iti{width:100%;}</style>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js'
])
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/intlTelInput.min.js"></script>
@endsection

@section('page-script')
@vite(['resources/assets/js/app-admin-users.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Utilisateurs</h4>

<div class="card mb-4 border-0 shadow-sm">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-1">Gestion des comptes</h5>
      <small class="text-muted">Liste, activation, plan et Login as centralisés.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddUser">
      <i class="ti tabler-user-plus me-1"></i> Ajouter un utilisateur
    </button>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table" id="usersTable">
      <thead>
      <tr>
        <th>ID</th>
        <th>Utilisateur</th>
        <th>Rôle</th>
        <th>Plan</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
      </thead>
    </table>
  </div>
</div>

<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ajouter un utilisateur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addUserForm" method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nom</label><input name="name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-md-4">
              <label class="form-label">Pays</label>
              <select class="form-select" name="country_code" id="add_country_code">
                @foreach($countries as $code => $label)
                  <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Contact</label><input id="add_phone_number" name="phone_number" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control" required></div>
            <div class="col-md-6">
              <label class="form-label">Rôle</label>
              <select name="role" class="form-select" required>
                @foreach($roles as $role)
                  <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Plan initial</label>
              <select name="plan_id" class="form-select">
                <option value="">Aucun</option>
                @foreach($plans as $plan)
                  <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-label-secondary" data-bs-dismiss="modal" type="button">Annuler</button>
          <button class="btn btn-primary" type="submit">Créer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier utilisateur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editUserForm" method="POST">
        @csrf @method('PUT')
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nom</label><input name="name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-md-4">
              <label class="form-label">Pays</label>
              <select class="form-select" name="country_code" id="edit_country_code">
                @foreach($countries as $code => $label)
                  <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4"><label class="form-label">Contact</label><input id="edit_phone_number" name="phone_number" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control" placeholder="Optionnel"></div>
            <div class="col-md-6">
              <label class="form-label">Rôle</label>
              <select name="role" class="form-select" required>
                @foreach($roles as $role)
                  <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Plan</label>
              <select name="plan_id" class="form-select">
                <option value="">Inchangé</option>
                @foreach($plans as $plan)
                  <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-label-secondary" data-bs-dismiss="modal" type="button">Annuler</button>
          <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAssignPlan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Assigner un plan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="assignPlanForm" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Plan</label>
            <select name="plan_id" class="form-select" required>
              @foreach($plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="form-label">Durée</label>
            <select name="duration" class="form-select"><option value="monthly">Mensuel</option><option value="annually">Annuel</option></select>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-label-secondary" data-bs-dismiss="modal" type="button">Annuler</button><button class="btn btn-success" type="submit">Assigner</button></div>
      </form>
    </div>
  </div>
</div>
@endsection
