'use strict';

$(function () {
  const table = $('#permissionsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '/admin/access/permissions',
    pageLength: 5,
    lengthChange: false,
    columns: [
      { data: 'name' },
      { data: 'roles_count', render: (d) => `${d} rôle(s)` },
      { data: 'is_core', render: (d) => d ? '<span class="badge bg-label-warning">Core</span>' : '<span class="badge bg-label-secondary">Custom</span>' },
      { data: 'created_at', defaultContent: '' },
      { data: 'actions', orderable: false, searchable: false }
    ]
  });

  $(document).on('submit', '#addPermissionForm, #editPermissionForm', function (e) {
    e.preventDefault();
    const $form = $(this);
    $.ajax({
      url: $form.attr('action'),
      method: $form.find('input[name="_method"]').val() || 'POST',
      data: $form.serialize(),
      success: function () {
        $('.modal').modal('hide');
        table.ajax.reload(null, false);
      },
      error: function (xhr) {
        alert(xhr.responseJSON?.message || 'Erreur.');
      }
    });
  });

  $(document).on('click', '.js-edit-permission', function () {
    const p = $(this).data('permission');
    $('#editPermissionForm').attr('action', `/admin/access/permissions/${p.id}`);
    $('#editPermissionForm [name="name"]').val(p.name);
    $('#editCorePermission').prop('checked', !!p.is_core);
    $('#editPermissionModal').modal('show');
  });

  $(document).on('click', '.js-delete-permission', function () {
    const id = $(this).data('id');
    $.ajax({
      url: `/admin/access/permissions/${id}`,
      method: 'POST',
      data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' }
    }).done(() => table.ajax.reload(null, false))
      .fail((xhr) => alert(xhr.responseJSON?.message || 'Suppression impossible.'));
  });
});
