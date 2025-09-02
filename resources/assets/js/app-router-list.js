'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_router_table = $('.datatables-routers');
    var dt_router;

    if (dt_router_table.length) {
        dt_router = dt_router_table.DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: '/routers' },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'ip_address', name: 'ip_address' },
                { data: 'brand', name: 'brand' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
        });
    }

    const addRouterOffcanvas = document.getElementById('offcanvasAddRouter');
    if (addRouterOffcanvas) {
        const addForm = document.getElementById('addNewRouterForm');
        
        $(addForm).on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                data: $(this).serialize(),
                url: '/routers',
                type: 'POST',
                success: function(response) {
                    if (dt_router) dt_router.ajax.reload();
                    bootstrap.Offcanvas.getInstance(addRouterOffcanvas).hide();
                    Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(err) {
                    Swal.fire({ icon: 'error', title: 'Erreur!', text: err.responseJSON.error || 'Une erreur est survenue.' });
                }
            });
        });
    }

    dt_router_table.on('click', '.item-edit', function() {
        var router_id = $(this).data('id');
        $.get('/routers/' + router_id + '/edit', function(data) {
            const editOffcanvas = document.getElementById('offcanvasAddRouter');
            $('#router_id').val(data.id);
            $(editOffcanvas).find('[name="name"]').val(data.name);
            $(editOffcanvas).find('[name="ip_address"]').val(data.ip_address);
            $(editOffcanvas).find('[name="brand"]').val(data.brand);
            $(editOffcanvas).find('[name="description"]').val(data.description);
            $('#offcanvasAddRouterLabel').html('Modifier le Routeur');
            new bootstrap.Offcanvas(editOffcanvas).show();
        });
    });

    dt_router_table.on('click', '.item-delete', function() {
        var router_id = $(this).data('id');
        Swal.fire({
            title: 'Êtes-vous sûr ?', text: "Cette action est irréversible !", icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Oui, supprimer !'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/routers/' + router_id, type: 'DELETE',
                    success: function(response) {
                        if (dt_router) dt_router.ajax.reload();
                        Swal.fire({ icon: 'success', title: 'Supprimé!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    }
                });
            }
        });
    });

    dt_router_table.on('click', '.item-install', function() {
        var router_id = $(this).data('id');
        $.get('/routers/' + router_id + '/generate-script', function(data) {
            $('#script-content').val(data.script);
            new bootstrap.Modal(document.getElementById('installScriptModal')).show();
        }).fail(function(err) {
            Swal.fire({ icon: 'error', title: 'Erreur!', text: err.responseJSON.error || 'Impossible de générer le script.' });
        });
    });

    $('#copy-script-btn').on('click', function() {
        const scriptInput = document.getElementById('script-content');
        scriptInput.select();
        scriptInput.setSelectionRange(0, 99999);
        document.execCommand('copy');
        
        const originalText = $(this).html();
        $(this).html('<i class="icon-base ti tabler-check"></i> Copié !');
        setTimeout(() => {
            $(this).html(originalText);
        }, 2000);
    });
});