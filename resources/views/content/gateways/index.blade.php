@extends('layouts/layoutMaster')
@section('title', 'Moyens de Paiement')
@section('content')
<h4 class="py-3 mb-4"><span class="text-muted fw-light">Gestion /</span> Moyens de Paiement</h4>

<div class="row">
    @foreach($supportedGateways as $provider)
    <div class="col-md-6 mb-4">
        <div class="card">
            <h5 class="card-header">Configuration de {{ ucfirst($provider) }}</h5>
            <div class="card-body">
                <form action="{{ route('user.payment-gateways.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="provider_name" value="{{ $provider }}">
                    <div class="mb-3">
                        <label class="form-label">Clé API / API Key</label>
                        <input type="password" name="api_key" class="form-control" value="{{ $gateways[$provider]->api_key ?? '' }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clé Secrète / Site ID</label>
                        <input type="password" name="secret_key" class="form-control" value="{{ $gateways[$provider]->secret_key ?? '' }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection