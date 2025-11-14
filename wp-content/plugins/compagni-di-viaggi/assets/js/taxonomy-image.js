/**
 * Taxonomy Image Upload
 */
jQuery(document).ready(function($) {
    'use strict';

    // Upload image
    $(document).on('click', '.cdv-upload-taxonomy-image', function(e) {
        e.preventDefault();

        var button = $(this);
        var wrapper = button.closest('.term-image-wrap');
        var imagePreview = wrapper.find('.cdv-taxonomy-image-preview');
        var imageId = wrapper.find('.cdv-taxonomy-image-id');
        var removeButton = wrapper.find('.cdv-remove-taxonomy-image');

        // Create a new media uploader instance each time
        var mediaUploader = wp.media({
            title: 'Seleziona o Carica Immagine',
            button: {
                text: 'Usa questa immagine'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        // When an image is selected
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();

            // Set the image preview
            imagePreview.attr('src', attachment.url).show();
            imageId.val(attachment.id);
            removeButton.show();

            // Debug log to verify
            console.log('CDV Taxonomy Image: Selected ID = ' + attachment.id + ', URL = ' + attachment.url);
        });

        // Open the media uploader
        mediaUploader.open();
    });

    // Remove image
    $(document).on('click', '.cdv-remove-taxonomy-image', function(e) {
        e.preventDefault();

        var button = $(this);
        var wrapper = button.closest('.term-image-wrap');
        var imagePreview = wrapper.find('.cdv-taxonomy-image-preview');
        var imageId = wrapper.find('.cdv-taxonomy-image-id');

        // Clear the image
        imagePreview.attr('src', '').hide();
        imageId.val('');
        button.hide();
    });
});
