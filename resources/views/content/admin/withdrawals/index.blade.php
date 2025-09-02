@extends('layouts/layoutMaster')
@section('title', 'Demandes de Retrait')
@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Demandes de Retrait</h4>

<div class="card">
    <h5 class="card-header">Gestion des demandes de retrait</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Utilisateur</th>
                    <th>Montant</th>
                    <th>Infos Paiement</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($requests as $request)
                <tr>
                    <td><strong>{{ $request->user->name }}</strong><br><small>{{ $request->user->email }}</small></td>
                    <td>{{ number_format($request->amount, 0, ',', ' ') }} FCFA</td>
                    <td>
                        {{ $request->payment_details['method'] }}<br>
                        <strong>{{ $request->payment_details['phone'] }}</strong>
                    </td>
                    <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                            $statusClasses = ['pending' => 'bg-label-warning', 'approved' => 'bg-label-success', 'rejected' => 'bg-label-danger'];
                        @endphp
                        <span class="badge {{ $statusClasses[$request->status] }}">{{ ucfirst($request->status) }}</span>
                    </td>
                    <td>
                        @if ($request->status == 'pending')
                        <div class="d-flex">
                            <form action="{{ route('admin.withdrawals.approve', $request->id) }}" method="POST" class="me-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">Approuver</button>
                            </form>
                            <form action="{{ route('admin.withdrawals.reject', $request->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger">Rejeter</button>
                            </form>
                        </div>
                        @else
                            Traitée
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center">Aucune demande de retrait pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $requests->links() }}
    </div>
</div>
@endsection