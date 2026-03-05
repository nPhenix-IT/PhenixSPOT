@php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
@endphp

<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)

    {{-- active menu method --}}
    @php
      $activeClass = null;
      $active = $configData["layout"] === 'vertical' ? 'active open':'active';
      $currentRouteName =  Route::currentRouteName();

      $shouldRenderSubmenu = true;
      if (isset($submenu->roles)) {
        $shouldRenderSubmenu = auth()->check() && auth()->user()->hasAnyRole($submenu->roles);
      }

      $canSeeAdminMenus = auth()->check() && auth()->user()->hasAnyRole(['Super-admin', 'Admin']);
      $submenuSlugForGuard = isset($submenu->slug) ? (string) $submenu->slug : '';
      $submenuUrlForGuard = isset($submenu->url) ? (string) $submenu->url : '';
      $isAdminSubmenu = Str::startsWith($submenuSlugForGuard, 'admin.') || Str::startsWith($submenuUrlForGuard, '/admin');
      if (!$canSeeAdminMenus && $isAdminSubmenu) {
        $shouldRenderSubmenu = false;
      }

      if ($currentRouteName === $submenu->slug) {
          $activeClass = 'active';
      }
      elseif (isset($submenu->submenu)) {
        if (gettype($submenu->slug) === 'array') {
          foreach($submenu->slug as $slug){
            if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = $active;
            }
          }
        }
        else{
          if (str_contains($currentRouteName,$submenu->slug) and strpos($currentRouteName,$submenu->slug) === 0) {
            $activeClass = $active;
          }
        }
      }
    @endphp

    @if ($shouldRenderSubmenu)
      <li class="menu-item {{$activeClass}}">
        <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
          @if (isset($submenu->icon))
          <i class="{{ $submenu->icon }} {{ $submenu->icon_color ?? '' }}"></i>
          @endif
          <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
          @isset($submenu->badge)
            <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
          @endisset
        </a>

        {{-- submenu --}}
        @if (isset($submenu->submenu))
          @include('layouts.sections.menu.submenu',['menu' => $submenu->submenu])
        @endif
      </li>
    @endif
    @endforeach
  @endif
</ul>