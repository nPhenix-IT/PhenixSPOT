@php
  $permissionPayload = [
    'id' => $permission->id,
    'name' => $permission->name,
    'is_core' => (bool) $permission->is_core,
  ];
@endphp

<div class="d-flex gap-1">
  <button
    type="button"
    class="btn btn-sm btn-outline-primary js-edit-permission"
    data-permission='@json($permissionPayload)'>
    Modifier
  </button>

  @if(!$permission->is_core)
    <button
      type="button"
      class="btn btn-sm btn-outline-danger js-delete-permission"
      data-id="{{ $permission->id }}">
      Supprimer
    </button>
  @endif
</div>