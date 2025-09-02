'use strict';

document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_server_table = $('.datatables-radius-servers');
    var dt_server;

    // Initialisation de la DataTable
    if (dt_server_table.length) {
        dt_server = dt_server_table.DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: '/admin/radius-servers' },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'ip_address', name: 'ip_address' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        });
    }

    const addModal = document.getElementById('addServerModal');
    if (addModal) {
        const addForm = addModal.querySelector('form');
        const modalTitle = addModal.querySelector('.modal-title');
        const serverIdInput = addModal.querySelector('#server_id');

        // Gérer l'ouverture de la modale pour un nouvel ajout
        $('#add-new-server-btn').on('click', function() {
            addForm.reset();
            serverIdInput.value = '';
            $(addForm).find('input[name="_method"]').remove();
            modalTitle.innerHTML = 'Ajouter un Serveur RADIUS';
            addForm.querySelector('[name="radius_secret"]').required = true;
            new bootstrap.Modal(addModal).show();
        });

        // Gérer la soumission du formulaire (création et mise à jour)
        $(addForm).on('submit', function(e) {
            e.preventDefault();
            let url = serverIdInput.value ? '/admin/radius-servers/' + serverIdInput.value : '/admin/radius-servers';
            let method = serverIdInput.value ? 'POST' : 'POST';
            
            let formData = $(this).serializeArray();
            if (serverIdInput.value) {
                formData.push({name: '_method', value: 'PUT'});
            }

            $.ajax({
                data: $.param(formData),
                url: url,
                type: method,
                success: function(response) {
                    if (dt_server) dt_server.ajax.reload();
                    bootstrap.Modal.getInstance(addModal).hide();
                    Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(err) {
                    Swal.fire({ icon: 'error', title: 'Erreur!', text: 'Une erreur est survenue.' });
                }
            });
        });

        // Gérer l'ouverture de la modale pour l'édition
        dt_server_table.on('click', '.item-edit', function() {
            var server_id = $(this).data('id');
            $.get('/admin/radius-servers/' + server_id + '/edit', function(data) {
                addForm.reset();
                serverIdInput.value = data.id;
                modalTitle.innerHTML = 'Modifier le Serveur RADIUS';
                $(addForm).find('[name="name"]').val(data.name);
                $(addForm).find('[name="ip_address"]').val(data.ip_address);
                $(addForm).find('[name="description"]').val(data.description);
                $(addForm).find('[name="is_active"]').prop('checked', data.is_active);
                $(addForm).find('[name="radius_secret"]').attr('placeholder', 'Laisser vide pour ne pas changer').prop('required', false);
                new bootstrap.Modal(addModal).show();
            });
        });

        // Gérer la suppression d'un serveur
        dt_server_table.on('click', '.item-delete', function() {
            var server_id = $(this).data('id');
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, supprimer !',
                cancelButtonText: 'Annuler',
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/admin/radius-servers/' + server_id,
                        type: 'DELETE',
                        success: function(response) {
                            if (dt_server) dt_server.ajax.reload();
                            Swal.fire({ icon: 'success', title: 'Supprimé!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                        },
                    });
                }
            });
        });
    }
});
