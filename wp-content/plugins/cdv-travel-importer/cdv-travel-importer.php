<?php
/**
 * Plugin Name: Compagni di Viaggi - Travel Importer
 * Plugin URI: https://www.compagnidiviaggi.com
 * Description: Importa viaggi da file JSON per popolare il database. Plugin standalone attivabile solo quando serve.
 * Version: 1.0.0
 * Author: Max74vr
 * Author URI: https://github.com/max74vr
 * License: GPL v2 or later
 * Text Domain: cdv-travel-importer
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CDV_IMPORTER_VERSION', '1.0.0');
define('CDV_IMPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CDV_IMPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Importer Class
 */
class CDV_Travel_Importer {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_cdv_import_travels', array($this, 'ajax_import_travels'));
        add_action('wp_ajax_cdv_delete_imported_travels', array($this, 'ajax_delete_imported_travels'));
        add_action('wp_ajax_cdv_download_unsplash_image', array($this, 'ajax_download_image'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Import Viaggi JSON',
            'Import Viaggi JSON',
            'manage_options',
            'cdv-travel-importer',
            array($this, 'admin_page')
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'tools_page_cdv-travel-importer') {
            return;
        }

        wp_enqueue_style(
            'cdv-importer-style',
            CDV_IMPORTER_PLUGIN_URL . 'assets/css/style.css',
            array(),
            CDV_IMPORTER_VERSION
        );

        wp_enqueue_script(
            'cdv-importer-script',
            CDV_IMPORTER_PLUGIN_URL . 'assets/js/script.js',
            array('jquery'),
            CDV_IMPORTER_VERSION,
            true
        );

        wp_localize_script('cdv-importer-script', 'cdvImporter', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cdv_importer_nonce'),
        ));
    }

    /**
     * Admin page
     */
    public function admin_page() {
        // Check if main plugin is active
        if (!class_exists('CDV_Post_Types')) {
            ?>
            <div class="wrap">
                <h1>Import Viaggi JSON</h1>
                <div class="notice notice-error">
                    <p><strong>Errore:</strong> Il plugin "Compagni di Viaggi" deve essere attivo per usare l'importer!</p>
                </div>
            </div>
            <?php
            return;
        }

        // Get import stats
        $imported_count = $this->get_imported_travels_count();

        ?>
        <div class="wrap cdv-importer-wrap">
            <h1>🚀 Import Viaggi da JSON</h1>

            <div class="cdv-importer-intro">
                <p>Carica un file JSON con i dati dei viaggi per popolare il database. Il JSON deve seguire lo schema definito in <code>TRAVEL_DATA_SCHEMA.md</code>.</p>
            </div>

            <?php if ($imported_count > 0) : ?>
                <div class="notice notice-info">
                    <p><strong><?php echo $imported_count; ?> viaggi importati</strong> con questo plugin sono presenti nel database.</p>
                    <p>
                        <button type="button" class="button button-link-delete" id="delete-imported-travels">
                            🗑️ Elimina tutti i viaggi importati
                        </button>
                    </p>
                </div>
            <?php endif; ?>

            <div class="cdv-importer-container">
                <div class="cdv-importer-left">
                    <div class="card">
                        <h2>📤 Upload File JSON</h2>

                        <form id="import-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="json-file">Seleziona file JSON:</label>
                                <input type="file" id="json-file" name="json_file" accept=".json" required>
                                <p class="description">File JSON contenente array di viaggi</p>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="download_images" id="download-images" checked>
                                    Scarica immagini da Unsplash (campo "image_search")
                                </label>
                                <p class="description">Le immagini verranno scaricate e impostate come featured image. Richiede più tempo.</p>
                            </div>

                            <div class="form-group">
                                <label for="author-id">Autore viaggi:</label>
                                <select name="author_id" id="author-id">
                                    <?php
                                    $users = get_users(array('role__in' => array('administrator', 'viaggiatore')));
                                    foreach ($users as $user) {
                                        $selected = $user->ID == 1 ? 'selected' : '';
                                        echo '<option value="' . $user->ID . '" ' . $selected . '>' . esc_html($user->display_name) . ' (' . $user->user_login . ')</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description">L'utente che risulterà organizzatore dei viaggi importati</p>
                            </div>

                            <div class="form-group">
                                <label for="post-status">Stato post:</label>
                                <select name="post_status" id="post-status">
                                    <option value="publish">Pubblicato (publish)</option>
                                    <option value="pending">In attesa (pending)</option>
                                    <option value="draft">Bozza (draft)</option>
                                </select>
                                <p class="description">Se scegli "pending", i viaggi dovranno essere approvati dall'admin</p>
                            </div>

                            <button type="submit" class="button button-primary button-hero" id="import-btn">
                                🚀 Importa Viaggi
                            </button>
                        </form>

                        <div id="import-progress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progress-fill"></div>
                            </div>
                            <p class="progress-text" id="progress-text">Importazione in corso...</p>
                        </div>

                        <div id="import-results" style="display: none;"></div>
                    </div>
                </div>

                <div class="cdv-importer-right">
                    <div class="card">
                        <h3>📋 Formato JSON Richiesto</h3>

                        <p>Il file JSON deve contenere un array di oggetti viaggi:</p>

                        <pre><code>[
  {
    "title": "Titolo viaggio",
    "content": "Descrizione completa...",
    "start_date": "2025-MM-DD",
    "end_date": "2025-MM-DD",
    "destination": "Città",
    "country": "Paese",
    "budget": 500,
    "max_participants": 8,
    "tipo_viaggio": ["Avventura", "Mare"],
    "image_search": "santorini greece"
  },
  ...
]</code></pre>

                        <h4>Campi Obbligatori:</h4>
                        <ul>
                            <li><code>title</code> - Titolo del viaggio</li>
                            <li><code>content</code> - Descrizione</li>
                            <li><code>start_date</code> - Data inizio (YYYY-MM-DD)</li>
                            <li><code>end_date</code> - Data fine (YYYY-MM-DD)</li>
                            <li><code>destination</code> - Destinazione</li>
                            <li><code>country</code> - Paese</li>
                        </ul>

                        <h4>Campi Opzionali:</h4>
                        <ul>
                            <li><code>budget</code> - Budget in euro</li>
                            <li><code>max_participants</code> - Numero massimo</li>
                            <li><code>tipo_viaggio</code> - Array di tipi</li>
                            <li><code>image_search</code> - Query Unsplash</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>💡 Genera JSON con AI</h3>

                        <p>Usa il prompt in <code>TRAVEL_DATA_SCHEMA.md</code> con ChatGPT per generare viaggi fittizi realistici!</p>

                        <ol>
                            <li>Apri ChatGPT</li>
                            <li>Copia il prompt da <code>TRAVEL_DATA_SCHEMA.md</code></li>
                            <li>ChatGPT genererà un JSON valido</li>
                            <li>Salva come file .json</li>
                            <li>Carica qui sopra</li>
                        </ol>
                    </div>

                    <div class="card">
                        <h3>⚠️ Note Importanti</h3>

                        <ul>
                            <li><strong>Backup:</strong> Fai sempre un backup del database prima di importare!</li>
                            <li><strong>Duplicati:</strong> Controlla che non ci siano già viaggi con lo stesso titolo</li>
                            <li><strong>Immagini:</strong> Download da Unsplash può richiedere tempo (5-10 sec per immagine)</li>
                            <li><strong>Moderazione:</strong> Se imposti "pending", dovrai approvare i viaggi manualmente</li>
                            <li><strong>Pulizia:</strong> Usa il pulsante "Elimina importati" per rimuovere tutti i viaggi importati</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Import travels from JSON
     */
    public function ajax_import_travels() {
        check_ajax_referer('cdv_importer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        // Get JSON data from POST
        $json_data = isset($_POST['json_data']) ? json_decode(stripslashes($_POST['json_data']), true) : null;
        $download_images = isset($_POST['download_images']) && $_POST['download_images'] === 'true';
        $author_id = isset($_POST['author_id']) ? intval($_POST['author_id']) : 1;
        $post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'publish';

        if (!$json_data || !is_array($json_data)) {
            wp_send_json_error(array('message' => 'JSON non valido'));
        }

        $imported = 0;
        $errors = array();

        foreach ($json_data as $index => $travel_data) {
            try {
                $post_id = $this->import_single_travel($travel_data, $author_id, $post_status);

                if (is_wp_error($post_id)) {
                    $errors[] = "Viaggio #" . ($index + 1) . ": " . $post_id->get_error_message();
                    continue;
                }

                // Mark as imported by this plugin
                update_post_meta($post_id, '_cdv_imported', '1');
                update_post_meta($post_id, '_cdv_import_date', current_time('mysql'));

                // Download image if requested
                if ($download_images && isset($travel_data['image_search'])) {
                    $this->download_and_attach_image($post_id, $travel_data['image_search']);
                }

                $imported++;

            } catch (Exception $e) {
                $errors[] = "Viaggio #" . ($index + 1) . ": " . $e->getMessage();
            }
        }

        wp_send_json_success(array(
            'imported' => $imported,
            'total' => count($json_data),
            'errors' => $errors,
        ));
    }

    /**
     * Import single travel
     */
    private function import_single_travel($data, $author_id, $post_status) {
        // Validate required fields
        $required = array('title', 'content', 'start_date', 'end_date', 'destination', 'country');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Campo obbligatorio mancante: $field");
            }
        }

        // Create post
        $post_data = array(
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content']),
            'post_status' => $post_status,
            'post_type' => 'viaggio',
            'post_author' => $author_id,
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Add meta fields
        update_post_meta($post_id, 'cdv_start_date', sanitize_text_field($data['start_date']));
        update_post_meta($post_id, 'cdv_end_date', sanitize_text_field($data['end_date']));
        update_post_meta($post_id, 'cdv_destination', sanitize_text_field($data['destination']));
        update_post_meta($post_id, 'cdv_country', sanitize_text_field($data['country']));

        if (isset($data['budget'])) {
            update_post_meta($post_id, 'cdv_budget', intval($data['budget']));
        }

        if (isset($data['max_participants'])) {
            update_post_meta($post_id, 'cdv_max_participants', intval($data['max_participants']));
        }

        update_post_meta($post_id, 'cdv_travel_status', 'open');

        // Add taxonomies
        if (isset($data['tipo_viaggio']) && is_array($data['tipo_viaggio'])) {
            wp_set_post_terms($post_id, $data['tipo_viaggio'], 'tipo_viaggio');
        }

        return $post_id;
    }

    /**
     * Download image from Unsplash and attach to post
     */
    private function download_and_attach_image($post_id, $search_query) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $image_url = "https://source.unsplash.com/1200x600/?" . urlencode($search_query);

        try {
            $image_id = media_sideload_image($image_url, $post_id, $search_query, 'id');

            if (!is_wp_error($image_id)) {
                set_post_thumbnail($post_id, $image_id);
                return $image_id;
            }
        } catch (Exception $e) {
            // Silently fail - image is optional
        }

        return false;
    }

    /**
     * AJAX: Delete all imported travels
     */
    public function ajax_delete_imported_travels() {
        check_ajax_referer('cdv_importer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        $args = array(
            'post_type' => 'viaggio',
            'posts_per_page' => -1,
            'meta_key' => '_cdv_imported',
            'meta_value' => '1',
            'fields' => 'ids',
        );

        $imported_posts = get_posts($args);

        $deleted = 0;
        foreach ($imported_posts as $post_id) {
            if (wp_delete_post($post_id, true)) {
                $deleted++;
            }
        }

        wp_send_json_success(array(
            'deleted' => $deleted,
            'message' => "$deleted viaggi eliminati con successo",
        ));
    }

    /**
     * Get count of imported travels
     */
    private function get_imported_travels_count() {
        $args = array(
            'post_type' => 'viaggio',
            'posts_per_page' => -1,
            'meta_key' => '_cdv_imported',
            'meta_value' => '1',
            'fields' => 'ids',
        );

        $imported = get_posts($args);
        return count($imported);
    }

    /**
     * AJAX: Download single image
     */
    public function ajax_download_image() {
        check_ajax_referer('cdv_importer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';

        if (!$post_id || !$search_query) {
            wp_send_json_error(array('message' => 'Parametri mancanti'));
        }

        $image_id = $this->download_and_attach_image($post_id, $search_query);

        if ($image_id) {
            wp_send_json_success(array(
                'image_url' => wp_get_attachment_url($image_id),
            ));
        } else {
            wp_send_json_error(array('message' => 'Errore download immagine'));
        }
    }
}

// Initialize plugin
function cdv_travel_importer_init() {
    return CDV_Travel_Importer::get_instance();
}
add_action('plugins_loaded', 'cdv_travel_importer_init');
