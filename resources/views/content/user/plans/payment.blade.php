@extends('layouts/layoutMaster')
@section('title', 'Paiement')

@section('page-script')
@vite(['resources/assets/js/app-payment-page.js'])
@endsection

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Résumé de la commande</h4>
                </div>
                <div class="card-body">
                    @php
                        $price = ($duration == 'annually') ? $plan->price_annually : $plan->price_monthly;
                        $feePercent = (float) config('fees.moneyfusion_payin_percent', 3);
                        $feeAmount = round(($price * $feePercent) / 100, 2);
                        $payableAmount = $price + $feeAmount;
                    @endphp

                    <div class="d-flex justify-content-between">
                        <p>Plan {{ strtoupper($plan->name) }} ({{ $duration == 'annually' ? 'Annuel' : 'Mensuel' }})</p>
                        <p id="original-price" data-price="{{ $price }}">{{ number_format($price, 0, ',', ' ') }} FCFA</p>
                    </div>

                    <div id="discount-row" class="d-flex justify-content-between text-success" style="display: none !important;">
                        <p>Réduction</p>
                        <p id="discount-amount"></p>
                    </div>

                    <div class="d-flex justify-content-between">
                        <p>Frais de transaction ({{ rtrim(rtrim(number_format($feePercent, 2, '.', ''), '0'), '.') }}%)</p>
                        <p id="transaction-fee" data-fee-percent="{{ $feePercent }}">{{ number_format($feeAmount, 0, ',', ' ') }} FCFA</p>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5>Total à payer</h5>
                        <h5 id="final-price" data-total="{{ $payableAmount }}">{{ number_format($payableAmount, 0, ',', ' ') }} FCFA</h5>
                    </div>

                    <div class="mt-4">
                        <label for="coupon_code" class="form-label">Bon de réduction</label>
                        <div class="input-group">
                            <input type="text" id="coupon_code" class="form-control" placeholder="Entrez votre code promo">
                            <button class="btn btn-outline-primary" type="button" id="apply-coupon-btn">Appliquer</button>
                        </div>
                        <div id="coupon-status" class="mt-2"></div>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="btn btn-success" id="pay-now-btn">Payer {{ number_format($payableAmount, 0, ',', ' ') }} FCFA</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection