@extends('layouts/layoutMaster')
@section('title', 'Mon Portefeuille')
@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Gestion /</span> Mon Portefeuille</h4>

<div class="row">
  <div class="col-md-4 mb-4">
    <div class="card">
      <div class="card-body text-center">
        <h5 class="card-title">Solde Actuel</h5>
        <h2 class="mb-0">{{ number_format($wallet->balance, 0, ',', ' ') }} FCFA</h2>
        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#withdrawModal">Demander un retrait</button>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <h5 class="card-header">Historique des Transactions</h5>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Description</th>
              <th>Montant</th>
            </tr>
          </thead>
          <tbody>
            @forelse($transactions as $transaction)
            <tr>
              <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
              <td>{{ $transaction->description }}</td>
              <td>
                @if($transaction->type == 'credit')
                  <span class="badge bg-label-success">+{{ number_format($transaction->amount, 0, ',', ' ') }}</span>
                @else
                  <span class="badge bg-label-danger">-{{ number_format($transaction->amount, 0, ',', ' ') }}</span>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-center">Aucune transaction.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer">
        {{ $transactions->links() }}
      </div>
    </div>
  </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Demande de Retrait</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('user.wallet.withdraw') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Montant (Minimum 5000 FCFA)</label>
            <input type="number" name="amount" class="form-control" min="5000" max="{{ $wallet->balance }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Moyen de paiement</label>
            <select name="payment_method" class="form-select" required>
              <option value="Orange Money">Orange Money</option>
              <option value="MTN Money">MTN Money</option>
              <option value="Moov Money">Moov Money</option>
              <option value="Wave Money">Wave Money</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Numéro de téléphone</label>
            <input type="text" name="phone_number" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary">Soumettre la demande</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection