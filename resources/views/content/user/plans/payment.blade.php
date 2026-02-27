@extends('layouts/layoutMaster')
@section('title', 'Paiement abonnement')

@section('page-script')
@vite(['resources/assets/js/app-payment-page.js'])
@endsection

@section('content')
@php
    $price = ($duration === 'annually') ? (float) $plan->price_annually : (float) $plan->price_monthly;
    $moneyfusionFeePercent = (float) config('fees.moneyfusion_payin_percent', 3);
    $walletBalance = (float) ($wallet->balance ?? 0);
@endphp

<div class="row g-0 rounded-4 overflow-hidden border bg-white shadow-sm">
    <div class="col-lg-6 p-4 p-lg-5 bg-body-secondary">
        <a href="{{ route('user.plans.index') }}" class="text-decoration-none d-inline-flex align-items-center gap-2 mb-4">
            <i class="ti tabler-arrow-left"></i>
            <strong>Retour aux plans</strong>
        </a>

        <h5 class="text-muted mb-1">Abonnement {{ strtoupper($plan->name) }}</h5>
        <div class="d-flex align-items-end gap-2 mb-4">
            <h1 class="mb-0 fw-bolder" id="left-total-price">{{ number_format($price, 0, ',', ' ') }} FCFA</h1>
            <span class="text-muted mb-2">{{ $duration === 'annually' ? 'par an' : 'par mois' }}</span>
        </div>

        <div class="border-top border-bottom py-3 mb-3">
            <div class="d-flex justify-content-between mb-2">
                <span>{{ strtoupper($plan->name) }} ({{ $duration === 'annually' ? 'Annuel' : 'Mensuel' }})</span>
                <strong id="original-price" data-price="{{ $price }}">{{ number_format($price, 0, ',', ' ') }} FCFA</strong>
            </div>
            <div id="discount-row" class="d-flex justify-content-between text-success" style="display:none;">
                <span>Réduction</span>
                <strong id="discount-amount">- 0 FCFA</strong>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <span>Frais MoneyFusion (<span id="fee-percent-label">{{ rtrim(rtrim(number_format($moneyfusionFeePercent, 2, '.', ''), '0'), '.') }}</span>%)</span>
                <strong id="transaction-fee" data-fee-percent="{{ $moneyfusionFeePercent }}">{{ number_format(round(($price * $moneyfusionFeePercent) / 100, 0), 0, ',', ' ') }} FCFA</strong>
            </div>
        </div>

        <div class="d-flex justify-content-between fs-5 fw-bold mb-3">
            <span>Total à payer aujourd'hui</span>
            <span id="final-price" data-total="{{ $price }}">{{ number_format($price + round(($price * $moneyfusionFeePercent) / 100, 0), 0, ',', ' ') }} FCFA</span>
        </div>

        <div class="mb-3">
            <label for="coupon_code" class="form-label">Code promo</label>
            <div class="input-group">
                <input type="text" id="coupon_code" class="form-control" placeholder="Entrez votre code promo">
                <button class="btn btn-outline-primary" type="button" id="apply-coupon-btn">Appliquer</button>
            </div>
            <div id="coupon-status" class="mt-2 small"></div>
        </div>

        <div class="alert alert-info mb-0">
            Solde Wallet disponible : <strong id="wallet-balance">{{ number_format($walletBalance, 0, ',', ' ') }} FCFA</strong>
        </div>
    </div>

    <div class="col-lg-6 p-4 p-lg-5">
        <h4 class="mb-3">Informations de contact</h4>
        <div class="border rounded-3 px-3 py-2 mb-4 bg-body-tertiary">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Email</small>
                <span class="fw-semibold">{{ auth()->user()->email }}</span>
            </div>
        </div>

        <h4 class="mb-3">Méthode de paiement</h4>
        <div class="border rounded-3 overflow-hidden mb-4" id="paymentMethodSelector">
            <label class="d-flex align-items-center justify-content-between p-3 border-bottom cursor-pointer mb-0">
                <span class="d-flex align-items-center gap-2">
                    <input class="form-check-input payment-channel" type="radio" name="payment_channel" value="moneyfusion" checked>
                    <strong>MoneyFusion</strong>
                </span>
                <span class="d-flex align-items-center gap-2">
                    <img src="{{ asset('assets/img/gateway/om.png') }}" alt="Orange Money" style="height:22px; width:auto;">
                    <img src="{{ asset('assets/img/gateway/mtn.png') }}" alt="MTN Money" style="height:22px; width:auto;">
                    <img src="{{ asset('assets/img/gateway/wave.png') }}" alt="Wave" style="height:22px; width:auto;">
                </span>
            </label>
            <label class="d-flex align-items-center justify-content-between p-3 cursor-pointer mb-0">
                <span class="d-flex align-items-center gap-2">
                    <input class="form-check-input payment-channel" type="radio" name="payment_channel" value="wallet">
                    <strong>Wallet interne</strong>
                </span>
                <span class="badge bg-label-primary">Instantané</span>
            </label>
        </div>

        <button
            class="btn btn-primary w-100 py-3 fs-5"
            id="pay-now-btn"
            data-checkout-url="{{ route('user.payment.checkout', ['plan' => $plan->id, 'duration' => $duration]) }}"
        >
            Payer et souscrire
        </button>

        <p class="text-muted text-center mt-3 mb-0 small">
            En confirmant, vous acceptez les conditions de service et la politique de confidentialité.
        </p>
    </div>
</div>
@endsection