<?php
/**
 * Travel Gallery System
 *
 * Manages photo galleries for travel posts
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Travel_Gallery {

    /**
     * Initialize
     */
    public static function init() {
        // AJAX handlers
        add_action('wp_ajax_cdv_upload_gallery_image', array(__CLASS__, 'ajax_upload_gallery_image'));
        add_action('wp_ajax_cdv_delete_gallery_image', array(__CLASS__, 'ajax_delete_gallery_image'));
        add_action('wp_ajax_cdv_set_featured_gallery_image', array(__CLASS__, 'ajax_set_featured_image'));
        add_action('wp_ajax_cdv_reorder_gallery_images', array(__CLASS__, 'ajax_reorder_gallery_images'));
    }

    /**
     * Upload gallery image via AJAX
     */
    public static function ajax_upload_gallery_image() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        if (!$travel_id) {
            wp_send_json_error(array('message' => 'ID viaggio non valido'));
        }

        // Check if user owns the travel
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Non hai i permessi per modificare questo viaggio'));
        }

        // Check if file was uploaded
        if (empty($_FILES['gallery_image'])) {
            wp_send_json_error(array('message' => 'Nessun file caricato'));
        }

        $file = $_FILES['gallery_image'];

        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        $file_type = wp_check_filetype($file['name']);

        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Formato file non valido. Usa JPG, PNG, GIF o WebP'));
        }

        // Check file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => 'Il file è troppo grande. Dimensione massima: 5MB'));
        }

        // Upload the file
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('gallery_image', $travel_id);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        // Add to gallery meta
        $gallery = get_post_meta($travel_id, 'cdv_gallery_images', true);
        if (!is_array($gallery)) {
            $gallery = array();
        }

        $gallery[] = $attachment_id;
        update_post_meta($travel_id, 'cdv_gallery_images', $gallery);

        // Return image data
        wp_send_json_success(array(
            'message' => 'Immagine caricata con successo',
            'image' => array(
                'id' => $attachment_id,
                'url' => wp_get_attachment_url($attachment_id),
                'thumb' => wp_get_attachment_image_url($attachment_id, 'thumbnail')
            )
        ));
    }

    /**
     * Delete gallery image
     */
    public static function ajax_delete_gallery_image() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

        if (!$travel_id || !$image_id) {
            wp_send_json_error(array('message' => 'Parametri non validi'));
        }

        // Check permissions
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Non hai i permessi'));
        }

        // Remove from gallery
        $gallery = get_post_meta($travel_id, 'cdv_gallery_images', true);
        if (is_array($gallery)) {
            $gallery = array_diff($gallery, array($image_id));
            $gallery = array_values($gallery); // Re-index
            update_post_meta($travel_id, 'cdv_gallery_images', $gallery);
        }

        // Delete attachment
        wp_delete_attachment($image_id, true);

        wp_send_json_success(array('message' => 'Immagine eliminata'));
    }

    /**
     * Set image as featured/thumbnail
     */
    public static function ajax_set_featured_image() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

        if (!$travel_id || !$image_id) {
            wp_send_json_error(array('message' => 'Parametri non validi'));
        }

        // Check permissions
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Non hai i permessi'));
        }

        // Set as featured image
        set_post_thumbnail($travel_id, $image_id);

        wp_send_json_success(array('message' => 'Immagine in evidenza aggiornata'));
    }

    /**
     * Reorder gallery images
     */
    public static function ajax_reorder_gallery_images() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $order = isset($_POST['order']) ? array_map('intval', $_POST['order']) : array();

        if (!$travel_id || empty($order)) {
            wp_send_json_error(array('message' => 'Parametri non validi'));
        }

        // Check permissions
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Non hai i permessi'));
        }

        // Update gallery order
        update_post_meta($travel_id, 'cdv_gallery_images', $order);

        wp_send_json_success(array('message' => 'Ordine aggiornato'));
    }

    /**
     * Get gallery images for a travel
     */
    public static function get_gallery_images($travel_id) {
        $gallery_ids = get_post_meta($travel_id, 'cdv_gallery_images', true);

        if (empty($gallery_ids) || !is_array($gallery_ids)) {
            return array();
        }

        $images = array();
        foreach ($gallery_ids as $image_id) {
            if (wp_attachment_is_image($image_id)) {
                $images[] = array(
                    'id' => $image_id,
                    'url' => wp_get_attachment_url($image_id),
                    'full' => wp_get_attachment_image_url($image_id, 'full'),
                    'large' => wp_get_attachment_image_url($image_id, 'large'),
                    'medium' => wp_get_attachment_image_url($image_id, 'medium'),
                    'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                    'title' => get_the_title($image_id),
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                );
            }
        }

        return $images;
    }

    /**
     * Get gallery image count
     */
    public static function get_gallery_count($travel_id) {
        $gallery = get_post_meta($travel_id, 'cdv_gallery_images', true);
        return is_array($gallery) ? count($gallery) : 0;
    }
}
