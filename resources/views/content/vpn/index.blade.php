@extends('layouts/layoutMaster')
@section('title', 'Gestion des Comptes VPN')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-vpn-list.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Services VPN /</span> Comptes VPN</h4>

@if(!$hasActiveSubscription)
<div class="alert alert-danger" role="alert">
    <h4 class="alert-heading">Abonnement Inactif !</h4>
    <p>Pour créer et gérer vos comptes VPN, veuillez souscrire à une de nos offres.</p>
    <hr><a href="{{ route('user.plans.index') }}" class="btn btn-danger">Voir les offres</a>
</div>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Utilisation de votre forfait</h5>
        <p>Vous avez créé <strong>{{ $vpnAccountCount }}</strong> sur les <strong>{{ $limit }}</strong> comptes VPN autorisés.</p>
        <div class="progress"><div class="progress-bar" style="width: {{ $limit > 0 ? ($vpnAccountCount / $limit) * 100 : 0 }}%;"></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste de vos comptes VPN</h5>
        <!--<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVpnAccountModal">-->
        <!--    <i class="ti ti-plus me-1"></i> Créer un compte-->
        <!--</button>-->
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-vpn-accounts table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Utilisateur</th>
                    <th>Serveur</th>
                    <th>IP Locale</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@else
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Utilisation de votre forfait</h5>
        <p>Vous avez créé <strong>{{ $vpnAccountCount }}</strong> sur les <strong>{{ $limit }}</strong> comptes VPN autorisés.</p>
        <div class="progress"><div class="progress-bar" style="width: {{ $limit > 0 ? ($vpnAccountCount / $limit) * 100 : 0 }}%;"></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste de vos comptes VPN</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVpnAccountModal">
            <i class="ti ti-plus me-1"></i> Créer un compte
        </button>
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-vpn-accounts table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Utilisateur</th>
                    <th>Serveur</th>
                    <th>IP Locale</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addVpnAccountModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Créer un nouveau compte VPN</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form id="addVpnAccountForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Serveur VPN</label>
                        <select name="vpn_server_id" class="form-select" required>
                            <option value="" selected disabled>Choisir un serveur</option>
                            @foreach($servers as $server)
                                <option value="{{ $server->id }}">{{ $server->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type de VPN</label>
                        <select name="vpn_type" class="form-select" required><option value="l2tp">L2TP</option></select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nom d'utilisateur</label><input type="text" name="username" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control" required></div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Redirection de ports (NAT)</label>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="forward_api" value="1" id="forward_api"><label class="form-check-label" for="forward_api">API</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="forward_winbox" value="1" id="forward_winbox"><label class="form-check-label" for="forward_winbox">Winbox</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="forward_web" value="1" id="forward_web"><label class="form-check-label" for="forward_web">Web</label></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
