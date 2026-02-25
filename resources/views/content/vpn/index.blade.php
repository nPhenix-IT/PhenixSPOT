@extends('layouts/layoutMaster')

@section('title', 'Mes Tunnels VPN')

@section('page-style')
<style>
/* Keyframes pour les animations dynamiques */
@keyframes pulse-soft {
    0% {
        box-shadow: 0 0 0 0 rgba(115, 103, 240, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(115, 103, 240, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(115, 103, 240, 0);
    }
}

@keyframes shine {
    from {
        left: -100%;
    }
    to {
        left: 100%;
    }
}

/* Design System - VPN Management Enhanced */
.vpn-card-hover {
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.vpn-card-hover:hover {
    transform: translateY(-5px) scale(1.01);
    box-shadow: 0 15px 30px rgba(115, 103, 240, 0.2) !important;
}

.status-pill {
    padding: 0.5em 1.2em;
    font-weight: 700;
    letter-spacing: 0.5px;
    border-radius: 50px;
    text-transform: uppercase;
    font-size: 0.7rem;
}

.avatar-protocol {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    transition: 0.4s;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

tr:hover .avatar-protocol {
    transform: rotate(8deg);
}

/* Code Editor Style for Script */
.code-editor-container {
    background: #121212;
    border-radius: 15px;
    position: relative;
    border: 1px solid #333;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.code-header {
    background: #252525;
    padding: 12px 20px;
    border-top-left-radius: 14px;
    border-top-right-radius: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #333;
}

.code-body {
    max-height: 400px;
    overflow-y: auto;
    padding: 20px;
    font-size: 0.9rem;
}

/* Protocol Cards UI */
.protocol-card {
    cursor: pointer;
    transition: 0.3s;
    border: 2px solid #f1f1f1;
    border-radius: 16px;
    position: relative;
    overflow: hidden;
}

.protocol-card:hover {
    border-color: #7367f0;
    background-color: rgba(115, 103, 240, 0.03);
}

input[type="radio"]:checked + .protocol-card {
    border-color: #7367f0;
    background: linear-gradient(to bottom right, rgba(115, 103, 240, 0.1), rgba(115, 103, 240, 0.02));
    box-shadow: 0 10px 20px rgba(115, 103, 240, 0.1);
}

.wallet-gradient-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
    border: 1px solid rgba(115, 103, 240, 0.2) !important;
    position: relative;
    overflow: hidden;
}

.wallet-gradient-card::after {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(to right, transparent, rgba(115, 103, 240, 0.05), transparent);
    animation: shine 3s infinite;
}

/* Animated Status Dots */
.pulse-dot {
    height: 8px;
    width: 8px;
    border-radius: 50%;
    display: inline-block;
}
.pulse-active {
    background: #28c76f;
    box-shadow: 0 0 0 rgba(40, 199, 111, 0.4);
    animation: pulse-success 2s infinite;
}

@keyframes pulse-success {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(40, 199, 111, 0.7);
    }
    70% {
        transform: scale(1);
        box-shadow: 0 0 0 6px rgba(40, 199, 111, 0);
    }
    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(40, 199, 111, 0);
    }
}

.font-monospace-code {
    font-family: "Fira Code", "JetBrains Mono", monospace;
    color: #e0e0e0;
    line-height: 1.7;
}

.glass-badge {
    background: rgba(115, 103, 240, 0.1);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(115, 103, 240, 0.2);
    color: #7367f0;
}

/* === PAGINATION FIX (Bootstrap 5 compatible) === */
.pagination {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.pagination .page-item {
    margin: 0;
}

.pagination .page-link {
    border-radius: 10px;
    min-width: 38px;
    height: 38px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;

    font-weight: 700;
    font-size: 0.9rem;

    color: #5b5f7a;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;

    transition: all 0.2s ease;
}

.pagination .page-link:hover {
    background-color: #f4f6fb;
    color: #4f46e5;
    border-color: #4f46e5;
}

.pagination .page-item.active .page-link {
    background-color: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
}

.pagination .page-item.disabled .page-link {
    color: #c0c4d1;
    background-color: #f9fafb;
    border-color: #e5e7eb;
    cursor: not-allowed;
}

/* Fix flèches Previous / Next */
.pagination .page-link svg,
.pagination .page-link i {
    width: 14px;
    height: 14px;
}

/* Mobile friendly */
@media (max-width: 576px) {
    .pagination .page-link {
        min-width: 34px;
        height: 34px;
        font-size: 0.85rem;
        padding: 0 10px;
    }
}

.disabled-proto {
    opacity: 0.4;
    filter: grayscale(100%);
}

.proto-wrapper {
    position: relative;
}

.proto-wrapper::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 110%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 0, 21, 85);
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 15px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
}

