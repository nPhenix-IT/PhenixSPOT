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
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
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
    <div class="avatar avatar-md avatar-online me-2"><img alt="Avatar" class="rounded-circle shadow" src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}"></div>
    <h5 class="menu-text mt-4 mb-1">
    @if (Auth::check())
      {{ Auth::user()->name }}
      @else
      John Doe
    @endif
    </h5>
  </div>

</aside>
