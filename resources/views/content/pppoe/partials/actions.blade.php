<div class="d-flex gap-2">
  <form method="POST" action="{{ route('user.pppoe.accounts.toggle', $account) }}">@csrf
    <button class="btn btn-sm {{ $account->is_active ? 'btn-label-warning' : 'btn-label-success' }}" type="submit">
      {{ $account->is_active ? 'Suspendre' : 'Activer' }}
    </button>
  </form>
  <form method="POST" action="{{ route('user.pppoe.accounts.destroy', $account) }}" onsubmit="return confirm('Supprimer ce compte PPPoE ?')">
    @csrf @method('DELETE')
    <button class="btn btn-sm btn-label-danger" type="submit">Supprimer</button>
  </form>
</div>
