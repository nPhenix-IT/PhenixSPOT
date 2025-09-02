@extends('layouts/layoutMaster')

@section('title', 'Tableau de Bord')

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Accueil /</span> Tableau de Bord
</h4>

<div class="row">
    @if(auth()->user()->hasRole(['Super-admin', 'Admin']))
        {{-- Widgets pour l'Administrateur --}}
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Utilisateurs Actifs</p>
                            <h4 class="mb-0">{{ $data['total_users'] }}</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded-pill p-2">
                                <i class="icon-base ti tabler-users ti-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Retraits en Attente</p>
                            <h4 class="mb-0">{{ $data['pending_withdrawals'] }}</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-warning rounded-pill p-2">
                                <i class="icon-base ti tabler-transfer-out ti-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Widgets pour l'Utilisateur --}}
        <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Solde du Portefeuille</p>
                            <h4 class="mb-0">{{ number_format($data['wallet_balance'], 0, ',', ' ') }} FCFA</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded-pill p-2">
                                <i class="icon-base ti tabler-wallet ti-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Routeurs</p>
                            <h4 class="mb-0">{{ $data['router_count'] }}</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded-pill p-2">
                                <i class="icon-base ti tabler-router ti-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Vouchers Actifs</p>
                            <h4 class="mb-0">{{ $data['active_vouchers'] }}</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded-pill p-2">
                                <i class="icon-base ti tabler-ticket ti-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des dernières transactions -->
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">Dernières Transactions</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['latest_transactions'] as $transaction)
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
                            <tr><td colspan="3" class="text-center">Aucune transaction récente.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        {{-- Widgets pour l'Utilisateur --}}
        <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Solde du Portefeuille</p>
                            <h4 class="mb-0">{{ number_format($data['wallet_balance'], 0, ',', ' ') }} FCFA</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-success rounded-pill p-2">
                                <i class="icon-base ti tabler-wallet ti-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Routeurs</p>
                            <h4 class="mb-0">{{ $data['router_count'] }}</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-info rounded-pill p-2">
                                <i class="icon-base ti tabler-router ti-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="card-info">
                            <p class="card-text">Vouchers Actifs</p>
                            <h4 class="mb-0">{{ $data['active_vouchers'] }}</h4>
                        </div>
                        <div class="card-icon">
                            <span class="badge bg-label-primary rounded-pill p-2">
                                <i class="icon-base ti tabler-ticket ti-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des dernières transactions -->
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">Dernières Transactions</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['latest_transactions'] as $transaction)
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
                            <tr><td colspan="3" class="text-center">Aucune transaction récente.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection