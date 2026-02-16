@php
use Illuminate\Support\Facades\Route;
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Inscription - PhenixSPOT')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
<!-- intl-tel-input CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/css/intlTelInput.css">
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
<style>
    .iti { width: 100%; }
    .iti__country-list { z-index: 2000; }
    
    /* Élargir le conteneur pour accueillir les 2 colonnes confortablement */
    @media (min-width: 768px) {
      .authentication-wrapper.authentication-basic .authentication-inner {
        max-width: 850px;
      }
    }
</style>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-auth.js'])
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- 1. CONFIGURATION DU NUMÉRO WHATSAPP (Champ visible) ---
    const whatsappInput = document.querySelector("#whatsapp_number_visible");
    let itiWhatsapp;

    if (whatsappInput) {
        itiWhatsapp = window.intlTelInput(whatsappInput, {
            initialCountry: "ci",
            preferredCountries: ["ci", "sn", "bf", "ml", "tg", "bj", "fr"],
            separateDialCode: true,
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/utils.js",
        });
    }

    // --- 2. CONFIGURATION DU NUMÉRO MOBILE MONEY / PHONE (Champ visible) ---
    const phoneInput = document.querySelector("#phone_number_visible");
    let itiPhone;

    if (phoneInput) {
        itiPhone = window.intlTelInput(phoneInput, {
            initialCountry: "ci",
            preferredCountries: ["ci", "sn", "bf", "ml", "tg", "bj", "fr"],
            separateDialCode: true,
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/utils.js",
        });
    }

    // --- 3. GESTION DE LA SOUMISSION DU FORMULAIRE ---
    const form = document.querySelector("#formAuthentication");
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Gestion WhatsApp -> vers 'whatsapp_number'
            if (itiWhatsapp) {
                const fullNumberWA = itiWhatsapp.getNumber();
                
                // Champ caché pour whatsapp_number
                let hiddenWhatsapp = document.querySelector('input[name="whatsapp_number"]');
                if (!hiddenWhatsapp) {
                    hiddenWhatsapp = document.createElement('input');
                    hiddenWhatsapp.type = 'hidden';
                    hiddenWhatsapp.name = 'whatsapp_number';
                    form.appendChild(hiddenWhatsapp);
                }
                hiddenWhatsapp.value = fullNumberWA;
            }

            // Gestion Mobile Money -> vers 'phone_number'
            if (itiPhone) {
                const fullNumberPhone = itiPhone.getNumber();
                
                // Champ caché pour phone_number
                let hiddenPhone = document.querySelector('input[name="phone_number"]');
                if (!hiddenPhone) {
                    hiddenPhone = document.createElement('input');
                    hiddenPhone.type = 'hidden';
                    hiddenPhone.name = 'phone_number';
                    form.appendChild(hiddenPhone);
                }
                hiddenPhone.value = fullNumberPhone;
            }

            // Soumission du formulaire
            this.submit();
        });
    }
});
</script>
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <!-- Register Card -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ url('/') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1 text-center">L'aventure commence ici 🚀</h4>
          <p class="mb-6 text-center">Créez votre compte pour explorer nos services !</p>

          <form id="formAuthentication" class="mb-4" action="{{ route('register') }}" method="POST">
            @csrf
            
            <div class="row">
                <!-- Name (Nom d'utilisateur) -->
                <div class="col-md-6 mb-6 form-control-validation">
                  <label for="name" class="form-label">Nom d'utilisateur</label>
                  <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Entrez votre nom d'utilisateur" autofocus value="{{ old('name') }}" required />
                  @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Email -->
                <div class="col-md-6 mb-6 form-control-validation">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Entrez votre email" value="{{ old('email') }}" required />
                  @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- WhatsApp Number -->
                <div class="col-md-6 mb-6 form-control-validation">
                  <label for="whatsapp_number_visible" class="form-label text-success fw-bold">Numéro WhatsApp (Code d'activation)</label>
                  <div class="input-group">
                    <!-- Nom 'whatsapp_raw' pour éviter conflit avec le champ caché 'whatsapp_number' -->
                    <input type="tel" id="whatsapp_number_visible" class="form-control @error('whatsapp_number') is-invalid @enderror" name="whatsapp_number" value="{{ old('whatsapp_number') }}" required style="width: 100%;" />
                    @error('whatsapp_number')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <!-- Phone Number (Mobile Money) -->
                <div class="col-md-6 mb-6 form-control-validation">
                  <label for="phone_number_visible" class="form-label">Numéro Téléphone</label>
                  <div class="input-group">
                    <!-- Nom 'phone_raw' pour éviter conflit avec le champ caché 'phone_number' -->
                    <input type="tel" id="phone_number_visible" class="form-control @error('phone_number') is-invalid @enderror" name="phone_number" value="{{ old('phone_number') }}" required style="width: 100%;" />
                    @error('phone_number')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                <!-- Password -->
                <div class="col-md-6 mb-6 form-password-toggle form-control-validation">
                  <label class="form-label" for="password">Mot de passe</label>
                  <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                    <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" 
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" 
                      aria-describedby="password" required />
                    <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                  </div>
                  @error('password')
                    <span class="invalid-feedback">{{ $message }}</span>
                  @enderror
                </div>

                <!-- Password Confirmation -->
                <div class="col-md-6 mb-6 form-password-toggle form-control-validation">
                  <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                  <div class="input-group input-group-merge">
                    <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" 
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" 
                      aria-describedby="password" required />
                    <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                  </div>
                </div>
            </div>

            <!-- Terms -->
            <div class="my-8">
              <div class="form-check mb-0 ms-2">
                <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms" required />
                <label class="form-check-label" for="terms-conditions">
                  J'accepte les <a href="javascript:void(0);">conditions d'utilisation & politique de confidentialité</a>
                </label>
              </div>
            </div>

            <!-- Bouton centré et moins large -->
            <div class="d-flex justify-content-center">
              <button class="btn btn-primary px-5" type="submit">S'inscrire</button>
            </div>
          </form>

          <p class="text-center">
            <span>Déjà un compte ?</span>
            <a href="{{ route('login') }}">
              <span>Connectez-vous</span>
            </a>
          </p>

        </div>
      </div>
      <!-- Register Card -->
    </div>
  </div>
</div>
@endsection