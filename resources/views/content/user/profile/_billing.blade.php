@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'account' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'account']) }}"><i class="icon-base ti tabler-users icon-sm me-1_5"></i> Compte</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'security' ? 'active' : '' }}" href="{{ route('user.profile', ['tab' => 'security']) }}"><i class="icon-base ti tabler-lock icon-sm me-1_5"></i> Sécurité</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $tab == 'billing' ? 'active' : '' }}" href="javascript:void(0);"><i class="icon-base ti tabler-bookmark icon-sm me-1_5"></i> Facturation & Forfaits</a>
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
  <h5 class="card-header">Current Plan</h5>
  <div class="card-body">
    @if($subscription && $subscription->plan)
      <div class="row g-4 align-items-center">
        <div class="col-md-7">
          <h5 class="mb-1">{{ $subscription->plan->name }}</h5>
          <p class="text-muted mb-2">{{ $subscription->plan->description ?: 'Plan SaaS actif' }}</p>
          <div class="mb-1"><strong>Statut :</strong> {{ ucfirst($subscription->status) }}</div>
          <div class="mb-1"><strong>Début :</strong> {{ optional($subscription->starts_at)->format('d/m/Y H:i') }}</div>
          <div class="mb-0"><strong>Expiration :</strong> {{ optional($subscription->ends_at)->format('d/m/Y H:i') }}</div>
        </div>
        <div class="col-md-5">
          <div class="border rounded p-3 bg-label-primary">
            <div class="fw-semibold mb-2">Tarif</div>
            <h4 class="mb-1">{{ number_format((float) $subscription->plan->price_monthly, 0, ',', ' ') }} FCFA / mois</h4>
            <a href="{{ route('user.plans.index') }}" class="btn btn-sm btn-primary mt-2">Changer de plan</a>
          </div>
        </div>
      </div>
      @else
      <div class="alert alert-info mb-0">
        Aucun abonnement actif pour le moment. <a href="{{ route('user.plans.index') }}">Voir les plans</a>.
      </div>
      @endif
  </div>
</div>

  </div>
</div>

<div class="card">
  <h5 class="card-header">Billing History</h5>
  <div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th>Transaction</th>
          <th>Montant total</th>
          <th>Client</th>
          <th>Téléphone</th>
          <th>Date</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        @forelse($billingHistory as $transaction)
          <tr>
            <td>{{ $transaction->transaction_id }}</td>
            <td>{{ number_format((float) $transaction->total_price, 0, ',', ' ') }} FCFA</td>
            <td>{{ $transaction->customer_name ?: '—' }}</td>
            <td>{{ $transaction->customer_number ?: '—' }}</td>
            <td>{{ optional($transaction->created_at)->format('d/m/Y H:i') }}</td>
            <td><span class="badge bg-label-success">{{ ucfirst($transaction->status) }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Aucun historique de facturation disponible.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection