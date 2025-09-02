'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_server_table = $('.datatables-vpn-servers');
    var dt_server;

    if (dt_server_table.length) {
        dt_server = dt_server_table.DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: '/admin/vpn-servers' },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'ip_address', name: 'ip_address' },
                { data: 'api_user', name: 'api_user' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
        });
    }

    const addModal = document.getElementById('addServerModal');
    if (addModal) {
        const addForm = addModal.querySelector('form');
        const modalTitle = addModal.querySelector('.modal-title');
        const serverIdInput = addModal.querySelector('#server_id');

        $('#add-new-server-btn').on('click', function() {
            addForm.reset();
            serverIdInput.value = '';
            $(addForm).find('input[name="_method"]').remove();
            modalTitle.innerHTML = 'Ajouter un Serveur VPN';
            addForm.querySelector('[name="api_password"]').required = true;
            new bootstrap.Modal(addModal).show();
        });

        $(addForm).on('submit', function(e) {
            e.preventDefault();
            let url = serverIdInput.value ? '/admin/vpn-servers/' + serverIdInput.value : '/admin/vpn-servers';
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

        dt_server_table.on('click', '.item-edit', function() {
            var server_id = $(this).data('id');
            $.get('/admin/vpn-servers/' + server_id + '/edit', function(data) {
                addForm.reset();
                serverIdInput.value = data.id;
                modalTitle.innerHTML = 'Modifier le Serveur VPN';
                $(addForm).find('[name="name"]').val(data.name);
                $(addForm).find('[name="ip_address"]').val(data.ip_address);
                $(addForm).find('[name="api_user"]').val(data.api_user);
                $(addForm).find('[name="api_port"]').val(data.api_port);
                $(addForm).find('[name="domain_name"]').val(data.domain_name);
                $(addForm).find('[name="local_ip_address"]').val(data.local_ip_address);
                $(addForm).find('[name="ip_range"]').val(data.ip_range);
                $(addForm).find('[name="account_limit"]').val(data.account_limit);
                $(addForm).find('[name="is_active"]').prop('checked', data.is_active);
                $(addForm).find('[name="api_password"]').attr('placeholder', 'Laisser vide pour ne pas changer').prop('required', false);
                new bootstrap.Modal(addModal).show();
            });
        });

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
                        url: '/admin/vpn-servers/' + server_id,
                        type: 'DELETE',
                        success: function(response) {
                            if (dt_server) dt_server.ajax.reload();
                            Swal.fire({ icon: 'success', title: 'Supprimé!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                        },
                    });
                }
            });
        });

        $('.test-connection-btn').on('click', function() {
            const form = $(this).closest('form');
            const statusSpan = form.find('.connection-status');
            const testButton = $(this);

            testButton.prop('disabled', true);
            statusSpan.html('<span class="spinner-border spinner-border-sm"></span> Test...');

            $.ajax({
                url: '/admin/vpn-servers/test-connection',
                type: 'POST',
                data: {
                    ip_address: form.find('[name="ip_address"]').val(),
                    api_user: form.find('[name="api_user"]').val(),
                    api_password: form.find('[name="api_password"]').val(),
                    api_port: form.find('[name="api_port"]').val(),
                },
                success: function(response) {
                    statusSpan.html('<span class="text-success">Succès !</span>');
                },
                error: function() {
                    statusSpan.html('<span class="text-danger">Échec.</span>');
                },
                complete: function() {
                    testButton.prop('disabled', false);
                }
            });
        });
    }
});
