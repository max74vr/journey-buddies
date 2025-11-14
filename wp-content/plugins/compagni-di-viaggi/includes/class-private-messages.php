<?php
/**
 * Private Messages System
 *
 * Gestisce la messaggistica privata tra utenti
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Private_Messages {

    /**
     * Initialize
     */
    public static function init() {
        // AJAX handlers - matching frontend calls
        add_action('wp_ajax_cdv_send_message', array(__CLASS__, 'ajax_send_message'));
        add_action('wp_ajax_cdv_get_conversation', array(__CLASS__, 'ajax_get_conversation'));
        add_action('wp_ajax_cdv_get_user_conversations', array(__CLASS__, 'ajax_get_user_conversations'));
        add_action('wp_ajax_cdv_block_conversation', array(__CLASS__, 'ajax_block_conversation'));

        // Admin actions
        add_action('wp_ajax_cdv_admin_get_all_conversations', array(__CLASS__, 'ajax_admin_get_all_conversations'));
        add_action('wp_ajax_cdv_admin_get_conversation', array(__CLASS__, 'ajax_admin_get_conversation'));

        // Hooks for automatic blocking
        add_action('cdv_participant_rejected', array(__CLASS__, 'block_on_rejection'), 10, 2);
    }

    /**
     * Create database table for private messages
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cdv_private_messages';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) UNSIGNED NOT NULL,
            receiver_id bigint(20) UNSIGNED NOT NULL,
            travel_id bigint(20) UNSIGNED NOT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY travel_id (travel_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Blocked conversations table
        $blocked_table = $wpdb->prefix . 'cdv_blocked_conversations';
        $sql_blocked = "CREATE TABLE IF NOT EXISTS $blocked_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            blocked_user_id bigint(20) UNSIGNED NOT NULL,
            travel_id bigint(20) UNSIGNED NOT NULL,
            reason varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_block (user_id, blocked_user_id, travel_id),
            KEY user_id (user_id),
            KEY blocked_user_id (blocked_user_id),
            KEY travel_id (travel_id)
        ) $charset_collate;";

        dbDelta($sql_blocked);
    }

    /**
     * Check if user can message another user
     */
    public static function can_message($sender_id, $receiver_id, $travel_id) {
        global $wpdb;

        // Get travel organizer
        $travel = get_post($travel_id);
        if (!$travel) {
            return false;
        }
        $organizer_id = $travel->post_author;

        // Check if they are both participants or have pending request
        $participants_table = $wpdb->prefix . 'cdv_travel_participants';

        $sender_participant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $participants_table
            WHERE travel_id = %d AND user_id = %d AND status IN ('accepted', 'pending')",
            $travel_id, $sender_id
        ));

        $receiver_participant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $participants_table
            WHERE travel_id = %d AND user_id = %d AND status IN ('accepted', 'pending')",
            $travel_id, $receiver_id
        ));

        // Allow messaging if:
        // 1. Both are participants (accepted or pending)
        // 2. OR one is the organizer and the other has a request (pending or accepted)
        $sender_is_organizer = ($sender_id == $organizer_id);
        $receiver_is_organizer = ($receiver_id == $organizer_id);

        if (!$sender_participant && !$sender_is_organizer) {
            return false;
        }

        if (!$receiver_participant && !$receiver_is_organizer) {
            return false;
        }

        // Check if conversation is blocked
        if (self::is_conversation_blocked($sender_id, $receiver_id, $travel_id)) {
            return false;
        }

        return true;
    }

    /**
     * Check if conversation is blocked
     */
    public static function is_conversation_blocked($user1_id, $user2_id, $travel_id) {
        global $wpdb;
        $blocked_table = $wpdb->prefix . 'cdv_blocked_conversations';

        $blocked = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $blocked_table
            WHERE travel_id = %d AND (
                (user_id = %d AND blocked_user_id = %d) OR
                (user_id = %d AND blocked_user_id = %d)
            )",
            $travel_id, $user1_id, $user2_id, $user2_id, $user1_id
        ));

        return !empty($blocked);
    }

    /**
     * Send a message
     */
    public static function send_message($sender_id, $receiver_id, $travel_id, $message) {
        global $wpdb;

        // Validate
        if (!self::can_message($sender_id, $receiver_id, $travel_id)) {
            return new WP_Error('cannot_message', 'Non puoi inviare messaggi a questo utente');
        }

        if (empty(trim($message))) {
            return new WP_Error('empty_message', 'Il messaggio non può essere vuoto');
        }

        $table_name = $wpdb->prefix . 'cdv_private_messages';

        $result = $wpdb->insert(
            $table_name,
            array(
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'travel_id' => $travel_id,
                'message' => sanitize_textarea_field($message),
                'is_read' => 0,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%s', '%d', '%s')
        );

        if (!$result) {
            return new WP_Error('db_error', 'Errore durante l\'invio del messaggio');
        }

        $message_id = $wpdb->insert_id;

        // Send email notification
        self::send_notification_email($receiver_id, $sender_id, $travel_id);

        return $message_id;
    }

    /**
     * Send notification email (without message content)
     */
    private static function send_notification_email($receiver_id, $sender_id, $travel_id) {
        $receiver = get_userdata($receiver_id);
        $sender = get_userdata($sender_id);
        $travel = get_post($travel_id);

        if (!$receiver || !$sender || !$travel) {
            return false;
        }

        $subject = 'Nuovo messaggio da ' . $sender->display_name;
        $message = sprintf(
            "Ciao %s,\n\n%s ti ha inviato un nuovo messaggio riguardo al viaggio \"%s\".\n\nAccedi alla tua dashboard per leggere il messaggio:\n%s\n\nNon rispondere a questa email.\n\nCompagni di Viaggi",
            $receiver->display_name,
            $sender->display_name,
            $travel->post_title,
            home_url('/dashboard?tab=messaggi&travel_id=' . $travel_id)
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        return wp_mail($receiver->user_email, $subject, $message, $headers);
    }

    /**
     * Get conversation between two users
     */
    public static function get_conversation($user1_id, $user2_id, $travel_id, $limit = 50, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_private_messages';

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name
            WHERE travel_id = %d AND (
                (sender_id = %d AND receiver_id = %d) OR
                (sender_id = %d AND receiver_id = %d)
            )
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $travel_id, $user1_id, $user2_id, $user2_id, $user1_id, $limit, $offset
        ));

        return array_reverse($messages);
    }

    /**
     * Get all conversations for a user
     */
    public static function get_user_conversations($user_id) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'cdv_private_messages';

        // Get unique conversations with last message
        // Use a simpler query that's compatible with all MySQL versions
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT
                m1.other_user_id,
                m1.travel_id,
                m1.last_message_time,
                COALESCE(unread.unread_count, 0) as unread_count
            FROM (
                SELECT
                    CASE WHEN sender_id = %d THEN receiver_id ELSE sender_id END as other_user_id,
                    travel_id,
                    MAX(created_at) as last_message_time
                FROM $messages_table
                WHERE sender_id = %d OR receiver_id = %d
                GROUP BY
                    CASE WHEN sender_id = %d THEN receiver_id ELSE sender_id END,
                    travel_id
            ) m1
            LEFT JOIN (
                SELECT
                    sender_id as other_user_id,
                    travel_id,
                    COUNT(*) as unread_count
                FROM $messages_table
                WHERE receiver_id = %d AND is_read = 0
                GROUP BY sender_id, travel_id
            ) unread ON m1.other_user_id = unread.other_user_id
                AND m1.travel_id = unread.travel_id
            ORDER BY m1.last_message_time DESC",
            $user_id, $user_id, $user_id, $user_id, $user_id
        ));

        return $conversations;
    }

    /**
     * Mark messages as read
     */
    public static function mark_as_read($user_id, $other_user_id, $travel_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_private_messages';

        return $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array(
                'receiver_id' => $user_id,
                'sender_id' => $other_user_id,
                'travel_id' => $travel_id,
                'is_read' => 0,
            ),
            array('%d'),
            array('%d', '%d', '%d', '%d')
        );
    }

    /**
     * Block conversation
     */
    public static function block_conversation($user_id, $blocked_user_id, $travel_id, $reason = '') {
        global $wpdb;
        $blocked_table = $wpdb->prefix . 'cdv_blocked_conversations';

        $result = $wpdb->insert(
            $blocked_table,
            array(
                'user_id' => $user_id,
                'blocked_user_id' => $blocked_user_id,
                'travel_id' => $travel_id,
                'reason' => $reason,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );

        return !is_wp_error($result);
    }

    /**
     * Unblock conversation
     */
    public static function unblock_conversation($user_id, $blocked_user_id, $travel_id) {
        global $wpdb;
        $blocked_table = $wpdb->prefix . 'cdv_blocked_conversations';

        return $wpdb->delete(
            $blocked_table,
            array(
                'user_id' => $user_id,
                'blocked_user_id' => $blocked_user_id,
                'travel_id' => $travel_id,
            ),
            array('%d', '%d', '%d')
        );
    }

    /**
     * Block conversation when participant is rejected
     */
    public static function block_on_rejection($travel_id, $user_id) {
        $organizer_id = get_post_field('post_author', $travel_id);

        // Block both ways
        self::block_conversation($organizer_id, $user_id, $travel_id, 'Partecipazione rifiutata');
        self::block_conversation($user_id, $organizer_id, $travel_id, 'Partecipazione rifiutata');
    }

    /**
     * Get unread messages count
     */
    public static function get_unread_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_private_messages';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name
            WHERE receiver_id = %d AND is_read = 0",
            $user_id
        ));
    }

    /**
     * AJAX: Send message
     */
    public static function ajax_send_message() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $sender_id = get_current_user_id();
        $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $message = isset($_POST['message']) ? $_POST['message'] : '';

        $result = self::send_message($sender_id, $receiver_id, $travel_id, $message);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Messaggio inviato',
            'message_id' => $result,
        ));
    }

    /**
     * AJAX: Get conversation
     */
    public static function ajax_get_conversation() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $other_user_id = isset($_POST['other_user_id']) ? intval($_POST['other_user_id']) : 0;
        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        if (!$other_user_id || !$travel_id) {
            wp_send_json_error(array('message' => 'Parametri mancanti'));
            return;
        }

        // Get messages
        $messages = self::get_conversation($user_id, $other_user_id, $travel_id);

        // Get other user info
        $other_user = get_userdata($other_user_id);
        $other_user_name = $other_user ? $other_user->display_name : 'Utente sconosciuto';

        // Get travel info
        $travel = get_post($travel_id);
        $travel_title = $travel ? $travel->post_title : 'Viaggio sconosciuto';

        // Check if blocked
        $is_blocked = self::is_conversation_blocked($user_id, $other_user_id, $travel_id);

        // Mark as read
        self::mark_as_read($user_id, $other_user_id, $travel_id);

        // Format messages for frontend
        $formatted_messages = array();
        foreach ($messages as $msg) {
            $formatted_messages[] = array(
                'id' => $msg->id,
                'message' => esc_html($msg->message),
                'is_sent' => ($msg->sender_id == $user_id),
                'avatar' => get_avatar($msg->sender_id, 40),
                'time_ago' => human_time_diff(strtotime($msg->created_at), current_time('timestamp')) . ' fa',
                'created_at' => $msg->created_at
            );
        }

        wp_send_json_success(array(
            'messages' => $formatted_messages,
            'other_user_name' => $other_user_name,
            'travel_title' => $travel_title,
            'is_blocked' => $is_blocked
        ));
    }

    /**
     * AJAX: Get user conversations
     */
    public static function ajax_get_user_conversations() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $conversations = self::get_user_conversations($user_id);

        // Format conversations for frontend
        $formatted_conversations = array();
        foreach ($conversations as $conv) {
            $other_user = get_userdata($conv->other_user_id);
            $travel = get_post($conv->travel_id);

            if ($other_user && $travel) {
                $formatted_conversations[] = array(
                    'other_user_id' => $conv->other_user_id,
                    'other_user_name' => $other_user->display_name,
                    'avatar' => get_avatar($conv->other_user_id, 50),
                    'travel_id' => $conv->travel_id,
                    'travel_title' => $travel->post_title,
                    'last_message_time' => human_time_diff(strtotime($conv->last_message_time), current_time('timestamp')) . ' fa',
                    'unread_count' => intval($conv->unread_count)
                );
            }
        }

        wp_send_json_success($formatted_conversations);
    }

    /**
     * AJAX: Block/Unblock conversation (toggle)
     */
    public static function ajax_block_conversation() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $other_user_id = isset($_POST['other_user_id']) ? intval($_POST['other_user_id']) : 0;
        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        if (!$other_user_id || !$travel_id) {
            wp_send_json_error(array('message' => 'Parametri mancanti'));
            return;
        }

        // Check if already blocked
        $is_blocked = self::is_conversation_blocked($user_id, $other_user_id, $travel_id);

        if ($is_blocked) {
            // Unblock
            $result = self::unblock_conversation($user_id, $other_user_id, $travel_id);
            if ($result) {
                wp_send_json_success(array('message' => 'Conversazione sbloccata', 'blocked' => false));
            } else {
                wp_send_json_error(array('message' => 'Errore durante lo sblocco'));
            }
        } else {
            // Block
            $result = self::block_conversation($user_id, $other_user_id, $travel_id, 'Bloccato dall\'utente');
            if ($result) {
                wp_send_json_success(array('message' => 'Conversazione bloccata', 'blocked' => true));
            } else {
                wp_send_json_error(array('message' => 'Errore durante il blocco'));
            }
        }
    }

    /**
     * AJAX: Unblock conversation
     */
    public static function ajax_unblock_conversation() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $blocked_user_id = isset($_POST['blocked_user_id']) ? intval($_POST['blocked_user_id']) : 0;
        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        $result = self::unblock_conversation($user_id, $blocked_user_id, $travel_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Conversazione sbloccata'));
        } else {
            wp_send_json_error(array('message' => 'Errore durante lo sblocco'));
        }
    }

    /**
     * AJAX: Mark messages as read
     */
    public static function ajax_mark_messages_read() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $other_user_id = isset($_POST['other_user_id']) ? intval($_POST['other_user_id']) : 0;
        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        self::mark_as_read($user_id, $other_user_id, $travel_id);

        wp_send_json_success(array('message' => 'Messaggi segnati come letti'));
    }

    /**
     * AJAX: Admin - Get all conversations
     */
    public static function ajax_admin_get_all_conversations() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        global $wpdb;
        $messages_table = $wpdb->prefix . 'cdv_private_messages';

        // Get all unique conversations
        $conversations = $wpdb->get_results(
            "SELECT
                sender_id,
                receiver_id,
                travel_id,
                MAX(created_at) as last_message_time,
                COUNT(*) as message_count
            FROM $messages_table
            GROUP BY sender_id, receiver_id, travel_id
            ORDER BY last_message_time DESC
            LIMIT 100"
        );

        wp_send_json_success(array('conversations' => $conversations));
    }

    /**
     * AJAX: Admin - Get specific conversation
     */
    public static function ajax_admin_get_conversation() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        $user1_id = isset($_POST['user1_id']) ? intval($_POST['user1_id']) : 0;
        $user2_id = isset($_POST['user2_id']) ? intval($_POST['user2_id']) : 0;
        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        $messages = self::get_conversation($user1_id, $user2_id, $travel_id);

        wp_send_json_success(array('messages' => $messages));
    }
}
