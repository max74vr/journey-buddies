<?php
/**
 * User badges system
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Badges {

    /**
     * Available badge types
     */
    private static $badge_types = array(
        'early_adopter' => array(
            'name' => 'Early Adopter',
            'icon' => '⭐',
            'description' => 'Tra i primi membri della community',
        ),
        'verified' => array(
            'name' => 'Verificato',
            'icon' => '✓',
            'description' => 'Identità verificata',
        ),
        'first_travel' => array(
            'name' => 'Primo Viaggio',
            'icon' => '🚀',
            'description' => 'Ha organizzato il primo viaggio',
        ),
        'first_story' => array(
            'name' => 'Narratore',
            'icon' => '📖',
            'description' => 'Ha pubblicato il primo racconto',
        ),
        'explorer' => array(
            'name' => 'Esploratore',
            'icon' => '🧭',
            'description' => 'Ha partecipato a 5 viaggi',
        ),
        'globetrotter' => array(
            'name' => 'Giramondo',
            'icon' => '✈️',
            'description' => 'Ha partecipato a 10 viaggi',
        ),
        'organizer' => array(
            'name' => 'Organizzatore',
            'icon' => '📅',
            'description' => 'Ha organizzato 5 viaggi',
        ),
        'trusted' => array(
            'name' => 'Affidabile',
            'icon' => '🌟',
            'description' => 'Reputazione superiore a 4.5',
        ),
        'social' => array(
            'name' => 'Socievole',
            'icon' => '🎉',
            'description' => 'Ha lasciato 10 recensioni positive',
        ),
        'storyteller' => array(
            'name' => 'Raccontastorie',
            'icon' => '📚',
            'description' => 'Ha pubblicato 10 racconti',
        ),
    );

    /**
     * Initialize
     */
    public static function init() {
        add_action('user_register', array(__CLASS__, 'award_early_adopter'));
        add_action('cdv_participant_accepted', array(__CLASS__, 'check_travel_badges'));
        add_action('cdv_review_added', array(__CLASS__, 'check_review_badges'));
    }

    /**
     * Award early adopter badge to new users
     */
    public static function award_early_adopter($user_id) {
        self::award_badge($user_id, 'early_adopter');
    }

    /**
     * Award badge to user
     */
    public static function award_badge($user_id, $badge_type) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_user_badges';

        // Check if badge already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND badge_type = %s",
            $user_id,
            $badge_type
        ));

        if ($exists) {
            return false;
        }

        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'badge_type' => $badge_type,
            ),
            array('%d', '%s')
        );

        if ($result) {
            do_action('cdv_badge_awarded', $user_id, $badge_type);
            return true;
        }

        return false;
    }

    /**
     * Get user badges
     */
    public static function get_user_badges($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_user_badges';

        $badges = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY earned_at DESC",
            $user_id
        ));

        $result = array();
        foreach ($badges as $badge) {
            if (isset(self::$badge_types[$badge->badge_type])) {
                $result[] = array_merge(
                    (array) $badge,
                    self::$badge_types[$badge->badge_type]
                );
            }
        }

        return $result;
    }

    /**
     * Check and award travel-related badges
     */
    public static function check_travel_badges($user_id) {
        global $wpdb;

        $table_participants = $wpdb->prefix . 'cdv_travel_participants';

        // Count completed travels
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT tp.travel_id)
            FROM $table_participants tp
            INNER JOIN {$wpdb->postmeta} pm ON tp.travel_id = pm.post_id
            WHERE tp.user_id = %d
            AND tp.status = 'accepted'
            AND pm.meta_key = 'cdv_travel_status'
            AND pm.meta_value = 'completed'",
            $user_id
        ));

        if ($count >= 10) {
            self::award_badge($user_id, 'globetrotter');
        } elseif ($count >= 5) {
            self::award_badge($user_id, 'explorer');
        }

        // Count organized travels
        $organized = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_author = %d
            AND p.post_type = 'viaggio'
            AND p.post_status = 'publish'
            AND pm.meta_key = 'cdv_travel_status'
            AND pm.meta_value = 'completed'",
            $user_id
        ));

        if ($organized >= 5) {
            self::award_badge($user_id, 'organizer');
        }

        // Check reputation
        $reputation = get_user_meta($user_id, 'cdv_reputation_score', true);
        if ($reputation >= 4.5) {
            self::award_badge($user_id, 'trusted');
        }
    }

    /**
     * Check and award review-related badges
     */
    public static function check_review_badges($user_id) {
        global $wpdb;

        $table_reviews = $wpdb->prefix . 'cdv_reviews';

        // Count positive reviews given
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
            FROM $table_reviews
            WHERE reviewer_id = %d
            AND ((punctuality + group_spirit + respect + adaptability) / 4) >= 4",
            $user_id
        ));

        if ($count >= 10) {
            self::award_badge($user_id, 'social');
        }
    }

    /**
     * Get all badge types
     */
    public static function get_badge_types() {
        return self::$badge_types;
    }

    /**
     * Check if user has badge
     */
    public static function has_badge($user_id, $badge_type) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_user_badges';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND badge_type = %s",
            $user_id,
            $badge_type
        ));

        return !is_null($result);
    }
}
