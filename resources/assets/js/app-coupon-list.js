'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_coupon_table = $('.datatables-coupons');
    var dt_coupon;
    const bulkDeleteBtn = $('#bulk-delete-coupons-btn');
    const selectAllCheckbox = $('#coupon-select-all');

    const selectedCouponIds = () => $('.coupon-select:checked').map(function() { return Number($(this).val()); }).get();
    const refreshBulkDeleteState = () => {
        bulkDeleteBtn.prop('disabled', selectedCouponIds().length === 0);
    };

    if (dt_coupon_table.length) {
        dt_coupon = dt_coupon_table.DataTable({
            processing: true, serverSide: true,
            ajax: { url: '/admin/coupons' },
            columns: [
                { data: 'select', name: 'select', orderable: false, searchable: false },
                { data: 'code', name: 'code' },
                { data: 'type', name: 'type' },
                { data: 'value_formatted', name: 'value' },
                { data: 'validity', name: 'validity', orderable: false, searchable: false },
                { data: 'scope', name: 'scope', orderable: false, searchable: false },
                { data: 'usage', name: 'usage', orderable: false, searchable: false },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            drawCallback: function() {
                selectAllCheckbox.prop('checked', false);
                refreshBulkDeleteState();
            }
        });
    }

    const addModal = document.getElementById('addCouponModal');
    if (addModal) {
        const addForm = addModal.querySelector('form');
        const modalTitle = addModal.querySelector('.modal-title');
        const couponIdInput = addModal.querySelector('#coupon_id');

        $('#add-new-coupon-btn').on('click', function() {
            addForm.reset();
            couponIdInput.value = '';
            $(addForm).find('input[name="_method"]').remove();
            modalTitle.innerHTML = 'Ajouter un Bon de Réduction';
            new bootstrap.Modal(addModal).show();
        });

        $(addForm).on('submit', function(e) {
            e.preventDefault();
            let url = couponIdInput.value ? '/admin/coupons/' + couponIdInput.value : '/admin/coupons';
            let method = couponIdInput.value ? 'POST' : 'POST';
            let formData = $(this).serializeArray();
            if (couponIdInput.value) {
                formData.push({name: '_method', value: 'PUT'});
            }

            $.ajax({
                data: $.param(formData), url: url, type: method,
                success: function(response) {
                    if (dt_coupon) dt_coupon.ajax.reload();
                    bootstrap.Modal.getInstance(addModal).hide();
                    Swal.fire({ icon: 'success', title: 'Succès!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                },
                error: function(err) { Swal.fire({ icon: 'error', title: 'Erreur!', text: 'Une erreur est survenue.' }); }
            });
        });

        dt_coupon_table.on('click', '.item-edit', function() {
            var coupon_id = $(this).data('id');
            $.get('/admin/coupons/' + coupon_id + '/edit', function(data) {
                addForm.reset();
                couponIdInput.value = data.id;
                modalTitle.innerHTML = 'Modifier le Bon de Réduction';
                $(addForm).find('[name="code"]').val(data.code);
                $(addForm).find('[name="type"]').val(data.type);
                $(addForm).find('[name="value"]').val(data.value);
                $(addForm).find('[name="starts_at"]').val(data.starts_at ? data.starts_at.replace(' ', 'T').slice(0,16) : '');
                $(addForm).find('[name="ends_at"]').val(data.ends_at ? data.ends_at.replace(' ', 'T').slice(0,16) : '');
                $(addForm).find('[name="user_id"]').val(data.user_id || '');
                $(addForm).find('[name="plan_id"]').val(data.plan_id || '');
                $(addForm).find('[name="is_active"]').prop('checked', data.is_active);
                $(addForm).find('[name="auto_generate"]').prop('checked', false);
                $(addForm).find('[name="generate_count"]').val(1);
                $(addForm).find('[name="prefix"]').val('');
                new bootstrap.Modal(addModal).show();
            });
        });

        dt_coupon_table.on('click', '.item-delete', function() {
            var coupon_id = $(this).data('id');
            Swal.fire({
                title: 'Êtes-vous sûr ?', text: "Cette action est irréversible !", icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Oui, supprimer !'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/admin/coupons/' + coupon_id, type: 'DELETE',
                        success: function(response) {
                            if (dt_coupon) dt_coupon.ajax.reload();
                            Swal.fire({ icon: 'success', title: 'Supprimé!', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                        }
                    });
                }
            });
        });

        dt_coupon_table.on('change', '.coupon-select', function() {
            refreshBulkDeleteState();
        });

        selectAllCheckbox.on('change', function() {
            $('.coupon-select').prop('checked', this.checked);
            refreshBulkDeleteState();
        });

        bulkDeleteBtn.on('click', function() {
            const ids = selectedCouponIds();
            if (!ids.length) return;

            Swal.fire({
                title: 'Supprimer les coupons sélectionnés ?',
                text: `Vous allez supprimer ${ids.length} coupon(s).`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, supprimer',
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '/admin/coupons/bulk-delete',
                    type: 'DELETE',
                    data: { ids },
                    success: function(response) {
                        if (dt_coupon) dt_coupon.ajax.reload();
                        selectAllCheckbox.prop('checked', false);
                        refreshBulkDeleteState();
                        Swal.fire({ icon: 'success', title: 'Succès', text: response.success, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Erreur', text: 'Suppression groupée impossible.' });
                    }
                });
            });
        });
    }
});