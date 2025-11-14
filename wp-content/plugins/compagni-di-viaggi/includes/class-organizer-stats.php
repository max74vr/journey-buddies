<?php
/**
 * Organizer Statistics System
 * Statistiche avanzate per organizzatori di viaggi
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Organizer_Stats {

    /**
     * Initialize
     */
    public static function init() {
        // AJAX endpoints
        add_action('wp_ajax_cdv_get_organizer_stats', array(__CLASS__, 'ajax_get_organizer_stats'));
        add_action('wp_ajax_cdv_get_travel_analytics', array(__CLASS__, 'ajax_get_travel_analytics'));
    }

    /**
     * Get overview statistics for an organizer
     */
    public static function get_overview_stats($user_id) {
        global $wpdb;

        $stats = array();

        // Total travels created
        $stats['total_travels'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'viaggio' AND post_author = %d AND post_status = 'publish'",
            $user_id
        )));

        // Active travels (not completed/cancelled)
        $stats['active_travels'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'cdv_travel_status'
            WHERE p.post_type = 'viaggio' AND p.post_author = %d AND p.post_status = 'publish'
            AND (pm.meta_value IS NULL OR pm.meta_value IN ('open', 'full'))",
            $user_id
        )));

        // Total participants (accepted)
        $participants_table = $wpdb->prefix . 'cdv_participants';
        $stats['total_participants'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$participants_table} pt
            INNER JOIN {$wpdb->posts} p ON pt.travel_id = p.ID
            WHERE p.post_author = %d AND pt.status = 'accepted'",
            $user_id
        )));

        // Pending requests
        $stats['pending_requests'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$participants_table} pt
            INNER JOIN {$wpdb->posts} p ON pt.travel_id = p.ID
            WHERE p.post_author = %d AND pt.status = 'pending'",
            $user_id
        )));

        // Average rating
        $stats['average_rating'] = floatval(get_user_meta($user_id, 'cdv_reputation_score', true)) ?: 0;

        // Total reviews received
        $reviews_table = $wpdb->prefix . 'cdv_reviews';
        $stats['total_reviews'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$reviews_table} WHERE reviewed_id = %d",
            $user_id
        )));

        // Total revenue (sum of budgets for accepted participants)
        $stats['total_revenue'] = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT SUM(pm.meta_value) FROM {$participants_table} pt
            INNER JOIN {$wpdb->posts} p ON pt.travel_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'cdv_budget'
            WHERE p.post_author = %d AND pt.status = 'accepted'",
            $user_id
        )));

        // This month stats
        $first_day_this_month = date('Y-m-01');
        $stats['this_month_travels'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'viaggio' AND post_author = %d
            AND post_status = 'publish' AND post_date >= %s",
            $user_id,
            $first_day_this_month
        )));

        $stats['this_month_participants'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$participants_table} pt
            INNER JOIN {$wpdb->posts} p ON pt.travel_id = p.ID
            WHERE p.post_author = %d AND pt.status = 'accepted'
            AND pt.created_at >= %s",
            $user_id,
            $first_day_this_month
        )));

        return $stats;
    }

    /**
     * Get travel performance statistics
     */
    public static function get_travel_performance($user_id, $limit = 10) {
        global $wpdb;

        $travels = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title,
                pm_max.meta_value as max_participants,
                pm_budget.meta_value as budget,
                pm_start.meta_value as start_date,
                pm_status.meta_value as travel_status,
                (SELECT COUNT(*) FROM {$wpdb->prefix}cdv_participants
                 WHERE travel_id = p.ID AND status = 'accepted') as accepted_count,
                (SELECT COUNT(*) FROM {$wpdb->prefix}cdv_participants
                 WHERE travel_id = p.ID AND status = 'pending') as pending_count,
                p.post_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_max ON p.ID = pm_max.post_id AND pm_max.meta_key = 'cdv_max_participants'
            LEFT JOIN {$wpdb->postmeta} pm_budget ON p.ID = pm_budget.post_id AND pm_budget.meta_key = 'cdv_budget'
            LEFT JOIN {$wpdb->postmeta} pm_start ON p.ID = pm_start.post_id AND pm_start.meta_key = 'cdv_start_date'
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'cdv_travel_status'
            WHERE p.post_type = 'viaggio' AND p.post_author = %d AND p.post_status = 'publish'
            ORDER BY p.post_date DESC
            LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);

        // Calculate fill rate and revenue for each travel
        foreach ($travels as &$travel) {
            $max = intval($travel['max_participants']) ?: 1;
            $accepted = intval($travel['accepted_count']);
            $travel['fill_rate'] = round(($accepted / $max) * 100, 1);
            $travel['potential_revenue'] = floatval($travel['budget']) * $accepted;
        }

        return $travels;
    }

    /**
     * Get monthly statistics for charts
     */
    public static function get_monthly_stats($user_id, $months = 6) {
        global $wpdb;

        $stats = array();
        $participants_table = $wpdb->prefix . 'cdv_participants';

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-$i months"));
            $next_date = date('Y-m-01', strtotime('-' . ($i - 1) . ' months'));
            $month_label = date_i18n('M Y', strtotime($date));

            // Travels created this month
            $travels = intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                WHERE post_type = 'viaggio' AND post_author = %d
                AND post_status = 'publish'
                AND post_date >= %s AND post_date < %s",
                $user_id,
                $date,
                $next_date
            )));

            // Participants joined this month
            $participants = intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$participants_table} pt
                INNER JOIN {$wpdb->posts} p ON pt.travel_id = p.ID
                WHERE p.post_author = %d AND pt.status = 'accepted'
                AND pt.created_at >= %s AND pt.created_at < %s",
                $user_id,
                $date,
                $next_date
            )));

            // Revenue this month
            $revenue = floatval($wpdb->get_var($wpdb->prepare(
                "SELECT SUM(pm.meta_value) FROM {$participants_table} pt
                INNER JOIN {$wpdb->posts} p ON pt.travel_id = p.ID
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'cdv_budget'
                WHERE p.post_author = %d AND pt.status = 'accepted'
                AND pt.created_at >= %s AND pt.created_at < %s",
                $user_id,
                $date,
                $next_date
            )));

            $stats[] = array(
                'month' => $month_label,
                'travels' => $travels,
                'participants' => $participants,
                'revenue' => $revenue ?: 0
            );
        }

        return $stats;
    }

    /**
     * Get popular destinations
     */
    public static function get_popular_destinations($user_id, $limit = 10) {
        global $wpdb;

        $destinations = $wpdb->get_results($wpdb->prepare(
            "SELECT pm.meta_value as destination, COUNT(*) as travel_count,
                    SUM((SELECT COUNT(*) FROM {$wpdb->prefix}cdv_participants
                         WHERE travel_id = p.ID AND status = 'accepted')) as total_participants
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'cdv_destination'
            WHERE p.post_type = 'viaggio' AND p.post_author = %d AND p.post_status = 'publish'
            GROUP BY pm.meta_value
            ORDER BY travel_count DESC, total_participants DESC
            LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);

        return $destinations;
    }

    /**
     * Get participant demographics
     */
    public static function get_participant_demographics($user_id) {
        global $wpdb;
        $participants_table = $wpdb->prefix . 'cdv_participants';

        // Get all participants for organizer's travels
        $participant_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pt.user_id FROM {$participants_table} pt
            INNER JOIN {$wpdb->posts} p ON pt.travel_id = p.ID
            WHERE p.post_author = %d AND pt.status = 'accepted'",
            $user_id
        ));

        if (empty($participant_ids)) {
            return array(
                'total' => 0,
                'gender_breakdown' => array(),
                'age_groups' => array(),
                'repeat_travelers' => 0
            );
        }

        $demographics = array();
        $demographics['total'] = count($participant_ids);

        // Gender breakdown (if stored in user meta)
        $gender_counts = array();
        foreach ($participant_ids as $participant_id) {
            $gender = get_user_meta($participant_id, 'cdv_gender', true);
            $gender = $gender ?: 'Non specificato';
            $gender_counts[$gender] = isset($gender_counts[$gender]) ? $gender_counts[$gender] + 1 : 1;
        }
        $demographics['gender_breakdown'] = $gender_counts;

        // Age groups (if birthdate stored)
        $age_groups = array(
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-55' => 0,
            '56+' => 0
        );

        foreach ($participant_ids as $participant_id) {
            $birthdate = get_user_meta($participant_id, 'cdv_birthdate', true);
            if ($birthdate) {
                $age = date_diff(date_create($birthdate), date_create('now'))->y;
                if ($age >= 18 && $age <= 25) {
                    $age_groups['18-25']++;
                } elseif ($age >= 26 && $age <= 35) {
                    $age_groups['26-35']++;
                } elseif ($age >= 36 && $age <= 45) {
                    $age_groups['36-45']++;
                } elseif ($age >= 46 && $age <= 55) {
                    $age_groups['46-55']++;
                } elseif ($age >= 56) {
                    $age_groups['56+']++;
                }
            }
        }

        $demographics['age_groups'] = $age_groups;

        // Repeat travelers (participated in more than one travel)
        $placeholders = implode(',', array_fill(0, count($participant_ids), '%d'));
        $repeat_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$participants_table} pt
            INNER JOIN {$wpdb->posts} p ON pt.travel_id = p.ID
            WHERE p.post_author = %d AND pt.status = 'accepted'
            AND pt.user_id IN ($placeholders)
            GROUP BY pt.user_id
            HAVING COUNT(*) > 1",
            $user_id,
            ...$participant_ids
        ));

        $demographics['repeat_travelers'] = intval($repeat_count);

        return $demographics;
    }

    /**
     * AJAX: Get organizer statistics
     */
    public static function ajax_get_organizer_stats() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $user_id = get_current_user_id();

        $overview = self::get_overview_stats($user_id);
        $travel_performance = self::get_travel_performance($user_id, 10);
        $monthly_stats = self::get_monthly_stats($user_id, 6);
        $destinations = self::get_popular_destinations($user_id, 5);
        $demographics = self::get_participant_demographics($user_id);

        wp_send_json_success(array(
            'overview' => $overview,
            'travel_performance' => $travel_performance,
            'monthly_stats' => $monthly_stats,
            'popular_destinations' => $destinations,
            'demographics' => $demographics
        ));
    }

    /**
     * AJAX: Get analytics for a specific travel
     */
    public static function ajax_get_travel_analytics() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        if (!$travel_id) {
            wp_send_json_error(array('message' => 'ID viaggio non valido'));
        }

        // Verify ownership
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Non autorizzato'));
        }

        global $wpdb;
        $participants_table = $wpdb->prefix . 'cdv_participants';

        $analytics = array();

        // Participant stats
        $analytics['accepted'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$participants_table} WHERE travel_id = %d AND status = 'accepted'",
            $travel_id
        )));

        $analytics['pending'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$participants_table} WHERE travel_id = %d AND status = 'pending'",
            $travel_id
        )));

        $analytics['rejected'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$participants_table} WHERE travel_id = %d AND status = 'rejected'",
            $travel_id
        )));

        // Wishlist count
        $wishlist_table = $wpdb->prefix . 'cdv_wishlist';
        $analytics['wishlist_count'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wishlist_table} WHERE travel_id = %d",
            $travel_id
        )));

        // Max participants and budget
        $analytics['max_participants'] = intval(get_post_meta($travel_id, 'cdv_max_participants', true));
        $analytics['budget'] = floatval(get_post_meta($travel_id, 'cdv_budget', true));
        $analytics['potential_revenue'] = $analytics['budget'] * $analytics['accepted'];

        wp_send_json_success($analytics);
    }
}
