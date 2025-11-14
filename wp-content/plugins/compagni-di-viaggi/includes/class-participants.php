<?php
/**
 * Travel participants management
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Participants {

    /**
     * Initialize
     */
    public static function init() {
        // Hooks will be added here
    }

    /**
     * Request to join a travel
     */
    public static function request_join($travel_id, $user_id, $message = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_travel_participants';

        // Check if already requested
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE travel_id = %d AND user_id = %d",
            $travel_id,
            $user_id
        ));

        if ($existing) {
            return new WP_Error('already_requested', __('Hai già richiesto di partecipare a questo viaggio', 'compagni-di-viaggi'));
        }

        // Check if travel is full
        if (self::is_travel_full($travel_id)) {
            return new WP_Error('travel_full', __('Questo viaggio ha raggiunto il numero massimo di partecipanti', 'compagni-di-viaggi'));
        }

        // Check if travel is still open
        $status = get_post_meta($travel_id, 'cdv_travel_status', true);
        if ($status === 'completed' || $status === 'cancelled') {
            return new WP_Error('travel_closed', __('Questo viaggio non è più disponibile', 'compagni-di-viaggi'));
        }

        $result = $wpdb->insert(
            $table,
            array(
                'travel_id' => $travel_id,
                'user_id' => $user_id,
                'status' => 'pending',
                'message' => sanitize_textarea_field($message),
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result) {
            // Get travel organizer
            $travel = get_post($travel_id);
            $organizer_id = $travel->post_author;

            // Create initial private message with the request
            if (!empty($message) && class_exists('CDV_Private_Messages')) {
                $formatted_message = "📋 Richiesta di Partecipazione:\n\n" . $message;
                CDV_Private_Messages::send_message($user_id, $organizer_id, $travel_id, $formatted_message);
            }

            // Notify organizer
            self::notify_organizer($travel_id, $user_id);
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Accept participant
     */
    public static function accept_participant($travel_id, $user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_travel_participants';

        // Check if travel is full
        if (self::is_travel_full($travel_id)) {
            return new WP_Error('travel_full', __('Il viaggio ha raggiunto il numero massimo di partecipanti', 'compagni-di-viaggi'));
        }

        $result = $wpdb->update(
            $table,
            array('status' => 'accepted'),
            array('travel_id' => $travel_id, 'user_id' => $user_id),
            array('%s'),
            array('%d', '%d')
        );

        if ($result) {
            // Create chat group if it doesn't exist
            $chat_group = CDV_Chat::get_chat_group($travel_id);
            if (!$chat_group) {
                CDV_Chat::create_chat_group($travel_id);
            }

            // Notify user
            self::notify_participant($travel_id, $user_id, 'accepted');
            return true;
        }

        return false;
    }

    /**
     * Reject participant
     */
    public static function reject_participant($travel_id, $user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_travel_participants';

        $result = $wpdb->update(
            $table,
            array('status' => 'rejected'),
            array('travel_id' => $travel_id, 'user_id' => $user_id),
            array('%s'),
            array('%d', '%d')
        );

        if ($result) {
            // Notify user
            self::notify_participant($travel_id, $user_id, 'rejected');
            return true;
        }

        return false;
    }

    /**
     * Get participants for a travel
     */
    public static function get_participants($travel_id, $status = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_travel_participants';

        if ($status) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE travel_id = %d AND status = %s ORDER BY requested_at ASC",
                $travel_id,
                $status
            ));
        } else {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE travel_id = %d ORDER BY requested_at ASC",
                $travel_id
            ));
        }
    }

    /**
     * Check if user is a participant
     */
    public static function is_participant($travel_id, $user_id, $status = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_travel_participants';

        if ($status) {
            $result = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE travel_id = %d AND user_id = %d AND status = %s",
                $travel_id,
                $user_id,
                $status
            ));
        } else {
            $result = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE travel_id = %d AND user_id = %d",
                $travel_id,
                $user_id
            ));
        }

        return !is_null($result);
    }

    /**
     * Check if travel is full
     */
    public static function is_travel_full($travel_id) {
        $max_participants = get_post_meta($travel_id, 'cdv_max_participants', true);

        if (empty($max_participants) || $max_participants == 0) {
            return false;
        }

        $accepted_count = count(self::get_participants($travel_id, 'accepted'));

        return $accepted_count >= $max_participants;
    }

    /**
     * Get participant count
     */
    public static function get_participant_count($travel_id, $status = 'accepted') {
        return count(self::get_participants($travel_id, $status));
    }

    /**
     * Notify organizer of new request
     */
    private static function notify_organizer($travel_id, $user_id) {
        $travel = get_post($travel_id);
        $organizer = get_user_by('id', $travel->post_author);
        $user = get_user_by('id', $user_id);

        // You can implement email notification here
        // For now, we'll just add a WordPress notification
        do_action('cdv_participant_requested', $travel_id, $user_id, $organizer->ID);
    }

    /**
     * Notify participant of status change
     */
    private static function notify_participant($travel_id, $user_id, $status) {
        // You can implement email notification here
        do_action('cdv_participant_status_changed', $travel_id, $user_id, $status);
    }

    /**
     * Remove participant
     */
    public static function remove_participant($travel_id, $user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_travel_participants';

        return $wpdb->delete(
            $table,
            array('travel_id' => $travel_id, 'user_id' => $user_id),
            array('%d', '%d')
        );
    }
}
