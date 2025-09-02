'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    $('#apply-coupon-btn').on('click', function() {
        const couponCode = $('#coupon_code').val();
        const originalPrice = $('#original-price').data('price');
        const statusDiv = $('#coupon-status');

        $.ajax({
            url: '/apply-coupon',
            type: 'POST',
            data: {
                coupon_code: couponCode,
                original_price: originalPrice
            },
            success: function(response) {
                $('#discount-row').show();
                $('#discount-amount').text('- ' + response.discount_amount + ' FCFA');
                $('#final-price').text(response.final_price + ' FCFA');
                statusDiv.html(`<div class="text-success">${response.success}</div>`);
            },
            error: function(err) {
                $('#discount-row').hide();
                $('#final-price').text(new Intl.NumberFormat('fr-FR').format(originalPrice) + ' FCFA');
                statusDiv.html(`<div class="text-danger">${err.responseJSON.error}</div>`);
            }
        });
    });
});
