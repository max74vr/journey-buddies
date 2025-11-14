<?php
/**
 * Travel Stories (Racconti di Viaggi)
 *
 * Gestisce i racconti di viaggio pubblicati dagli utenti
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Travel_Stories {

    /**
     * Initialize
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_type'));
        add_action('init', array(__CLASS__, 'register_taxonomies'));
        add_action('wp_ajax_cdv_submit_story', array(__CLASS__, 'ajax_submit_story'));
        add_action('wp_ajax_cdv_delete_story', array(__CLASS__, 'ajax_delete_story'));
        add_action('wp_ajax_cdv_upload_story_image', array(__CLASS__, 'ajax_upload_story_image'));
    }

    /**
     * Register Custom Post Type
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => 'Racconti di Viaggio',
            'singular_name'         => 'Racconto',
            'menu_name'             => 'Racconti',
            'add_new'               => 'Aggiungi Racconto',
            'add_new_item'          => 'Aggiungi Nuovo Racconto',
            'edit_item'             => 'Modifica Racconto',
            'new_item'              => 'Nuovo Racconto',
            'view_item'             => 'Visualizza Racconto',
            'search_items'          => 'Cerca Racconti',
            'not_found'             => 'Nessun racconto trovato',
            'not_found_in_trash'    => 'Nessun racconto nel cestino',
            'all_items'             => 'Tutti i Racconti',
        );

        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'has_archive'           => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_nav_menus'     => true,
            'show_in_rest'          => true,
            'menu_position'         => 21,
            'menu_icon'             => 'dashicons-book-alt',
            'supports'              => array('title', 'editor', 'thumbnail', 'author', 'comments', 'excerpt'),
            'rewrite'               => array('slug' => 'racconti'),
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
        );

        register_post_type('racconto', $args);
    }

    /**
     * Register Taxonomies
     */
    public static function register_taxonomies() {
        // Categoria del racconto (consiglio, esperienza, guida, ecc.)
        register_taxonomy('categoria_racconto', 'racconto', array(
            'labels' => array(
                'name'          => 'Categorie Racconto',
                'singular_name' => 'Categoria',
                'search_items'  => 'Cerca Categorie',
                'all_items'     => 'Tutte le Categorie',
                'edit_item'     => 'Modifica Categoria',
                'add_new_item'  => 'Aggiungi Categoria',
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'categoria-racconto'),
        ));

        // Tag per i racconti
        register_taxonomy('tag_racconto', 'racconto', array(
            'labels' => array(
                'name'          => 'Tag Racconto',
                'singular_name' => 'Tag',
                'search_items'  => 'Cerca Tag',
                'all_items'     => 'Tutti i Tag',
                'edit_item'     => 'Modifica Tag',
                'add_new_item'  => 'Aggiungi Tag',
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'tag-racconto'),
        ));

        // Usa la stessa tassonomia destinazione dei viaggi
        register_taxonomy_for_object_type('destinazione', 'racconto');
    }

    /**
     * AJAX: Submit Story
     */
    public static function ajax_submit_story() {
        try {
            // Security check
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'Devi essere autenticato'));
            }

            check_ajax_referer('cdv_ajax_nonce', 'nonce');

            $user_id = get_current_user_id();
            $user = wp_get_current_user();

            // Check if user is viaggiatore
            if (!in_array('viaggiatore', $user->roles) && !in_array('administrator', $user->roles)) {
                wp_send_json_error(array('message' => 'Solo i viaggiatori possono pubblicare racconti'));
            }

            // Get data
            $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;
            $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
            $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
            $destination = isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '';
            $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
            $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
            $travel_date = isset($_POST['travel_date']) ? sanitize_text_field($_POST['travel_date']) : '';
            $duration = isset($_POST['duration']) ? sanitize_text_field($_POST['duration']) : '';

            // Validation
            if (empty($title) || empty($content)) {
                wp_send_json_error(array('message' => 'Titolo e contenuto sono obbligatori'));
            }

            // If editing, check ownership
            if ($story_id > 0) {
                $existing_post = get_post($story_id);
                if (!$existing_post || $existing_post->post_type !== 'racconto') {
                    wp_send_json_error(array('message' => 'Racconto non trovato'));
                }
                if ($existing_post->post_author != $user_id && !current_user_can('edit_others_posts')) {
                    wp_send_json_error(array('message' => 'Non hai i permessi per modificare questo racconto'));
                }
            }

            // Create or update post
            $post_data = array(
                'post_type'    => 'racconto',
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => $story_id > 0 ? get_post_status($story_id) : 'pending', // New stories are pending
                'post_author'  => $user_id,
            );

            if ($story_id > 0) {
                $post_data['ID'] = $story_id;
                $result = wp_update_post($post_data);
            } else {
                $result = wp_insert_post($post_data);
            }

            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => 'Errore durante il salvataggio: ' . $result->get_error_message()));
            }

            $post_id = $story_id > 0 ? $story_id : $result;

            // Save meta data
            if ($destination) {
                update_post_meta($post_id, 'cdv_destination', $destination);
                // Also set as taxonomy
                wp_set_post_terms($post_id, array($destination), 'destinazione');
            }

            if ($travel_date) {
                update_post_meta($post_id, 'cdv_travel_date', $travel_date);
            }

            if ($duration) {
                update_post_meta($post_id, 'cdv_duration', $duration);
            }

            // Set category
            if ($category > 0) {
                wp_set_post_terms($post_id, array($category), 'categoria_racconto');
            }

            // Set tags
            if (!empty($tags)) {
                $tags_array = array_map('trim', explode(',', $tags));
                wp_set_post_terms($post_id, $tags_array, 'tag_racconto');
            }

            // Award badge for first story
            if ($story_id === 0) {
                $user_stories = get_posts(array(
                    'post_type' => 'racconto',
                    'author' => $user_id,
                    'posts_per_page' => 1,
                ));

                if (count($user_stories) === 1) {
                    CDV_Badges::award_badge($user_id, 'first_story');
                }
            }

            // Get the final post status
            $final_status = get_post_status($post_id);
            $is_pending = $final_status === 'pending';

            $message = $story_id > 0
                ? 'Racconto aggiornato con successo!'
                : ($is_pending
                    ? 'Racconto inviato con successo! Sarà pubblicato dopo l\'approvazione da parte dell\'amministratore.'
                    : 'Racconto pubblicato con successo!');

            wp_send_json_success(array(
                'message' => $message,
                'story_id' => $post_id,
                'story_url' => get_permalink($post_id),
                'is_pending' => $is_pending,
            ));

        } catch (Exception $e) {
            error_log('CDV: Error in story submission: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Si è verificato un errore: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX: Delete Story
     */
    public static function ajax_delete_story() {
        try {
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'Devi essere autenticato'));
            }

            check_ajax_referer('cdv_ajax_nonce', 'nonce');

            $user_id = get_current_user_id();
            $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;

            if (!$story_id) {
                wp_send_json_error(array('message' => 'ID racconto non valido'));
            }

            $post = get_post($story_id);
            if (!$post || $post->post_type !== 'racconto') {
                wp_send_json_error(array('message' => 'Racconto non trovato'));
            }

            // Check ownership
            if ($post->post_author != $user_id && !current_user_can('delete_others_posts')) {
                wp_send_json_error(array('message' => 'Non hai i permessi per eliminare questo racconto'));
            }

            $result = wp_delete_post($story_id, true);

            if (!$result) {
                wp_send_json_error(array('message' => 'Errore durante l\'eliminazione'));
            }

            wp_send_json_success(array('message' => 'Racconto eliminato con successo'));

        } catch (Exception $e) {
            error_log('CDV: Error in story deletion: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Si è verificato un errore'));
        }
    }

    /**
     * AJAX: Upload Story Image
     */
    public static function ajax_upload_story_image() {
        try {
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'Devi essere autenticato'));
            }

            check_ajax_referer('cdv_ajax_nonce', 'nonce');

            $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;

            if (!$story_id) {
                wp_send_json_error(array('message' => 'ID racconto non valido'));
            }

            // Check ownership
            $post = get_post($story_id);
            if ($post->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
                wp_send_json_error(array('message' => 'Non hai i permessi per modificare questo racconto'));
            }

            if (!isset($_FILES['story_image'])) {
                wp_send_json_error(array('message' => 'Nessuna immagine caricata'));
            }

            // Validate file
            $allowed_types = array('image/jpeg', 'image/png', 'image/jpg');
            $max_size = 10 * 1024 * 1024; // 10MB

            $file = $_FILES['story_image'];

            if (!in_array($file['type'], $allowed_types)) {
                wp_send_json_error(array('message' => 'Formato immagine non valido. Usa JPG o PNG.'));
            }

            if ($file['size'] > $max_size) {
                wp_send_json_error(array('message' => 'Immagine troppo grande. Massimo 10MB.'));
            }

            // Upload file
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $upload = wp_handle_upload($file, array('test_form' => false));

            if (isset($upload['error'])) {
                wp_send_json_error(array('message' => $upload['error']));
            }

            // Create attachment
            $attachment_id = wp_insert_attachment(array(
                'post_mime_type' => $upload['type'],
                'post_title'     => 'Immagine Racconto ' . $story_id,
                'post_content'   => '',
                'post_status'    => 'inherit',
                'post_parent'    => $story_id,
            ), $upload['file'], $story_id);

            if (is_wp_error($attachment_id)) {
                wp_send_json_error(array('message' => 'Errore durante la creazione dell\'allegato'));
            }

            // Generate metadata
            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attach_data);

            // Set as featured image
            set_post_thumbnail($story_id, $attachment_id);

            wp_send_json_success(array(
                'message' => 'Immagine caricata con successo',
                'image_url' => wp_get_attachment_url($attachment_id),
                'attachment_id' => $attachment_id,
            ));

        } catch (Exception $e) {
            error_log('CDV: Error in story image upload: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Si è verificato un errore'));
        }
    }

    /**
     * Get user stories
     */
    public static function get_user_stories($user_id, $status = 'publish') {
        $args = array(
            'post_type'      => 'racconto',
            'author'         => $user_id,
            'post_status'    => $status,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        return new WP_Query($args);
    }

    /**
     * Get story stats
     */
    public static function get_story_stats($story_id) {
        $views = get_post_meta($story_id, 'cdv_views', true);
        $likes = get_post_meta($story_id, 'cdv_likes', true);

        return array(
            'views' => $views ? intval($views) : 0,
            'likes' => $likes ? intval($likes) : 0,
            'comments' => get_comments_number($story_id),
        );
    }

    /**
     * Increment story views
     */
    public static function increment_views($story_id) {
        $views = get_post_meta($story_id, 'cdv_views', true);
        $views = $views ? intval($views) + 1 : 1;
        update_post_meta($story_id, 'cdv_views', $views);
    }
}
