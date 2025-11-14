/**
 * CDV Travel Importer - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Import form submission
         */
        $('#import-form').on('submit', function(e) {
            e.preventDefault();

            const fileInput = $('#json-file')[0];
            const file = fileInput.files[0];

            if (!file) {
                alert('Seleziona un file JSON');
                return;
            }

            // Check file extension
            if (!file.name.endsWith('.json')) {
                alert('Il file deve essere in formato JSON');
                return;
            }

            // Read file
            const reader = new FileReader();

            reader.onload = function(e) {
                try {
                    const jsonData = JSON.parse(e.target.result);

                    if (!Array.isArray(jsonData)) {
                        alert('Il JSON deve contenere un array di viaggi');
                        return;
                    }

                    if (jsonData.length === 0) {
                        alert('Il file JSON è vuoto');
                        return;
                    }

                    // Confirm import
                    const downloadImages = $('#download-images').is(':checked');
                    const confirmMsg = `Stai per importare ${jsonData.length} viaggi.` +
                        (downloadImages ? '\n\nIl download delle immagini potrebbe richiedere alcuni minuti.' : '') +
                        '\n\nContinuare?';

                    if (!confirm(confirmMsg)) {
                        return;
                    }

                    // Start import
                    importTravels(jsonData);

                } catch (error) {
                    alert('Errore nel parsing del JSON: ' + error.message);
                }
            };

            reader.onerror = function() {
                alert('Errore nella lettura del file');
            };

            reader.readAsText(file);
        });

        /**
         * Import travels via AJAX
         */
        function importTravels(jsonData) {
            const downloadImages = $('#download-images').is(':checked');
            const authorId = $('#author-id').val();
            const postStatus = $('#post-status').val();

            // Show progress
            $('#import-form').hide();
            $('#import-progress').show();
            $('#import-results').hide();

            // Update progress
            updateProgress(0, `Importazione di ${jsonData.length} viaggi in corso...`);

            $.ajax({
                url: cdvImporter.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_import_travels',
                    nonce: cdvImporter.nonce,
                    json_data: JSON.stringify(jsonData),
                    download_images: downloadImages,
                    author_id: authorId,
                    post_status: postStatus
                },
                success: function(response) {
                    if (response.success) {
                        showResults(response.data);
                    } else {
                        showError(response.data.message || 'Errore durante l\'importazione');
                    }
                },
                error: function(xhr, status, error) {
                    showError('Errore di connessione: ' + error);
                },
                complete: function() {
                    $('#import-progress').hide();
                }
            });
        }

        /**
         * Update progress bar
         */
        function updateProgress(percentage, text) {
            $('#progress-fill').css('width', percentage + '%').text(percentage + '%');
            $('#progress-text').text(text);
        }

        /**
         * Show import results
         */
        function showResults(data) {
            const { imported, total, errors } = data;

            let html = '<h3>✅ Importazione Completata!</h3>';
            html += `<p><strong>${imported} su ${total}</strong> viaggi importati con successo.</p>`;

            if (errors && errors.length > 0) {
                html += '<h4>⚠️ Errori riscontrati:</h4>';
                html += '<ul class="error-list">';
                errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';

                $('#import-results').removeClass('success').addClass('warning');
            } else {
                $('#import-results').removeClass('warning').addClass('success');
            }

            html += '<p><button type="button" class="button" id="import-another">Importa Altri Viaggi</button></p>';
            html += '<p><a href="' + window.location.pathname + '?page=cdv-travel-importer" class="button button-primary">Aggiorna Pagina</a></p>';

            $('#import-results').html(html).show();

            // Import another button
            $(document).on('click', '#import-another', function() {
                $('#import-results').hide();
                $('#import-form').show();
                $('#json-file').val('');
            });
        }

        /**
         * Show error message
         */
        function showError(message) {
            let html = '<h3>❌ Errore</h3>';
            html += '<p>' + message + '</p>';
            html += '<p><button type="button" class="button" id="try-again">Riprova</button></p>';

            $('#import-results').html(html).addClass('error').show();

            $(document).on('click', '#try-again', function() {
                $('#import-results').hide();
                $('#import-form').show();
            });
        }

        /**
         * Delete all imported travels
         */
        $('#delete-imported-travels').on('click', function() {
            if (!confirm('Sei sicuro di voler eliminare TUTTI i viaggi importati con questo plugin?\n\nQuesta azione è irreversibile!')) {
                return;
            }

            const $btn = $(this);
            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: cdvImporter.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_delete_imported_travels',
                    nonce: cdvImporter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Errore: ' + (response.data.message || 'Operazione fallita'));
                    }
                },
                error: function() {
                    alert('Errore di connessione');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        });

    });

})(jQuery);
