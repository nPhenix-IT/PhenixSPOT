@extends('layouts/layoutMaster')

@section('title', 'PPPoE / PPP')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
@vite('resources/assets/js/app-pppoe-accounts.js')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('createPppoeProfileForm');
  if (!form) return;

  const radios = form.querySelectorAll('input[name="limit_type"]');
  const sessionWrap = document.getElementById('pppoeSessionLimitFields');
  const dataWrap = document.getElementById('pppoeDataLimitFields');

  const syncLimitFields = () => {
    const selected = form.querySelector('input[name="limit_type"]:checked')?.value;
    const showSession = selected === 'both' || selected === 'time';
    const showData = selected === 'both' || selected === 'data';

    sessionWrap.classList.toggle('d-none', !showSession);
    dataWrap.classList.toggle('d-none', !showData);
  };

  radios.forEach(r => r.addEventListener('change', syncLimitFields));
  syncLimitFields();
});
</script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">Module PPPoE / PPP</h4>
      <p class="text-muted mb-0">Provisioning RADIUS + NAS, observabilité sessions, IPAM basique, audit opérationnel.</p>
    </div>
    @if($hasPppoeAccess)
      <div class="d-flex gap-2">
        <button class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#createProfileModal">Nouveau profil PPP</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">Nouveau compte PPPoE</button>
      </div>
    @endif
  </div>

  @if(!$hasPppoeAccess)
    <div class="alert alert-warning">
      Le module PPPoE n'est pas disponible sur votre plan actuel. Activez l'option PPPoE dans votre abonnement.
    </div>
  @else
    <div class="row g-3 mb-4">
      <div class="col-md-3"><div class="card"><div class="card-body"><small>Total comptes</small><h4 class="mb-0">{{ $kpis['accounts_total'] ?? 0 }}</h4></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body"><small>Comptes actifs</small><h4 class="mb-0">{{ $kpis['accounts_active'] ?? 0 }}</h4></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body"><small>Comptes en ligne</small><h4 class="mb-0 text-success">{{ $kpis['accounts_online'] ?? 0 }}</h4></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body"><small>Alertes flapping</small><h4 class="mb-0 text-danger">{{ $kpis['alerts_flapping'] ?? 0 }}</h4></div></div></div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">Comptes PPPoE</h5></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped" id="pppoeAccountsTable" data-source="{{ route('user.pppoe.index') }}">
            <thead>
            <tr>
              <th>Username</th><th>Routeur</th><th>Profil PPP</th><th>IP Fixe</th><th>Connexion</th><th>Statut</th><th>Actions</th>
            </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header"><h6 class="mb-0">Historique sessions PPPoE (20 dernières)</h6></div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead><tr><th>User</th><th>Début</th><th>Fin</th><th>Durée(s)</th><th>In/Out (octets)</th><th>Cause</th></tr></thead>
              <tbody>
              @forelse($sessionHistory as $s)
                <tr>
                  <td>{{ $s->username }}</td>
                  <td>{{ $s->acctstarttime }}</td>
                  <td>{{ $s->acctstoptime ?? '—' }}</td>
                  <td>{{ $s->acctsessiontime ?? 0 }}</td>
                  <td>{{ number_format((float)($s->acctinputoctets ?? 0),0,',',' ') }} / {{ number_format((float)($s->acctoutputoctets ?? 0),0,',',' ') }}</td>
                  <td>{{ $s->acctterminatecause ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted">Aucune session.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header"><h6 class="mb-0">Timeline connexions / déconnexions</h6></div>
          <div class="card-body" style="max-height: 360px; overflow:auto;">
            @forelse($timeline as $event)
              <div class="border-start ps-3 mb-3">
                <div class="fw-semibold">{{ $event->username }} • {{ $event->event === 'connect' ? 'Connexion' : 'Déconnexion' }}</div>
                <div class="small text-muted">{{ $event->at }} — {{ $event->details }}</div>
              </div>
            @empty
              <div class="text-muted">Aucun évènement.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div class="card h-100"><div class="card-header"><h6 class="mb-0">Alarmes - Flapping</h6></div><div class="card-body">
          @forelse(($alarms['flapping'] ?? collect()) as $a)
            <div class="d-flex justify-content-between"><span>{{ $a->username }}</span><span class="badge bg-label-danger">{{ $a->cnt }}</span></div>
          @empty <div class="text-muted">Aucune alarme.</div> @endforelse
        </div></div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100"><div class="card-header"><h6 class="mb-0">Sessions courtes (&lt;120s)</h6></div><div class="card-body">
          @forelse(($alarms['short_sessions'] ?? collect()) as $a)
            <div class="small mb-2"><strong>{{ $a->username }}</strong> - {{ $a->acctsessiontime }}s ({{ $a->acctterminatecause ?? 'n/a' }})</div>
          @empty <div class="text-muted">Aucune alarme.</div> @endforelse
        </div></div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100"><div class="card-header"><h6 class="mb-0">Échecs auth (24h)</h6></div><div class="card-body">
          @forelse(($alarms['auth_failures'] ?? collect()) as $a)
            <div class="small mb-2"><strong>{{ $a->username }}</strong> — {{ $a->reply }} ({{ $a->authdate }})</div>
          @empty <div class="text-muted">Aucun échec auth détecté.</div> @endforelse
        </div></div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-lg-4"><div class="card"><div class="card-header"><h6 class="mb-0">KPI par NAS</h6></div><div class="card-body">@foreach($kpiByNas as $r)<div class="d-flex justify-content-between"><span>{{ $r->label }}</span><span>{{ $r->active }}/{{ $r->accounts }}</span></div>@endforeach</div></div></div>
      <div class="col-lg-4"><div class="card"><div class="card-header"><h6 class="mb-0">KPI par Profil</h6></div><div class="card-body">@foreach($kpiByProfile as $r)<div class="d-flex justify-content-between"><span>{{ $r->label }}</span><span>{{ $r->active }}/{{ $r->accounts }}</span></div>@endforeach</div></div></div>
      <div class="col-lg-4"><div class="card"><div class="card-header"><h6 class="mb-0">KPI par Zone</h6></div><div class="card-body">@foreach($kpiByZone as $r)<div class="d-flex justify-content-between"><span>{{ $r->label }}</span><span>{{ $r->accounts }}</span></div>@endforeach</div></div></div>
    </div>

    <div class="card">
      <div class="card-header"><h6 class="mb-0">Audit opérationnel PPPoE</h6></div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Date</th><th>Action</th><th>Statut</th><th>Message</th></tr></thead>
          <tbody>
          @forelse($recentAudits as $log)
            <tr><td>{{ $log->created_at }}</td><td>{{ $log->action }}</td><td><span class="badge {{ $log->status === 'ok' ? 'bg-label-success' : 'bg-label-warning' }}">{{ $log->status }}</span></td><td>{{ $log->message }}</td></tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted">Aucun log.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>

@if($hasPppoeAccess)
  <div class="modal fade" id="createProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="{{ route('user.pppoe.profiles.store') }}" id="createPppoeProfileForm" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Nouveau profil PPP</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Nom du profil</label><input class="form-control" name="name" required></div>
          <div class="mb-3"><label class="form-label">Prix de vente</label><input class="form-control" type="number" step="0.01" min="0" name="price" value="0" required></div>

          <label class="form-label d-block">Type de limitation</label>
          <div class="mb-3 d-flex gap-3 flex-wrap">
            <label class="form-check"><input class="form-check-input" type="radio" name="limit_type" value="both" checked> <span class="form-check-label">Les deux</span></label>
            <label class="form-check"><input class="form-check-input" type="radio" name="limit_type" value="time"> <span class="form-check-label">Temps</span></label>
            <label class="form-check"><input class="form-check-input" type="radio" name="limit_type" value="data"> <span class="form-check-label">Données</span></label>
            <label class="form-check"><input class="form-check-input" type="radio" name="limit_type" value="unlimited"> <span class="form-check-label">Illimité</span></label>
          </div>

          <div class="mb-3"><label class="form-label">Débit (Upload/Download)</label><input class="form-control" name="rate_limit" placeholder="Ex: 10M/10M"></div>

          <div id="pppoeSessionLimitFields" class="row g-2 mb-3">
            <div class="col-7"><label class="form-label">Durée de session</label><input class="form-control" type="number" min="1" name="session_duration" placeholder="Ex: 24"></div>
            <div class="col-5"><label class="form-label">Unité</label><select class="form-select" name="session_unit"><option value="hours">Heure(s)</option><option value="days">Jour(s)</option><option value="weeks">Semaine(s)</option><option value="months">Mois</option></select></div>
          </div>

          <div id="pppoeDataLimitFields" class="row g-2 mb-3">
            <div class="col-7"><label class="form-label">Quota de données</label><input class="form-control" type="number" min="1" name="data_limit_value" placeholder="Ex: 5"></div>
            <div class="col-5"><label class="form-label">Unité</label><select class="form-select" name="data_unit"><option value="mb">Mo</option><option value="gb">Go</option></select></div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-7"><label class="form-label">Validité</label><input class="form-control" type="number" min="1" name="validity_duration" placeholder="Ex: 30" required></div>
            <div class="col-5"><label class="form-label">Unité</label><select class="form-select" name="validity_unit" required><option value="hours">Heure(s)</option><option value="days">Jour(s)</option><option value="weeks">Semaine(s)</option><option value="months">Mois</option></select></div>
          </div>

          <div class="mb-3"><label class="form-label">Pool distant (start-end)</label><input class="form-control" name="remote_pool" placeholder="10.10.30.2-10.10.30.254"></div>
          <div class="mb-3"><label class="form-label">Exclusions IP (csv)</label><input class="form-control" name="pool_exclusions" placeholder="10.10.30.2,10.10.30.10"></div>
          <div class="mb-3"><label class="form-label">DNS</label><input class="form-control" name="dns_server"></div>
          <div class="mb-3"><label class="form-label">Commentaire</label><textarea class="form-control" name="comment"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-label-secondary" data-bs-dismiss="modal" type="button">Annuler</button><button class="btn btn-primary" type="submit">Créer</button></div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="createAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="{{ route('user.pppoe.accounts.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Nouveau compte PPPoE</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
          <div class="mb-3"><label class="form-label">Mot de passe</label><input class="form-control" name="password" required></div>
          <div class="mb-3"><label class="form-label">Routeur</label><select class="form-select" name="router_id"><option value="">--</option>@foreach($routers as $router)<option value="{{ $router->id }}">{{ $router->name }}</option>@endforeach</select></div>
          <div class="mb-3"><label class="form-label">Profil PPP</label><select class="form-select" name="pppoe_profile_id"><option value="">--</option>@foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->name }} @if($profile->remote_pool)({{ $profile->remote_pool }})@endif</option>@endforeach</select></div>
          <div class="mb-3"><label class="form-label">IP Fixe (laisser vide pour allocation auto)</label><input class="form-control" name="ip_address" placeholder="10.10.10.2"></div>
          <div class="mb-3"><label class="form-label">Expire le</label><input class="form-control" type="datetime-local" name="expires_at"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-label-secondary" data-bs-dismiss="modal" type="button">Annuler</button><button class="btn btn-primary" type="submit">Créer & provisionner</button></div>
      </form>
    </div>
  </div>
@endif
@endsection
