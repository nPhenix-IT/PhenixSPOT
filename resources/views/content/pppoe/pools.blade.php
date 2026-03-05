@extends('layouts/layoutMaster')

@section('title', 'PPPoE / Pool')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">Pool PPPoE</h4>
      <p class="text-muted mb-0">Gestion des plages IP PPPoE par profil.</p>
    </div>
    <a href="{{ route('user.pppoe.profiles.index') }}" class="btn btn-primary">Gérer les profils</a>
  </div>

  @if(!$hasPppoeAccess)
    <div class="alert alert-warning">Le module PPPoE n'est pas disponible sur votre plan actuel.</div>
  @else
    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Profil</th>
              <th>Pool distant</th>
              <th>Exclusions</th>
              <th>Comptes liés</th>
            </tr>
          </thead>
          <tbody>
            @forelse($profiles as $profile)
              <tr>
                <td>{{ $profile->name }}</td>
                <td>{{ $profile->remote_pool ?: '—' }}</td>
                <td>{{ $profile->pool_exclusions ?: '—' }}</td>
                <td>{{ $profile->accounts_count }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted">Aucun profil PPPoE.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection
