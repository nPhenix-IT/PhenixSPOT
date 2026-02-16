@extends('layouts/layoutMaster')

@section('title', 'Modifier serveur VPN')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Infrastructure / Serveurs VPN /</span> Modifier</h4>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Modifier {{ $vpnServer->name }}</h5></div>
      <div class="card-body">
        <form action="{{ route('admin.vpn-servers.update', $vpnServer->id) }}" method="POST">
          @csrf
          @method('PUT')
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nom</label><input class="form-control" name="name" value="{{ old('name', $vpnServer->name) }}" required></div>
            <div class="col-md-6"><label class="form-label">IP</label><input class="form-control" name="ip_address" value="{{ old('ip_address', $vpnServer->ip_address) }}" required></div>
            <div class="col-md-6"><label class="form-label">Domaine</label><input class="form-control" name="domain_name" value="{{ old('domain_name', $vpnServer->domain_name) }}"></div>
            <div class="col-md-6"><label class="form-label">Profil PPP</label><input class="form-control" name="profile_name" value="{{ old('profile_name', $vpnServer->profile_name) }}" required></div>
            <div class="col-md-4"><label class="form-label">User API</label><input class="form-control" name="api_user" value="{{ old('api_user', $vpnServer->api_user) }}" required></div>
            <div class="col-md-4"><label class="form-label">Pass API (laisser vide)</label><input type="password" class="form-control" name="api_password"></div>
            <div class="col-md-4"><label class="form-label">Port API</label><input class="form-control" name="api_port" value="{{ old('api_port', $vpnServer->api_port) }}" required></div>
            <div class="col-md-6"><label class="form-label">Gateway</label><input class="form-control" name="gateway_ip" value="{{ old('gateway_ip', $vpnServer->gateway_ip) }}" required></div>
            <div class="col-md-6"><label class="form-label">Pool IP</label><input class="form-control" name="ip_pool" value="{{ old('ip_pool', $vpnServer->ip_pool) }}" required></div>
            <div class="col-md-6"><label class="form-label">Max comptes</label><input type="number" class="form-control" name="max_accounts" value="{{ old('max_accounts', $vpnServer->max_accounts ?? $vpnServer->account_limit) }}" required></div>
            <div class="col-md-6"><label class="form-label">Location</label><input class="form-control" name="location" value="{{ old('location', $vpnServer->location) }}"></div>
          </div>
          <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a href="{{ route('admin.vpn-servers.index') }}" class="btn btn-label-secondary">Retour</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
