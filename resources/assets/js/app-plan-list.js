'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_plan_table = $('.datatables-plans');
    var dt_plan;

    if (dt_plan_table.length) {
        dt_plan = dt_plan_table.DataTable({
            processing: true, serverSide: true,
            ajax: { url: '/admin/plans' },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'price', name: 'price_monthly' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
        });
    }

    const addModal = document.getElementById('addPlanModal');
    if (addModal) {
        const addForm = addModal.querySelector('form');
        const modalTitle = addModal.querySelector('.modal-title');
        const planIdInput = addModal.querySelector('#plan_id');

        $('#add-new-plan-btn').on('click', function() {
            addForm.reset();
            planIdInput.value = '';
            $(addForm).find('input[name="_method"]').remove();
            modalTitle.innerHTML = 'Ajouter un Forfait';
            new bootstrap.Modal(addModal).show();
        });

        $(addForm).on('submit', function(e) {
            e.preventDefault();
            let url = planIdInput.value ? '/admin/plans/' + planIdInput.value : '/admin/plans';
            let method = planIdInput.value ? 'POST' : 'POST';
            let formData = $(this).serializeArray();
            if (planIdInput.value) {
                formData.push({name: '_method', value: 'PUT'});
            }

            $.ajax({
                data: $.param(formData), url: url, type: method,
                success: function(response) {
                    if (dt_plan) dt_plan.ajax.reload();
                    bootstrap.Modal.getInstance(addModal).hide();
                    Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(err) { Swal.fire({ icon: 'error', title: 'Erreur!', text: 'Une erreur est survenue.' }); }
            });
        });

        dt_plan_table.on('click', '.item-edit', function() {
            var plan_id = $(this).data('id');
            $.get('/admin/plans/' + plan_id + '/edit', function(data) {
                addForm.reset();
                planIdInput.value = data.id;
                modalTitle.innerHTML = 'Modifier le Forfait';
                $(addForm).find('[name="name"]').val(data.name);
                $(addForm).find('[name="description"]').val(data.description);
                $(addForm).find('[name="price_monthly"]').val(data.price_monthly);
                $(addForm).find('[name="price_annually"]').val(data.price_annually);
                $(addForm).find('[name="features[routers]"]').val(data.features.routers);
                $(addForm).find('[name="features[vpn_accounts]"]').val(data.features.vpn_accounts);
                $(addForm).find('[name="features[active_users]"]').val(data.features.active_users || data.features.users || '');
                $(addForm).find('[name="features[pppoe]"]').prop('checked', !!data.features.pppoe);
                $(addForm).find('[name="features[sales_page]"]').prop('checked', data.features.sales_page);
                $(addForm).find('[name="features[advanced_reports]"]').prop('checked', !!data.features.advanced_reports);
                $(addForm).find('[name="features[support_level]"]').val(data.features.support_level || 'Standard');
                $(addForm).find('[name="is_active"]').prop('checked', data.is_active);
                new bootstrap.Modal(addModal).show();
            });
        });

        dt_plan_table.on('click', '.item-delete', function() {
            var plan_id = $(this).data('id');
            Swal.fire({
                title: 'Êtes-vous sûr ?', text: "Cette action est irréversible !", icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Oui, supprimer !'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/admin/plans/' + plan_id, type: 'DELETE',
                        success: function(response) {
                            if (dt_plan) dt_plan.ajax.reload();
                            Swal.fire({ icon: 'success', title: 'Supprimé!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                        }
                    });
                }
            });
        });
    }
});