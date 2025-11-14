/**
 * Admin JavaScript for Compagni di Viaggi
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Approve travel button
        $(document).on('click', '.cdv-approve-travel', function(e) {
            e.preventDefault();

            const btn = $(this);
            const travelId = btn.data('travel-id');

            if (!confirm('Sei sicuro di voler approvare questo viaggio?')) {
                return;
            }

            btn.prop('disabled', true).text('Approvazione...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_approve_travel',
                    nonce: cdvAdmin.nonce,
                    travel_id: travelId
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showNotice('Viaggio approvato con successo!', 'success');

                        // Reload page after 1 second
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotice(response.data.message || 'Errore durante l\'approvazione', 'error');
                        btn.prop('disabled', false).text('✓ Approva');
                    }
                },
                error: function() {
                    showNotice('Errore di connessione', 'error');
                    btn.prop('disabled', false).text('✓ Approva');
                }
            });
        });

        // Reject travel button
        $(document).on('click', '.cdv-reject-travel', function(e) {
            e.preventDefault();

            const btn = $(this);
            const travelId = btn.data('travel-id');

            const reason = prompt('Motivo del rifiuto (opzionale):');

            if (reason === null) {
                return; // User cancelled
            }

            btn.prop('disabled', true).text('Rifiuto...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_reject_travel',
                    nonce: cdvAdmin.nonce,
                    travel_id: travelId,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('Viaggio rifiutato', 'info');

                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotice(response.data.message || 'Errore durante il rifiuto', 'error');
                        btn.prop('disabled', false).text('✗ Rifiuta');
                    }
                },
                error: function() {
                    showNotice('Errore di connessione', 'error');
                    btn.prop('disabled', false).text('✗ Rifiuta');
                }
            });
        });

        // Show admin notice
        function showNotice(message, type) {
            const noticeClass = type === 'success' ? 'notice-success' :
                               type === 'error' ? 'notice-error' :
                               'notice-info';

            const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap h1').first().after(notice);

            // Auto dismiss after 3 seconds
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    });

})(jQuery);
