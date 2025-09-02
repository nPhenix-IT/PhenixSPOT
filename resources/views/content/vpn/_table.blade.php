<div class="table-responsive text-nowrap">
  <table class="table table-striped">
    <thead class="table-light">
      <tr>
        <th>Utilisateur</th>
        <th>Serveur</th>
        <th>IP Locale</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody class="table-border-bottom-0">
      @forelse ($vpnAccounts as $account)
        <tr>
          <td><strong>{{ $account->username }}</strong></td>
          <td>{{ $account->vpnServer->name ?? 'N/A' }}</td>
          <td><span class="badge bg-label-dark">{{ $account->local_ip_address }}</span></td>
          <td><span class="badge bg-label-{{ $account->is_active ? 'success' : 'secondary' }}">{{ $account->is_active ? 'Actif' : 'Inactif' }}</span></td>
          <td>
            <form action="{{ route('user.vpn-accounts.destroy', $account->id) }}" method="POST" class="delete-form">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-sm btn-icon item-delete" title="Supprimer"><i class="ti ti-trash"></i></button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center">Aucun compte VPN créé.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="card-footer">
  {{ $vpnAccounts->links() }}
</div>