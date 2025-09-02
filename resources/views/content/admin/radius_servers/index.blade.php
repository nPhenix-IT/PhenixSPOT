@extends('layouts/layoutMaster')
@section('title', 'Gestion des Serveurs RADIUS')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-radius-server-list.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Serveurs RADIUS</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des serveurs RADIUS</h5>
        <button class="btn btn-primary" type="button" id="add-new-server-btn">
            <i class="ti ti-plus me-1"></i> Ajouter un serveur
        </button>
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-radius-servers table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Adresse IP</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addServerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modal-title">Ajouter un Serveur RADIUS</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form id="addServerForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="server_id">
                    <div class="mb-3"><label class="form-label">Nom</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Adresse IP</label><input type="text" name="ip_address" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Secret RADIUS</label><input type="password" name="radius_secret" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked><label class="form-check-label" for="is_active">Actif</label></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection