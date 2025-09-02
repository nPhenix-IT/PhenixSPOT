@php
$configData = Helper::appClasses();
$customizerHidden = true;
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Acheter un forfait WiFi')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-4">
                <h2>Forfaits WiFi de {{ $user->name }}</h2>
                <p>Sélectionnez un forfait pour vous connecter.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success text-center">
                    <h4>{{ session('success') }}</h4>
                    <p>Veuillez utiliser ce code pour vous connecter au portail captif.</p>
                </div>
            @endif

            <div class="row">
                @forelse($profiles as $profile)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">{{ $profile->name }}</h5>
                            <h3 class="card-price">{{ number_format($profile->price, 0, ',', ' ') }} <span class="fs-6">FCFA</span></h3>
                            <ul class="list-unstyled mt-3 mb-4">
                                <li>{{ $profile->rate_limit ?? 'Vitesse par défaut' }}</li>
                                <li>{{ $profile->data_limit ? round($profile->data_limit / (1024*1024*1024), 2) . ' Go' : 'Données illimitées' }}</li>
                            </ul>
                            <form action="{{ route('public.sale.purchase', $user->slug) }}" method="POST">
                                @csrf
                                <input type="hidden" name="profile_id" value="{{ $profile->id }}">
                                <!--<div class="row mb-3">-->
                                    <!--<div class="col-md-6">-->
                                    <!--    <label class="form-label">Votre nom complet</label>-->
                                    <!--    <input type="text" name="customer_name" class="form-control" required>-->
                                    <!--</div>-->
                                    <div class="col-md-6">
                                        <label class="form-label">Votre numéro de téléphone</label>
                                        <input type="text" name="customer_number" class="form-control" required>
                                    </div>
                                <!--</div>-->
                                <button type="submit" class="btn btn-primary">Acheter maintenant</button>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-warning">Aucun forfait n'est disponible pour le moment.</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection