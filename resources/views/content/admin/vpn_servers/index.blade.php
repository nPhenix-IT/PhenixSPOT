@extends('layouts/layoutMaster')

@section('title', 'Gestion Infrastructure VPN')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Infrastructure VPN</h4>
        <p class="text-muted mb-0">Gérez vos concentrateurs RouterOS et WireGuard</p>
    </div>

    <div class="d-flex gap-2">
        <button type="button" id="btnOpenRouterModal" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCreateRouterOs">
            <i class="ti tabler-router me-1"></i> + RouterOS
        </button>
        <button type="button" id="btnOpenWireguardModal" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateWireGuard">
            <i class="ti tabler-shield-lock me-1"></i> + WireGuard
        </button>
    </div>
</div>

{{-- Modale Ajout serveur MikroTik CHR (L2TP) --}}
<div class="modal fade" id="modalCreateRouterOs" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form id="formCreateRouterOs" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un serveur MikroTik CHR (L2TP)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="server_type" value="routeros">

                <h6 class="fw-semibold text-warning mb-3">🔶 Section : Connexion API</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Nom du Serveur</label>
                        <input type="text" name="name" class="form-control" placeholder="VPN-Abidjan-01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IP Publique (API)</label>
                        <input type="text" name="ip_address" class="form-control" placeholder="102.45.x.x" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Port API</label>
                        <input type="number" name="api_port" class="form-control" placeholder="8728" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">User API</label>
                        <input type="text" name="api_user" class="form-control" placeholder="admin" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pass API</label>
                        <input type="password" name="api_password" class="form-control" required>
                    </div>
                </div>

                <h6 class="fw-semibold text-success mb-3">🟢 Section : Configuration Réseau VPN</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du Profil PPP</label>
                        <input type="text" name="profile_name" class="form-control" placeholder="PPP-PHENIX" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gateway Locale (Routeur)</label>
                        <input type="text" name="gateway_ip" class="form-control" placeholder="10.10.10.1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Domaine</label>
                        <input type="text" name="domain_name" class="form-control" placeholder="vpn.phenixspot.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pool d'IP Clients</label>
                        <input type="text" name="ip_pool" class="form-control" placeholder="10.10.20.2-10.10.20.254" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profile</label>
                        <input type="text" class="form-control" value="L2TP" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Max Comptes</label>
                        <input type="number" name="max_accounts" class="form-control" placeholder="100" min="1">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Abidjan - Cocody">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="create_routeros_is_active" value="1" checked>
                            <label class="form-check-label" for="create_routeros_is_active">Statut actif</label>
                        </div>
                    </div>
                </div>

                <div id="createRouterAlert" class="mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

{{-- Modale Ajout serveur WireGuard (Ubuntu) --}}
<div class="modal fade" id="modalCreateWireGuard" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form id="formCreateWireGuard" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un serveur WireGuard (Ubuntu)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="server_type" value="wireguard">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du serveur</label>
                        <input type="text" name="name" class="form-control" placeholder="WG-Abidjan-01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">WG Endpoint: IP publique / Domaine</label>
                        <input type="text" name="wg_endpoint_address" class="form-control" placeholder="vpn.phenixspot.com" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Port WireGuard</label>
                        <input type="number" name="wg_endpoint_port" class="form-control" placeholder="51820" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Interface WireGuard</label>
                        <input type="text" name="wg_interface" class="form-control" placeholder="wg0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Max peers</label>
                        <input type="number" name="max_accounts" class="form-control" placeholder="1000" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Clé publique du serveur</label>
                        <input type="text" name="wg_server_public_key" class="form-control" required>
                        <small class="text-warning">⚠️ Obligatoire pour générer les clients</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Clé privée du serveur</label>
                        <input type="password" name="wg_server_private_key" class="form-control" required>
                        <small class="text-warning">⚠️ Stockée chiffrée</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subnet VPN</label>
                        <input type="text" name="wg_network" class="form-control" placeholder="10.66.0.0/16" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gateway VPN (IP serveur dans le tunnel)</label>
                        <input type="text" name="wg_server_address" class="form-control" placeholder="10.66.0.1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DNS par défaut</label>
                        <input type="text" name="wg_dns" class="form-control" placeholder="1.1.1.1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">MTU (optionnel)</label>
                        <input type="number" name="wg_mtu" class="form-control" placeholder="1420">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Persistent keepalive (optionnel)</label>
                        <input type="number" name="wg_persistent_keepalive" class="form-control" placeholder="25">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Abidjan Datacenter">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="create_wg_is_active" value="1" checked>
                            <label class="form-check-label" for="create_wg_is_active">Statut actif</label>
                        </div>
                    </div>
                </div>

                <div id="createWireguardAlert" class="mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<div class="row" id="vpnServersList">
    @forelse($servers as $server)
    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-{{ $server->server_type === 'wireguard' ? 'success' : 'primary' }}">
                            <i class="ti tabler-{{ $server->server_type === 'wireguard' ? 'shield' : 'server' }} fs-2"></i>
                        </span>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-icon btn-label-secondary btn-edit-server" data-id="{{ $server->id }}">
                            <i class="ti tabler-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-label-danger btn-delete-server" data-id="{{ $server->id }}">
                            <i class="ti tabler-trash"></i>
                        </button>
                    </div>
                </div>
                <h5 class="mb-1">{{ $server->name }}</h5>
                <p class="text-muted small mb-3">
                    <i class="ti tabler-world me-1"></i> {{ $server->ip_address ?: $server->wg_endpoint_address }}
                </p>

                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                    <span class="badge bg-label-secondary">{{ strtoupper($server->server_type) }}</span>
                    @if($server->is_active)
                        <span class="badge bg-label-success">Actif</span>
                    @else
                        <span class="badge bg-label-danger">Inactif</span>
                    @endif
                    @if($server->is_online)
                        <span class="badge bg-success badge-dot me-1"></span><small>En ligne</small>
                    @endif
                </div>
            </div>
            <div class="card-footer border-top bg-transparent py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Protocols: <strong>{{ is_array($server->supported_protocols) ? implode(', ', $server->supported_protocols) : 'Standard' }}</strong>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary btn-test-conn" data-id="{{ $server->id }}">
                        <i class="ti tabler-refresh me-1"></i> Test
                    </button>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <img src="https://illustrations.popsy.co/amber/no-messages.svg" alt="Empty" style="height: 150px" class="mb-3">
        <h5>Aucun serveur configuré</h5>
        <p class="text-muted">Commencez par ajouter un serveur Mikrotik ou Wireguard.</p>
    </div>
    @endforelse