.proto-wrapper:hover::after {
    opacity: 1;
}
</style>
@endsection

@section('content')
<!-- Header & Actions -->
<div class="row align-items-center mb-5 g-3">
    <div class="col-12 col-md-6">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1 mb-2">
                <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-muted">VPN Control Center</a></li>
                <li class="breadcrumb-item active fw-bold">Infrastructure</li>
            </ol>
        </nav>
        <h3 class="fw-black mb-0 text-dark">
            Mes Tunnels <span class="badge glass-badge ms-2 fs-tiny">Active Node v2.0</span>
        </h3>
    </div>
    <div class="col-12 col-md-6 text-md-end">
        <div class="d-flex flex-wrap gap-3 justify-content-md-end align-items-center">
            <div class="card shadow-none wallet-gradient-card py-2 px-4 d-flex flex-row align-items-center">
                <div class="avatar bg-label-primary rounded-circle me-3">
                    <i class="ti tabler-wallet fs-3"></i>
                </div>
                <div class="text-start">
                    <small class="text-muted d-block fw-bold" style="font-size: 0.65rem; letter-spacing: 1px"
                        >SOLDE DISPONIBLE</small
                    >
                    <span class="fw-black h5 mb-0 text-primary"
                        >{{ number_format(auth()->user()->wallet->balance ?? 0 , 0, ',', ' ')}} <small>FCFA</small></span
                    >
                </div>
            </div>
            <button
                type="button"
                class="btn btn-primary btn-lg shadow-md vpn-card-hover rounded-pill px-4"
                data-bs-toggle="modal"
                data-bs-target="#createVpnModal"
            >
                <i class="ti tabler-plus me-1"></i> Nouveau Tunnel
            </button>
        </div>
    </div>
</div>

@if(!$hasActiveSubscription)
<div class="alert alert-danger">Abonnement inactif. Veuillez activer un plan pour créer des tunnels VPN.</div>
@endif
<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-2">Surveillance du forfait</h6>
        <p class="mb-2">
            Comptes actifs: <strong>{{ $vpnAccountCount }}</strong> / <strong>{{ $limitLabel ?? $limit }}</strong> inclus.
        </p>
        @if(($limit ?? 0) !== PHP_INT_MAX)
            <div class="progress">
                <div
                    class="progress-bar"
                    style="width: {{ $usagePercent ?? 0 }}%;"
                ></div>
            </div>
        @endif
        @if($isAtLimit)
        <small class="text-warning d-block mt-2"
            >Limite atteinte: les nouveaux comptes seront facturés à 500 FCFA/compte/mois.</small
        >
        @endif
    </div>
</div>

