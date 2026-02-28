@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Inscription - PhenixSPOT')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/css/intlTelInput.css">
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
<style>
  .iti { width: 100%; }
  .iti__country-list { z-index: 2000; }
  @media (min-width: 768px) {
    .authentication-wrapper.authentication-basic .authentication-inner { max-width: 480px; }
  }
  /* Garantit que le bouton oeil est cliquable */
  .js-toggle-password { pointer-events: auto; user-select: none; }
</style>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/intlTelInput.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('formAuthentication');
  const phoneVisible = document.getElementById('phone_number_visible');
  const hiddenPhone = document.getElementById('phone_number');

  if (!form || !phoneVisible || !hiddenPhone) return;

  // (Optionnel) détection UI du pays pour l’affichage uniquement (pas pour enregistrer country_code)
  async function detectCountryISO2() {
    try {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 2500);

      const res = await fetch('https://ipapi.co/json/', { signal: controller.signal });
      clearTimeout(timeout);

      if (!res.ok) throw new Error('Geo lookup failed');
      const data = await res.json();
      return (data?.country_code ? String(data.country_code).toLowerCase() : 'ci') || 'ci';
    } catch (e) {
      return 'ci';
    }
  }

  const iti = window.intlTelInput(phoneVisible, {
    initialCountry: "auto",
    geoIpLookup: function(callback) {
      detectCountryISO2().then(iso2 => callback(iso2));
    },
    preferredCountries: ["ci", "sn", "bf", "ml", "tg", "bj", "fr"],
    separateDialCode: true,
    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/utils.js",
  });

  // Restaurer old('phone_number') si présent
  const oldPhone = @json(old('phone_number'));
  if (oldPhone) {
    try { iti.setNumber(oldPhone); } catch (e) {}
  }

  // Au submit, on pousse le numéro E.164 dans phone_number (hidden)
  form.addEventListener('submit', function() {
    hiddenPhone.value = iti.getNumber() || '';
  });

  // Toggle password (ultra robuste)
  function toggleFromButton(btn) {
    const group = btn.closest('.input-group');
    if (!group) return;

    const input = group.querySelector('input');
    const icon  = btn.querySelector('i');
    if (!input || !icon) return;

    const show = (input.type === 'password');
    input.type = show ? 'text' : 'password';

    icon.classList.toggle('tabler-eye-off', !show);
    icon.classList.toggle('tabler-eye', show);
  }

  document.querySelectorAll('.js-toggle-password').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      toggleFromButton(btn);
    }, { passive: false });

    btn.addEventListener('touchstart', function(e) {
      e.preventDefault();
      toggleFromButton(btn);
    }, { passive: false });

    btn.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleFromButton(btn);
      }
    });
  });

  // fallback delegation
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.js-toggle-password');
    if (btn) toggleFromButton(btn);
  });
});
</script>
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ url('/') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
            </a>
          </div>

          <h4 class="mb-1 text-center">L'aventure commence ici 🚀</h4>
          <p class="mb-6 text-center">Créez votre compte pour explorer nos services !</p>

          <form id="formAuthentication" action="{{ route('register') }}" method="POST">
            @csrf

            <div class="mb-3">
              <label for="name" class="form-label">Nom d'utilisateur</label>
              <input type="text"
                     class="form-control @error('name') is-invalid @enderror"
                     id="name"
                     name="name"
                     value="{{ old('name') }}"
                     placeholder="Koffi Stéphane"
                     required>
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email"
                     class="form-control @error('email') is-invalid @enderror"
                     id="email"
                     name="email"
                     value="{{ old('email') }}"
                     placeholder="koffi@gmail.com"
                     required>
              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
              <label for="phone_number_visible" class="form-label">Numéro WhatsApp</label>

              {{-- Input visible sans name --}}
              <input type="tel"
                     id="phone_number_visible"
                     class="form-control @error('phone_number') is-invalid @enderror"
                     placeholder=""
                     required>

              {{-- Le backend détecte country_code ISO2 (CI/SN/FR...) via MaxMind --}}
              <input type="hidden" id="phone_number" name="phone_number" value="{{ old('phone_number') }}">

              @error('phone_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password">Mot de passe</label>
              <div class="input-group input-group-merge">
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••••••"
                       required>
                <span class="input-group-text cursor-pointer js-toggle-password"
                      role="button" tabindex="0" aria-label="Afficher/Masquer le mot de passe">
                  <i class="ti tabler-eye-off"></i>
                </span>
              </div>
              @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
              <div class="input-group input-group-merge">
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       class="form-control"
                       placeholder="••••••••••••"
                       required>
                <span class="input-group-text cursor-pointer js-toggle-password"
                      role="button" tabindex="0" aria-label="Afficher/Masquer la confirmation du mot de passe">
                  <i class="ti tabler-eye-off"></i>
                </span>
              </div>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
              <label class="form-check-label" for="terms">J'accepte les conditions</label>
            </div>

            <div class="d-grid w-100">
              <button type="submit" class="btn btn-primary">S'inscrire</button>
            </div>
          </form>

          <p class="text-center mt-4 mb-0">
            <span>Déjà un compte ?</span>
            <a href="{{ route('login') }}"><span>Connectez-vous</span></a>
          </p>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection