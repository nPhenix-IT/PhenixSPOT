@php
use Illuminate\Support\Facades\Route;
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Connexion')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      
      <!-- Connexion -->
      <div class="card">
        <div class="card-body">
          
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ url('/') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">
                {{ config('variables.templateName') }}
              </span>
            </a>
          </div>
          <!-- /Logo -->

          <h4 class="mb-1">
            Bienvenue sur {{ config('variables.templateName') }} ! 👋
          </h4>
          <p class="mb-6">
            Veuillez vous connecter à votre compte pour continuer.
          </p>

          <!-- Message de session -->
          @if (session('status'))
          <div class="alert alert-success mb-1 rounded-0" role="alert">
            <div class="alert-body">
              {{ session('status') }}
            </div>
          </div>
          @endif

          <form id="formAuthentication" class="mb-4" action="{{ route('login') }}" method="POST">
            @csrf
            
            <!-- Email -->
            <div class="mb-6 form-control-validation">
              <label for="email" class="form-label">Adresse email</label>
              <input type="text"
                     class="form-control @error('email') is-invalid @enderror"
                     id="email"
                     name="email"
                     placeholder="koffi@gmail.com"
                     autofocus
                     value="{{ old('email') }}" />
              @error('email')
              <span class="invalid-feedback" role="alert">
                <span class="fw-medium">{{ $message }}</span>
              </span>
              @enderror
            </div>

            <!-- Mot de passe -->
            <div class="mb-6 form-password-toggle form-control-validation">
              <label class="form-label" for="password">Mot de passe</label>
              <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                <input type="password"
                       id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       name="password"
                       placeholder="••••••••••••"
                       aria-describedby="password" />
                <span class="input-group-text cursor-pointer">
                  <i class="icon-base ti tabler-eye-off"></i>
                </span>
              </div>
              @error('password')
              <span class="invalid-feedback" role="alert">
                <span class="fw-medium">{{ $message }}</span>
              </span>
              @enderror
            </div>

            <!-- Options -->
            <div class="my-8">
              <div class="d-flex justify-content-between">
                <div class="form-check mb-0 ms-2">
                  <input class="form-check-input"
                         type="checkbox"
                         id="remember-me"
                         name="remember"
                         {{ old('remember') ? 'checked' : '' }} />
                  <label class="form-check-label" for="remember-me">
                    Se souvenir de moi
                  </label>
                </div>

                <!--@if (Route::has('password.request'))-->
                <!--<a href="{{ route('password.request') }}">-->
                <!--  <p class="mb-0">Mot de passe oublié ?</p>-->
                <!--</a>-->
                <!--@endif-->
              </div>
            </div>

            <!-- Bouton -->
            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">
                Se connecter
              </button>
            </div>
          </form>

          <p class="text-center">
            <span>Nouveau sur notre plateforme ?</span>
            @if (Route::has('register'))
            <a href="{{ route('register') }}">
              <span>Créer un compte</span>
            </a>
            @endif
          </p>

          <!--<div class="divider my-6">-->
          <!--  <div class="divider-text">ou</div>-->
          <!--</div>-->

          <!--<div class="d-flex justify-content-center">-->
          <!--  <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-facebook me-1_5">-->
          <!--    <i class="icon-base ti tabler-brand-facebook-filled icon-20px"></i>-->
          <!--  </a>-->

          <!--  <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-twitter me-1_5">-->
          <!--    <i class="icon-base ti tabler-brand-twitter-filled icon-20px"></i>-->
          <!--  </a>-->

          <!--  <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-github me-1_5">-->
          <!--    <i class="icon-base ti tabler-brand-github-filled icon-20px"></i>-->
          <!--  </a>-->

          <!--  <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-google-plus">-->
          <!--    <i class="icon-base ti tabler-brand-google-filled icon-20px"></i>-->
          <!--  </a>-->
          <!--</div>-->

        </div>
      </div>
      <!-- /Connexion -->
    </div>
  </div>
</div>
@endsection