'use strict';

$(function () {
  const table = $('#rolesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '/admin/access/roles',
    columns: [
      { data: 'name' },
      { data: 'users_count', render: (d) => `${d} utilisateur(s)` },
      { data: 'permissions_list', defaultContent: '—' },
      { data: 'actions', orderable: false, searchable: false }
    ]
  });

  const updateSelectedCount = (target) => {
    const checked = $(`.js-role-permission[data-target="${target}"]:checked`).length;
    $(`#${target}RoleSelectedCount`).text(checked);
  };

  const filterPermissions = (target, term) => {
    const keyword = (term || '').trim().toLowerCase();
    const root = target === 'add' ? '#addRolePermissions' : '#editRolePermissions';

    $(`${root} .permission-col`).each(function () {
      const name = ($(this).data('name') || '').toString();
      $(this).toggle(!keyword || name.includes(keyword));
    });
  };

  const toggleAllVisible = (target, checked) => {
    const root = target === 'add' ? '#addRolePermissions' : '#editRolePermissions';

    $(`${root} .permission-col:visible .js-role-permission`).prop('checked', checked);
    updateSelectedCount(target);
  };

  $(document).on('change', '.js-role-permission', function () {
    updateSelectedCount($(this).data('target'));
  });

  $(document).on('input', '.js-permission-search', function () {
    filterPermissions($(this).data('target'), $(this).val());
  });

  $(document).on('click', '.js-select-all', function () {
    toggleAllVisible($(this).data('target'), true);
  });

  $(document).on('click', '.js-clear-all', function () {
    toggleAllVisible($(this).data('target'), false);
  });

  $(document).on('submit', '#addRoleForm, #editRoleForm', function (e) {
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

  $(document).on('click', '.js-edit-role', function () {
    const role = $(this).data('role');
    const $form = $('#editRoleForm');
    $form.attr('action', `/admin/access/roles/${role.id}`);
    $form.find('[name="name"]').val(role.name);
    $form.find('input[name="permissions[]"]').prop('checked', false);
    (role.permissions || []).forEach((pid) => {
      $form.find(`input[name="permissions[]"][value="${pid}"]`).prop('checked', true);
    });

    $('.js-permission-search[data-target="edit"]').val('');
    filterPermissions('edit', '');
    updateSelectedCount('edit');
    $('#editRoleModal').modal('show');
  });

  $('#addRoleModal').on('shown.bs.modal', function () {
    $('#addRoleForm')[0].reset();
    $('.js-permission-search[data-target="add"]').val('');
    filterPermissions('add', '');
    updateSelectedCount('add');
  });
});
