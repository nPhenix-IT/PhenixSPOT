@php
  $rolePayload = [
    'id' => $role->id,
    'name' => $role->name,
    'permissions' => $role->permissions->pluck('id')->values(), // liste clean
  ];
@endphp

<button
  type="button"
  class="btn btn-sm btn-outline-primary js-edit-role"
  data-role='@json($rolePayload)'>
  Modifier
</button>