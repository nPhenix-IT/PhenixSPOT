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
@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-4">
                <h2>Forfaits WiFi de {{ $user->name }}</h2>
                <p>Sélectionnez un forfait pour vous connecter.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success text-center"><h4>{{ session('success') }}</h4></div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">Veuillez corriger les erreurs ci-dessous.</div>
            @endif

            <form action="{{ route('public.sale.purchase', $user->slug) }}" method="POST">
                @csrf
                <div class="row">
                    @forelse($profiles as $profile)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">{{ $profile->name }}</h5>
                                <h3 class="card-price">{{ $profile->price == 0 ? 'Gratuit' : number_format($profile->price, 0, ',', ' ') . ' FCFA' }}</h3>
                                <ul class="list-unstyled mt-3 mb-4">
                                    <li>{{ $profile->rate_limit ?? 'Vitesse par défaut' }}</li>
                                    <li>{{ $profile->data_limit ? round($profile->data_limit / (1024*1024*1024), 2) . ' Go' : 'Données illimitées' }}</li>
                                </ul>
                                <div class="form-check mt-3">
                                    <input name="profile_id" class="form-check-input" type="radio" value="{{ $profile->id }}" id="profile{{$profile->id}}" required>
                                    <label class="form-check-label" for="profile{{$profile->id}}">Sélectionner</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12"><div class="alert alert-warning">Aucun forfait disponible.</div></div>
                    @endforelse
                </div>

                @if($profiles->isNotEmpty())
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">Votre nom</label><input type="text" name="customer_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Votre numéro</label><input type="text" name="customer_number" class="form-control" required></div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Payer avec Money Fusion</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection