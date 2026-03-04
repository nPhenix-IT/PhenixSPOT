'use strict';

$(function () {
  const table = $('#usersTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '/admin/users',
    order: [[0, 'desc']],
    columns: [
      { data: 'id' },
      {
        data: null,
        render: function (data) {
          return `<div class="d-flex flex-column"><strong>${data.name}</strong><small>${data.email}</small></div>`;
        }
      },
      { data: 'role_names', defaultContent: '—' },
      { data: 'plan_name', defaultContent: '—' },
      { data: 'status_badge', orderable: false, searchable: false },
      { data: 'actions', orderable: false, searchable: false }
    ]
  });

  const initIntl = (phoneSelector, countrySelector) => {
    const phoneInput = document.querySelector(phoneSelector);
    const countrySelect = document.querySelector(countrySelector);
    if (!phoneInput || !countrySelect || !window.intlTelInput) return null;

    const iti = window.intlTelInput(phoneInput, {
      initialCountry: (countrySelect.value || 'CI').toLowerCase(),
      separateDialCode: true,
      nationalMode: false,
      utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/utils.js'
    });

    countrySelect.addEventListener('change', function () {
      iti.setCountry((countrySelect.value || 'CI').toLowerCase());
    });

    phoneInput.addEventListener('countrychange', function () {
      const code = iti.getSelectedCountryData()?.iso2;
      if (code) countrySelect.value = code.toUpperCase();
      phoneInput.value = iti.getNumber();
    });

    return iti;
  };

  const addIti = initIntl('#add_phone_number', '#add_country_code');
  const editIti = initIntl('#edit_phone_number', '#edit_country_code');

  $(document).on('submit', '#addUserForm, #editUserForm, #assignPlanForm', function (e) {
    e.preventDefault();
    const $form = $(this);
    $.ajax({
      url: $form.attr('action'),
      method: $form.find('input[name="_method"]').val() || $form.attr('method') || 'POST',
      data: $form.serialize(),
      success: function () {
        $('.modal').modal('hide');
        table.ajax.reload(null, false);
      },
      error: function (xhr) {
        alert(xhr.responseJSON?.message || 'Erreur de validation.');
      }
    });
  });

  $(document).on('click', '.js-edit-user', function () {
    const user = $(this).data('user');
    const $form = $('#editUserForm');
    $form.attr('action', `/admin/users/${user.id}`);
    $form.find('[name="name"]').val(user.name);
    $form.find('[name="email"]').val(user.email);
    $form.find('[name="phone_number"]').val(user.phone_number || '');
    $form.find('[name="country_code"]').val((user.country_code || 'CI').toUpperCase());
    $form.find('[name="role"]').val(user.role || 'User');
    $form.find('[name="plan_id"]').val('');
    if (editIti) editIti.setCountry((user.country_code || 'CI').toLowerCase());
    $('#modalEditUser').modal('show');
  });

  $(document).on('click', '.js-toggle-user', function () {
    const userId = $(this).data('id');
    $.post(`/admin/users/${userId}/toggle-status`, {_token: $('meta[name="csrf-token"]').attr('content')})
      .done(() => table.ajax.reload(null, false))
      .fail((xhr) => alert(xhr.responseJSON?.message || 'Action impossible.'));
  });

  $(document).on('click', '.js-assign-plan', function () {
    const userId = $(this).data('id');
    $('#assignPlanForm').attr('action', `/admin/users/${userId}/assign-plan`);
    $('#modalAssignPlan').modal('show');
  });
});
