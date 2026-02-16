'use strict';
document.addEventListener('DOMContentLoaded', function () {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

const dtProfileTable = $('.datatables-profiles');
  let dtProfile;

  function secondsToDuration(seconds) {
    const value = Number(seconds || 0);
    if (value <= 0) return { duration: '', unit: 'hours' };
    if (value % 2592000 === 0) return { duration: value / 2592000, unit: 'months' };
    if (value % 604800 === 0) return { duration: value / 604800, unit: 'weeks' };
    if (value % 86400 === 0) return { duration: value / 86400, unit: 'days' };
    return { duration: Math.ceil(value / 3600), unit: 'hours' };
  }

  function bytesToData(bytes) {
    const value = Number(bytes || 0);
    if (value <= 0) return { amount: '', unit: 'mb' };
    const gb = 1024 * 1024 * 1024;
    if (value % gb === 0) return { amount: value / gb, unit: 'gb' };
    return { amount: Math.round(value / (1024 * 1024)), unit: 'mb' };
  }

function updateFormVisibility(form) {
    const limitType = form.querySelector('input[name="limit_type"]:checked').value;
    const timeGroup = form.querySelector('.form-group-time');
    const dataGroup = form.querySelector('.form-group-data');
    const timeInput = timeGroup.querySelector('input[name="session_duration"]');
    const dataInput = dataGroup.querySelector('input[name="data_limit_value"]');
    const timeStar = timeGroup.querySelector('.required-star');
    const dataStar = dataGroup.querySelector('.required-star');

    timeGroup.style.display = 'none';
    dataGroup.style.display = 'none';
    timeInput.required = false;
    dataInput.required = false;
    timeStar.style.display = 'none';
    dataStar.style.display = 'none';

    if (limitType === 'both') {
      timeGroup.style.display = 'flex';
      dataGroup.style.display = 'flex';
      timeInput.required = true;
      dataInput.required = true;
      timeStar.style.display = 'inline';
      dataStar.style.display = 'inline';
    } else if (limitType === 'time') {
      timeGroup.style.display = 'flex';
      timeInput.required = true;
      timeStar.style.display = 'inline';
    } else if (limitType === 'data') {
      dataGroup.style.display = 'flex';
      dataInput.required = true;
      dataStar.style.display = 'inline';
    }
  }

  if (dtProfileTable.length) {
    dtProfile = dtProfileTable.DataTable({
      processing: true,
      serverSide: true,
      ajax: { url: '/profiles' },
      columns: [
        { data: 'name', name: 'name' },
        {
          data: 'price',
          name: 'price',
          render: function (data) {
            const price = Math.round(Number(data || 0));
            return price === 0 ? 'Gratuit' : `${price.toLocaleString('fr-FR')} FCFA`;
          }
        },
        { data: 'rate_limit', name: 'rate_limit' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
      ]
    });
  }

const addModal = document.getElementById('addProfileModal');
  const editModal = document.getElementById('editProfileModal');

  if (addModal) {
    const addForm = addModal.querySelector('form');
    updateFormVisibility(addForm);

    addForm.querySelectorAll('input[name="limit_type"]').forEach(radio => {
      radio.addEventListener('change', () => updateFormVisibility(addForm));
    });

    $('#add-new-profile-btn').on('click', function () {
      addForm.reset();
      addForm.querySelector('input[name="limit_type"][value="both"]').checked = true;
      updateFormVisibility(addForm);
      new bootstrap.Modal(addModal).show();
    });

    $(addForm).on('submit', function (e) {
      e.preventDefault();
      $.ajax({
        data: $(this).serialize(),
        url: $(this).attr('action'),
        type: 'POST',
        success: function (response) {
          if (dtProfile) dtProfile.ajax.reload();
          bootstrap.Modal.getInstance(addModal).hide();
          Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        },
        error: function (err) {
          Swal.fire({ icon: 'error', title: 'Erreur!', text: err?.responseJSON?.message || 'Une erreur est survenue.' });
        }
      });
    });
  }

  if (editModal) {
    const editForm = editModal.querySelector('form');
    editForm.querySelectorAll('input[name="limit_type"]').forEach(radio => {
      radio.addEventListener('change', () => updateFormVisibility(editForm));
    });

    $(document).on('click', '.item-edit', function (e) {
      e.preventDefault();
      const id = $(this).data('id');
      if (!id) return;

      $.get(`/profiles/${id}/edit`, function (profile) {
        editForm.action = `/profiles/${profile.id}`;
        editForm.querySelector('[name="name"]').value = profile.name || '';
        editForm.querySelector('[name="price"]').value = Math.round(Number(profile.price || 0));
        editForm.querySelector('[name="rate_limit"]').value = profile.rate_limit || '';
        editForm.querySelector('[name="device_limit"]').value = profile.device_limit || 1;

        const limitType = profile.limit_type || 'both';
        const limitRadio = editForm.querySelector(`input[name="limit_type"][value="${limitType}"]`);
        if (limitRadio) limitRadio.checked = true;

        const session = secondsToDuration(profile.session_timeout);
        editForm.querySelector('[name="session_duration"]').value = session.duration;
        editForm.querySelector('[name="session_unit"]').value = session.unit;

        const data = bytesToData(profile.data_limit);
        editForm.querySelector('[name="data_limit_value"]').value = data.amount;
        editForm.querySelector('[name="data_unit"]').value = data.unit;

        const validity = secondsToDuration(profile.validity_period);
        editForm.querySelector('[name="validity_duration"]').value = validity.duration || 1;
        editForm.querySelector('[name="validity_unit"]').value = validity.unit;

        updateFormVisibility(editForm);
        new bootstrap.Modal(editModal).show();
      }).fail(function () {
        Swal.fire({ icon: 'error', title: 'Erreur!', text: 'Impossible de charger le profil.' });
      });
    });

    $(editForm).on('submit', function (e) {
      e.preventDefault();
      $.ajax({
        data: $(this).serialize(),
        url: $(this).attr('action'),
        type: 'POST',
        success: function (response) {
          if (dtProfile) dtProfile.ajax.reload();
          bootstrap.Modal.getInstance(editModal).hide();
          Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        },
        error: function (err) {
          Swal.fire({ icon: 'error', title: 'Erreur!', text: err?.responseJSON?.message || 'Une erreur est survenue.' });
        }
      });
    });
  }

  $(document).on('click', '.item-delete', function (e) {
    e.preventDefault();
    const id = $(this).data('id');
    if (!id) return;

    Swal.fire({
      title: 'Êtes-vous sûr ?',
      text: 'Cette action est irréversible.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Oui, supprimer !',
      cancelButtonText: 'Annuler'
    }).then(result => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: `/profiles/${id}`,
        type: 'POST',
        data: { _method: 'DELETE' },
        success: function (response) {
          if (dtProfile) dtProfile.ajax.reload();
          Swal.fire({ icon: 'success', title: 'Supprimé', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        },
        error: function () {
          Swal.fire({ icon: 'error', title: 'Erreur!', text: 'Impossible de supprimer ce profil.' });
        }
      });
    });
  });
});