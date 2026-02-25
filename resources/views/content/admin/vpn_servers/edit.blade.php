@extends('layouts/layoutMaster')

@section('title', 'Modifier Serveur VPN')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Infrastructure / Serveurs VPN /</span> Modifier
</h4>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Modifier le serveur</h5>
    <a href="{{ route('admin.vpn-servers.index') }}" class="btn btn-label-secondary">
      <i class="ti tabler-arrow-left me-1"></i> Retour
    </a>
  </div>

  <div class="card-body">
    {{-- $server est normalement passé par le controller edit() --}}
    <form action="{{ route('admin.vpn-servers.update', $server->id) }}" method="POST">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nom</label>
          <input class="form-control" name="name" required value="{{ old('name', $server->name) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Type</label>
          <input class="form-control" name="server_type" value="{{ old('server_type', $server->server_type) }}" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label">Location</label>
          <input class="form-control" name="location" value="{{ old('location', $server->location) }}">
        </div>

        <div class="col-md-6 d-flex align-items-end gap-4">
          <div>
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $server->is_active) ? 'checked' : '' }}>
              <label class="form-check-label">Actif</label>
            </div>
          </div>

          <div>
            <input type="hidden" name="is_online" value="0">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_online" value="1" {{ old('is_online', $server->is_online) ? 'checked' : '' }}>
              <label class="form-check-label">En ligne</label>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="divider text-start">
            <div class="divider-text">RouterOS (API)</div>
          </div>
        </div>

        <div class="col-md-8">
          <label class="form-label">IP API</label>
          <input class="form-control" name="ip_address" value="{{ old('ip_address', $server->ip_address) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Port API</label>
          <input class="form-control" type="number" name="api_port" value="{{ old('api_port', $server->api_port) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Domaine</label>
          <input class="form-control" name="domain_name" value="{{ old('domain_name', $server->domain_name) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Profil PPP</label>
          <input class="form-control" name="profile_name" value="{{ old('profile_name', $server->profile_name) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">User API</label>
          <input class="form-control" name="api_user" value="{{ old('api_user', $server->api_user) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Pass API (laisser vide si inchangé)</label>
          <input class="form-control" name="api_password" type="password" value="">
        </div>

        <div class="col-md-6">
          <label class="form-label">Gateway IP</label>
          <input class="form-control" name="gateway_ip" value="{{ old('gateway_ip', $server->gateway_ip) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">Pool IP</label>
          <input class="form-control" name="ip_pool" value="{{ old('ip_pool', $server->ip_pool) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">Max comptes</label>
          <input class="form-control" type="number" name="max_accounts" value="{{ old('max_accounts', $server->max_accounts) }}">
        </div>

        <div class="col-12">
          <div class="divider text-start">
            <div class="divider-text">WireGuard</div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">WG Network</label>
          <input class="form-control" name="wg_network" value="{{ old('wg_network', $server->wg_network) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label">WG Server Address</label>
          <input class="form-control" name="wg_server_address" value="{{ old('wg_server_address', $server->wg_server_address) }}">
        </div>

        <div class="col-md-12">
          <label class="form-label">WG Server Public Key</label>
          <input class="form-control" name="wg_server_public_key" value="{{ old('wg_server_public_key', $server->wg_server_public_key) }}">
        </div>

        <div class="col-md-8">
          <label class="form-label">WG Endpoint Address</label>
          <input class="form-control" name="wg_endpoint_address" value="{{ old('wg_endpoint_address', $server->wg_endpoint_address) }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">WG Endpoint Port</label>
          <input class="form-control" type="number" name="wg_endpoint_port" value="{{ old('wg_endpoint_port', $server->wg_endpoint_port) }}">
        </div>

        <div class="col-md-6">
          <label class="form-label">WG Interface</label>
          <input class="form-control" name="wg_interface" value="{{ old('wg_interface', $server->wg_interface) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label">WG DNS</label>
          <input class="form-control" name="wg_dns" value="{{ old('wg_dns', $server->wg_dns) }}">
        </div>

        <div class="col-md-4">
          <label class="form-label">WG MTU</label>
          <input class="form-control" type="number" name="wg_mtu" value="{{ old('wg_mtu', $server->wg_mtu) }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">WG Keepalive</label>
          <input class="form-control" type="number" name="wg_persistent_keepalive" value="{{ old('wg_persistent_keepalive', $server->wg_persistent_keepalive) }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">WG Client IP Start</label>
          <input class="form-control" name="wg_client_ip_start" value="{{ old('wg_client_ip_start', $server->wg_client_ip_start) }}">
        </div>

        <div class="col-md-12">
          <label class="form-label">supported_protocols</label>
          <input class="form-control" name="supported_protocols" value="{{ old('supported_protocols', is_string($server->supported_protocols) ? $server->supported_protocols : json_encode($server->supported_protocols)) }}">
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end gap-2">
        <a href="{{ route('admin.vpn-servers.index') }}" class="btn btn-label-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary">
          <i class="ti tabler-device-floppy me-1"></i> Sauvegarder
        </button>
      </div>
    </form>
  </div>
</div>
@endsection