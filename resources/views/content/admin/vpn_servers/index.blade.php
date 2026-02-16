@extends('layouts/layoutMaster')

@section('title', 'Gestion des Serveurs MikroTik')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Infrastructure /</span> Serveurs VPN</h4>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-xl-8 col-lg-7 col-md-12 mb-4">
        <div class="card h-100">
            <h5 class="card-header">Serveurs Disponibles</h5>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Nom & Profil</th>
                            <th>Réseau VPN</th>
                            <th>Capacité</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($servers as $server)
                        <tr>
                            <td>
                                @if($server->is_online)
                                    <span class="badge bg-label-success me-1">En Ligne</span>
                                @else
                                    <span class="badge bg-label-danger me-1">Hors Ligne</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $server->name }}</strong><br>
                                <small class="text-muted">{{ $server->domain_name ?? $server->ip_address }}</small><br>
                                <span class="badge bg-label-info mt-1">{{ $server->profile_name ?? 'Défaut' }}</span>
                            </td>
                            <td>
                                <small>GW: {{ $server->gateway_ip }}</small><br>
                                <small>Pool: {{ \Illuminate\Support\Str::limit($server->ip_pool, 18) }}</small>
                            </td>
                            <td>
                                <span class="badge bg-label-primary">
                                    {{ $server->accounts_count }} / {{ $server->max_accounts ?? $server->account_limit }}
                                </span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti tabler-dots-vertical"></i></button>
                                    <div class="dropdown-menu">
                                        <form action="{{ route('admin.vpn-servers.test-connection') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="server_id" value="{{ $server->id }}">
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti tabler-activity me-1"></i> Tester Connexion
                                            </button>
                                        </form>
                                        <a class="dropdown-item" href="{{ route('admin.vpn-servers.edit', $server->id) }}">
                                            <i class="ti tabler-pencil me-1"></i> Modifier
                                        </a>
                                        <form action="{{ route('admin.vpn-servers.destroy', $server->id) }}" method="POST" onsubmit="return confirm('Supprimer ce serveur ?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="ti tabler-trash me-1"></i> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">Aucun serveur configuré.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
</div>

    <div class="col-xl-4 col-lg-5 col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ajouter un CHR</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.vpn-servers.store') }}" method="POST">
                    @csrf

                    <div class="divider text-start">
                        <div class="divider-text text-primary">Connexion API</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nom du Serveur</label>
                        <input type="text" class="form-control" name="name" placeholder="Ex: Paris-VPN-01" required />
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">IP Publique (API)</label>
                            <input type="text" class="form-control" name="ip_address" placeholder="1.2.3.4" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Port API</label>
                            <input type="number" class="form-control" name="api_port" value="8728" required />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Domaine (Optionnel)</label>
                        <input type="text" class="form-control" name="domain_name" placeholder="vpn1.monapp.com" />
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">User API</label>
                            <input type="text" class="form-control" name="api_user" placeholder="admin" required />
                        </div>
                        <div class="col">
                            <label class="form-label">Pass API</label>
                            <input type="password" class="form-control" name="api_password" placeholder="********" required />
                        </div>
                    </div>
                    <div class="row">
                     <div class="divider text-start">
                        <div class="divider-text text-success">Configuration Réseau VPN</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nom du Profil PPP</label>
                        <input type="text" class="form-control" name="profile_name" placeholder="Ex: default-encryption" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gateway Locale (Routeur)</label>
                        <input type="text" class="form-control" name="gateway_ip" placeholder="10.10.10.1" required />
                    </div>
                </div>

                    <div class="mb-3">
                        <label class="form-label">Pool d'IP Clients</label>
                        <input type="text" class="form-control" name="ip_pool" placeholder="10.10.10.2-10.10.10.254" required />
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Max Comptes</label>
                            <input type="number" class="form-control" name="max_accounts" value="100" min="1" required />
                        </div>
                        <div class="col">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" placeholder="Paris" />
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti tabler-plus me-1"></i> Ajouter le Serveur
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection