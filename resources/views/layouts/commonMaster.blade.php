<!DOCTYPE html>
@php
  use Illuminate\Support\Str;
  use App\Helpers\Helpers;

  $menuFixed =
      $configData['layout'] === 'vertical'
          ? $menuFixed ?? ''
          : ($configData['layout'] === 'front'
              ? ''
              : $configData['headerType']);
  $navbarType =
      $configData['layout'] === 'vertical'
          ? $configData['navbarType']
          : ($configData['layout'] === 'front'
              ? 'layout-navbar-fixed'
              : '');
  $isFront = ($isFront ?? '') == true ? 'Front' : '';
  $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';

  // Get skin name from configData - only applies to admin layouts
  $isAdminLayout = !Str::contains($configData['layout'] ?? '', 'front');
  $skinName = $isAdminLayout ? $configData['skinName'] ?? 'default' : 'default';

  // Get semiDark value from configData - only applies to admin layouts
  $semiDarkEnabled = $isAdminLayout && filter_var($configData['semiDark'] ?? false, FILTER_VALIDATE_BOOLEAN);

  // Generate primary color CSS if color is set
  $primaryColorCSS = '';
  if (isset($configData['color']) && $configData['color']) {
      $primaryColorCSS = Helpers::generatePrimaryColorCSS($configData['color']);
  }

@endphp

@php
  $globalOnboarding = null;
  if (auth()->check()) {
      $authUser = auth()->user();
      if (!$authUser->hasRole(['Super-admin', 'Admin'])) {
          $globalOnboarding = app(\App\Services\OnboardingService::class)->forUser($authUser);
      }
  }
@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
  class="{{ $navbarType ?? '' }} {{ $contentLayout ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
  dir="{{ $configData['textDirection'] }}" data-skin="{{ $skinName }}" data-assets-path="{{ asset('/assets') . '/' }}"
  data-base-url="{{ url('/') }}" data-framework="laravel" data-template="{{ $configData['layout'] }}-menu-template"
  data-bs-theme="{{ $configData['theme'] }}" @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>
    @yield('title') | {{ config('variables.templateName') ? config('variables.templateName') : 'TemplateName' }}
    - {{ config('variables.templateSuffix') ? config('variables.templateSuffix') : 'TemplateSuffix' }}
  </title>
  <meta name="description"
    content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords"
    content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}" />
  <meta property="og:title" content="{{ config('variables.ogTitle') ? config('variables.ogTitle') : '' }}" />
  <meta property="og:type" content="{{ config('variables.ogType') ? config('variables.ogType') : '' }}" />
  <meta property="og:url" content="{{ config('variables.productPage') ? config('variables.productPage') : '' }}" />
  <meta property="og:image" content="{{ config('variables.ogImage') ? config('variables.ogImage') : '' }}" />
  <meta property="og:description"
    content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta property="og:site_name"
    content="{{ config('variables.creatorName') ? config('variables.creatorName') : '' }}" />
  <meta name="robots" content="noindex, nofollow" />
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}" />
  <!-- Favicon -->
  <!--<link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />-->
  
  <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon/favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}" />
    <meta name="apple-mobile-web-app-title" content="PhenixSPOT" />
    <!--<link rel="manifest" href="{{ asset('assets/img/favicon/site.webmanifest') }}" />-->
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}" />
    <meta name="theme-color" content="#4f46e5" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
  <!-- Include Styles -->
  <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/styles' . $isFront)

  @if (
      $primaryColorCSS &&
          (config('custom.custom.primaryColor') ||
              isset($_COOKIE['admin-primaryColor']) ||
              isset($_COOKIE['front-primaryColor'])))
    <!-- Primary Color Style -->
    <style id="primary-color-style">
      {!! $primaryColorCSS !!}
    </style>
  @endif

  @if (($globalOnboarding['show'] ?? false) === true)
    <style>
      .onboarding-fab {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 1085;
        border-radius: 999px;
        padding: 0.8rem 1rem;
        box-shadow: 0 8px 22px rgba(115, 103, 240, 0.35);
      }

      .onboarding-fab .badge {
        margin-left: 6px;
      }

      .onboarding-widget {
        position: fixed;
        right: 18px;
        bottom: 82px;
        z-index: 1085;
        width: min(360px, calc(100vw - 32px));
        border-radius: 16px;
        box-shadow: 0 12px 34px rgba(15, 23, 42, 0.2);
        display: none;
      }


      .onboarding-fab.is-nudging {
        animation: onboarding-nudge .9s ease-in-out 2;
      }

      @keyframes onboarding-nudge {
        0%, 100% { transform: translateX(0) scale(1); }
        20% { transform: translateX(-4px) scale(1.03); }
        40% { transform: translateX(4px) scale(1.03); }
        60% { transform: translateX(-3px) scale(1.02); }
        80% { transform: translateX(3px) scale(1.02); }
      }
    </style>
  @endif
  
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-ZL5JP3HMSL"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    
      gtag('config', 'G-ZL5JP3HMSL');
    </script>
  <!-- Include Scripts for customizer, helper, analytics, config -->
  <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scriptsIncludes' . $isFront)
