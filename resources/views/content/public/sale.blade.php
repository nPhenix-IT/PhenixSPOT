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
                <h2 style="color: {{ optional($settings)->primary_color ?? '#1f2937' }}">
                    {{ optional($settings)->title ?: "Forfaits WiFi de {$user->name}" }}
                </h2>
                <p>{{ optional($settings)->description ?: 'Sélectionnez un forfait pour vous connecter.' }}</p>
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
                    @php
                        $commissionPayer = optional($settings)->commission_payer ?? 'seller';
                        $commissionAmount = round(($profile->price * $commissionPercent) / 100, 2);
                        $displayPrice = $commissionPayer === 'client'
                            ? $profile->price + $commissionAmount
                            : $profile->price;
                    @endphp
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">{{ $profile->name }}</h5>
                                <h3 class="card-price">
                                    {{ $displayPrice == 0 ? 'Gratuit' : number_format($displayPrice, 0, ',', ' ') . ' FCFA' }}
                                </h3>
                                <div class="text-muted small mb-2">
                                    @if ($commissionPayer === 'client' && $commissionAmount > 0)
                                        Prix incluant {{ number_format($commissionAmount, 0, ',', ' ') }} FCFA de commission.
                                    @elseif ($commissionAmount > 0)
                                        Commission prise en charge par le vendeur.
                                    @endif
                                </div>
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
                        <button type="submit" class="btn btn-primary btn-lg"
                          style="background-color: {{ optional($settings)->primary_color ?? '#1f2937' }}; border-color: {{ optional($settings)->primary_color ?? '#1f2937' }};">
                          Payer avec Money Fusion
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection