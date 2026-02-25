'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_router_table = $('.datatables-routers');
    var dt_router;


    const addRouterOffcanvas = document.getElementById('offcanvasAddRouter');
    const addForm = document.getElementById('addNewRouterForm');

    $('[data-bs-target="#offcanvasAddRouter"]').on('click', function() {
        if (!addForm) return;
        addForm.reset();
        $('#router_id').val('');
        $('#offcanvasAddRouterLabel').html('Ajouter un Routeur');
    });

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

    if (addRouterOffcanvas) {

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
            $(editOffcanvas).find('[name="api_address"]').val(data.api_address);
            $(editOffcanvas).find('[name="api_port"]').val(data.api_port);
            $(editOffcanvas).find('[name="api_user"]').val(data.api_user);
            $(editOffcanvas).find('[name="api_password"]').val(data.api_password);
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
    
        $.get('/routers/' + router_id + '/radius/install-command', function(data) {
            $('#script-content').val(data.script);
            new bootstrap.Modal(document.getElementById('installScriptModal')).show();
        }).fail(function(err) {
            const msg = err?.responseJSON?.message || err?.responseJSON?.error || 'Impossible de générer le script.';
            Swal.fire({ icon: 'error', title: 'Erreur!', text: msg });
        });
    });



    $('#test-router-api-btn').on('click', function() {
        const apiAddress = $('[name="api_address"]').val();
        const apiPort = $('[name="api_port"]').val();

        if (!apiAddress || !apiPort) {
            Swal.fire({ icon: 'warning', title: 'Champs requis', text: 'Veuillez renseigner API Adresse et API Port.' });
            return;
        }

        $.ajax({
            url: '/routers/test-api',
            type: 'POST',
            data: { api_address: apiAddress, api_port: apiPort },
            success: function(response) {
                Swal.fire({ icon: 'success', title: 'API OK', text: response.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            error: function(err) {
                Swal.fire({ icon: 'error', title: 'Test échoué', text: err?.responseJSON?.message || err?.responseJSON?.error || 'Impossible de joindre API.' });
            }
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