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
        @include('_partials.macros',["height"=>20])
      </span>
      <span class="app-brand-text demo menu-text fw-bold">{{config('variables.templateName')}}</span>
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

    {{-- Vérification des rôles pour n'afficher que les menus autorisés --}}
    @php
      $shouldRender = true;
      if (isset($menu->roles)) {
        // L'utilisateur doit être connecté et avoir l'un des rôles requis
        $shouldRender = auth()->check() && auth()->user()->hasAnyRole($menu->roles);
      }
    @endphp

    @if ($shouldRender)
      {{-- Logique pour les en-têtes de menu (ex: "Administration") --}}
      @if (isset($menu->menuHeader))
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
      </li>

      @else

      {{-- Logique pour déterminer si un menu est actif --}}
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

      {{-- Affichage de l'élément de menu --}}
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

        {{-- Affichage du sous-menu (si existant) --}}
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
      {{-- Avatar (reste visible même menu réduit) --}}
      <div class="avatar avatar-md avatar-online me-2">
        <img
          alt="Avatar"
          class="rounded-circle shadow"
          src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}"
        >
      </div>
    
      {{-- Texte (sera masqué automatiquement quand menu réduit grâce à menu-text) --}}
      <div class="menu-text d-flex flex-column">
        <h5 class="mt-4 mb-1">
            {{ Auth::user()->name }}
        </h5>
    
        @if (Auth::check())
          @php
            // Charge relations si pas encore chargées
            $u = Auth::user()->loadMissing('subscription.plan');
    
            // Abonnement actif en priorité, sinon dernier abonnement
            $activeSub = $u->subscription()
              ->with('plan')
              ->where('status', 'active')
              ->first() ?? $u->subscription;
    
            $planName = ($activeSub && $activeSub->plan) ? $activeSub->plan->name : null;
          @endphp
    
          <div class="text-muted small">
            {{ $planName ?? 'Aucun forfait actif' }}
          </div>
        @endif
      </div>
    </div>
    
    <style>
      /* Centrer l’avatar quand menu est réduit (selon classe de ton thème) */
      :is(body, html, .layout-wrapper, .layout-container).layout-menu-collapsed #layout-menu .menu-block,
      :is(body, html, .layout-wrapper, .layout-container).menu-collapsed #layout-menu .menu-block,
      #layout-menu.layout-menu-collapsed .menu-block,
      #layout-menu.menu-collapsed .menu-block {
        justify-content: center;
      }
    
      :is(body, html, .layout-wrapper, .layout-container).layout-menu-collapsed #layout-menu .menu-block .avatar,
      :is(body, html, .layout-wrapper, .layout-container).menu-collapsed #layout-menu .menu-block .avatar,
      #layout-menu.layout-menu-collapsed .menu-block .avatar,
      #layout-menu.menu-collapsed .menu-block .avatar {
        margin-right: 0 !important;
      }
    </style>

</aside>
