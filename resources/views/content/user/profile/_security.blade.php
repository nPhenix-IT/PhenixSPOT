@section('content')
<div class="row">
  <div class="col-md-12">
        <div class="nav-align-top">
          <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">
            <li class="nav-item">
              <a class="nav-link {{ $tab == 'account' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'account']) }}"><i class="icon-base ti tabler-users icon-sm me-1_5"></i> Compte</a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ $tab == 'security' ? 'active' : '' }}" href="javascript:void(0);"><i class="icon-base ti tabler-lock icon-sm me-1_5"></i> Sécurité</a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ $tab == 'billing' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'billing']) }}"><i class="icon-base ti tabler-bookmark icon-sm me-1_5"></i> Facturation & Forfaits</a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ $tab == 'notifications' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'notifications']) }}"><i class="icon-base ti tabler-bell icon-sm me-1_5"></i> Notifications</a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ $tab == 'connections' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'connections']) }}"><i class="icon-base ti tabler-link icon-sm me-1_5"></i> Connexions</a>
            </li>
          </ul>
        </div>
        <div class="card mb-6">
          <h5 class="card-header">Change Password</h5>
          <div class="card-body pt-1">
            <form action="{{ route('user.profile.security.password.update') }}" method="POST">
              @csrf
              <div class="row mb-4">
                <div class="col-md-6">
                  <label class="form-label" for="current_password">Mot de passe actuel</label>
                  <input class="form-control" type="password" name="current_password" id="current_password" required />
                  @error('current_password')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
              </div>
        
              <div class="row gy-4">
                <div class="col-md-6">
                  <label class="form-label" for="password">Nouveau mot de passe</label>
                  <input class="form-control" type="password" id="password" name="password" required />
                  @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="password_confirmation">Confirmer le nouveau mot de passe</label>
                  <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required />
                </div>
              </div>
              <div class="mt-5">
                <button type="submit" class="btn btn-primary me-2">Mettre à jour</button>
              </div>
            </form>
          </div>
        </div>
        
        <div class="card">
          <h5 class="card-header text-danger">Delete Account</h5>
          <div class="card-body">
                <div class="alert alert-warning mb-4">
                  <h6 class="alert-heading mb-1">Êtes-vous sûr de vouloir supprimer votre compte ?</h6>
                  <p class="mb-0">Cette action est irréversible.</p>
                </div>
                <form action="{{ route('user.profile.security.delete-account') }}" method="POST">
                  @csrf
                  @method('DELETE')
                  <div class="mb-3 col-md-6">
                    <label for="delete_password" class="form-label">Mot de passe de confirmation</label>
                    <input type="password" class="form-control" id="delete_password" name="password" required />
                    @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                  </div>
                  <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="confirm_delete" id="confirm_delete" value="1" required />
                    <label class="form-check-label" for="confirm_delete">Je confirme la suppression définitive de mon compte</label>
                    @error('confirm_delete')<div class="text-danger small">{{ $message }}</div>@enderror
                  </div>
                  <button type="submit" class="btn btn-danger">Supprimer mon compte</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection