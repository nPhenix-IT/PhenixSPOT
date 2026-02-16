@extends('layouts/layoutMaster')

@section('title', 'Gestion des Routeurs')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/vendor/libs/@form-validation/form-validation.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-router-list.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Gestion /</span> Mes Routeurs</h4>

@if(!$hasActiveSubscription)
<div class="alert alert-danger" role="alert">
    <h4 class="alert-heading">Abonnement Inactif !</h4>
    <p>Pour ajouter et gérer vos routeurs, veuillez souscrire à une de nos offres.</p>
    <hr><a href="{{ route('user.plans.index') }}" class="btn btn-danger">Voir les offres</a>
</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Liste de mes routeurs</h5>
        @if($hasActiveSubscription)
        <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddRouter">
            <i class="icon-base ti tabler-plus me-sm-1"></i> <span class="d-none d-sm-inline-block"> Ajouter un Routeur</span>
        </button>
        @endif
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-routers table table-striped border-top">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Adresse Public RADIUS</th>
                    <th>Fabricant</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Offcanvas to add/edit router -->
<div class="offcanvas offcanvas-end" tabindex="-1"  data-bs-backdrop="static" id="offcanvasAddRouter" aria-labelledby="offcanvasAddRouterLabel">
    <div class="offcanvas-header">
      <h5 id="offcanvasAddRouterLabel" class="offcanvas-title">Ajouter un Routeur</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 pt-0 h-100">
      <form class="add-new-router-form pt-0" id="addNewRouterForm">
        <input type="hidden" name="id" id="router_id">
        <div class="mb-4">
          <h6 class="mb-3 text-primary">Configuration RADIUS</h6>
          <div class="mb-3"><label class="form-label">Nom du routeur</label><input type="text" class="form-control" name="name" /></div>
          <div class="mb-3"><label class="form-label">Adresse Public (AAA)</label><input type="text" class="form-control" name="ip_address" placeholder="60.20.11.1 ou vpn.phenixspot.com" /></div>
          <div class="mb-3">
              <label class="form-label">Fabricant</label>
              <select name="brand" class="form-select">
                  <option value="MikroTik">MikroTik</option>
                  <option value="TP-Link">TP-Link</option>
                  <option value="Ubiquiti">Ubiquiti</option>
                  <option value="Cisco">Cisco</option>
                  <option value="Autres">Autres</option>
              </select>
          </div>
        </div>

        <div class="mb-4 border-top pt-3">
          <h6 class="mb-3 text-primary">Configuration API</h6>
          <div class="mb-3"><label class="form-label">API Adresse</label><input type="text" class="form-control" name="api_address" placeholder="192.168.88.1 ou vpn.phenixspot.com" /></div>
          <div class="mb-3"><label class="form-label">API Port</label><input type="number" class="form-control" name="api_port" placeholder="8728" min="1" max="65535" /></div>
          <div class="mb-3"><label class="form-label">API User</label><input type="text" class="form-control" name="api_user" placeholder="admin" /></div>
          <div class="mb-3"><label class="form-label">API Password</label><input type="text" class="form-control" name="api_password" placeholder="admin" /></div>
          <button type="button" class="btn btn-outline-primary btn-sm" id="test-router-api-btn"><i class="icon-base ti tabler-plug-connected me-1"></i> Test rapide</button>
        </div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
        <button type="submit" class="btn btn-primary me-sm-3 me-1">Enregistrer</button>
        <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Annuler</button>
      </form>
    </div>
</div>

<!-- Modal de configuration -->
<div class="modal fade" id="installScriptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Script d'installation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#install-script-tab">Installer</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#connection-problems-tab">Problèmes de connexion ?</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="install-script-tab" role="tabpanel">
                        <div class="p-3">
                            <p class="mb-3">Copiez le script ci-dessous et collez-le dans le terminal de votre routeur.</p>
                            <div class="input-group">
                                <input type="text" id="script-content" class="form-control" readonly>
                                <button class="btn btn-outline-primary" type="button" id="copy-script-btn"><i class="icon-base ti tabler-copy"></i></button>
                            </div>
                            <div class="alert alert-primary mt-3" role="alert">
                                <h6 class="alert-heading mb-1">Instructions</h6>
                                <ol class="mb-0 ps-3">
                                    <li>Connectez-vous à votre routeur MikroTik via Winbox ou l'interface Web.</li>
                                    <li>Ouvrez la fenêtre du terminal (New Terminal).</li>
                                    <li>Collez le code fourni.</li>
                                    <li>Appuyez sur Entrée.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="connection-problems-tab" role="tabpanel">
                        <div class="p-3">
                            <p>Si votre routeur n'arrive pas à se connecter, vérifiez les points suivants :</p>
                            <ul>
                                <li>Assurez-vous que votre routeur a un accès à Internet.</li>
                                <li>Vérifiez qu'aucun pare-feu ne bloque les ports RADIUS (1812, 1813).</li>
                                <li>L'adresse IP de votre routeur doit être une IP publique accessible depuis notre serveur.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection
