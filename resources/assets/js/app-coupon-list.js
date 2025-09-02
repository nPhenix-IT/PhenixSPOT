'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var dt_coupon_table = $('.datatables-coupons');
    var dt_coupon;

    if (dt_coupon_table.length) {
        dt_coupon = dt_coupon_table.DataTable({
            processing: true, serverSide: true,
            ajax: { url: '/admin/coupons' },
            columns: [
                { data: 'code', name: 'code' },
                { data: 'type', name: 'type' },
                { data: 'value_formatted', name: 'value' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
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
                $(addForm).find('[name="is_active"]').prop('checked', data.is_active);
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
    }
});