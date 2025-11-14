<?php
/**
 * Notifications System
 * Gestisce le notifiche in-app per gli utenti
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Notifications {

    /**
     * Initialize
     */
    public static function init() {
        add_action('wp_ajax_cdv_get_notifications', array(__CLASS__, 'ajax_get_notifications'));
        add_action('wp_ajax_cdv_mark_notification_read', array(__CLASS__, 'ajax_mark_read'));
        add_action('wp_ajax_cdv_mark_all_notifications_read', array(__CLASS__, 'ajax_mark_all_read'));
    }

    /**
     * Create database table for notifications
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_notifications';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            link varchar(255) DEFAULT NULL,
            related_id bigint(20) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create a notification
     */
    public static function create($user_id, $type, $title, $message, $link = null, $related_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_notifications';

        $data = array(
            'user_id' => $user_id,
            'type' => sanitize_text_field($type),
            'title' => sanitize_text_field($title),
            'message' => sanitize_text_field($message),
            'link' => $link ? esc_url_raw($link) : null,
            'related_id' => $related_id,
            'is_read' => 0,
            'created_at' => current_time('mysql')
        );

        $wpdb->insert($table_name, $data);
        return $wpdb->insert_id;
    }

    /**
     * Get user notifications
     */
    public static function get_notifications($user_id, $limit = 20, $unread_only = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_notifications';

        $where = $wpdb->prepare("WHERE user_id = %d", $user_id);

        if ($unread_only) {
            $where .= " AND is_read = 0";
        }

        $sql = "SELECT * FROM $table_name
                $where
                ORDER BY created_at DESC
                LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    /**
     * Get unread count
     */
    public static function get_unread_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_notifications';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }

    /**
     * Mark notification as read
     */
    public static function mark_as_read($notification_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_notifications';

        $where = array('id' => $notification_id);
        if ($user_id) {
            $where['user_id'] = $user_id;
        }

        return $wpdb->update(
            $table_name,
            array('is_read' => 1),
            $where
        );
    }

    /**
     * Mark all notifications as read
     */
    public static function mark_all_as_read($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_notifications';

        return $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('user_id' => $user_id, 'is_read' => 0)
        );
    }

    /**
     * Delete old notifications
     */
    public static function cleanup_old_notifications($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_notifications';

        $date = date('Y-m-d H:i:s', strtotime("-$days days"));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s AND is_read = 1",
            $date
        ));
    }

    /**
     * AJAX: Get notifications
     */
    public static function ajax_get_notifications() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $user_id = get_current_user_id();
        $notifications = self::get_notifications($user_id, 50);
        $unread_count = self::get_unread_count($user_id);

        $formatted = array();
        foreach ($notifications as $notification) {
            $formatted[] = array(
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'link' => $notification->link,
                'is_read' => (bool) $notification->is_read,
                'time_ago' => self::time_ago($notification->created_at),
                'icon' => self::get_icon_for_type($notification->type)
            );
        }

        wp_send_json_success(array(
            'notifications' => $formatted,
            'unread_count' => $unread_count
        ));
    }

    /**
     * AJAX: Mark notification as read
     */
    public static function ajax_mark_read() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        $user_id = get_current_user_id();

        if (!$notification_id) {
            wp_send_json_error(array('message' => 'ID notifica non valido'));
        }

        $result = self::mark_as_read($notification_id, $user_id);

        if ($result !== false) {
            $unread_count = self::get_unread_count($user_id);
            wp_send_json_success(array(
                'message' => 'Notifica segnata come letta',
                'unread_count' => $unread_count
            ));
        } else {
            wp_send_json_error(array('message' => 'Errore durante l\'aggiornamento'));
        }
    }

    /**
     * AJAX: Mark all notifications as read
     */
    public static function ajax_mark_all_read() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Non autenticato'));
        }

        $user_id = get_current_user_id();
        self::mark_all_as_read($user_id);

        wp_send_json_success(array(
            'message' => 'Tutte le notifiche sono state segnate come lette',
            'unread_count' => 0
        ));
    }

    /**
     * Get icon for notification type
     */
    private static function get_icon_for_type($type) {
        $icons = array(
            'join_request' => '👋',
            'request_accepted' => '✅',
            'request_rejected' => '❌',
            'new_message' => '💬',
            'new_participant' => '👤',
            'travel_full' => '🔒',
            'review_received' => '⭐',
            'travel_cancelled' => '🚫',
            'travel_updated' => '📝',
        );

        return isset($icons[$type]) ? $icons[$type] : '🔔';
    }

    /**
     * Time ago helper
     */
    private static function time_ago($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) {
            return 'Adesso';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minut' . ($mins == 1 ? 'o' : 'i') . ' fa';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' or' . ($hours == 1 ? 'a' : 'e') . ' fa';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' giorn' . ($days == 1 ? 'o' : 'i') . ' fa';
        } else {
            return date('d M Y', $time);
        }
    }

    /**
     * Helper: Notify when join request is created
     */
    public static function notify_join_request($travel_id, $requester_id) {
        $travel = get_post($travel_id);
        $organizer_id = $travel->post_author;
        $requester = get_userdata($requester_id);

        self::create(
            $organizer_id,
            'join_request',
            'Nuova Richiesta di Partecipazione',
            $requester->display_name . ' vuole partecipare a "' . $travel->post_title . '"',
            home_url('/dashboard?tab=requests'),
            $travel_id
        );
    }

    /**
     * Helper: Notify when request is accepted
     */
    public static function notify_request_accepted($travel_id, $participant_id) {
        $travel = get_post($travel_id);

        self::create(
            $participant_id,
            'request_accepted',
            'Richiesta Accettata!',
            'La tua richiesta per "' . $travel->post_title . '" è stata accettata',
            get_permalink($travel_id),
            $travel_id
        );
    }

    /**
     * Helper: Notify when request is rejected
     */
    public static function notify_request_rejected($travel_id, $participant_id) {
        $travel = get_post($travel_id);

        self::create(
            $participant_id,
            'request_rejected',
            'Richiesta Rifiutata',
            'La tua richiesta per "' . $travel->post_title . '" è stata rifiutata',
            null,
            $travel_id
        );
    }

    /**
     * Helper: Notify new message
     */
    public static function notify_new_message($recipient_id, $sender_id, $travel_id) {
        $sender = get_userdata($sender_id);
        $travel = get_post($travel_id);

        self::create(
            $recipient_id,
            'new_message',
            'Nuovo Messaggio',
            $sender->display_name . ' ti ha inviato un messaggio su "' . $travel->post_title . '"',
            home_url('/dashboard?tab=messages&user_id=' . $sender_id . '&travel_id=' . $travel_id),
            $travel_id
        );
    }

    /**
     * Helper: Notify new review
     */
    public static function notify_new_review($user_id, $reviewer_id, $travel_id) {
        $reviewer = get_userdata($reviewer_id);
        $travel = get_post($travel_id);

        self::create(
            $user_id,
            'review_received',
            'Nuova Recensione',
            $reviewer->display_name . ' ha lasciato una recensione per il viaggio "' . $travel->post_title . '"',
            home_url('/dashboard?tab=reviews'),
            $travel_id
        );
    }
}
