@extends('layouts/layoutMaster')
@section('title', 'Statut du Paiement')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    <h2 class="mb-3">Paiement en cours de traitement</h2>
                    <p>Votre transaction est en cours de validation. Vous recevrez une confirmation sous peu.</p>
                    <p>Si votre code n'est pas généré après quelques minutes, veuillez contacter le vendeur.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection