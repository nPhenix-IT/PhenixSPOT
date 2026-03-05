@php
$configData = Helper::appClasses();
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  @if(!isset($navbarFull))
  <div class="app-brand demo">
    <a href="{{url('/')}}" class="app-brand-link">
      <span class="app-brand-logo demo">
        {{-- Logo agrandi à 28 --}}
        @include('_partials.macros',["height"=>28])
      </span>
      <span class="app-brand-text demo menu-text fw-bold">
        {{config('variables.templateName')}}
      </span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ti menu-toggle-icon icon_color d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuData[0]->menu as $menu)

    {{-- Vérification des rôles --}}
    @php
      $shouldRender = true;
      if (isset($menu->roles)) {
        $shouldRender = auth()->check() && auth()->user()->hasAnyRole($menu->roles);
      }
    @endphp

    @if ($shouldRender)
      @if (isset($menu->menuHeader))
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
      </li>
      @else

      @php
      $activeClass = null;
      $currentRouteName = Route::currentRouteName();

      if ($currentRouteName === $menu->slug) {
        $activeClass = 'active';
      }
      elseif (isset($menu->submenu)) {
        if (gettype($menu->slug) === 'string') {
          $activeClass = Str::startsWith($currentRouteName, $menu->slug) ? 'active open' : '';
        }
        else {
          foreach ($menu->slug as $slug){
            if (Str::startsWith($currentRouteName, $slug)) {
              $activeClass = 'active open';
            }
          }
        }
      }
      @endphp

      <li class="menu-item {{$activeClass}}">
        <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
          @if (isset($menu->icon))
          <i class="{{ $menu->icon }} {{ $menu->icon_color ?? '' }}"></i>
          @endif
          <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
          @if (isset($menu->badge))
          <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge }}</div>
          @endif
        </a>

        @if (isset($menu->submenu))
        @include('layouts.sections.menu.submenu',['menu' => $menu->submenu])
        @endif
      </li>
      @endif
    @endif
    @endforeach
  </ul>
  
  <div class="menu-divider mt-0"></div>
  <div class="menu-block my-2 d-flex align-items-center">
    <div class="avatar avatar-md avatar-online me-2">
      <img
        alt="Avatar"
        class="rounded-circle shadow"
        src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}"
      >
    </div>
    
    <div class="menu-text d-flex flex-column">
      <h5 class="mt-4 mb-1" style="color: white !important;">
        {{ Auth::user()->name }}
      </h5>
    
      @if (Auth::check())
        @php
          $u = Auth::user()->loadMissing('subscription.plan');
          $activeSub = $u->subscription()->with('plan')->where('status', 'active')->first() ?? $u->subscription;
          $planName = ($activeSub && $activeSub->plan) ? $activeSub->plan->name : null;
        @endphp
        <div class="text-muted small" style="color: rgba(255, 255, 255, 0.7) !important;">
          {{ $planName ?? 'Aucun forfait actif' }}
        </div>
      @endif
    </div>
  </div>
    
  <style>
    /* --- Visibilité Titre PhenixSPOT --- */
    .app-brand .app-brand-text {
      color: #ffffff !important; /* Forcé en blanc pour visibilité maximale */
      opacity: 1 !important;
      z-index: 5;
      transition: all 0.2s ease-in-out;
    }

    .app-brand-link:hover .app-brand-text {
      color: rgba(255, 255, 255, 0.8) !important;
    }

    /* --- Visibilité Flèche sous-menu (Toggle) --- */
    /* Force les flèches (les chevrons à droite) en blanc */
    .menu-vertical .menu-link.menu-toggle::after {
      border-color: #ffffff !important;
      opacity: 1 !important;
    }

    /* --- Styles Menu Block & Collapsed --- */
    :is(body, html, .layout-wrapper, .layout-container).layout-menu-collapsed #layout-menu .menu-block,
    #layout-menu.layout-menu-collapsed .menu-block {
      justify-content: center;
    }
    
    :is(body, html, .layout-wrapper, .layout-container).layout-menu-collapsed #layout-menu .menu-block .avatar,
    #layout-menu.layout-menu-collapsed .menu-block .avatar {
      margin-right: 0 !important;
    }
  </style>

</aside>