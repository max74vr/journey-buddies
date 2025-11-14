<?php
/**
 * Chat system
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Chat {

    /**
     * Initialize
     */
    public static function init() {
        // Hooks will be added here
    }

    /**
     * Create chat group for a travel
     */
    public static function create_chat_group($travel_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_chat_groups';

        $travel = get_post($travel_id);
        if (!$travel) {
            return false;
        }

        $wpdb->insert(
            $table,
            array(
                'travel_id' => $travel_id,
                'name' => 'Chat: ' . get_the_title($travel_id),
            ),
            array('%d', '%s')
        );

        return $wpdb->insert_id;
    }

    /**
     * Get chat group by travel ID
     */
    public static function get_chat_group($travel_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_chat_groups';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE travel_id = %d",
            $travel_id
        ));
    }

    /**
     * Send message to chat group
     */
    public static function send_message($chat_group_id, $user_id, $message) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_chat_messages';

        // Anti-spam check
        if (!self::check_spam($chat_group_id, $user_id)) {
            return new WP_Error('spam', __('Stai inviando troppi messaggi. Attendi un momento.', 'compagni-di-viaggi'));
        }

        $result = $wpdb->insert(
            $table,
            array(
                'chat_group_id' => $chat_group_id,
                'user_id' => $user_id,
                'message' => sanitize_textarea_field($message),
            ),
            array('%d', '%d', '%s')
        );

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get messages from chat group
     */
    public static function get_messages($chat_group_id, $limit = 50, $offset = 0) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_chat_messages';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
            WHERE chat_group_id = %d
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $chat_group_id,
            $limit,
            $offset
        ));
    }

    /**
     * Get new messages since timestamp
     */
    public static function get_new_messages($chat_group_id, $since) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_chat_messages';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
            WHERE chat_group_id = %d
            AND created_at > %s
            ORDER BY created_at ASC",
            $chat_group_id,
            $since
        ));
    }

    /**
     * Check if user can access chat
     */
    public static function can_user_access_chat($chat_group_id, $user_id) {
        global $wpdb;

        $chat_group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cdv_chat_groups WHERE id = %d",
            $chat_group_id
        ));

        if (!$chat_group) {
            return false;
        }

        // Check if user is organizer or accepted participant
        $travel = get_post($chat_group->travel_id);
        if ($travel->post_author == $user_id) {
            return true;
        }

        return CDV_Participants::is_participant($chat_group->travel_id, $user_id, 'accepted');
    }

    /**
     * Anti-spam check
     */
    private static function check_spam($chat_group_id, $user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_chat_messages';

        // Check messages in last minute
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
            WHERE chat_group_id = %d
            AND user_id = %d
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
            $chat_group_id,
            $user_id
        ));

        // Max 10 messages per minute
        return $count < 10;
    }
}
