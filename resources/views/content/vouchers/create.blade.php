@extends('layouts/layoutMaster')
@section('title', 'Gérer les Vouchers')
@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Gestion Hotspot /</span> Gérer les Vouchers</h4>

<div class="card mb-4">
  <h5 class="card-header">Générer de nouveaux vouchers</h5>
  <div class="card-body">
    <form action="{{ route('user.vouchers.store') }}" method="POST" class="row g-3">
      @csrf
      <div class="col-md-8">
        <label for="profile_id" class="form-label">Profil</label>
        <select name="profile_id" class="form-select" required>
          <option value="" selected disabled>Sélectionner un profil...</option>
          @foreach ($profiles as $profile)
            <option value="{{ $profile->id }}">{{ $profile->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label for="quantity" class="form-label">Quantité</label>
        <input type="number" name="quantity" class="form-control" value="10" min="1" max="500" required>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary">Générer</button>
      </div>
    </form>
  </div>
</div>

<form id="bulk-action-form" action="{{ route('user.vouchers.bulk-delete') }}" method="POST">
  @csrf
  @method('DELETE')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
      <h5 class="mb-0">Liste des vouchers</h5>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger" id="delete-selected-btn" disabled><i class="icon-base ti tabler-trash me-1"></i> Supprimer la sélection</button>
      </div>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table table-striped">
        <thead class="table-light">
          <tr>
            <th><input class="form-check-input" type="checkbox" id="select-all-checkbox"></th>
            <th>Code</th>
            <th>Profil</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($vouchers as $voucher)
            <tr>
              <td><input class="form-check-input voucher-checkbox" type="checkbox" value="{{ $voucher->id }}" name="ids[]"></td>
              <td><strong>{{ $voucher->code }}</strong></td>
              <td>{{ $voucher->profile->name ?? 'N/A' }}</td>
              <td><span class="badge bg-label-success">{{ $voucher->status }}</span></td>
              <td>
                <form action="{{ route('user.vouchers.destroy', $voucher->id) }}" method="POST" class="d-inline delete-form">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-icon item-delete"><i class="icon-base ti tabler-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center">Aucun voucher généré.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      {{ $vouchers->links() }}
    </div>
  </div>
</form>
@endsection
