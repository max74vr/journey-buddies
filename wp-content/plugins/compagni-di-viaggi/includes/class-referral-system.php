<?php
/**
 * Referral System
 * Sistema di referral per incentivare gli utenti a invitare amici
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Referral_System {

    /**
     * Initialize
     */
    public static function init() {
        // AJAX endpoints
        add_action('wp_ajax_cdv_get_referral_stats', array(__CLASS__, 'ajax_get_referral_stats'));
        add_action('wp_ajax_cdv_generate_referral_code', array(__CLASS__, 'ajax_generate_referral_code'));

        // Track referrals on user registration
        add_action('user_register', array(__CLASS__, 'track_referral_signup'), 10, 1);

        // Generate referral code for existing users
        add_action('init', array(__CLASS__, 'ensure_referral_codes'));
    }

    /**
     * Create database table for referrals
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_referrals';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            referrer_id bigint(20) NOT NULL,
            referred_id bigint(20) NOT NULL,
            referral_code varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            reward_points int(11) DEFAULT 0,
            reward_given tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY referrer_id (referrer_id),
            KEY referred_id (referred_id),
            KEY referral_code (referral_code),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Generate a unique referral code for a user
     */
    public static function generate_referral_code($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        // Check if user already has a code
        $existing_code = get_user_meta($user_id, 'cdv_referral_code', true);
        if ($existing_code) {
            return $existing_code;
        }

        // Generate unique code based on username + random string
        $username_part = strtoupper(substr($user->user_login, 0, 4));
        $random_part = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $code = $username_part . $random_part;

        // Ensure uniqueness
        while (self::code_exists($code)) {
            $random_part = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            $code = $username_part . $random_part;
        }

        update_user_meta($user_id, 'cdv_referral_code', $code);
        update_user_meta($user_id, 'cdv_referral_code_generated_at', current_time('mysql'));

        return $code;
    }

    /**
     * Check if referral code exists
     */
    private static function code_exists($code) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'cdv_referral_code' AND meta_value = %s",
            $code
        ));
        return $count > 0;
    }

    /**
     * Get user ID by referral code
     */
    public static function get_user_by_referral_code($code) {
        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'cdv_referral_code' AND meta_value = %s LIMIT 1",
            $code
        ));
        return $user_id ? intval($user_id) : null;
    }

    /**
     * Get referral link for a user
     */
    public static function get_referral_link($user_id) {
        $code = get_user_meta($user_id, 'cdv_referral_code', true);
        if (!$code) {
            $code = self::generate_referral_code($user_id);
        }

        $register_url = home_url('/registrazione');
        return add_query_arg('ref', $code, $register_url);
    }

    /**
     * Track referral signup
     */
    public static function track_referral_signup($user_id) {
        // Check if there's a referral code in session or cookie
        $referral_code = null;

        // Check cookie first
        if (isset($_COOKIE['cdv_referral_code'])) {
            $referral_code = sanitize_text_field($_COOKIE['cdv_referral_code']);
        }

        // Check GET parameter (for direct links)
        if (isset($_GET['ref'])) {
            $referral_code = sanitize_text_field($_GET['ref']);
            // Set cookie for future pages
            setcookie('cdv_referral_code', $referral_code, time() + (30 * DAY_IN_SECONDS), '/');
        }

        if (!$referral_code) {
            return;
        }

        // Find referrer
        $referrer_id = self::get_user_by_referral_code($referral_code);
        if (!$referrer_id || $referrer_id == $user_id) {
            return; // Invalid code or self-referral
        }

        // Record referral
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_referrals';

        $wpdb->insert($table_name, array(
            'referrer_id' => $referrer_id,
            'referred_id' => $user_id,
            'referral_code' => $referral_code,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ));

        // Store in user meta for easy access
        update_user_meta($user_id, 'cdv_referred_by', $referrer_id);
        update_user_meta($user_id, 'cdv_referral_code_used', $referral_code);

        // Send notification to referrer
        if (class_exists('CDV_Notifications')) {
            CDV_Notifications::create(
                $referrer_id,
                'new_referral',
                'Nuovo Referral!',
                'Un nuovo utente si è registrato usando il tuo codice referral!',
                home_url('/dashboard?tab=referral')
            );
        }

        // Clear cookie
        setcookie('cdv_referral_code', '', time() - 3600, '/');
    }

    /**
     * Complete referral (when referred user completes first travel or meets criteria)
     */
    public static function complete_referral($referral_id, $reward_points = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_referrals';

        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $referral_id
        ));

        if (!$referral || $referral->status === 'completed') {
            return false;
        }

        // Update referral status
        $wpdb->update(
            $table_name,
            array(
                'status' => 'completed',
                'completed_at' => current_time('mysql'),
                'reward_points' => $reward_points,
                'reward_given' => 1
            ),
            array('id' => $referral_id)
        );

        // Award points to referrer
        $current_points = get_user_meta($referral->referrer_id, 'cdv_referral_points', true);
        $current_points = $current_points ? intval($current_points) : 0;
        update_user_meta($referral->referrer_id, 'cdv_referral_points', $current_points + $reward_points);

        // Increase reputation slightly
        if (class_exists('CDV_Reviews')) {
            $current_reputation = get_user_meta($referral->referrer_id, 'cdv_reputation_score', true);
            $current_reputation = $current_reputation ? floatval($current_reputation) : 0;
            // Small boost (0.1) for referrals
            update_user_meta($referral->referrer_id, 'cdv_reputation_score', min(5.0, $current_reputation + 0.1));
        }

        // Send notification
        if (class_exists('CDV_Notifications')) {
            CDV_Notifications::create(
                $referral->referrer_id,
                'referral_completed',
                'Referral Completato!',
                'Hai guadagnato ' . $reward_points . ' punti! Il tuo referral ha completato la sua prima azione.',
                home_url('/dashboard?tab=referral')
            );
        }

        return true;
    }

    /**
     * Get referral stats for a user
     */
    public static function get_referral_stats($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_referrals';

        $stats = array();

        // Total referrals
        $stats['total'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE referrer_id = %d",
            $user_id
        )));

        // Completed referrals
        $stats['completed'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE referrer_id = %d AND status = 'completed'",
            $user_id
        )));

        // Pending referrals
        $stats['pending'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE referrer_id = %d AND status = 'pending'",
            $user_id
        )));

        // Total points earned
        $stats['total_points'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT SUM(reward_points) FROM $table_name WHERE referrer_id = %d AND reward_given = 1",
            $user_id
        )));

        // Current points balance
        $stats['current_points'] = intval(get_user_meta($user_id, 'cdv_referral_points', true));

        // Referral code
        $stats['code'] = get_user_meta($user_id, 'cdv_referral_code', true);
        if (!$stats['code']) {
            $stats['code'] = self::generate_referral_code($user_id);
        }

        // Referral link
        $stats['link'] = self::get_referral_link($user_id);

        // Recent referrals
        $stats['recent'] = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.user_login, u.display_name
            FROM $table_name r
            LEFT JOIN {$wpdb->users} u ON r.referred_id = u.ID
            WHERE r.referrer_id = %d
            ORDER BY r.created_at DESC
            LIMIT 10",
            $user_id
        ));

        return $stats;
    }

    /**
     * Ensure all users have referral codes
     */
    public static function ensure_referral_codes() {
        // Only run once per day to avoid performance issues
        $last_run = get_option('cdv_referral_codes_last_run');
        if ($last_run && (time() - $last_run) < DAY_IN_SECONDS) {
            return;
        }

        // Get users without referral codes (limited to avoid timeout)
        global $wpdb;
        $users = $wpdb->get_results(
            "SELECT u.ID FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'cdv_referral_code'
            WHERE um.meta_value IS NULL
            LIMIT 50"
        );

        foreach ($users as $user) {
            self::generate_referral_code($user->ID);
        }

        update_option('cdv_referral_codes_last_run', time());
    }

    /**
     * AJAX: Get referral stats
     */
    public static function ajax_get_referral_stats() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $user_id = get_current_user_id();
        $stats = self::get_referral_stats($user_id);

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Generate/regenerate referral code
     */
    public static function ajax_generate_referral_code() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $user_id = get_current_user_id();

        // Check if regeneration is requested
        $regenerate = isset($_POST['regenerate']) && $_POST['regenerate'] === 'true';

        if ($regenerate) {
            // Delete old code
            delete_user_meta($user_id, 'cdv_referral_code');
        }

        $code = self::generate_referral_code($user_id);
        $link = self::get_referral_link($user_id);

        wp_send_json_success(array(
            'code' => $code,
            'link' => $link
        ));
    }

    /**
     * Check if referred user should trigger completion
     * Call this when user completes first travel participation
     */
    public static function check_and_complete_referral($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_referrals';

        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE referred_id = %d AND status = 'pending' LIMIT 1",
            $user_id
        ));

        if ($referral) {
            self::complete_referral($referral->id, 50);
        }
    }
}
