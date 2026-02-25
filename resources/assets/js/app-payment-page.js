'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    const feePercent = parseFloat($('#transaction-fee').data('fee-percent') || 3);

    const formatFcfa = value => `${new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0)))} FCFA`;

    const refreshTotals = (basePrice) => {
        const feeAmount = (Number(basePrice || 0) * feePercent) / 100;
        const total = Number(basePrice || 0) + feeAmount;

        $('#transaction-fee').text(formatFcfa(feeAmount));
        $('#final-price').text(formatFcfa(total)).attr('data-total', total);
        $('#pay-now-btn').text(`Payer ${formatFcfa(total)}`);
    };

    $('#apply-coupon-btn').on('click', function() {
        const couponCode = $('#coupon_code').val();
        const originalPrice = Number($('#original-price').data('price') || 0);
        const statusDiv = $('#coupon-status');

        $.ajax({
            url: '/apply-coupon',
            type: 'POST',
            data: {
                coupon_code: couponCode,
                original_price: originalPrice
            },
            success: function(response) {
                const finalBasePrice = Number(String(response.final_price).replace(/\s/g, '').replace(',', '.')) || 0;
                $('#discount-row').show();
                $('#discount-amount').text('- ' + response.discount_amount + ' FCFA');
                refreshTotals(finalBasePrice);
                statusDiv.html(`<div class="text-success">${response.success}</div>`);
            },
            error: function(err) {
                $('#discount-row').hide();
                refreshTotals(originalPrice);
                statusDiv.html(`<div class="text-danger">${err.responseJSON.error}</div>`);
            }
        });
    });
});