<!-- Table Container -->
<div class="card border-0 shadow-lg rounded-4 overflow-hidden">
    <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-label-secondary text-uppercase" style="font-size: 0.7rem; letter-spacing: 1.2px">
                <tr>
                    <th class="ps-4 py-3">Ressource & Protocol</th>
                    <th>Node Gateway</th>
                    <th>Point d'accès IP</th>
                    <th>Validité</th>
                    <th>Monitoring</th>
                    <th class="text-center pe-4">Administration</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse($accounts as $account)
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div
                                class="avatar-protocol me-3 @if($account->protocol == 'l2tp') bg-label-info @elseif($account->protocol == 'ovpn') bg-label-warning @else bg-label-primary @endif"
                            >
                                <i
                                    class="ti tabler-{{ $account->protocol == 'l2tp' ? 'lock' : ($account->protocol == 'ovpn' ? 'shield-lock' : 'wifi') }} fs-3"
                                ></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-black text-dark">{{ $account->username }}</h6>
                                <span class="text-muted small fw-bold" style="font-size: 0.6rem">
                                    Type:
                                    <span class="text-uppercase text-success">{{ $account->protocol }}</span></span
                                >
                                <div
                                    class="d-flex align-items-center mt-1 cursor-pointer"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editCommentModal{{ $account->id }}"
                                    style="cursor: pointer"
                                    title="Cliquez pour renommer"
                                >
                                    <span class="d-block text-primary small fw-bold me-1" style="font-size: 0.7rem">
                                        <i class="ti tabler-tag me-1" style="font-size: 0.6rem"></i>{{
                                        $account->commentaire ?? 'Ajouter un nom...' }}
                                    </span>
                                    <i class="ti tabler-pencil text-muted opacity-50" style="font-size: 0.6rem"></i>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2 text-primary">
                                <i class="ti tabler-map-pin-filled fs-4"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">{{ $account->server->name }}</div>
                                <div class="text-muted extra-small" style="font-size: 0.7rem">
                                    {{ $account->server->location }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="d-inline-flex align-items-center bg-label-dark px-3 py-1 rounded-pill">
                            <code class="text-primary fw-black small">{{ $account->remote_ip }}</code>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-black {{ $account->expires_at->isPast() ? 'text-danger' : 'text-dark' }}">
                                {{ $account->expires_at->format('d M Y') }}
                            </span>
                            <small
                                class="{{ $account->expires_at->isPast() ? 'text-danger opacity-75' : 'text-muted' }} fw-bold"
                                style="font-size: 0.7rem"
                            >
                                <i class="ti tabler-clock-hour-4 me-1"></i>{{ $account->expires_at->diffForHumans() }}
                            </small>
                        </div>
                    </td>

                    <td>
                        @if($account->isValid())
                        <span
                            class="badge status-active status-pill d-inline-flex align-items-center"
                            id="status-badge-{{ $account->id }}"
                        >
                            <span class="pulse-dot pulse-active me-2"></span> OPERATIONAL
                        </span>
                        @else
                        <span class="badge bg-label-danger status-pill d-inline-flex align-items-center">
                            <i class="ti tabler-alert-circle me-1"></i> EXPIRED
                        </span>
                        @endif
                    </td>

                    <td class="text-center pe-4">
                        <div class="d-flex justify-content-center gap-2">
                            <button
                                type="button"
                                class="btn btn-icon btn-label-info rounded-pill shadow-sm vpn-card-hover"
                                onclick="checkOnlineStatus({{ $account->id }})"
                                data-bs-toggle="tooltip"
                                title="Test de connexion Live"
                            >
                                <i class="ti tabler-activity-heartbeat"></i>
                            </button>

                            <button
                                type="button"
                                class="btn btn-icon btn-label-primary rounded-pill shadow-sm vpn-card-hover"
                                data-bs-toggle="modal"
                                data-bs-target="#scriptModal{{ $account->id }}"
                                title="Scripts"
                            >
                                <i class="ti tabler-terminal-2"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-icon btn-label-warning rounded-pill shadow-sm vpn-card-hover"
                                data-bs-toggle="modal"
                                data-bs-target="#editPortsModal{{ $account->id }}"
                                title="NAT Config"
                            >
                                <i class="ti tabler-adjustments-alt"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-icon btn-label-success rounded-pill shadow-sm vpn-card-hover"
                                data-bs-toggle="modal"
                                data-bs-target="#renewModal{{ $account->id }}"
                                title="Renew Account"
                            >
                                <i class="ti tabler-bolt"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-icon btn-label-danger rounded-pill shadow-sm vpn-card-hover"
                                onclick="confirmDelete({{ $account->id }})"
                                title="Supprimer"
                            >
                                <i class="ti tabler-trash"></i>
                            </button>
                            <form
                                id="delete-form-{{ $account->id }}"
                                action="{{ route('user.vpn.destroy', $account->id) }}"
                                method="POST"
                                style="display: none"
                            >
                                @csrf @method('DELETE')
                            </form>
                        </div>
                    </td>
                </tr>

                <div
                    class="modal fade"
                    data-bs-backdrop="static"
                    id="editCommentModal{{ $account->id }}"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header border-bottom py-3 bg-white">
                                <h6 class="modal-title fw-black text-dark">Renommer le Routeur</h6>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                ></button>
                            </div>
                            <form action="{{ route('user.vpn.update', $account->id) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="modal-body p-4 bg-white">
                                    <label class="form-label small fw-bold text-muted">IDENTIFIANT (COMMENTAIRE)</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="commentaire"
                                        value="{{ $account->commentaire }}"
                                        placeholder="Ex: Zone 1, Abidjan"
                                    />
                                </div>
                                <div class="modal-footer p-3 bg-light border-0 justify-content-center">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                                        Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL SCRIPT : CONSOLE STYLE -->
                <div class="modal fade" data-bs-backdrop="static" id="scriptModal{{ $account->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header bg-dark py-3 border-0">
                                <div class="d-flex align-items-center text-white">
                                    <div class="bg-primary p-2 rounded-3 me-3">
                                        <i class="ti tabler-terminal fs-3 text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="modal-title text-white mb-0 fw-black">Node Terminal #{{ $account->id }}</h5>
                                        <small class="text-white-50 fw-bold">Paramètres de configuration et de déploiement</small>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="row g-0">
                                    <div class="col-lg-4 border-end bg-light p-4">
                                        <label class="text-uppercase text-muted fw-black mb-4 small d-block" style="letter-spacing: 1px;">Informations d'accès</label>
                                        
                                        <div class="vstack gap-3">
                                            <div class="bg-white p-3 rounded-3 shadow-sm border border-primary border-opacity-10">
                                                <label class="form-label text-muted small fw-bold mb-1">Passerelle</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control border-0 bg-light fw-bold" value="{{ $account->server->domain_name ?? $account->server->ip_address }}" readonly>
                                                    <button class="btn btn-primary" onclick="copyText('{{ $account->server->domain_name ?? $account->server->ip_address }}')"><i class="ti tabler-copy"></i></button>
                                                </div>
                                            </div>

                                            <div class="bg-white p-3 rounded-3 shadow-sm border border-primary border-opacity-10">
                                                <label class="form-label text-muted small fw-bold mb-1">Nom d'utilisateur VPN</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control border-0 bg-light" value="{{ $account->username }}" readonly>
                                                    <button class="btn btn-primary" onclick="copyText('{{ $account->username }}')"><i class="ti tabler-copy"></i></button>
                                                </div>
                                            </div>

                                            <div class="bg-white p-3 rounded-3 shadow-sm border border-primary border-opacity-10">
                                                <label class="form-label text-muted small fw-bold mb-1">Mot de passey</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control border-0 bg-light" value="{{ $account->password }}" readonly>
                                                    <button class="btn btn-primary" onclick="copyText('{{ $account->password }}')"><i class="ti tabler-copy"></i></button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-5">
                                            <label class="text-uppercase text-muted fw-black mb-3 small d-block">Connexions actives</label>
                                            <div class="list-group list-group-flush rounded-3 border overflow-hidden shadow-sm">
                                                @if($account->port_api)
                                                 <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                                    <span class="badge bg-label-info rounded-pill">API</span>
                                                    <span class="font-monospace fw-black text-dark">{{ $account->server->domain_name }}:{{ $account->port_api }}</span>
                                                </div>
                                                @endif
                                                @if($account->port_winbox)
                                                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                                    <span class="badge bg-label-info rounded-pill">Winbox</span>
                                                    <span class="font-monospace fw-black text-dark">{{ $account->server->domain_name }}:{{ $account->port_winbox }}</span>
                                                </div>
                                                @endif
                                                @if($account->port_web)
                                                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                                    <span class="badge bg-label-success rounded-pill">Web Port</span>
                                                    <a href="http://{{ $account->server->domain_name }}:{{ $account->port_web }}" target="_blank" class="fw-black text-primary">{{ $account->server->domain_name }}:{{ $account->port_web }}</a>
                                                </div>
                                                @endif
                                                @if($account->port_custom)
                                                <div class="list-group-item d-flex justify-content-between align-items-center p-3 bg-label-primary">
                                                    <span class="badge bg-primary rounded-pill">Personnalisé</span>
                                                    <span class="font-monospace fw-black">{{ $account->server->domain_name }}:{{ $account->port_custom }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-8 p-4 bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0 fw-black text-dark"><i class="ti tabler-code me-2 text-primary"></i>Script d'installation MikroTik</h6>
                                            <!--<small>Copier et coller ce script dans le Terminal de votre MikroTik.</small>-->
                                            <button class="btn btn-primary btn-sm rounded-pill shadow-sm" onclick="copyToClipboard('code{{ $account->id }}')">
                                                <i class="ti tabler-copy me-1"></i> Tout copier
                                            </button>
                                        </div>
                                        <div class="code-editor-container">
                                            <div class="code-header">
                                                <div class="d-flex gap-2">
                                                    <span class="dot dot-1"></span><span class="dot dot-2"></span><span class="dot dot-3"></span>
                                                </div>
                                                <small class="text-white-50 font-monospace">provisioning_v1.rsc</small>
                                            </div>
                                            <div class="code-body" id="code{{ $account->id }}">
                                                <pre class="font-monospace-code m-0">
/tool fetch url="{{ $account->script_loader_url }}" mode=https check-certificate=no dst-path=vpn.rsc; /import vpn.rsc; /file remove vpn.rsc
                                                </pre>
                                            </div>
                                        </div>
                                        <div class="alert alert-label-primary mt-4 mb-0 border-0 shadow-none d-flex rounded-3">
                                            <i class="ti tabler-info-hexagon me-3 fs-3 text-primary"></i>
                                            <div class="small fw-bold">
                                                L'exécution de ce script crée une interface virtuelle persistante.<br/>Assurez-vous que les horloges système sont synchronisées.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="modal fade"
                    data-bs-backdrop="static"
                    id="renewModal{{ $account->id }}"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg overflow-hidden">
                            <div class="modal-header border-bottom py-3 bg-white">
                                <h5 class="modal-title fw-black d-flex align-items-center text-dark">
                                    <i class="ti tabler-refresh me-2 text-success fs-3"></i> Extension de Validité
                                </h5>
                                <button type="button" class="btn-close text-danger" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="{{ route('user.vpn.renew', $account->id) }}" method="POST">
                                @csrf
                                <div class="modal-body p-4">
                                    <select
                                        class="form-select"
                                        name="duration"
                                        id="renewDuration{{$account->id}}"
                                        onchange="updateRenewPrice({{$account->id}})"
                                    >
                                        <option value="1" data-price="500">1 MOIS</option>
                                        <option value="3" data-price="1500">3 MOIS</option>
                                        <option value="6" data-price="3000">6 MOIS</option>
                                        <option value="12" data-price="6000">12 MOIS</option>
                                    </select>
                                </div>
                                <div class="modal-footer bg-light p-4 justify-content-between border-0">
                                    <div class="text-start">
                                        <small class="text-muted d-block fw-bold">TOTAL À DÉBITER</small
                                        ><span class="h4 mb-0 text-success fw-black"
                                            ><span id="renewPrice{{$account->id}}"
                                                >{{ ($account->is_supplementary ? 500 : 0) }}</span
                                            >
                                            FCFA</span
                                        >
                                    </div>
                                    <button type="submit" class="btn btn-success btn-lg px-4 shadow-md rounded-pill">
                                        <i class="ti tabler-check me-2"></i> Payer maintenant
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    class="modal fade"
                    data-bs-backdrop="static"
                    id="editPortsModal{{ $account->id }}"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                            <div class="modal-header border-bottom py-3 bg-white">
                                <h5 class="modal-title fw-black text-dark d-flex align-items-center">
                                    <i class="ti tabler-settings-automation me-2 text-warning fs-3"></i> Network NAT
                                    Overrides
                                </h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                ></button>
                            </div>
                            <form action="{{ route('user.vpn.update_ports', $account->id) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="modal-body p-4 bg-white">
                                    <div class="vstack gap-3">
                                        <input
                                            type="number"
                                            class="form-control"
                                            name="port_api_target"
                                            value="{{ $account->remote_port_api }}"
                                            required
                                        /><input
                                            type="number"
                                            class="form-control"
                                            name="port_winbox_target"
                                            value="{{ $account->remote_port_winbox }}"
                                            required
                                        /><input
                                            type="number"
                                            class="form-control"
                                            name="port_web_target"
                                            value="{{ $account->remote_port_web }}"
                                            required
                                        />@if($account->port_custom)<input
                                            type="number"
                                            class="form-control"
                                            name="port_custom_target"
                                            value="{{ $account->remote_port_custom }}"
                                        />@endif
                                    </div>
                                </div>
                                <div class="modal-footer border-top p-4 bg-light">
                                    <button type="submit" class="btn btn-primary w-100 btn-lg shadow-md rounded-pill">
                                        <i class="ti tabler-cloud-upload me-2"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">Aucune instance détectée</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if ($accounts->hasPages())
<div class="d-flex justify-content-center mt-4">{{ $accounts->links('pagination::bootstrap-5') }}</div>
@endif

<div class="modal fade" data-bs-backdrop="static" id="createVpnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">
            <div class="modal-header border-bottom py-4 bg-white">
                <div class="d-flex align-items-center">
                    <div class="bg-label-primary p-3 rounded-3 me-3 shadow-sm">
                        <i class="ti tabler-atom-2 fs-2"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-black mb-0 text-dark">Nouveau Tunnel VPN</h4>
                        <small class="text-muted fw-bold">Provisionnement instantané d'une ressource cloud</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('user.vpn.store') }}" method="POST">
                @csrf
                <div class="modal-body p-5 bg-white">
                    <div class="mb-5">
                        <label class="form-label fw-black text-dark small mb-2"
                            ><i class="ti tabler-tag me-1 text-primary"></i> IDENTIFICATION DU ROUTEUR
                            (COMMENTAIRE)</label
                        >
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-end-0"
                                ><i class="ti tabler-router text-primary fs-3"></i></span
                            ><input
                                type="text"
                                class="form-control bg-white border-start-0 ps-0"
                                name="commentaire"
                                placeholder="Ex: Router 1, Zone 1, Bouaké"
                            />
                        </div>
                    </div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label class="form-label fw-black text-dark small mb-2"
                                ><i class="ti tabler-cpu me-1 text-primary"></i> PASSERELLE GÉOGRAPHIQUE</label
                            ><select
                                class="form-select form-select-lg border-2 shadow-sm rounded-3"
                                name="server_id"
                                required
                            >
                                <option value="" selected disabled>Choisir un serveur</option>
                                @foreach($servers as $server)
                                <option value="{{ $server->id }}">{{ $server->name }} • {{ $server->location }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-black text-dark small mb-2"
                                ><i class="ti tabler-calendar-heart me-1 text-primary"></i> PÉRIODE DE SERVICE</label
                            ><select
                                class="form-select form-select-lg border-2 shadow-sm rounded-3"
                                name="duration"
                                id="duration"
                                onchange="updatePrice()"
                            >
                                <option value="1" data-price="500">1 MOIS (STANDARD PLAN)</option>
                                <option value="3" data-price="1500">3 MOIS (PRO BUNDLE)</option>
                                <option value="6" data-price="3000">6 MOIS (CORP READY)</option>
                                <option value="12" data-price="6000">12 MOIS (UNLIMITED)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label
                            class="form-label fw-black text-dark small d-block mb-3 text-uppercase"
                            style="letter-spacing: 1px"
                            >Algorithme de Chiffrement (Protocol)</label
                        >
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="w-100 cursor-pointer"
                                    ><input
                                        name="protocol"
                                        class="d-none"
                                        type="radio"
                                        value="l2tp"
                                        id="proto1"
                                        checked
                                    />
                                    <div class="protocol-card card p-4 h-100 text-center shadow-none">
                                        <i class="ti tabler-lock-square fs-1 text-info mb-3"></i>
                                        <div class="fw-black text-dark h6 mb-1">L2TP/IPsec</div>
                                        <small class="text-muted fw-bold">STABLE & FAST</small>
                                    </div></label
                                >
                            </div>
                            <div class="col-md-4">
                                <label
                                    class="w-100 cursor-pointer proto-wrapper"
                                    data-tooltip="Service bientôt disponible"
                                    ><input
                                        name="protocol"
                                        class="d-none"
                                        type="radio"
                                        value="ovpn"
                                        id="proto2"
                                        disabled
                                    />
                                    <div class="protocol-card card p-4 h-100 text-center shadow-none">
                                        <i class="ti tabler-shield-checkered fs-1 text-warning mb-3"></i>
                                        <div class="fw-black text-dark h6 mb-1">OpenVPN</div>
                                        <small class="text-muted fw-bold">SECURE TUNNEL</small>
                                    </div></label
                                >
                            </div>
                            <div class="col-md-4">
                                <label
                                    class="w-100 cursor-pointer proto-wrapper"
                                    data-tooltip="Service bientôt disponible"
                                    ><input
                                        name="protocol"
                                        class="d-none"
                                        type="radio"
                                        value="sstp"
                                        id="proto3"
                                        disabled
                                    />
                                    <div class="protocol-card card p-4 h-100 text-center shadow-none">
                                        <i class="ti tabler-wifi-2 fs-1 text-primary mb-3"></i>
                                        <div class="fw-black text-dark h6 mb-1">SSTP/HTTPS</div>
                                        <small class="text-muted fw-bold">BYPASS FIREWALL</small>
                                    </div></label
                                >
                            </div>
                        </div>
                    </div>

                    <div class="card bg-label-dark border-0 shadow-none rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-switch me-3 mb-0">
                                        <input
                                            class="form-check-input scale-125"
                                            type="checkbox"
                                            id="useCustomPort"
                                            name="use_custom_port"
                                            onchange="toggleCustomPort()"
                                        />
                                    </div>
                                    <div>
                                        <label class="form-label fw-black text-dark mb-0 h6" for="useCustomPort"
                                            >Endpoint Redirection (NAT)</label
                                        ><small class="text-muted d-block fw-bold"
                                            >Mapping d'un port externe personnalisé vers votre IP LAN</small
                                        >
                                    </div>
                                </div>
                                <span class="badge bg-primary text-white fw-black rounded-pill p-2 px-3 shadow-sm"
                                    >+200 FCFA / mois</span
                                >
                            </div>
                            <div
                                id="customPortContainer"
                                style="display: none"
                                class="mt-4 pt-4 border-top border-secondary border-opacity-10"
                            >
                                <div class="input-group input-group-lg shadow-sm">
                                    <span class="input-group-text bg-white border-end-0"
                                        ><i class="ti tabler-plug-connected text-primary fs-3"></i></span
                                    ><input
                                        type="number"
                                        class="form-control bg-white border-start-0 ps-0"
                                        name="custom_port_number"
                                        placeholder="Entrez le port (ex: 8080)"
                                        min="1024"
                                        max="65000"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" id="paymentMethodBlock" style="display: none">
                        <label class="form-label fw-black text-dark small mb-2">Paiement compte supplémentaire</label>
                        <div class="d-flex gap-3">
                            <label class="form-check"
                                ><input
                                    class="form-check-input"
                                    type="radio"
                                    name="payment_method"
                                    value="wallet"
                                    checked
                                /><span class="form-check-label">Wallet</span></label
                            >
                            <label class="form-check"
                                ><input
                                    class="form-check-input"
                                    type="radio"
                                    name="payment_method"
                                    value="moneyfusion"
                                /><span class="form-check-label">MoneyFusion</span></label
                            >
                        </div>
                        @if($isAtLimit)
                        <small class="text-warning"
                            >Limite atteinte : un compte supplémentaire sera facturé immédiatement.</small
                        >
                        @else
                        <small class="text-muted">Affiché uniquement si vous ajoutez un port supplémentaire.</small>
                        @endif
                    </div>
                </div>

                <div class="modal-footer bg-light p-5 justify-content-between border-0">
                    <div class="text-start">
                        <small class="text-muted d-block fw-black" style="letter-spacing: 1px"
                            >INVESTISSEMENT TOTAL</small
                        ><span class="h2 mb-0 text-primary fw-black"><span id="totalPrice">0</span> FCFA</span>
                    </div>
                    <div class="d-flex gap-3">
                        <button
                            type="button"
                            class="btn btn-label-secondary btn-lg px-4 rounded-pill fw-bold"
                            data-bs-dismiss="modal"
                        >
                            ANNULER</button
                        ><button
                            type="submit"
                            class="btn btn-primary btn-lg px-5 shadow-lg rounded-pill fw-black vpn-card-hover"
                        >
                            <i class="ti tabler-rocket me-2"></i> ACTIVER LE TUNNEL
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<style>
.swal2-container { z-index: 20000 !important; }
.status-active { background: rgba(40, 199, 111, 0.1) !important; color: #28c76f !important; border: 1px solid rgba(40, 199, 111, 0.2); }
.dot-1 { background: #ff5f56; } .dot-2 { background: #ffbd2e; } .dot-3 { background: #27c93f; }
.dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
.scale-125 { transform: scale(1.25); }
.fw-black { font-weight: 900 !important; }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    background: '#ffffff',
    color: '#1e293b'
});

function updatePrice() {
    const select = document.getElementById('duration');
    const customPortCheck = document.getElementById('useCustomPort');
    const totalPriceEl = document.getElementById('totalPrice');
    const paymentMethodBlock = document.getElementById('paymentMethodBlock');
    const subscriptionAtLimit = @json($isAtLimit);
    if (select && totalPriceEl) {
        let price = subscriptionAtLimit ? (parseInt(select.options[select.selectedIndex].getAttribute('data-price')) || 0) : 0;
        const durationMonths = parseInt(select.value || '1');
        if (customPortCheck && customPortCheck.checked) price += (200 * durationMonths);
        animateValue(totalPriceEl, parseInt(totalPriceEl.innerText) || 0, price, 400);
        if (paymentMethodBlock) {
            paymentMethodBlock.style.display = (subscriptionAtLimit || (customPortCheck && customPortCheck.checked)) ? 'block' : 'none';
        }
    }
}

function animateValue(obj, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) window.requestAnimationFrame(step);
    };
    window.requestAnimationFrame(step);
}

function toggleCustomPort() {
    const isChecked = document.getElementById('useCustomPort').checked;
    const container = document.getElementById('customPortContainer');
    isChecked ? $(container).slideDown(400) : $(container).slideUp(400);
    updatePrice();
}

function updateRenewPrice(id) {
    const select = document.getElementById('renewDuration' + id);
    const priceDisplay = document.getElementById('renewPrice' + id);
    if (select && priceDisplay) {
        let price = parseInt(select.options[select.selectedIndex].getAttribute('data-price'));
        priceDisplay.innerText = price;
    }
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        Toast.fire({
            icon: 'success',
            title: 'Copié !'
        });
    });
}