</div>

{{-- Modale Edition (Générique) --}}
<div class="modal fade" id="modalEditServer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formEditServer" class="modal-content">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="server_type" id="edit_server_type">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le serveur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Nom du serveur</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    
                    {{-- Champs dynamiques affichés selon le type via JS --}}
                    <div class="row g-3 mt-1" id="fields-routeros">
                        <div class="col-md-6">
                            <label class="form-label">IP / Hostname</label>
                            <label class="form-label">IP Publique (API)</label>
                            <input type="text" name="ip_address" id="edit_ip_address" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port API</label>
                            <input type="number" name="api_port" id="edit_api_port" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">User API</label>
                            <input type="text" name="api_user" id="edit_api_user" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pass API (laisser vide pour conserver)</label>
                            <input type="password" name="api_password" id="edit_api_password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom du Profil PPP</label>
                            <input type="text" name="profile_name" id="edit_profile_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gateway Locale</label>
                            <input type="text" name="gateway_ip" id="edit_gateway_ip" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Domaine</label>
                            <input type="text" name="domain_name" id="edit_domain_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pool d'IP Clients</label>
                            <input type="text" name="ip_pool" id="edit_ip_pool" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Comptes</label>
                            <input type="number" name="max_accounts_router" id="edit_max_accounts_router" class="form-control" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location_router" id="edit_location_router" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mt-1" id="fields-wireguard">
                        <div class="col-md-6">
                            <label class="form-label">Nom du serveur</label>
                            <input type="text" name="name_wg" id="edit_name_wg" class="form-control" placeholder="WG-Abidjan-01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IP publique / Domaine</label>
                            <input type="text" name="wg_endpoint_address" id="edit_wg_endpoint" class="form-control" placeholder="vpn.phenixspot.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Port WireGuard</label>
                            <input type="number" name="wg_endpoint_port" id="edit_wg_port" class="form-control" placeholder="51820">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Interface WireGuard</label>
                            <input type="text" name="wg_interface" id="edit_wg_interface" class="form-control" placeholder="wg0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max peers</label>
                            <input type="number" name="max_accounts" id="edit_max_accounts_wg" class="form-control" placeholder="1000" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Clé publique du serveur</label>
                            <input type="text" name="wg_server_public_key" id="edit_wg_public_key" class="form-control">
                            <small class="text-warning">⚠️ Obligatoire pour générer les clients</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Clé privée du serveur</label>
                            <input type="password" name="wg_server_private_key" id="edit_wg_private_key" class="form-control" placeholder="Laisser vide pour conserver l'ancienne clé">
                            <small class="text-warning">⚠️ Stockée chiffrée</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subnet VPN</label>
                            <input type="text" name="wg_network" id="edit_wg_network" class="form-control" placeholder="10.66.0.0/16">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gateway VPN (IP serveur dans le tunnel)</label>
                            <input type="text" name="wg_server_address" id="edit_wg_server_address" class="form-control" placeholder="10.66.0.1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DNS par défaut</label>
                            <input type="text" name="wg_dns" id="edit_wg_dns" class="form-control" placeholder="1.1.1.1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">MTU (optionnel)</label>
                            <input type="number" name="wg_mtu" id="edit_wg_mtu" class="form-control" placeholder="1420">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Persistent keepalive (optionnel)</label>
                            <input type="number" name="wg_persistent_keepalive" id="edit_wg_keepalive" class="form-control" placeholder="25">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="edit_location_wg" class="form-control" placeholder="Abidjan Datacenter">
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label">Serveur activé</label>
                        </div>
                    </div>
                </div>
                <div id="editAlert" class="mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary" id="btnSaveEdit">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

{{-- Injection des routes pour le JS --}}
<div id="jsRoutes" 
     data-store-url="{{ route('admin.vpn-servers.store') }}"
     data-json-url="{{ route('admin.vpn-servers.json', ['vpnServer' => '__ID__']) }}"
     data-test-url="{{ route('admin.vpn-servers.test-connection') }}"
     data-update-url="{{ route('admin.vpn-servers.update', ['vpn_server' => '__ID__']) }}"
     data-delete-url="{{ route('admin.vpn-servers.destroy', ['vpn_server' => '__ID__']) }}">
</div>

@endsection

@section('page-script')
@include('content.admin.vpn_servers._index_script')
@endsection