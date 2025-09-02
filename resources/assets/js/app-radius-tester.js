'use strict';
document.addEventListener('DOMContentLoaded', function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    $('#radiusTestForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const button = form.find('button[type="submit"]');
        const resultCard = $('#result-card');
        const outputPre = $('#test-output');

        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Test en cours...');
        resultCard.hide();

        $.ajax({
            url: '/admin/radius-tester/test',
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                outputPre.text(response.output);
                resultCard.show();
            },
            error: function(err) {
                outputPre.text('Erreur lors de l\'exécution du test.');
                resultCard.show();
            },
            complete: function() {
                button.prop('disabled', false).text('Lancer le test');
            }
        });
    });
});