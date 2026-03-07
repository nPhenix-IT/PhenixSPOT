@extends('layouts/layoutMaster')
@section('title', 'Historique SMS')

@section('content')
@php
  $walletBalance = (float) (auth()->user()->wallet->balance ?? 0);
@endphp

<div class="d-flex justify-content-between align-items-center py-3 mb-4">
  <h4 class="mb-0"><span class="text-muted fw-light">Utilisateur /</span> Historique SMS</h4>
  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#smsPackModal">
    <i class="icon-base ti tabler-package me-1"></i>Acheter un pack SMS
  </button>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4 mb-4 align-items-stretch">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <small class="text-muted">Solde crédits SMS</small>
        <h4 class="mt-1 mb-0">{{ number_format((float) auth()->user()->sms_credit_balance, 0, ',', ' ') }} SMS</h4>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <small class="text-muted">Solde wallet</small>
        <h4 class="mt-1 mb-0">{{ number_format($walletBalance, 0, ',', ' ') }} FCFA</h4>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Recharges SMS</h5></div>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>Date</th><th>Transaction</th><th>Pack</th><th>Méthode</th><th>Base</th><th>Frais</th><th>Montant</th><th>Crédits</th><th>Statut</th></tr></thead>
      <tbody>
      @forelse($recharges as $recharge)
        @php
          $baseAmount = (float) data_get($recharge->meta, 'base_amount_fcfa', $recharge->amount_fcfa);
          $feeAmount = (float) data_get($recharge->meta, 'fee_amount_fcfa', 0);
        @endphp
        <tr>
          <td>{{ $recharge->created_at?->format('d/m/Y H:i') }}</td>
          <td><code>{{ $recharge->transaction_id }}</code></td>
          <td>{{ $recharge->package?->name ?: 'Pack #' . $recharge->sms_package_id }}</td>
          <td>{{ strtoupper($recharge->payment_method) }}</td>
          <td>{{ number_format($baseAmount, 0, ',', ' ') }} FCFA</td>
          <td>{{ number_format($feeAmount, 0, ',', ' ') }} FCFA</td>
          <td>{{ number_format((float) $recharge->amount_fcfa, 0, ',', ' ') }} FCFA</td>
          <td>{{ number_format((int) $recharge->credits, 0, ',', ' ') }}</td>
          <td><span class="badge bg-label-{{ $recharge->status === 'completed' ? 'success' : ($recharge->status === 'failed' ? 'danger' : 'warning') }}">{{ $recharge->status }}</span></td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center text-muted">Aucune recharge SMS.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-body">{{ $recharges->links() }}</div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Transactions SMS</h5></div>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>Date</th><th>Type</th><th>Statut</th><th>Destinataire</th><th>Coût</th><th>Solde après</th><th>Contexte</th></tr></thead>
      <tbody>
        @forelse($transactions as $tx)
          <tr>
            <td>{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
            <td>{{ strtoupper($tx->type) }}</td>
            <td><span class="badge bg-label-{{ $tx->status === 'sent' || $tx->status === 'credited' ? 'success' : ($tx->status === 'blocked' ? 'danger' : 'warning') }}">{{ $tx->status }}</span></td>
            <td>{{ $tx->recipient ?: '-' }}</td>
            <td>{{ number_format((float) $tx->amount_fcfa, 0, ',', ' ') }} FCFA</td>
            <td>{{ number_format((float) $tx->balance_after, 0, ',', ' ') }} SMS</td>
            <td>{{ $tx->context ?: '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted">Aucune transaction SMS.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-body">{{ $transactions->links() }}</div>
</div>

<div class="modal fade" id="smsPackModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="smsPackModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="smsPackModalLabel">Acheter un pack SMS</h5>
          <small class="text-muted">Frais MoneyFusion: {{ rtrim(rtrim(number_format((float) $moneyfusionPayinPercent, 2, ".", ""), "0"), ".") }}%</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          @forelse($packages as $pack)
            @php
              $mfFee = round(((float) $pack->price_fcfa * (float) $moneyfusionPayinPercent) / 100, 0);
              $mfTotal = (float) $pack->price_fcfa + $mfFee;
            @endphp
            <div class="col-md-6 col-xl-4">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 fw-semibold">{{ $pack->name }}</h6>
                    <span class="badge bg-label-primary">{{ number_format($pack->credits, 0, ',', ' ') }} SMS</span>
                  </div>
                  <p class="text-muted small mb-3">{{ $pack->description ?: 'Pack SMS prêt à l’emploi.' }}</p>

                  <div class="bg-label-secondary rounded p-2 mb-3">
                    <div class="d-flex justify-content-between"><span>Prix pack</span><strong>{{ number_format((float) $pack->price_fcfa, 0, ',', ' ') }} FCFA</strong></div>
                    <div class="d-flex justify-content-between"><span>Frais MoneyFusion</span><span>{{ number_format($mfFee, 0, ',', ' ') }} FCFA</span></div>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2"><span>Total MoneyFusion</span><strong>{{ number_format($mfTotal, 0, ',', ' ') }} FCFA</strong></div>
                  </div>

                  <div class="mt-auto d-grid gap-2">
                    @if($walletBalance > 0)
                      <form method="POST" action="{{ route('user.sms-recharges.buy-wallet', $pack) }}">
                        @csrf
                        <button class="btn btn-primary w-100" type="submit">
                          <i class="icon-base ti tabler-wallet me-1"></i>Acheter via Wallet
                        </button>
                      </form>
                    @endif
                    <form method="POST" action="{{ route('user.sms-recharges.buy-moneyfusion', $pack) }}">
                      @csrf
                      <button class="btn btn-outline-success w-100" type="submit">
                        <i class="icon-base ti tabler-credit-card me-1"></i>Payer via MoneyFusion
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          @empty
            <div class="col-12"><div class="alert alert-warning mb-0">Aucun pack SMS actif.</div></div>
          @endforelse
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
@endsection
