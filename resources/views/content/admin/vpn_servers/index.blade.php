@extends('layouts/layoutMaster')
@section('title', 'Gestion des Serveurs VPN')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-vpn-server-list.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Serveurs VPN</h4>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des serveurs CHR</h5>
        <button class="btn btn-primary" type="button" id="add-new-server-btn">
            <i class="icon-base ti tabler-plus me-1"></i> Ajouter un serveur
        </button>
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-vpn-servers table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Adresse IP</th>
                    <th>Utilisateur API</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addServerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Ajouter un Serveur VPN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addServerForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="server_id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Nom du serveur</label><input type="text" name="name" class="form-control" placeholder="CHR Principal - OVH" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Adresse IP Publique</label><input type="text" name="ip_address" class="form-control" placeholder="1.2.3.4" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Utilisateur API</label><input type="text" name="api_user" class="form-control" placeholder="api-user" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Mot de passe API</label><input type="password" name="api_password" class="form-control" placeholder="Laisser vide pour ne pas changer"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Port API</label><input type="number" name="api_port" class="form-control" value="8728" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Nom de domaine</label><input type="text" name="domain_name" class="form-control" placeholder="vpn.mondomaine.com"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Adresse IP Locale</label><input type="text" name="local_ip_address" class="form-control" placeholder="10.0.10.1"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Plage d'adresses IP</label><input type="text" name="ip_range" class="form-control" placeholder="10.0.10.2-10.0.10.254"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Nombre de comptes max</label><input type="number" name="account_limit" class="form-control" placeholder="100" required></div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active">
                        <label class="form-check-label" for="is_active">Définir comme serveur actif</label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <button type="button" class="btn btn-secondary test-connection-btn"><i class='ti ti-plug me-1'></i> Tester</button>
                        <span class="connection-status ms-2"></span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection