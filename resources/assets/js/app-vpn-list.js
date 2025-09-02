'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_vpn_table = $('.datatables-vpn-accounts');
    var dt_vpn;

    if (dt_vpn_table.length) {
        dt_vpn = dt_vpn_table.DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: '/vpn-accounts' },
            columns: [
                { data: 'username', name: 'username' },
                { data: 'server_name', name: 'vpnServer.name' },
                { data: 'local_ip_address', name: 'local_ip_address' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
        });
    }

    const addModal = document.getElementById('addVpnAccountModal');
    if (addModal) {
        const addForm = addModal.querySelector('form');
        $(addForm).on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                data: $(this).serialize(),
                url: '/vpn-accounts',
                type: 'POST',
                success: function(response) {
                    if (dt_vpn) dt_vpn.ajax.reload();
                    bootstrap.Modal.getInstance(addModal).hide();
                    Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(err) {
                    Swal.fire({ icon: 'error', title: 'Erreur!', text: err.responseJSON.error || 'Une erreur est survenue.' });
                }
            });
        });
    }

    dt_vpn_table.on('click', '.item-delete', function() {
        var vpn_id = $(this).data('id');
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
                    url: '/vpn-accounts/' + vpn_id,
                    type: 'DELETE',
                    success: function(response) {
                        if (dt_vpn) dt_vpn.ajax.reload();
                        Swal.fire({ icon: 'success', title: 'Supprimé!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    },
                });
            }
        });
    });
});