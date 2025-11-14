<?php
/**
 * User Profiles
 *
 * Gestisce i profili pubblici degli utenti
 */

class CDV_User_Profiles {

    public static function init() {
        add_action('init', array(__CLASS__, 'add_rewrite_rules'));
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'handle_profile_template'));

        // Flush rewrite rules if needed
        if (get_option('cdv_flush_rewrite_rules_flag')) {
            flush_rewrite_rules();
            delete_option('cdv_flush_rewrite_rules_flag');
        }
    }

    /**
     * Aggiunge rewrite rules per i profili utente
     * URL: /utente/username/
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^utente/([^/]+)/?$',
            'index.php?cdv_user_profile=$matches[1]',
            'top'
        );

        // Set flag per flush rewrite rules al prossimo caricamento
        if (!get_option('cdv_rewrite_rules_version') || get_option('cdv_rewrite_rules_version') !== '1.1') {
            update_option('cdv_flush_rewrite_rules_flag', '1');
            update_option('cdv_rewrite_rules_version', '1.1');
        }
    }

    /**
     * Aggiunge query vars personalizzate
     */
    public static function add_query_vars($vars) {
        $vars[] = 'cdv_user_profile';
        return $vars;
    }

    /**
     * Carica il template del profilo utente
     */
    public static function handle_profile_template() {
        $username = get_query_var('cdv_user_profile');

        if (!$username) {
            return;
        }

        // Cerca l'utente per username
        $user = get_user_by('login', $username);

        if (!$user) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        // Controlla che sia un viaggiatore approvato
        // Administrators and users without the meta are allowed (for compatibility)
        $approved = get_user_meta($user->ID, 'cdv_user_approved', true);
        $is_admin = in_array('administrator', $user->roles);

        // Allow if: admin, approved ('1' or 'approved'), or meta not set (backward compatibility)
        if (!$is_admin && $approved !== 'approved' && $approved !== '1' && !empty($approved) && $approved !== false) {
            // Only block if approval meta is explicitly set to a rejection value
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        // Carica il template
        include locate_template('page-profilo-utente.php');
        exit;
    }

    /**
     * Ottiene i dati pubblici del profilo utente
     */
    public static function get_public_profile($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // Dati base sempre pubblici
        $profile = array(
            'ID' => $user->ID,
            'username' => $user->user_login,
            'display_name' => $user->display_name,
            'avatar_url' => get_avatar_url($user->ID, array('size' => 200)),
            'bio' => get_user_meta($user->ID, 'cdv_bio', true),
            'city' => get_user_meta($user->ID, 'cdv_city', true),
            'country' => get_user_meta($user->ID, 'cdv_country', true),
            'languages' => get_user_meta($user->ID, 'cdv_languages', true),
            'travel_styles' => get_user_meta($user->ID, 'cdv_travel_styles', true),
            'interests' => get_user_meta($user->ID, 'cdv_interests', true),
            'member_since' => $user->user_registered,
            'badges' => CDV_Badges::get_user_badges($user->ID),
        );

        // Dati condizionali in base alle impostazioni privacy
        $show_age = get_user_meta($user->ID, 'cdv_show_age', true);
        if ($show_age === 'yes') {
            $birth_date = get_user_meta($user->ID, 'cdv_birth_date', true);
            if ($birth_date) {
                $profile['age'] = self::calculate_age($birth_date);
            }
        }

        $show_email = get_user_meta($user->ID, 'cdv_show_email', true);
        if ($show_email === 'yes') {
            $profile['email'] = $user->user_email;
        }

        $show_phone = get_user_meta($user->ID, 'cdv_show_phone', true);
        if ($show_phone === 'yes') {
            $profile['phone'] = get_user_meta($user->ID, 'cdv_phone', true);
        }

        $show_social = get_user_meta($user->ID, 'cdv_show_social', true);
        if ($show_social === 'yes') {
            $profile['instagram'] = get_user_meta($user->ID, 'cdv_instagram', true);
            $profile['facebook'] = get_user_meta($user->ID, 'cdv_facebook', true);
        }

        return $profile;
    }

    /**
     * Calcola l'età da una data di nascita
     */
    private static function calculate_age($birth_date) {
        $dob = new DateTime($birth_date);
        $now = new DateTime();
        return $now->diff($dob)->y;
    }

    /**
     * Ottiene i viaggi dell'utente (organizzati)
     */
    public static function get_user_travels($user_id, $status = 'publish') {
        $args = array(
            'post_type' => 'viaggio',
            'author' => $user_id,
            'post_status' => $status,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        return new WP_Query($args);
    }

    /**
     * Ottiene le statistiche dell'utente
     */
    public static function get_user_stats($user_id) {
        global $wpdb;

        // Conta viaggi organizzati
        $organized_count = count_user_posts($user_id, 'viaggio');

        // Conta partecipazioni
        $table_name = $wpdb->prefix . 'cdv_participants';
        $participated_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'accepted'",
            $user_id
        ));

        // Media recensioni
        $reviews_table = $wpdb->prefix . 'cdv_reviews';
        $avg_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $reviews_table WHERE reviewed_user_id = %d",
            $user_id
        ));

        // Conta recensioni
        $reviews_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $reviews_table WHERE reviewed_user_id = %d",
            $user_id
        ));

        return array(
            'organized' => (int) $organized_count,
            'participated' => (int) $participated_count,
            'avg_rating' => $avg_rating ? round($avg_rating, 1) : 0,
            'reviews_count' => (int) $reviews_count,
        );
    }

    /**
     * Genera l'URL del profilo utente
     */
    public static function get_profile_url($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return '';
        }

        return home_url('/utente/' . $user->user_login . '/');
    }
}
