@php
  $payload = [
    'id' => $user->id,
    'name' => $user->name,
    'email' => $user->email,
    'country_code' => $user->country_code,
    'phone_number' => $user->phone_number,
    'role' => $user->roles->pluck('name')->first(),
  ];
@endphp

<div class="d-flex gap-1">
  <button
    type="button"
    class="btn btn-sm btn-outline-primary js-edit-user"
    data-user='@json($payload)'>
    Modifier
  </button>

  <button type="button" class="btn btn-sm btn-outline-warning js-toggle-user" data-id="{{ $user->id }}">
    {{ $user->is_active ? 'Désactiver' : 'Activer' }}
  </button>

  <button type="button" class="btn btn-sm btn-outline-success js-assign-plan" data-id="{{ $user->id }}">Plan</button>

  <form action="{{ route('admin.users.impersonate', $user) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-sm btn-outline-info">Login as</button>
  </form>
</div>