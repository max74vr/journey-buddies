/**
 * GDPR Compliance JavaScript
 */

(function($) {
    'use strict';

    // Cookie banner functionality
    $(document).ready(function() {
        const $cookieBanner = $('#cdv-cookie-banner');
        const $cookieModal = $('#cdv-cookie-modal');

        // Accept all cookies
        $('#cdv-accept-cookies').on('click', function() {
            acceptCookies('all', true, true);
        });

        // Accept only essential cookies
        $('#cdv-accept-essential').on('click', function() {
            acceptCookies('essential', false, false);
        });

        // Show cookie settings modal
        $('#cdv-cookie-settings').on('click', function() {
            $cookieModal.addClass('active');
        });

        // Close modal
        $('.cdv-modal-close, .cdv-modal').on('click', function(e) {
            if (e.target === this) {
                $cookieModal.removeClass('active');
            }
        });

        // Save cookie preferences from modal
        $('#cdv-save-cookie-preferences').on('click', function() {
            const analytics = $('#analytics-cookies').is(':checked');
            const marketing = $('#marketing-cookies').is(':checked');

            acceptCookies('custom', analytics, marketing);
            $cookieModal.removeClass('active');
        });

        // Accept cookies function
        function acceptCookies(type, analytics, marketing) {
            // Set cookies immediately on client-side as well
            setCookie('cdv_cookies_accepted', '1', 365);
            setCookie('cdv_analytics_consent', analytics ? '1' : '0', 365);
            setCookie('cdv_marketing_consent', marketing ? '1' : '0', 365);

            // Hide banner immediately
            $cookieBanner.fadeOut(300, function() {
                $(this).remove();
            });

            // Send to server for logging
            $.ajax({
                url: cdvGDPR.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_accept_cookies',
                    nonce: cdvGDPR.nonce,
                    consent_type: type,
                    analytics: analytics,
                    marketing: marketing
                },
                success: function(response) {
                    if (response.success) {
                        // Reload analytics scripts if accepted
                        if (analytics) {
                            loadAnalytics();
                        }
                    }
                },
                error: function() {
                    console.error('Error saving cookie preferences');
                }
            });
        }

        // Helper function to set cookies
        function setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            const secure = window.location.protocol === 'https:' ? '; secure' : '';
            document.cookie = name + '=' + value + '; expires=' + expires.toUTCString() + '; path=/' + secure + '; SameSite=Lax';
        }

        // Load analytics scripts (placeholder)
        function loadAnalytics() {
            // This is where you would load Google Analytics, etc.
            console.log('Analytics enabled');
        }

        // Export user data
        $(document).on('click', '#cdv-export-data', function(e) {
            e.preventDefault();
            const $btn = $(this);

            $btn.prop('disabled', true).text('Generazione in corso...');

            $.ajax({
                url: cdvGDPR.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_export_data',
                    nonce: cdvGDPR.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Convert data to JSON string
                        const dataStr = JSON.stringify(response.data.data, null, 2);
                        const dataBlob = new Blob([dataStr], { type: 'application/json' });

                        // Create download link
                        const url = URL.createObjectURL(dataBlob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);

                        showNotification('I tuoi dati sono stati esportati con successo!', 'success');
                    } else {
                        showNotification(response.data.message || 'Errore durante l\'esportazione', 'error');
                    }
                },
                error: function() {
                    showNotification('Errore di connessione', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Scarica i Tuoi Dati');
                }
            });
        });

        // Request account deletion
        $(document).on('click', '#cdv-request-deletion', function(e) {
            e.preventDefault();

            const reason = prompt('Per favore, specifica il motivo della richiesta di cancellazione (opzionale):');

            if (reason === null) {
                return; // User cancelled
            }

            if (!confirm('Sei sicuro di voler richiedere la cancellazione del tuo account? Questa azione è irreversibile e tutti i tuoi dati verranno eliminati.')) {
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).text('Invio in corso...');

            $.ajax({
                url: cdvGDPR.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_request_deletion',
                    nonce: cdvGDPR.nonce,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                        $btn.text('Richiesta Inviata').removeClass('btn-danger').addClass('btn-secondary');
                    } else {
                        showNotification(response.data.message || 'Errore durante l\'invio della richiesta', 'error');
                        $btn.prop('disabled', false).text('Richiedi Cancellazione Account');
                    }
                },
                error: function() {
                    showNotification('Errore di connessione', 'error');
                    $btn.prop('disabled', false).text('Richiedi Cancellazione Account');
                }
            });
        });

        // Notification function
        function showNotification(message, type) {
            const notification = $('<div class="cdv-notification ' + type + '">' + message + '</div>');
            $('body').append(notification);

            notification.fadeIn(300);

            setTimeout(function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }

        // Update cookie consent
        $(document).on('change', '.gdpr-consent-checkbox', function() {
            const consentType = $(this).data('consent-type');
            const value = $(this).is(':checked');

            $.ajax({
                url: cdvGDPR.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_update_consent',
                    nonce: cdvGDPR.nonce,
                    consent_type: consentType,
                    value: value
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Preferenze aggiornate', 'success');
                    }
                }
            });
        });
    });

})(jQuery);
