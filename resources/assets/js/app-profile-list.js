'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_profile_table = $('.datatables-profiles');
    var dt_profile;

    if (dt_profile_table.length) {
        dt_profile = dt_profile_table.DataTable({
            processing: true, serverSide: true,
            ajax: { url: '/profiles' },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'price', name: 'price' },
                { data: 'rate_limit', name: 'rate_limit' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
        });
    }

    function updateFormVisibility(form) {
        const limitType = form.querySelector('input[name="limit_type"]:checked').value;
        const timeGroup = form.querySelector('.form-group-time');
        const dataGroup = form.querySelector('.form-group-data');
        const timeInput = timeGroup.querySelector('input');
        const dataInput = dataGroup.querySelector('input');
        const timeStar = timeGroup.querySelector('.required-star');
        const dataStar = dataGroup.querySelector('.required-star');

        timeGroup.style.display = 'none'; dataGroup.style.display = 'none';
        timeInput.required = false; dataInput.required = false;
        timeStar.style.display = 'none'; dataStar.style.display = 'none';

        if (limitType === 'both') {
            timeGroup.style.display = 'flex'; dataGroup.style.display = 'flex';
            timeInput.required = true; dataInput.required = true;
            timeStar.style.display = 'inline'; dataStar.style.display = 'inline';
        } else if (limitType === 'time') {
            timeGroup.style.display = 'flex'; timeInput.required = true; timeStar.style.display = 'inline';
        } else if (limitType === 'data') {
            dataGroup.style.display = 'flex'; dataInput.required = true; dataStar.style.display = 'inline';
        }
    }

    const addModal = document.getElementById('addProfileModal');
    if(addModal) {
        const addForm = addModal.querySelector('form');
        updateFormVisibility(addForm);
        addForm.querySelectorAll('input[name="limit_type"]').forEach(radio => {
            radio.addEventListener('change', () => updateFormVisibility(addForm));
        });
        $('#add-new-profile-btn').on('click', function() {
            addForm.reset();
            new bootstrap.Modal(addModal).show();
        });
        $(addForm).on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                data: $(this).serialize(), url: $(this).attr('action'), type: 'POST',
                success: function (response) {
                    if(dt_profile) dt_profile.ajax.reload();
                    bootstrap.Modal.getInstance(addModal).hide();
                    Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function (err) { Swal.fire({ icon: 'error', title: 'Erreur!', text: 'Une erreur est survenue.' }); }
            });
        });
    }

    const editModal = document.getElementById('editProfileModal');
    if (editModal) {
        const editForm = editModal.querySelector('form');
        editForm.querySelectorAll('input[name="limit_type"]').forEach(radio => {
            radio.addEventListener('change', () => updateFormVisibility(editForm));
        });

        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const profile = JSON.parse(button.getAttribute('data-profile'));
            editForm.action = '/profiles/' + profile.id;
            editForm.querySelector('[name="name"]').value = profile.name;
            editForm.querySelector('[name="price"]').value = profile.price;
            editForm.querySelector('[name="rate_limit"]').value = profile.rate_limit;
            editForm.querySelector('[name="device_limit"]').value = profile.device_limit;
            editForm.querySelector(`input[name="limit_type"][value="${profile.limit_type}"]`).checked = true;
            updateFormVisibility(editForm);
        });

        $(editForm).on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                data: $(this).serialize(), url: $(this).attr('action'), type: 'POST',
                success: function (response) {
                    if(dt_profile) dt_profile.ajax.reload();
                    bootstrap.Modal.getInstance(editModal).hide();
                    Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function (err) { Swal.fire({ icon: 'error', title: 'Erreur!', text: 'Une erreur est survenue.' }); }
            });
        });
    }

    $(document).on('click', '.delete-form button[type="submit"]', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Êtes-vous sûr ?', text: "Cette action est irréversible.", icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Oui, supprimer !', cancelButtonText: 'Annuler'
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});