function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(copyText).then(function() {
        Toast.fire({
            icon: 'success',
            title: 'Script copié !'
        });
    });
}

function checkOnlineStatus(accountId) {
    Swal.fire({
        title: 'Analyse en cours',
        text: 'Interrogation du serveur MikroTik...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading()
        }
    });
    const url = `{{ route('user.vpn.check-status', ':id') }}`.replace(':id', accountId);
    fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.is_online) {
                Swal.fire({
                    icon: 'success',
                    title: '✅ Tunnel Connecté',
                    html: `<b>${data.server_name}</b> a confirmé la session active.<br>IP: ${data.remote_ip}`,
                    timer: 4000
                });
                const badge = document.getElementById(`status-badge-${accountId}`);
                if (badge) {
                    badge.innerHTML = '<span class="pulse-dot pulse-active me-2"></span> OPERATIONAL';
                    badge.className = 'badge status-active status-pill d-inline-flex align-items-center';
                }
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: '⚠️ Hors Ligne',
                    text: 'Aucune session active trouvée sur le serveur. Vérifiez la configuration de votre routeur.'
                });
            }
        }).catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Impossible de joindre le serveur de gestion.'
            });
        });
}

function confirmDelete(accountId) {
    Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ea5455',
            cancelButtonColor: '#82868b',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler'
        })
        .then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + accountId).submit();
            }
        })
}

document.addEventListener('DOMContentLoaded', function() {
    updatePrice();
    @if(session('success')) Toast.fire({
        icon: 'success',
        title: 'Opération réussie',
        text: "{{ session('success') }}"
    });
    @endif
    @if(session('error')) Swal.fire({
        icon: 'error',
        title: 'Erreur système',
        text: "{{ session('error') }}",
        confirmButtonColor: '#7367f0'
    });
    @endif
    @if($errors->any())
    let errs = '<div class="text-start">';
    @foreach($errors->all() as $error) errs += '<div class="mb-1 text-danger small"><i class="ti tabler-x me-1"></i> {{ $error }}</div>';
    @endforeach
    errs += '</div>';
    Swal.fire({
        icon: 'warning',
        title: 'Validation',
        html: errs,
        confirmButtonColor: '#7367f0'
    });
    new bootstrap.Modal(document.getElementById('createVpnModal')).show();
    @endif
});
</script>
@endsection