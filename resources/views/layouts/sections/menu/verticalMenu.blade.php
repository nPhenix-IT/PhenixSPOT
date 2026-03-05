@php
$configData = Helper::appClasses();
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu" @foreach ($configData['menuAttributes'] as $attribute => $value)
  {{ $attribute }}="{{ $value }}" @endforeach>

  <!-- ! Hide app brand if navbar-full -->
  @if (!isset($navbarFull))
  <div class="app-brand demo">
    <a href="{{ url('/') }}" class="app-brand-link">
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <span class="app-brand-text demo menu-text fw-bold ms-3">{{ config('variables.templateName') }}</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
      <i class="icon-base ti tabler-x d-block d-xl-none"></i>
    </a>
  </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuData[0]->menu as $menu)

    @php
      $currentUser = auth()->user();
      $shouldRender = true;

      if (isset($menu->roles)) {
        $shouldRender = auth()->check() && $currentUser->hasAnyRole($menu->roles);
      }

      if (isset($menu->permission)) {
        $shouldRender = $shouldRender && auth()->check() && $currentUser->can($menu->permission);
      }

      // Durcissement: n'afficher les menus admin QUE pour Admin/Super-admin
      $canSeeAdminMenus = auth()->check() && $currentUser->hasAnyRole(['Super-admin', 'Admin']);
      $menuHeader = isset($menu->menuHeader) ? Str::lower((string) $menu->menuHeader) : '';
      $menuSlug = isset($menu->slug) ? (string) $menu->slug : '';
      $menuUrl = isset($menu->url) ? (string) $menu->url : '';

      $isAdminMenu =
        Str::contains($menuHeader, 'administration') ||
        Str::startsWith($menuSlug, 'admin.') ||
        Str::startsWith($menuSlug, 'admin-') ||
        Str::startsWith($menuUrl, '/admin');

      if (!$isAdminMenu && isset($menu->submenu) && is_array($menu->submenu)) {
        foreach ($menu->submenu as $submenuItem) {
          $submenuSlug = isset($submenuItem->slug) ? (string) $submenuItem->slug : '';
          $submenuUrl = isset($submenuItem->url) ? (string) $submenuItem->url : '';

          if (Str::startsWith($submenuSlug, 'admin.') || Str::startsWith($submenuUrl, '/admin')) {
            $isAdminMenu = true;
            break;
          }
        }
      }

      if (!$canSeeAdminMenus && $isAdminMenu) {
        $shouldRender = false;
      }

      if ($shouldRender && isset($menu->submenu) && is_array($menu->submenu)) {
        $visibleSubmenuCount = 0;

        foreach ($menu->submenu as $submenuItem) {
          $submenuVisible = true;

          if (isset($submenuItem->roles)) {
            $submenuVisible = auth()->check() && $currentUser->hasAnyRole($submenuItem->roles);
          }

          if (isset($submenuItem->permission)) {
            $submenuVisible = $submenuVisible && auth()->check() && $currentUser->can($submenuItem->permission);
          }

          $submenuSlug = isset($submenuItem->slug) ? (string) $submenuItem->slug : '';
          $submenuUrl = isset($submenuItem->url) ? (string) $submenuItem->url : '';
          $isAdminSubmenu = Str::startsWith($submenuSlug, 'admin.') || Str::startsWith($submenuUrl, '/admin');

          if (!$canSeeAdminMenus && $isAdminSubmenu) {
            $submenuVisible = false;
          }

          if ($submenuVisible) {
            $visibleSubmenuCount++;
          }
        }

        if ($visibleSubmenuCount === 0) {
          $shouldRender = false;
        }
      }
    @endphp

    @if (!$shouldRender)
      @continue
    @endif

    {{-- menu headers --}}
    @if (isset($menu->menuHeader))
    <li class="menu-header small">
      <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
    </li>
    @else
    {{-- active menu method --}}
    @php
    $activeClass = null;
    $currentRouteName = Route::currentRouteName();

    if ($currentRouteName === $menu->slug) {
      $activeClass = 'active';
    } elseif (isset($menu->submenu)) {
      if (gettype($menu->slug) === 'array') {
        foreach ($menu->slug as $slug) {
          if (str_contains($currentRouteName, $slug) && strpos($currentRouteName, $slug) === 0) {
            $activeClass = 'active open';
          }
        }
      } else {
        if (str_contains($currentRouteName, $menu->slug) && strpos($currentRouteName, $menu->slug) === 0) {
          $activeClass = 'active open';
        }
      }
    }
    @endphp

    {{-- main menu --}}
    <li class="menu-item {{ $activeClass }}">
      <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
        class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) &&
        !empty($menu->target)) target="_blank" @endif>
        @isset($menu->icon)
        <i class="{{ $menu->icon }}"></i>
        @endisset
        <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
        @isset($menu->badge)
        <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge ?? $menu->badge }}</div>
        @endisset
      </a>

      {{-- submenu --}}
      @isset($menu->submenu)
      @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
      @endisset
    </li>
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

    /*.app-brand-link:hover .app-brand-text {*/
    /*  color: rgba(255, 255, 255, 0.8) !important;*/
    /*}*/

    /* --- Visibilité Flèche sous-menu (Toggle) --- */
    /* Force les flèches (les chevrons à droite) en blanc */
    /*.menu-vertical .menu-link.menu-toggle::after {*/
    /*  border-color: #ffffff !important;*/
    /*  opacity: 1 !important;*/
    /*}*/

    /* --- Styles Menu Block & Collapsed --- */
    /*:is(body, html, .layout-wrapper, .layout-container).layout-menu-collapsed #layout-menu .menu-block,*/
    /*#layout-menu.layout-menu-collapsed .menu-block {*/
    /*  justify-content: center;*/
    /*}*/
    
    /*:is(body, html, .layout-wrapper, .layout-container).layout-menu-collapsed #layout-menu .menu-block .avatar,*/
    /*#layout-menu.layout-menu-collapsed .menu-block .avatar {*/
    /*  margin-right: 0 !important;*/
    /*}*/
  </style>


</aside>