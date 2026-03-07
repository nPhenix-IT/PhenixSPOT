@extends('layouts/layoutMaster')
@section('title', 'Gestion SMS')

@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Packs SMS</h4>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Paramètres globaux SMS</h5></div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.sms-packages.settings.update') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">Coût unitaire (FCFA)</label>
            <input class="form-control" type="number" step="0.01" min="0" name="unit_cost_fcfa" value="{{ old('unit_cost_fcfa', $settings->unit_cost_fcfa) }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Sender name par défaut</label>
            <input class="form-control" type="text" name="default_sender_name" value="{{ old('default_sender_name', $settings->default_sender_name) }}" maxlength="20">
          </div>
          <button class="btn btn-primary">Enregistrer</button>
        </form>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header"><h5 class="mb-0">Créer un pack SMS</h5></div>
      <div class="card-body">
        <form method="POST" action="{{ route('admin.sms-packages.store') }}">
          @csrf
          <div class="mb-3"><label class="form-label">Nom</label><input class="form-control" name="name" required></div>
          <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Crédits SMS</label><input class="form-control" type="number" name="credits" min="1" required></div>
            <div class="col-md-6"><label class="form-label">Prix FCFA</label><input class="form-control" type="number" step="0.01" min="0" name="price_fcfa" required></div>
          </div>
          <div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Pack actif</label></div>
          <button class="btn btn-success mt-3">Créer le pack</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Liste des packs</h5></div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Pack</th><th>Crédits</th><th>Prix</th><th>État</th><th>Action</th></tr></thead>
          <tbody>
          @forelse($packages as $pack)
            <tr>
              <td>{{ $pack->name }}<br><small class="text-muted">{{ $pack->description }}</small></td>
              <td>{{ number_format($pack->credits, 0, ',', ' ') }}</td>
              <td>{{ number_format($pack->price_fcfa, 0, ',', ' ') }} FCFA</td>
              <td><span class="badge bg-label-{{ $pack->is_active ? 'success' : 'secondary' }}">{{ $pack->is_active ? 'Actif' : 'Inactif' }}</span></td>
              <td>
                <form method="POST" action="{{ route('admin.sms-packages.destroy', $pack) }}" onsubmit="return confirm('Supprimer ce pack ?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted">Aucun pack SMS.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
