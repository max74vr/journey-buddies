<?php
/**
 * GDPR Compliance System
 *
 * Implements GDPR requirements including:
 * - Cookie consent management
 * - Data export (right to access)
 * - Data deletion (right to be forgotten)
 * - Consent tracking
 * - Privacy controls
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_GDPR {

    /**
     * Initialize
     */
    public static function init() {
        // Cookie consent banner
        add_action('wp_footer', array(__CLASS__, 'render_cookie_banner'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));

        // AJAX handlers
        add_action('wp_ajax_cdv_accept_cookies', array(__CLASS__, 'ajax_accept_cookies'));
        add_action('wp_ajax_nopriv_cdv_accept_cookies', array(__CLASS__, 'ajax_accept_cookies'));
        add_action('wp_ajax_cdv_export_data', array(__CLASS__, 'ajax_export_data'));
        add_action('wp_ajax_cdv_request_deletion', array(__CLASS__, 'ajax_request_deletion'));
        add_action('wp_ajax_cdv_update_consent', array(__CLASS__, 'ajax_update_consent'));

        // Admin actions
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_post_cdv_process_deletion', array(__CLASS__, 'process_deletion_request'));

        // Scheduled data cleanup
        add_action('cdv_gdpr_data_cleanup', array(__CLASS__, 'cleanup_old_data'));

        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('cdv_gdpr_data_cleanup')) {
            wp_schedule_event(time(), 'daily', 'cdv_gdpr_data_cleanup');
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public static function enqueue_scripts() {
        wp_enqueue_style('cdv-gdpr', CDV_PLUGIN_URL . 'assets/css/gdpr.css', array(), CDV_VERSION);
        wp_enqueue_script('cdv-gdpr', CDV_PLUGIN_URL . 'assets/js/gdpr.js', array('jquery'), CDV_VERSION, true);

        wp_localize_script('cdv-gdpr', 'cdvGDPR', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cdv_gdpr_nonce')
        ));
    }

    /**
     * Check if user has accepted cookies
     */
    public static function has_accepted_cookies() {
        return isset($_COOKIE['cdv_cookies_accepted']) && $_COOKIE['cdv_cookies_accepted'] === '1';
    }

    /**
     * Render cookie consent banner
     */
    public static function render_cookie_banner() {
        if (self::has_accepted_cookies()) {
            return;
        }

        $privacy_url = get_privacy_policy_url();
        ?>
        <div id="cdv-cookie-banner" class="cdv-cookie-banner">
            <div class="cookie-banner-content">
                <div class="cookie-banner-text">
                    <h4>🍪 Cookie e Privacy</h4>
                    <p>Utilizziamo cookie essenziali per il funzionamento del sito e cookie analitici per migliorare la tua esperienza.
                    <?php if ($privacy_url) : ?>
                        <a href="<?php echo esc_url($privacy_url); ?>" target="_blank">Leggi la nostra Privacy Policy</a>
                    <?php endif; ?>
                    </p>
                </div>
                <div class="cookie-banner-actions">
                    <button id="cdv-accept-cookies" class="btn btn-primary">
                        Accetta tutti
                    </button>
                    <button id="cdv-accept-essential" class="btn btn-secondary">
                        Solo essenziali
                    </button>
                    <button id="cdv-cookie-settings" class="btn btn-link">
                        Impostazioni
                    </button>
                </div>
            </div>
        </div>

        <!-- Cookie Settings Modal -->
        <div id="cdv-cookie-modal" class="cdv-modal">
            <div class="cdv-modal-content">
                <div class="cdv-modal-header">
                    <h3>Impostazioni Cookie</h3>
                    <button class="cdv-modal-close">&times;</button>
                </div>
                <div class="cdv-modal-body">
                    <div class="cookie-category">
                        <div class="cookie-category-header">
                            <label>
                                <input type="checkbox" checked disabled>
                                <strong>Cookie Essenziali</strong>
                            </label>
                        </div>
                        <p class="cookie-description">
                            Necessari per il funzionamento del sito (autenticazione, carrello, preferenze).
                        </p>
                    </div>
                    <div class="cookie-category">
                        <div class="cookie-category-header">
                            <label>
                                <input type="checkbox" id="analytics-cookies" checked>
                                <strong>Cookie Analitici</strong>
                            </label>
                        </div>
                        <p class="cookie-description">
                            Ci aiutano a capire come utilizzi il sito per migliorare la tua esperienza.
                        </p>
                    </div>
                    <div class="cookie-category">
                        <div class="cookie-category-header">
                            <label>
                                <input type="checkbox" id="marketing-cookies">
                                <strong>Cookie di Marketing</strong>
                            </label>
                        </div>
                        <p class="cookie-description">
                            Utilizzati per mostrare contenuti pubblicitari personalizzati.
                        </p>
                    </div>
                </div>
                <div class="cdv-modal-footer">
                    <button id="cdv-save-cookie-preferences" class="btn btn-primary">
                        Salva Preferenze
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Accept cookies
     */
    public static function ajax_accept_cookies() {
        check_ajax_referer('cdv_gdpr_nonce', 'nonce');

        $consent_type = isset($_POST['consent_type']) ? sanitize_text_field($_POST['consent_type']) : 'all';
        $analytics = isset($_POST['analytics']) ? (bool)$_POST['analytics'] : false;
        $marketing = isset($_POST['marketing']) ? (bool)$_POST['marketing'] : false;

        // Cookie options for better browser compatibility
        $cookie_options = array(
            'expires' => time() + YEAR_IN_SECONDS,
            'path' => '/',
            'domain' => '', // Use default domain
            'secure' => is_ssl(), // Only send over HTTPS if site uses SSL
            'httponly' => false, // Allow JavaScript to read these cookies
            'samesite' => 'Lax' // Prevent CSRF while allowing normal navigation
        );

        // Set cookie consent (1 year)
        setcookie('cdv_cookies_accepted', '1', $cookie_options);

        $cookie_options_analytics = $cookie_options;
        setcookie('cdv_analytics_consent', $analytics ? '1' : '0', $cookie_options_analytics);

        $cookie_options_marketing = $cookie_options;
        setcookie('cdv_marketing_consent', $marketing ? '1' : '0', $cookie_options_marketing);

        // Also set cookies in $_COOKIE superglobal for immediate availability
        $_COOKIE['cdv_cookies_accepted'] = '1';
        $_COOKIE['cdv_analytics_consent'] = $analytics ? '1' : '0';
        $_COOKIE['cdv_marketing_consent'] = $marketing ? '1' : '0';

        // Log consent if user is logged in
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'cdv_cookie_consent', array(
                'timestamp' => current_time('mysql'),
                'type' => $consent_type,
                'analytics' => $analytics,
                'marketing' => $marketing,
                'ip' => self::get_user_ip()
            ));
        }

        wp_send_json_success(array('message' => 'Preferenze salvate'));
    }

    /**
     * AJAX: Export user data
     */
    public static function ajax_export_data() {
        check_ajax_referer('cdv_gdpr_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $data = self::get_user_data($user_id);

        // Create JSON file
        $filename = 'compagni-di-viaggi-data-' . $user_id . '-' . date('Y-m-d') . '.json';

        wp_send_json_success(array(
            'data' => $data,
            'filename' => $filename,
            'download_url' => admin_url('admin-ajax.php?action=cdv_download_data&nonce=' . wp_create_nonce('cdv_download_' . $user_id))
        ));
    }

    /**
     * Get all user data for export
     */
    public static function get_user_data($user_id) {
        global $wpdb;

        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $data = array(
            'user_info' => array(
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'registration_date' => $user->user_registered
            ),
            'profile' => array(
                'bio' => get_user_meta($user_id, 'cdv_bio', true),
                'location' => get_user_meta($user_id, 'cdv_location', true),
                'birthdate' => get_user_meta($user_id, 'cdv_birthdate', true),
                'gender' => get_user_meta($user_id, 'cdv_gender', true),
                'languages' => get_user_meta($user_id, 'cdv_languages', true),
                'interests' => get_user_meta($user_id, 'cdv_interests', true),
                'travel_style' => get_user_meta($user_id, 'cdv_travel_style', true),
                'verified' => get_user_meta($user_id, 'cdv_verified', true),
                'reputation_score' => get_user_meta($user_id, 'cdv_reputation_score', true)
            ),
            'travels' => array(),
            'participations' => array(),
            'reviews' => array(),
            'messages' => array(),
            'wishlist' => CDV_Wishlist::get_user_wishlist($user_id)
        );

        // Get user's travels
        $travels = get_posts(array(
            'post_type' => 'viaggio',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));

        foreach ($travels as $travel) {
            $data['travels'][] = array(
                'title' => $travel->post_title,
                'content' => $travel->post_content,
                'destination' => get_post_meta($travel->ID, 'cdv_destination', true),
                'country' => get_post_meta($travel->ID, 'cdv_country', true),
                'start_date' => get_post_meta($travel->ID, 'cdv_start_date', true),
                'end_date' => get_post_meta($travel->ID, 'cdv_end_date', true),
                'budget' => get_post_meta($travel->ID, 'cdv_budget', true),
                'created_date' => $travel->post_date,
                'status' => $travel->post_status
            );
        }

        // Get participations
        $participations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cdv_participants WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        foreach ($participations as $participation) {
            $travel = get_post($participation['travel_id']);
            if ($travel) {
                $data['participations'][] = array(
                    'travel_title' => $travel->post_title,
                    'status' => $participation['status'],
                    'joined_date' => $participation['joined_at']
                );
            }
        }

        // Get reviews written by user
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cdv_reviews WHERE reviewer_id = %d",
            $user_id
        ), ARRAY_A);

        foreach ($reviews as $review) {
            $reviewed_user = get_userdata($review['user_id']);
            $data['reviews'][] = array(
                'reviewed_user' => $reviewed_user ? $reviewed_user->user_login : 'Unknown',
                'rating' => $review['rating'],
                'comment' => $review['comment'],
                'travel_id' => $review['travel_id'],
                'created_date' => $review['created_at']
            );
        }

        // Get reviews received by user
        $data['reviews_received'] = array();
        $reviews_received = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cdv_reviews WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        foreach ($reviews_received as $review) {
            $reviewer = get_userdata($review['reviewer_id']);
            $data['reviews_received'][] = array(
                'reviewer' => $reviewer ? $reviewer->user_login : 'Unknown',
                'rating' => $review['rating'],
                'comment' => $review['comment'],
                'travel_id' => $review['travel_id'],
                'created_date' => $review['created_at']
            );
        }

        // Get messages (sent)
        $messages_sent = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cdv_messages WHERE sender_id = %d ORDER BY sent_at DESC LIMIT 100",
            $user_id
        ), ARRAY_A);

        foreach ($messages_sent as $msg) {
            $recipient = get_userdata($msg['recipient_id']);
            $data['messages'][] = array(
                'type' => 'sent',
                'recipient' => $recipient ? $recipient->user_login : 'Unknown',
                'message' => $msg['message'],
                'date' => $msg['sent_at']
            );
        }

        // Get messages (received)
        $messages_received = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cdv_messages WHERE recipient_id = %d ORDER BY sent_at DESC LIMIT 100",
            $user_id
        ), ARRAY_A);

        foreach ($messages_received as $msg) {
            $sender = get_userdata($msg['sender_id']);
            $data['messages'][] = array(
                'type' => 'received',
                'sender' => $sender ? $sender->user_login : 'Unknown',
                'message' => $msg['message'],
                'date' => $msg['sent_at'],
                'read' => $msg['is_read']
            );
        }

        return $data;
    }

    /**
     * AJAX: Request account deletion
     */
    public static function ajax_request_deletion() {
        check_ajax_referer('cdv_gdpr_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        // Create deletion request
        $request_id = wp_insert_post(array(
            'post_type' => 'cdv_deletion_request',
            'post_title' => 'Richiesta cancellazione dati - User ' . $user_id,
            'post_content' => $reason,
            'post_status' => 'pending',
            'post_author' => $user_id
        ));

        if ($request_id) {
            // Send email to admin
            $admin_email = get_option('admin_email');
            $user = get_userdata($user_id);

            wp_mail(
                $admin_email,
                'Richiesta Cancellazione Dati GDPR',
                sprintf(
                    "L'utente %s (%s) ha richiesto la cancellazione dei propri dati.\n\nMotivo: %s\n\nPer elaborare la richiesta, vai su: %s",
                    $user->display_name,
                    $user->user_email,
                    $reason,
                    admin_url('admin.php?page=cdv-gdpr-requests')
                )
            );

            // Send confirmation to user
            wp_mail(
                $user->user_email,
                'Richiesta Cancellazione Dati Ricevuta',
                "La tua richiesta di cancellazione dati è stata ricevuta. Verrà elaborata entro 30 giorni come previsto dal GDPR.\n\nTi invieremo una conferma quando il processo sarà completato."
            );

            wp_send_json_success(array(
                'message' => 'Richiesta inviata con successo. Riceverai una conferma via email.'
            ));
        } else {
            wp_send_json_error(array('message' => 'Errore durante la creazione della richiesta'));
        }
    }

    /**
     * Process deletion request (admin action)
     */
    public static function process_deletion_request() {
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi necessari');
        }

        check_admin_referer('cdv_process_deletion');

        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $action = isset($_POST['deletion_action']) ? sanitize_text_field($_POST['deletion_action']) : '';

        if (!$request_id || !$user_id) {
            wp_die('Parametri non validi');
        }

        if ($action === 'anonymize') {
            self::anonymize_user_data($user_id);
            wp_update_post(array('ID' => $request_id, 'post_status' => 'completed'));
            wp_redirect(admin_url('admin.php?page=cdv-gdpr-requests&message=anonymized'));
        } elseif ($action === 'delete') {
            self::delete_user_data($user_id);
            wp_delete_user($user_id);
            wp_update_post(array('ID' => $request_id, 'post_status' => 'completed'));
            wp_redirect(admin_url('admin.php?page=cdv-gdpr-requests&message=deleted'));
        } else {
            wp_die('Azione non valida');
        }

        exit;
    }

    /**
     * Anonymize user data
     */
    public static function anonymize_user_data($user_id) {
        global $wpdb;

        // Anonymize user account
        wp_update_user(array(
            'ID' => $user_id,
            'user_email' => 'deleted-' . $user_id . '@anonymized.local',
            'display_name' => 'Utente Anonimizzato',
            'first_name' => '',
            'last_name' => ''
        ));

        // Delete all user meta except essential data
        $essential_meta = array('wp_capabilities', 'wp_user_level');
        $all_meta = get_user_meta($user_id);

        foreach ($all_meta as $key => $value) {
            if (!in_array($key, $essential_meta)) {
                delete_user_meta($user_id, $key);
            }
        }

        // Anonymize reviews
        $wpdb->update(
            $wpdb->prefix . 'cdv_reviews',
            array('comment' => '[Commento rimosso]'),
            array('reviewer_id' => $user_id)
        );

        // Delete messages
        $wpdb->delete($wpdb->prefix . 'cdv_messages', array('sender_id' => $user_id));
        $wpdb->delete($wpdb->prefix . 'cdv_messages', array('recipient_id' => $user_id));

        // Delete group messages
        $wpdb->delete($wpdb->prefix . 'cdv_group_messages', array('user_id' => $user_id));
    }

    /**
     * Delete all user data
     */
    public static function delete_user_data($user_id) {
        global $wpdb;

        // Delete user's travels
        $travels = get_posts(array(
            'post_type' => 'viaggio',
            'author' => $user_id,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));

        foreach ($travels as $travel) {
            wp_delete_post($travel->ID, true);
        }

        // Delete from custom tables
        $wpdb->delete($wpdb->prefix . 'cdv_participants', array('user_id' => $user_id));
        $wpdb->delete($wpdb->prefix . 'cdv_reviews', array('reviewer_id' => $user_id));
        $wpdb->delete($wpdb->prefix . 'cdv_reviews', array('user_id' => $user_id));
        $wpdb->delete($wpdb->prefix . 'cdv_messages', array('sender_id' => $user_id));
        $wpdb->delete($wpdb->prefix . 'cdv_messages', array('recipient_id' => $user_id));
        $wpdb->delete($wpdb->prefix . 'cdv_group_messages', array('user_id' => $user_id));
    }

    /**
     * Cleanup old data (scheduled task)
     */
    public static function cleanup_old_data() {
        global $wpdb;

        $retention_days = apply_filters('cdv_data_retention_days', 730); // 2 years default

        // Delete old messages
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}cdv_messages
             WHERE sent_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));

        // Delete old group messages
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}cdv_group_messages
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));

        // Delete old completed travels
        $old_travels = get_posts(array(
            'post_type' => 'viaggio',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'cdv_end_date',
                    'value' => date('Y-m-d', strtotime('-' . $retention_days . ' days')),
                    'compare' => '<',
                    'type' => 'DATE'
                )
            )
        ));

        foreach ($old_travels as $travel) {
            // Archive instead of delete
            wp_update_post(array(
                'ID' => $travel->ID,
                'post_status' => 'archived'
            ));
        }
    }

    /**
     * Get user IP address
     */
    private static function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'cdv-admin',
            'GDPR - Richieste Cancellazione',
            'GDPR',
            'manage_options',
            'cdv-gdpr-requests',
            array(__CLASS__, 'render_admin_page')
        );
    }

    /**
     * Render admin page for deletion requests
     */
    public static function render_admin_page() {
        $requests = get_posts(array(
            'post_type' => 'cdv_deletion_request',
            'post_status' => array('pending', 'completed'),
            'posts_per_page' => -1
        ));

        ?>
        <div class="wrap">
            <h1>Richieste Cancellazione Dati GDPR</h1>

            <?php if (isset($_GET['message'])) : ?>
                <div class="notice notice-success">
                    <p>
                        <?php
                        if ($_GET['message'] === 'anonymized') {
                            echo 'Dati utente anonimizzati con successo';
                        } elseif ($_GET['message'] === 'deleted') {
                            echo 'Dati utente eliminati con successo';
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Email</th>
                        <th>Motivo</th>
                        <th>Data Richiesta</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request) :
                        $user = get_userdata($request->post_author);
                        if (!$user) continue;
                    ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html(wp_trim_words($request->post_content, 20)); ?></td>
                            <td><?php echo esc_html($request->post_date); ?></td>
                            <td>
                                <?php if ($request->post_status === 'pending') : ?>
                                    <span style="color: orange;">In Attesa</span>
                                <?php else : ?>
                                    <span style="color: green;">Completata</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($request->post_status === 'pending') : ?>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                        <?php wp_nonce_field('cdv_process_deletion'); ?>
                                        <input type="hidden" name="action" value="cdv_process_deletion">
                                        <input type="hidden" name="request_id" value="<?php echo $request->ID; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                        <input type="hidden" name="deletion_action" value="anonymize">
                                        <button type="submit" class="button" onclick="return confirm('Anonimizzare i dati di questo utente?')">
                                            Anonimizza
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                        <?php wp_nonce_field('cdv_process_deletion'); ?>
                                        <input type="hidden" name="action" value="cdv_process_deletion">
                                        <input type="hidden" name="request_id" value="<?php echo $request->ID; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                        <input type="hidden" name="deletion_action" value="delete">
                                        <button type="submit" class="button button-primary" onclick="return confirm('ATTENZIONE: Eliminare completamente questo utente e tutti i suoi dati? Questa azione è irreversibile!')">
                                            Elimina Completamente
                                        </button>
                                    </form>
                                <?php else : ?>
                                    <em>Completata</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)) : ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Nessuna richiesta</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Register custom post type for deletion requests
     */
    public static function register_deletion_request_post_type() {
        register_post_type('cdv_deletion_request', array(
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'editor', 'author')
        ));
    }
}

// Register post type on init
add_action('init', array('CDV_GDPR', 'register_deletion_request_post_type'));
