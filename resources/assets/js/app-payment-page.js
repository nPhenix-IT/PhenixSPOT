'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    const swal = window.Swal;
    const feePercentDefault = parseFloat($('#transaction-fee').data('fee-percent') || 3);
    const formatFcfa = value => `${new Intl.NumberFormat('fr-FR').format(Math.round(Number(value || 0)))} FCFA`;

    let originalPrice = Number($('#original-price').data('price') || 0);
    let discountedBasePrice = originalPrice;
    let selectedChannel = $('input.payment-channel:checked').val() || 'moneyfusion';

    const toast = (icon, title) => {
        if (!swal) return;
        swal.fire({ toast: true, position: 'top-end', timer: 3200, showConfirmButton: false, icon, title });
    };

    const resolveFeePercent = () => (selectedChannel === 'moneyfusion' ? feePercentDefault : 0);

    const refreshTotals = () => {
        const feePercent = resolveFeePercent();
        const feeAmount = Math.round((discountedBasePrice * feePercent) / 100);
        const total = discountedBasePrice + feeAmount;

        $('#fee-percent-label').text(String(feePercent).replace('.', ','));
        $('#transaction-fee').text(formatFcfa(feeAmount));
        $('#final-price').text(formatFcfa(total)).attr('data-total', total);
        $('#left-total-price').text(formatFcfa(total));
    };

    $('input.payment-channel').on('change', function() {
        selectedChannel = this.value;
        refreshTotals();
    });

    $('#apply-coupon-btn').on('click', function() {
        const couponCode = ($('#coupon_code').val() || '').trim();
        const statusDiv = $('#coupon-status');

        if (!couponCode) {
            statusDiv.html('<div class="text-warning">Veuillez saisir un code promo.</div>');
            return;
        }

        $.ajax({
            url: '/apply-coupon',
            type: 'POST',
            data: {
                coupon_code: couponCode,
                original_price: originalPrice
            },
            success: function(response) {
                discountedBasePrice = Number(response.final_price_raw || originalPrice);
                $('#discount-row').show();
                $('#discount-amount').text('- ' + response.discount_amount + ' FCFA');
                statusDiv.html(`<div class="text-success">${response.success}</div>`);
                refreshTotals();
                toast('success', 'Code promo appliqué');
            },
            error: function(err) {
                discountedBasePrice = originalPrice;
                $('#discount-row').hide();
                refreshTotals();
                const msg = err?.responseJSON?.error || 'Code promo invalide.';
                statusDiv.html(`<div class="text-danger">${msg}</div>`);
                toast('error', msg);
            }
        });
    });

    $('#pay-now-btn').on('click', function() {
        const checkoutUrl = $(this).data('checkout-url');
        const couponCode = ($('#coupon_code').val() || '').trim();
        const finalBasePrice = discountedBasePrice;
        const button = $(this);

        button.prop('disabled', true);

        $.ajax({
            url: checkoutUrl,
            type: 'POST',
            data: {
                payment_channel: selectedChannel,
                coupon_code: couponCode,
                final_price: finalBasePrice,
            },
            success: function(response) {
                toast('success', response.message || 'Paiement initialisé');
                if (response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }
                button.prop('disabled', false);
            },
            error: function(err) {
                const msg = err?.responseJSON?.message || 'Impossible de lancer le paiement.';
                if (swal) {
                    swal.fire({ icon: 'error', title: 'Erreur', text: msg });
                }
                toast('error', msg);
                button.prop('disabled', false);
            }
        });
    });

    refreshTotals();
});