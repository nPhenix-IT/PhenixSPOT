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
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCreateRouterOs">
            <i class="ti tabler-router me-1"></i> + RouterOS
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateWireGuard">
            <i class="ti tabler-shield-lock me-1"></i> + WireGuard
        </button>
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
                            <input type="text" name="ip_address" id="edit_ip_address" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port API</label>
                            <input type="number" name="api_port" id="edit_api_port" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mt-1" id="fields-wireguard">
                        <div class="col-md-6">
                            <label class="form-label">WG Endpoint</label>
                            <input type="text" name="wg_endpoint_address" id="edit_wg_endpoint" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port WG</label>
                            <input type="number" name="wg_endpoint_port" id="edit_wg_port" class="form-control">
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
     data-json-url="{{ route('admin.vpn-servers.json', ['vpnServer' => '__ID__']) }}"
     data-update-url="{{ route('admin.vpn-servers.update', ['vpn_server' => '__ID__']) }}"
     data-delete-url="{{ route('admin.vpn-servers.destroy', ['vpn_server' => '__ID__']) }}">
</div>

@endsection

@section('page-script')
@include('content.admin.vpn_servers._index_script')
@endsection