</head>

<body>
  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->
  @if (($globalOnboarding['show'] ?? false) === true)
    <div class="card onboarding-widget" id="global-onboarding-widget" aria-live="polite">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Assistant PhenixSPOT</h6>
          <button class="btn btn-sm btn-icon btn-label-secondary" id="global-onboarding-close" type="button" aria-label="Fermer">
            <i class="ti tabler-x"></i>
          </button>
        </div>

        <p class="text-muted small mb-2">{{ $globalOnboarding['completed'] }}/{{ $globalOnboarding['total'] }} étapes terminées</p>
        <div class="progress mb-3" style="height: 7px;">
          <div class="progress-bar" role="progressbar" style="width: {{ $globalOnboarding['progress_percent'] }}%" aria-valuenow="{{ $globalOnboarding['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        @if(!empty($globalOnboarding['next_step']))
          <div class="small mb-2"><strong>Prochaine étape :</strong> {{ $globalOnboarding['next_step']['title'] }}</div>
          <div class="d-flex gap-2">
            <a href="{{ $globalOnboarding['next_step']['route'] }}" class="btn btn-sm btn-primary">{{ $globalOnboarding['next_step']['route_label'] }}</a>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-label-secondary">Voir tout</a>
          </div>
        @endif
      </div>
    </div>

    <button class="btn btn-primary onboarding-fab" id="global-onboarding-fab" type="button">
      <i class="ti tabler-message-2 me-1"></i> Onboarding
      <span class="badge bg-white text-primary">{{ $globalOnboarding['total'] - $globalOnboarding['completed'] }}</span>
    </button>
  @endif

  {{-- remove while creating package --}}
  {{-- remove while creating package end --}}

  <!-- Include Scripts -->
  <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scripts' . $isFront)
  
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('/service-worker.js').catch(function (error) {
          console.warn('Service Worker registration failed:', error);
        });
      });
    }
  </script>
  @if (($globalOnboarding['show'] ?? false) === true)
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const fab = document.getElementById('global-onboarding-fab');
        const widget = document.getElementById('global-onboarding-widget');
        const closeBtn = document.getElementById('global-onboarding-close');
        if (!fab || !widget) return;

        const userId = @json((int) auth()->id());
        const openKey = `phenixspot.onboarding.widget.open.${userId}`;

        const show = () => {
          widget.style.display = 'block';
          localStorage.setItem(openKey, '1');
        };

        const hide = () => {
          widget.style.display = 'none';
          localStorage.setItem(openKey, '0');
        };

        const initial = localStorage.getItem(openKey);
        if (initial === null || initial === '1') {
          show();
        } else {
          hide();
        }

        const emitBeep = () => {
          try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = 920;
            gain.gain.value = 0.05;
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start();
            oscillator.stop(ctx.currentTime + 0.12);
            oscillator.onended = () => ctx.close();
          } catch (e) {
            // silence en cas de blocage autoplay navigateur
          }
        };

        const nudgeReminder = () => {
          if (document.hidden) return;
          fab.classList.remove('is-nudging');
          void fab.offsetWidth;
          fab.classList.add('is-nudging');
          setTimeout(() => fab.classList.remove('is-nudging'), 1800);

          if ('vibrate' in navigator) {
            navigator.vibrate([120, 60, 120]);
          }

          emitBeep();
        };

        nudgeReminder();
        setInterval(nudgeReminder, 30000);

        fab.addEventListener('click', function () {
          const isHidden = widget.style.display === 'none';
          if (isHidden) show(); else hide();
        });

        if (closeBtn) {
          closeBtn.addEventListener('click', hide);
        }
      });
    </script>
  @endif
</body>

</html>
