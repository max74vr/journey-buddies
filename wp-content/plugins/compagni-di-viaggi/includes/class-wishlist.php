<?php
/**
 * Travel Wishlist System
 *
 * Allows users to save travels to their wishlist
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Wishlist {

    /**
     * Initialize
     */
    public static function init() {
        // AJAX handlers
        add_action('wp_ajax_cdv_toggle_wishlist', array(__CLASS__, 'ajax_toggle_wishlist'));
        add_action('wp_ajax_cdv_get_wishlist', array(__CLASS__, 'ajax_get_wishlist'));
    }

    /**
     * Add travel to wishlist
     */
    public static function add_to_wishlist($user_id, $travel_id) {
        $wishlist = self::get_user_wishlist($user_id);

        if (in_array($travel_id, $wishlist)) {
            return false; // Already in wishlist
        }

        $wishlist[] = $travel_id;
        return update_user_meta($user_id, 'cdv_wishlist', $wishlist);
    }

    /**
     * Remove travel from wishlist
     */
    public static function remove_from_wishlist($user_id, $travel_id) {
        $wishlist = self::get_user_wishlist($user_id);
        $wishlist = array_diff($wishlist, array($travel_id));
        $wishlist = array_values($wishlist); // Re-index

        return update_user_meta($user_id, 'cdv_wishlist', $wishlist);
    }

    /**
     * Check if travel is in wishlist
     */
    public static function is_in_wishlist($user_id, $travel_id) {
        $wishlist = self::get_user_wishlist($user_id);
        return in_array($travel_id, $wishlist);
    }

    /**
     * Get user's wishlist
     */
    public static function get_user_wishlist($user_id) {
        $wishlist = get_user_meta($user_id, 'cdv_wishlist', true);

        if (!is_array($wishlist)) {
            return array();
        }

        return $wishlist;
    }

    /**
     * Get wishlist count
     */
    public static function get_wishlist_count($user_id) {
        return count(self::get_user_wishlist($user_id));
    }

    /**
     * Get wishlist travels with details
     */
    public static function get_wishlist_travels($user_id) {
        $wishlist_ids = self::get_user_wishlist($user_id);

        if (empty($wishlist_ids)) {
            return array();
        }

        $travels = new WP_Query(array(
            'post_type' => 'viaggio',
            'post__in' => $wishlist_ids,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'post__in',
        ));

        return $travels;
    }

    /**
     * AJAX: Toggle wishlist (add/remove)
     */
    public static function ajax_toggle_wishlist() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        if (!$travel_id) {
            wp_send_json_error(array('message' => 'ID viaggio non valido'));
        }

        $user_id = get_current_user_id();
        $is_in_wishlist = self::is_in_wishlist($user_id, $travel_id);

        if ($is_in_wishlist) {
            // Remove from wishlist
            $result = self::remove_from_wishlist($user_id, $travel_id);
            $action = 'removed';
            $message = 'Rimosso dalla wishlist';
        } else {
            // Add to wishlist
            $result = self::add_to_wishlist($user_id, $travel_id);
            $action = 'added';
            $message = 'Aggiunto alla wishlist';
        }

        if ($result) {
            wp_send_json_success(array(
                'message' => $message,
                'action' => $action,
                'count' => self::get_wishlist_count($user_id),
                'in_wishlist' => !$is_in_wishlist
            ));
        } else {
            wp_send_json_error(array('message' => 'Errore durante l\'operazione'));
        }
    }

    /**
     * AJAX: Get wishlist travels
     */
    public static function ajax_get_wishlist() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $travels = self::get_wishlist_travels($user_id);

        $result = array();

        if ($travels->have_posts()) {
            while ($travels->have_posts()) {
                $travels->the_post();
                $travel_id = get_the_ID();

                $result[] = array(
                    'id' => $travel_id,
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($travel_id, 'medium'),
                    'destination' => get_post_meta($travel_id, 'cdv_destination', true),
                    'start_date' => get_post_meta($travel_id, 'cdv_start_date', true),
                    'end_date' => get_post_meta($travel_id, 'cdv_end_date', true),
                    'budget' => get_post_meta($travel_id, 'cdv_budget', true),
                    'status' => get_post_meta($travel_id, 'cdv_travel_status', true)
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'travels' => $result,
            'count' => count($result)
        ));
    }

    /**
     * Get wishlist button HTML
     */
    public static function get_wishlist_button_html($travel_id, $class = 'btn btn-secondary') {
        if (!is_user_logged_in()) {
            return '<a href="' . wp_login_url(get_permalink($travel_id)) . '" class="' . esc_attr($class) . '">
                ♡ Salva
            </a>';
        }

        $user_id = get_current_user_id();
        $in_wishlist = self::is_in_wishlist($user_id, $travel_id);

        $icon = $in_wishlist ? '♥' : '♡';
        $text = $in_wishlist ? 'Salvato' : 'Salva';
        $active_class = $in_wishlist ? ' wishlist-active' : '';

        return '<button class="' . esc_attr($class . $active_class) . ' wishlist-btn" data-travel-id="' . $travel_id . '">
            <span class="wishlist-icon">' . $icon . '</span> <span class="wishlist-text">' . $text . '</span>
        </button>';
    }
}
