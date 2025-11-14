<?php
/**
 * Reviews system
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Reviews {

    /**
     * Initialize
     */
    public static function init() {
        // Hooks will be added here
    }

    /**
     * Add review
     */
    public static function add_review($travel_id, $reviewer_id, $reviewed_id, $scores, $comment = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_reviews';

        // Validate scores (1-5)
        foreach ($scores as $score) {
            if ($score < 1 || $score > 5) {
                return new WP_Error('invalid_score', __('I punteggi devono essere tra 1 e 5', 'compagni-di-viaggi'));
            }
        }

        // Check if reviewer and reviewed were both participants
        if (!self::can_review($travel_id, $reviewer_id, $reviewed_id)) {
            return new WP_Error('cannot_review', __('Non puoi recensire questo utente', 'compagni-di-viaggi'));
        }

        // Check if review already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE travel_id = %d AND reviewer_id = %d AND reviewed_id = %d",
            $travel_id,
            $reviewer_id,
            $reviewed_id
        ));

        if ($exists) {
            return new WP_Error('review_exists', __('Hai già recensito questo utente per questo viaggio', 'compagni-di-viaggi'));
        }

        // Insert review
        $result = $wpdb->insert(
            $table,
            array(
                'travel_id' => $travel_id,
                'reviewer_id' => $reviewer_id,
                'reviewed_id' => $reviewed_id,
                'punctuality' => $scores['punctuality'],
                'group_spirit' => $scores['group_spirit'],
                'respect' => $scores['respect'],
                'adaptability' => $scores['adaptability'],
                'comment' => sanitize_textarea_field($comment),
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s')
        );

        if ($result) {
            // Update reviewed user's reputation
            CDV_User_Meta::update_user_reputation($reviewed_id);
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Check if user can review another user for a travel
     */
    public static function can_review($travel_id, $reviewer_id, $reviewed_id) {
        // Travel must be completed
        $status = get_post_meta($travel_id, 'cdv_travel_status', true);
        if ($status !== 'completed') {
            return false;
        }

        // Both must have been participants
        $reviewer_participated = CDV_Participants::is_participant($travel_id, $reviewer_id, 'accepted');
        $reviewed_participated = CDV_Participants::is_participant($travel_id, $reviewed_id, 'accepted');

        // Or reviewer is the organizer
        $travel = get_post($travel_id);
        $reviewer_is_organizer = ($travel->post_author == $reviewer_id);

        return ($reviewer_is_organizer || $reviewer_participated) && $reviewed_participated;
    }

    /**
     * Get reviews for a user
     */
    public static function get_user_reviews($user_id, $limit = 10) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_reviews';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
            WHERE reviewed_id = %d
            ORDER BY created_at DESC
            LIMIT %d",
            $user_id,
            $limit
        ));
    }

    /**
     * Get pending reviews for a user (travels they should review)
     */
    public static function get_pending_reviews($user_id) {
        global $wpdb;

        $table_participants = $wpdb->prefix . 'cdv_travel_participants';
        $table_reviews = $wpdb->prefix . 'cdv_reviews';

        // Get completed travels where user was a participant
        $travels = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT tp.travel_id
            FROM $table_participants tp
            INNER JOIN {$wpdb->posts} p ON tp.travel_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE tp.user_id = %d
            AND tp.status = 'accepted'
            AND pm.meta_key = 'cdv_travel_status'
            AND pm.meta_value = 'completed'",
            $user_id
        ));

        $pending = array();

        foreach ($travels as $travel) {
            // Get other participants
            $participants = CDV_Participants::get_participants($travel->travel_id, 'accepted');

            foreach ($participants as $participant) {
                if ($participant->user_id == $user_id) {
                    continue;
                }

                // Check if already reviewed
                $reviewed = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_reviews
                    WHERE travel_id = %d
                    AND reviewer_id = %d
                    AND reviewed_id = %d",
                    $travel->travel_id,
                    $user_id,
                    $participant->user_id
                ));

                if (!$reviewed) {
                    $pending[] = array(
                        'travel_id' => $travel->travel_id,
                        'user_id' => $participant->user_id,
                    );
                }
            }
        }

        return $pending;
    }

    /**
     * Get detailed review statistics for a user
     */
    public static function get_user_review_stats($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cdv_reviews';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_reviews,
                AVG(punctuality) as avg_punctuality,
                AVG(group_spirit) as avg_group_spirit,
                AVG(respect) as avg_respect,
                AVG(adaptability) as avg_adaptability,
                (AVG(punctuality) + AVG(group_spirit) + AVG(respect) + AVG(adaptability)) / 4 as overall_average
            FROM $table
            WHERE reviewed_id = %d",
            $user_id
        ));

        // Get rating distribution
        $distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT
                ROUND((punctuality + group_spirit + respect + adaptability) / 4) as rating,
                COUNT(*) as count
            FROM $table
            WHERE reviewed_id = %d
            GROUP BY rating
            ORDER BY rating DESC",
            $user_id
        ), OBJECT_K);

        return array(
            'stats' => $stats,
            'distribution' => $distribution,
        );
    }

    /**
     * Add reply to a review (from reviewed user)
     */
    public static function add_review_reply($review_id, $user_id, $reply_text) {
        global $wpdb;
        $table = $wpdb->prefix . 'cdv_reviews';

        // Get the review
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $review_id
        ));

        if (!$review) {
            return new WP_Error('review_not_found', 'Recensione non trovata');
        }

        // Check if user is the reviewed person
        if ($review->reviewed_id != $user_id) {
            return new WP_Error('not_authorized', 'Non sei autorizzato a rispondere a questa recensione');
        }

        // Check if reply already exists
        if (!empty($review->reply)) {
            return new WP_Error('reply_exists', 'Hai già risposto a questa recensione');
        }

        // Add reply
        $result = $wpdb->update(
            $table,
            array(
                'reply' => sanitize_textarea_field($reply_text),
                'reply_date' => current_time('mysql')
            ),
            array('id' => $review_id),
            array('%s', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Report a review as inappropriate
     */
    public static function report_review($review_id, $user_id, $reason) {
        global $wpdb;
        $table = $wpdb->prefix . 'cdv_review_reports';

        // Check if already reported by this user
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE review_id = %d AND reporter_id = %d",
            $review_id,
            $user_id
        ));

        if ($exists) {
            return new WP_Error('already_reported', 'Hai già segnalato questa recensione');
        }

        $result = $wpdb->insert(
            $table,
            array(
                'review_id' => $review_id,
                'reporter_id' => $user_id,
                'reason' => sanitize_text_field($reason),
                'status' => 'pending',
            ),
            array('%d', '%d', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get review badge based on stats
     */
    public static function get_review_badge($user_id) {
        $stats = self::get_user_review_stats($user_id);

        if (!$stats['stats'] || $stats['stats']->total_reviews < 3) {
            return null;
        }

        $avg = $stats['stats']->overall_average;
        $total = $stats['stats']->total_reviews;

        if ($avg >= 4.8 && $total >= 20) {
            return array('badge' => 'super_host', 'label' => '🌟 Super Host', 'color' => '#FFD700');
        } elseif ($avg >= 4.5 && $total >= 10) {
            return array('badge' => 'trusted_traveler', 'label' => '✨ Viaggiatore Fidato', 'color' => '#4CAF50');
        } elseif ($avg >= 4.0 && $total >= 5) {
            return array('badge' => 'reliable', 'label' => '👍 Affidabile', 'color' => '#2196F3');
        }

        return null;
    }

    /**
     * Render star rating HTML
     */
    public static function render_stars($rating, $max = 5) {
        $rating = floatval($rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
        $empty_stars = $max - $full_stars - $half_star;

        $html = '<div class="star-rating" data-rating="' . esc_attr($rating) . '">';

        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '<span class="star star-full">★</span>';
        }

        // Half star
        if ($half_star) {
            $html .= '<span class="star star-half">★</span>';
        }

        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<span class="star star-empty">☆</span>';
        }

        $html .= '<span class="rating-value">' . number_format($rating, 1) . '</span>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get reviews with pagination and filters
     */
    public static function get_reviews_paginated($user_id, $page = 1, $per_page = 10, $min_rating = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cdv_reviews';

        $offset = ($page - 1) * $per_page;

        $where = $wpdb->prepare("WHERE reviewed_id = %d", $user_id);

        if ($min_rating !== null) {
            $where .= $wpdb->prepare(" AND (punctuality + group_spirit + respect + adaptability) / 4 >= %f", $min_rating);
        }

        $reviews = $wpdb->get_results(
            "SELECT * FROM $table
            $where
            ORDER BY created_at DESC
            LIMIT $per_page OFFSET $offset"
        );

        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table $where"
        );

        return array(
            'reviews' => $reviews,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        );
    }

    /**
     * Check if review is helpful (likes system)
     */
    public static function mark_review_helpful($review_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cdv_review_helpful';

        // Check if already marked
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE review_id = %d AND user_id = %d",
            $review_id,
            $user_id
        ));

        if ($exists) {
            // Remove helpful mark
            $wpdb->delete($table, array('id' => $exists));
            return array('action' => 'removed', 'count' => self::get_helpful_count($review_id));
        } else {
            // Add helpful mark
            $wpdb->insert(
                $table,
                array('review_id' => $review_id, 'user_id' => $user_id),
                array('%d', '%d')
            );
            return array('action' => 'added', 'count' => self::get_helpful_count($review_id));
        }
    }

    /**
     * Get helpful count for a review
     */
    public static function get_helpful_count($review_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cdv_review_helpful';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE review_id = %d",
            $review_id
        ));
    }
}
