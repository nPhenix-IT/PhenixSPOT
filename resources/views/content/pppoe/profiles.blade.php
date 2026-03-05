@extends('layouts/layoutMaster')

@section('title', 'PPPoE / Profils')

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('createPppoeProfileForm');
  if (!form) return;

  const radios = form.querySelectorAll('input[name="limit_type"]');
  const sessionWrap = document.getElementById('pppoeSessionLimitFields');
  const dataWrap = document.getElementById('pppoeDataLimitFields');

  const syncLimitFields = () => {
    const selected = form.querySelector('input[name="limit_type"]:checked')?.value;
    sessionWrap.classList.toggle('d-none', !(selected === 'both' || selected === 'time'));
    dataWrap.classList.toggle('d-none', !(selected === 'both' || selected === 'data'));
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
      <h4 class="mb-1">Profils PPPoE</h4>
      <p class="text-muted mb-0">CRUD séparé des profils de limitation PPPoE.</p>
    </div>
    @if($hasPppoeAccess)
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProfileModal">Nouveau profil PPP</button>
    @endif
  </div>

  @if(!$hasPppoeAccess)
    <div class="alert alert-warning">Le module PPPoE n'est pas disponible sur votre plan actuel.</div>
  @else
    <div class="card mb-4">
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Type limite</th>
              <th>Débit</th>
              <th>Session (s)</th>
              <th>Data (octets)</th>
              <th>Validité (s)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($profiles as $profile)
              <tr>
                <td>{{ $profile->name }}</td>
                <td>{{ $profile->limit_type }}</td>
                <td>{{ $profile->rate_limit ?: '—' }}</td>
                <td>{{ $profile->session_timeout ?: 0 }}</td>
                <td>{{ $profile->data_limit ?: 0 }}</td>
                <td>{{ $profile->validity_period ?: 0 }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted">Aucun profil.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

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
            <div class="mb-3"><label class="form-label">Débit (Upload/Download)</label><input class="form-control" name="rate_limit"></div>
            <div id="pppoeSessionLimitFields" class="row g-2 mb-3">
              <div class="col-7"><label class="form-label">Durée de session</label><input class="form-control" type="number" min="1" name="session_duration"></div>
              <div class="col-5"><label class="form-label">Unité</label><select class="form-select" name="session_unit"><option value="hours">Heure(s)</option><option value="days">Jour(s)</option><option value="weeks">Semaine(s)</option><option value="months">Mois</option></select></div>
            </div>
            <div id="pppoeDataLimitFields" class="row g-2 mb-3">
              <div class="col-7"><label class="form-label">Quota de données</label><input class="form-control" type="number" min="1" name="data_limit_value"></div>
              <div class="col-5"><label class="form-label">Unité</label><select class="form-select" name="data_unit"><option value="mb">Mo</option><option value="gb">Go</option></select></div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-7"><label class="form-label">Validité</label><input class="form-control" type="number" min="1" name="validity_duration" required></div>
              <div class="col-5"><label class="form-label">Unité</label><select class="form-select" name="validity_unit" required><option value="hours">Heure(s)</option><option value="days">Jour(s)</option><option value="weeks">Semaine(s)</option><option value="months">Mois</option></select></div>
            </div>
            <div class="mb-3"><label class="form-label">Pool distant (start-end)</label><input class="form-control" name="remote_pool"></div>
            <div class="mb-3"><label class="form-label">Exclusions IP (csv)</label><input class="form-control" name="pool_exclusions"></div>
            <div class="mb-3"><label class="form-label">DNS</label><input class="form-control" name="dns_server"></div>
            <div class="mb-3"><label class="form-label">Commentaire</label><textarea class="form-control" name="comment"></textarea></div>
          </div>
          <div class="modal-footer"><button class="btn btn-label-secondary" data-bs-dismiss="modal" type="button">Annuler</button><button class="btn btn-primary" type="submit">Créer</button></div>
        </form>
      </div>
    </div>
  @endif
</div>
@endsection
