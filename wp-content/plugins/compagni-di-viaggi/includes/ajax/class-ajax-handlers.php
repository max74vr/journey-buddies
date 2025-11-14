<?php
/**
 * AJAX handlers for frontend interactions
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Ajax_Handlers {

    /**
     * Initialize
     */
    public static function init() {
        // For logged-in users
        add_action('wp_ajax_cdv_join_travel', array(__CLASS__, 'join_travel'));
        add_action('wp_ajax_cdv_send_message', array(__CLASS__, 'send_message'));
        add_action('wp_ajax_cdv_get_new_messages', array(__CLASS__, 'get_new_messages'));
        add_action('wp_ajax_cdv_add_review', array(__CLASS__, 'add_review'));
        add_action('wp_ajax_cdv_reply_review', array(__CLASS__, 'reply_review'));
        add_action('wp_ajax_cdv_report_review', array(__CLASS__, 'report_review'));
        add_action('wp_ajax_cdv_mark_review_helpful', array(__CLASS__, 'mark_review_helpful'));
        add_action('wp_ajax_cdv_accept_participant', array(__CLASS__, 'accept_participant'));
        add_action('wp_ajax_cdv_reject_participant', array(__CLASS__, 'reject_participant'));
        add_action('wp_ajax_cdv_approve_participant', array(__CLASS__, 'accept_participant'));
        add_action('wp_ajax_cdv_change_travel_status', array(__CLASS__, 'change_travel_status'));
        add_action('wp_ajax_cdv_delete_travel', array(__CLASS__, 'delete_travel'));
        add_action('wp_ajax_cdv_resend_verification', array(__CLASS__, 'resend_verification'));

        // Profile management
        add_action('wp_ajax_cdv_update_profile', array(__CLASS__, 'update_profile'));
        add_action('wp_ajax_cdv_change_password', array(__CLASS__, 'change_password'));
        add_action('wp_ajax_cdv_remove_profile_image', array(__CLASS__, 'remove_profile_image'));
        add_action('wp_ajax_cdv_delete_account', array(__CLASS__, 'delete_account'));
        add_action('wp_ajax_cdv_upload_profile_image', array(__CLASS__, 'upload_profile_image'));

        // Travel creation and editing
        add_action('wp_ajax_cdv_create_travel', array(__CLASS__, 'create_travel'));
        add_action('wp_ajax_cdv_update_travel', array(__CLASS__, 'update_travel'));
        add_action('wp_ajax_cdv_validate_address', array(__CLASS__, 'validate_address'));

        // Group Chat
        add_action('wp_ajax_cdv_send_group_message', array(__CLASS__, 'send_group_message'));
        add_action('wp_ajax_cdv_get_group_messages', array(__CLASS__, 'get_group_messages'));

        // Contact Organizer
        add_action('wp_ajax_cdv_contact_organizer', array(__CLASS__, 'contact_organizer'));

        // Participant Management
        add_action('wp_ajax_cdv_remove_participant', array(__CLASS__, 'remove_participant'));
        add_action('wp_ajax_cdv_leave_travel', array(__CLASS__, 'leave_travel'));

        // For non-logged-in users (if needed)
        // add_action('wp_ajax_nopriv_action_name', array(__CLASS__, 'method_name'));
    }

    /**
     * AJAX: Join travel
     */
    public static function join_travel() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

        if (!$travel_id) {
            wp_send_json_error(array('message' => 'ID viaggio non valido'));
        }

        $result = CDV_Participants::request_join($travel_id, get_current_user_id(), $message);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Send notification to organizer
        CDV_Notifications::notify_join_request($travel_id, get_current_user_id());

        wp_send_json_success(array(
            'message' => 'Richiesta inviata con successo',
            'id' => $result,
        ));
    }

    /**
     * AJAX: Send chat message
     */
    public static function send_message() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $chat_group_id = isset($_POST['chat_group_id']) ? intval($_POST['chat_group_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

        if (!$chat_group_id || empty($message)) {
            wp_send_json_error(array('message' => 'Dati non validi'));
        }

        // Check access
        if (!CDV_Chat::can_user_access_chat($chat_group_id, get_current_user_id())) {
            wp_send_json_error(array('message' => 'Non hai accesso a questa chat'));
        }

        $result = CDV_Chat::send_message($chat_group_id, get_current_user_id(), $message);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $user = wp_get_current_user();

        wp_send_json_success(array(
            'message' => array(
                'id' => $result,
                'user' => array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'avatar' => get_avatar_url($user->ID, array('size' => 40)),
                ),
                'message' => $message,
                'created_at' => current_time('mysql'),
            ),
        ));
    }

    /**
     * AJAX: Get new chat messages
     */
    public static function get_new_messages() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $chat_group_id = isset($_POST['chat_group_id']) ? intval($_POST['chat_group_id']) : 0;
        $since = isset($_POST['since']) ? sanitize_text_field($_POST['since']) : '';

        if (!$chat_group_id) {
            wp_send_json_error(array('message' => 'ID chat non valido'));
        }

        // Check access
        if (!CDV_Chat::can_user_access_chat($chat_group_id, get_current_user_id())) {
            wp_send_json_error(array('message' => 'Non hai accesso a questa chat'));
        }

        $messages = CDV_Chat::get_new_messages($chat_group_id, $since);

        $formatted = array();
        foreach ($messages as $msg) {
            $user = get_user_by('id', $msg->user_id);
            $formatted[] = array(
                'id' => $msg->id,
                'user' => array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'avatar' => get_avatar_url($user->ID, array('size' => 40)),
                ),
                'message' => $msg->message,
                'created_at' => $msg->created_at,
            );
        }

        wp_send_json_success(array('messages' => $formatted));
    }

    /**
     * AJAX: Add review
     */
    public static function add_review() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $reviewed_id = isset($_POST['reviewed_id']) ? intval($_POST['reviewed_id']) : 0;
        $scores = array(
            'punctuality' => isset($_POST['punctuality']) ? intval($_POST['punctuality']) : 0,
            'group_spirit' => isset($_POST['group_spirit']) ? intval($_POST['group_spirit']) : 0,
            'respect' => isset($_POST['respect']) ? intval($_POST['respect']) : 0,
            'adaptability' => isset($_POST['adaptability']) ? intval($_POST['adaptability']) : 0,
        );
        $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';

        if (!$travel_id || !$reviewed_id) {
            wp_send_json_error(array('message' => 'Dati non validi'));
        }

        $result = CDV_Reviews::add_review($travel_id, get_current_user_id(), $reviewed_id, $scores, $comment);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Recensione aggiunta con successo',
            'id' => $result,
        ));
    }

    /**
     * AJAX: Accept participant
     */
    public static function accept_participant() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$travel_id || !$user_id) {
            wp_send_json_error(array('message' => 'Dati non validi'));
        }

        // Check if current user is the organizer
        $travel = get_post($travel_id);
        if ($travel->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Solo l\'organizzatore può accettare partecipanti'));
        }

        $result = CDV_Participants::accept_participant($travel_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Send notification to participant
        CDV_Notifications::notify_request_accepted($travel_id, $user_id);

        wp_send_json_success(array('message' => 'Partecipante accettato'));
    }

    /**
     * AJAX: Reject participant
     */
    public static function reject_participant() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        if (!$travel_id || !$user_id) {
            wp_send_json_error(array('message' => 'Dati non validi'));
        }

        // Check if current user is the organizer
        $travel = get_post($travel_id);
        if ($travel->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Solo l\'organizzatore può rifiutare partecipanti'));
        }

        $result = CDV_Participants::reject_participant($travel_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Send notification to participant
        CDV_Notifications::notify_request_rejected($travel_id, $user_id);

        wp_send_json_success(array('message' => 'Partecipante rifiutato'));
    }

    /**
     * AJAX: Change travel status
     */
    public static function change_travel_status() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Devi essere autenticato');
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$travel_id || !$status) {
            wp_send_json_error('Dati non validi');
        }

        // Check if current user is the author
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_author != get_current_user_id()) {
            wp_send_json_error('Non hai i permessi per modificare questo viaggio');
        }

        // Validate status
        $valid_statuses = array('open', 'full', 'closed', 'completed');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error('Stato non valido');
        }

        update_post_meta($travel_id, 'cdv_travel_status', $status);

        wp_send_json_success('Stato aggiornato con successo');
    }

    /**
     * AJAX: Delete travel
     */
    public static function delete_travel() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Devi essere autenticato');
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        if (!$travel_id) {
            wp_send_json_error('ID viaggio non valido');
        }

        // Check if current user is the author
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_author != get_current_user_id()) {
            wp_send_json_error('Non hai i permessi per eliminare questo viaggio');
        }

        // Delete the post (moves to trash)
        $result = wp_trash_post($travel_id);

        if (!$result) {
            wp_send_json_error('Errore durante l\'eliminazione del viaggio');
        }

        wp_send_json_success('Viaggio eliminato con successo');
    }

    /**
     * AJAX: Resend verification email
     */
    public static function resend_verification() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Devi essere autenticato');
        }

        $user_id = get_current_user_id();

        // Controlla se già verificato
        if (CDV_Email_Verification::is_email_verified($user_id)) {
            wp_send_json_error('Email già verificata');
        }

        // Reinvia email
        $result = CDV_Email_Verification::resend_verification_email($user_id);

        if ($result) {
            wp_send_json_success('Email di verifica inviata con successo');
        } else {
            wp_send_json_error('Errore durante l\'invio dell\'email');
        }
    }

    /**
     * AJAX: Update Profile
     */
    public static function update_profile() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();

        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';

        // Update WordPress user
        $user_data = array(
            'ID' => $user_id,
            'display_name' => $display_name,
            'user_email' => $email,
        );

        $result = wp_update_user($user_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Update user meta
        update_user_meta($user_id, 'cdv_city', $city);
        update_user_meta($user_id, 'cdv_phone', $phone);
        update_user_meta($user_id, 'cdv_bio', $bio);

        wp_send_json_success(array('message' => 'Profilo aggiornato con successo'));
    }

    /**
     * AJAX: Change Password
     */
    public static function change_password() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $user = get_user_by('id', $user_id);

        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

        // Verify current password
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            wp_send_json_error(array('message' => 'Password attuale non corretta'));
        }

        // Update password
        wp_set_password($new_password, $user_id);

        wp_send_json_success(array('message' => 'Password cambiata con successo'));
    }

    /**
     * AJAX: Delete Account
     */
    public static function delete_account() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();

        // Don't allow admins to delete themselves via frontend
        if (user_can($user_id, 'manage_options')) {
            wp_send_json_error(array('message' => 'Gli amministratori non possono eliminare il proprio account'));
        }

        // Delete user's travels
        $travels = get_posts(array(
            'post_type' => 'viaggio',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));

        foreach ($travels as $travel_id) {
            wp_delete_post($travel_id, true);
        }

        // Delete user
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);

        // Logout
        wp_logout();

        wp_send_json_success(array('message' => 'Account eliminato con successo'));
    }

    /**
     * AJAX: Create Travel
     */
    public static function create_travel() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();

        // Check if user has capability
        if (!current_user_can('create_viaggi')) {
            wp_send_json_error(array('message' => 'Non hai i permessi per creare viaggi'));
        }

        // Validate required fields
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $destination = isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '';
        $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        $budget = isset($_POST['budget']) ? intval($_POST['budget']) : 0;
        $max_participants = isset($_POST['max_participants']) ? intval($_POST['max_participants']) : 5;
        $date_type = isset($_POST['date_type']) ? sanitize_text_field($_POST['date_type']) : 'precise';

        if (empty($title) || empty($description) || empty($destination) || empty($country) ||
            $budget <= 0 || $max_participants < 2) {
            wp_send_json_error(array('message' => 'Compila tutti i campi obbligatori'));
        }

        // Handle dates based on type
        $start_date = '';
        $end_date = '';
        $travel_month = '';

        if ($date_type === 'month') {
            // Month-based date
            $travel_month = isset($_POST['travel_month']) ? sanitize_text_field($_POST['travel_month']) : '';

            if (empty($travel_month)) {
                wp_send_json_error(array('message' => 'Seleziona il mese del viaggio'));
            }

            // Validate month format
            if (!preg_match('/^\d{4}-\d{2}$/', $travel_month)) {
                wp_send_json_error(array('message' => 'Formato mese non valido'));
            }

            // Convert month to first and last day
            $start_date = $travel_month . '-01';
            $last_day = date('t', strtotime($start_date));
            $end_date = $travel_month . '-' . $last_day;

            if (strtotime($start_date) < strtotime('today')) {
                wp_send_json_error(array('message' => 'Il mese selezionato deve essere futuro'));
            }
        } else {
            // Precise dates
            $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
            $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

            if (empty($start_date) || empty($end_date)) {
                wp_send_json_error(array('message' => 'Inserisci le date del viaggio'));
            }

            if (strtotime($start_date) < strtotime('today')) {
                wp_send_json_error(array('message' => 'La data di inizio deve essere futura'));
            }

            if (strtotime($end_date) <= strtotime($start_date)) {
                wp_send_json_error(array('message' => 'La data di fine deve essere successiva alla data di inizio'));
            }
        }

        // Create travel post
        $post_data = array(
            'post_type' => 'viaggio',
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'pending', // Pending approval
            'post_author' => $user_id,
        );

        $travel_id = wp_insert_post($post_data);

        if (is_wp_error($travel_id)) {
            wp_send_json_error(array('message' => 'Errore durante la creazione del viaggio'));
        }

        // Save meta data
        update_post_meta($travel_id, 'cdv_destination', $destination);
        update_post_meta($travel_id, 'cdv_country', $country);
        update_post_meta($travel_id, 'cdv_start_date', $start_date);
        update_post_meta($travel_id, 'cdv_end_date', $end_date);
        update_post_meta($travel_id, 'cdv_date_type', $date_type);
        if ($date_type === 'month') {
            update_post_meta($travel_id, 'cdv_travel_month', $travel_month);
        }
        update_post_meta($travel_id, 'cdv_budget', $budget);
        update_post_meta($travel_id, 'cdv_max_participants', $max_participants);
        update_post_meta($travel_id, 'cdv_travel_status', 'open');

        // Save optional travel details
        if (isset($_POST['travel_transport']) && is_array($_POST['travel_transport'])) {
            $transport = array_map('sanitize_text_field', $_POST['travel_transport']);
            update_post_meta($travel_id, 'cdv_travel_transport', $transport);
        }

        if (!empty($_POST['travel_accommodation'])) {
            update_post_meta($travel_id, 'cdv_travel_accommodation', sanitize_text_field($_POST['travel_accommodation']));
        }

        if (!empty($_POST['travel_difficulty'])) {
            update_post_meta($travel_id, 'cdv_travel_difficulty', sanitize_text_field($_POST['travel_difficulty']));
        }

        if (!empty($_POST['travel_meals'])) {
            update_post_meta($travel_id, 'cdv_travel_meals', sanitize_text_field($_POST['travel_meals']));
        }

        if (!empty($_POST['travel_guide_type'])) {
            update_post_meta($travel_id, 'cdv_travel_guide_type', sanitize_text_field($_POST['travel_guide_type']));
        }

        if (!empty($_POST['travel_requirements'])) {
            update_post_meta($travel_id, 'cdv_travel_requirements', sanitize_textarea_field($_POST['travel_requirements']));
        }

        // Set travel types
        if (isset($_POST['travel_types']) && is_array($_POST['travel_types'])) {
            $travel_types = array_map('intval', $_POST['travel_types']);
            wp_set_post_terms($travel_id, $travel_types, 'tipo_viaggio');
        }

        // Geocode and save map coordinates
        if ($destination && $country) {
            $address = $destination . ', ' . $country;
            $geocoded = CDV_Travel_Maps::geocode($address);

            if ($geocoded && isset($geocoded['lat']) && isset($geocoded['lon'])) {
                CDV_Travel_Maps::save_travel_coordinates($travel_id, $geocoded['lat'], $geocoded['lon']);
            }
        }

        // Add organizer as first participant
        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_travel_participants';

        $wpdb->insert(
            $table_name,
            array(
                'travel_id' => $travel_id,
                'user_id' => $user_id,
                'status' => 'accepted',
                'is_organizer' => 1,
                'requested_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );

        wp_send_json_success(array(
            'message' => 'Viaggio creato con successo! In attesa di approvazione da parte degli amministratori.',
            'redirect_url' => home_url('/dashboard'),
        ));
    }

    /**
     * AJAX: Update Travel
     */
    public static function update_travel() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();
        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;

        if (!$travel_id) {
            wp_send_json_error(array('message' => 'ID viaggio non valido'));
        }

        // Check if travel exists and user is the organizer
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_type !== 'viaggio') {
            wp_send_json_error(array('message' => 'Viaggio non trovato'));
        }

        if ($travel->post_author != $user_id) {
            wp_send_json_error(array('message' => 'Non sei l\'organizzatore di questo viaggio'));
        }

        // Validate required fields
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';
        $destination = isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '';
        $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        $budget = isset($_POST['budget']) ? intval($_POST['budget']) : 0;
        $max_participants = isset($_POST['max_participants']) ? intval($_POST['max_participants']) : 5;
        $date_type = isset($_POST['date_type']) ? sanitize_text_field($_POST['date_type']) : 'precise';

        if (empty($title) || empty($description) || empty($destination) || empty($country) ||
            $budget <= 0 || $max_participants < 2) {
            wp_send_json_error(array('message' => 'Compila tutti i campi obbligatori'));
        }

        // Handle dates based on type
        $start_date = '';
        $end_date = '';
        $travel_month = '';

        if ($date_type === 'month') {
            // Month-based date
            $travel_month = isset($_POST['travel_month']) ? sanitize_text_field($_POST['travel_month']) : '';

            if (empty($travel_month)) {
                wp_send_json_error(array('message' => 'Seleziona il mese del viaggio'));
            }

            // Validate month format
            if (!preg_match('/^\d{4}-\d{2}$/', $travel_month)) {
                wp_send_json_error(array('message' => 'Formato mese non valido'));
            }

            // Convert month to first and last day
            $start_date = $travel_month . '-01';
            $last_day = date('t', strtotime($start_date));
            $end_date = $travel_month . '-' . $last_day;

            if (strtotime($start_date) < strtotime('today')) {
                wp_send_json_error(array('message' => 'Il mese selezionato deve essere futuro'));
            }
        } else {
            // Precise dates
            $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
            $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

            if (empty($start_date) || empty($end_date)) {
                wp_send_json_error(array('message' => 'Inserisci le date del viaggio'));
            }

            if (strtotime($start_date) < strtotime('today')) {
                wp_send_json_error(array('message' => 'La data di inizio deve essere futura'));
            }

            if (strtotime($end_date) <= strtotime($start_date)) {
                wp_send_json_error(array('message' => 'La data di fine deve essere successiva alla data di inizio'));
            }
        }

        // Update travel post
        $post_data = array(
            'ID' => $travel_id,
            'post_title' => $title,
            'post_content' => $description,
        );

        $result = wp_update_post($post_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Errore durante l\'aggiornamento del viaggio'));
        }

        // Clean post cache to ensure permalink is regenerated correctly
        clean_post_cache($travel_id);

        // Update meta data
        update_post_meta($travel_id, 'cdv_destination', $destination);
        update_post_meta($travel_id, 'cdv_country', $country);
        update_post_meta($travel_id, 'cdv_start_date', $start_date);
        update_post_meta($travel_id, 'cdv_end_date', $end_date);
        update_post_meta($travel_id, 'cdv_date_type', $date_type);
        if ($date_type === 'month') {
            update_post_meta($travel_id, 'cdv_travel_month', $travel_month);
        } else {
            delete_post_meta($travel_id, 'cdv_travel_month');
        }
        update_post_meta($travel_id, 'cdv_budget', $budget);
        update_post_meta($travel_id, 'cdv_max_participants', $max_participants);

        // Update optional travel details
        if (isset($_POST['travel_transport']) && is_array($_POST['travel_transport'])) {
            $transport = array_map('sanitize_text_field', $_POST['travel_transport']);
            update_post_meta($travel_id, 'cdv_travel_transport', $transport);
        } else {
            delete_post_meta($travel_id, 'cdv_travel_transport');
        }

        if (!empty($_POST['travel_accommodation'])) {
            update_post_meta($travel_id, 'cdv_travel_accommodation', sanitize_text_field($_POST['travel_accommodation']));
        } else {
            delete_post_meta($travel_id, 'cdv_travel_accommodation');
        }

        if (!empty($_POST['travel_difficulty'])) {
            update_post_meta($travel_id, 'cdv_travel_difficulty', sanitize_text_field($_POST['travel_difficulty']));
        } else {
            delete_post_meta($travel_id, 'cdv_travel_difficulty');
        }

        if (!empty($_POST['travel_meals'])) {
            update_post_meta($travel_id, 'cdv_travel_meals', sanitize_text_field($_POST['travel_meals']));
        } else {
            delete_post_meta($travel_id, 'cdv_travel_meals');
        }

        if (!empty($_POST['travel_guide_type'])) {
            update_post_meta($travel_id, 'cdv_travel_guide_type', sanitize_text_field($_POST['travel_guide_type']));
        } else {
            delete_post_meta($travel_id, 'cdv_travel_guide_type');
        }

        if (!empty($_POST['travel_requirements'])) {
            update_post_meta($travel_id, 'cdv_travel_requirements', sanitize_textarea_field($_POST['travel_requirements']));
        } else {
            delete_post_meta($travel_id, 'cdv_travel_requirements');
        }

        // Update travel types
        if (isset($_POST['travel_types']) && is_array($_POST['travel_types'])) {
            $travel_types = array_map('intval', $_POST['travel_types']);
            wp_set_post_terms($travel_id, $travel_types, 'tipo_viaggio');
        } else {
            wp_set_post_terms($travel_id, array(), 'tipo_viaggio');
        }

        // Update geocoding if destination/country changed
        if ($destination && $country) {
            $address = $destination . ', ' . $country;
            $geocoded = CDV_Travel_Maps::geocode($address);

            if ($geocoded && isset($geocoded['lat']) && isset($geocoded['lon'])) {
                CDV_Travel_Maps::save_travel_coordinates($travel_id, $geocoded['lat'], $geocoded['lon']);
            }
        }

        // Get the permalink - force refresh
        $permalink = get_permalink($travel_id);

        // If permalink still has query string parameters, build it manually using the post slug
        if (strpos($permalink, '?') !== false) {
            $updated_post = get_post($travel_id);
            if ($updated_post && !empty($updated_post->post_name)) {
                $permalink = home_url('/viaggio/' . $updated_post->post_name . '/');
            }
        }

        wp_send_json_success(array(
            'message' => 'Viaggio aggiornato con successo!',
            'redirect_url' => $permalink,
        ));
    }

    /**
     * AJAX: Upload profile image
     */
    public static function upload_profile_image() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();

        // Check if file was uploaded
        if (!isset($_FILES['profile_image'])) {
            wp_send_json_error(array('message' => 'Nessun file caricato'));
        }

        $file = $_FILES['profile_image'];

        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Formato file non valido. Usa JPG o PNG'));
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File troppo grande. Massimo 5MB'));
        }

        // Handle upload using WordPress
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Delete old profile image if exists - check both meta keys
        $old_attachment_id = get_user_meta($user_id, 'cdv_profile_image', true);
        if (!$old_attachment_id) {
            $old_attachment_id = get_user_meta($user_id, 'cdv_profile_image_id', true);
        }
        if ($old_attachment_id) {
            wp_delete_attachment($old_attachment_id, true);
        }

        // Upload new image
        $attachment_id = media_handle_upload('profile_image', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => 'Errore durante il caricamento: ' . $attachment_id->get_error_message()));
        }

        // Save attachment ID to user meta (use cdv_profile_image for consistency)
        update_user_meta($user_id, 'cdv_profile_image', $attachment_id);
        // Remove old meta key if it exists
        delete_user_meta($user_id, 'cdv_profile_image_id');

        // Get image URL - use medium size for cropped version
        $image_url = wp_get_attachment_image_url($attachment_id, 'medium');

        wp_send_json_success(array(
            'message' => 'Immagine profilo aggiornata con successo',
            'image_url' => $image_url,
        ));
    }

    /**
     * AJAX: Remove profile image
     */
    public static function remove_profile_image() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $user_id = get_current_user_id();

        // Get attachment ID - check both possible meta keys
        $attachment_id = get_user_meta($user_id, 'cdv_profile_image', true);
        if (!$attachment_id) {
            $attachment_id = get_user_meta($user_id, 'cdv_profile_image_id', true);
        }

        if (!$attachment_id) {
            wp_send_json_error(array('message' => 'Nessuna foto profilo personalizzata da rimuovere'));
        }

        // Delete attachment from media library
        $deleted = wp_delete_attachment($attachment_id, true);

        if (!$deleted) {
            wp_send_json_error(array('message' => 'Errore durante la rimozione dell\'immagine'));
        }

        // Remove all user meta related to profile image
        delete_user_meta($user_id, 'cdv_profile_image');
        delete_user_meta($user_id, 'cdv_profile_image_id');
        delete_user_meta($user_id, 'cdv_profile_image_approved');

        wp_send_json_success(array(
            'message' => 'Foto profilo rimossa con successo. Verrà utilizzato il Gravatar predefinito.',
        ));
    }

    /**
     * AJAX: Send group message
     */
    public static function send_group_message() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $user_id = get_current_user_id();

        if (empty($travel_id) || empty($message)) {
            wp_send_json_error(array('message' => 'Parametri mancanti'));
        }

        // Send message
        $result = CDV_Group_Chat::send_message($travel_id, $user_id, $message);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Messaggio inviato',
            'message_id' => $result,
        ));
    }

    /**
     * AJAX: Get group messages
     */
    public static function get_group_messages() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $user_id = get_current_user_id();

        if (empty($travel_id)) {
            wp_send_json_error(array('message' => 'Travel ID mancante'));
        }

        // Check if user is participant
        if (!CDV_Group_Chat::is_participant($travel_id, $user_id)) {
            wp_send_json_error(array('message' => 'Non sei un partecipante di questo viaggio'));
        }

        // Get messages
        $messages = CDV_Group_Chat::get_messages($travel_id, 100);
        $formatted = CDV_Group_Chat::format_messages($messages, $user_id);

        // Get participants
        $participants = CDV_Group_Chat::get_participants($travel_id);

        wp_send_json_success(array(
            'messages' => $formatted,
            'participants' => $participants,
            'participants_count' => count($participants),
        ));
    }

    /**
     * AJAX: Validate address with geocoding
     */
    public static function validate_address() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $destination = isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '';
        $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';

        if (empty($destination) || empty($country)) {
            wp_send_json_error(array('message' => 'Destinazione e paese sono obbligatori'));
        }

        // Try to geocode the address
        $address = $destination . ', ' . $country;
        $geocoded = CDV_Travel_Maps::geocode($address);

        if ($geocoded && isset($geocoded['lat']) && isset($geocoded['lon'])) {
            wp_send_json_success(array(
                'message' => 'Indirizzo valido',
                'lat' => $geocoded['lat'],
                'lon' => $geocoded['lon'],
                'display_name' => isset($geocoded['display_name']) ? $geocoded['display_name'] : $address
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Indirizzo non trovato. Verifica che destinazione e paese siano corretti e riprova.'
            ));
        }
    }

    /**
     * AJAX: Reply to review
     */
    public static function reply_review() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
        $reply_text = isset($_POST['reply']) ? sanitize_textarea_field($_POST['reply']) : '';

        if (!$review_id || empty($reply_text)) {
            wp_send_json_error(array('message' => 'Dati non validi'));
        }

        $result = CDV_Reviews::add_review_reply($review_id, get_current_user_id(), $reply_text);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Risposta aggiunta con successo',
            'reply' => $reply_text,
            'reply_date' => current_time('mysql')
        ));
    }

    /**
     * AJAX: Report review
     */
    public static function report_review() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';

        if (!$review_id || empty($reason)) {
            wp_send_json_error(array('message' => 'Dati non validi'));
        }

        $result = CDV_Reviews::report_review($review_id, get_current_user_id(), $reason);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Recensione segnalata con successo'));
    }

    /**
     * AJAX: Mark review as helpful
     */
    public static function mark_review_helpful() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;

        if (!$review_id) {
            wp_send_json_error(array('message' => 'ID recensione non valido'));
        }

        $result = CDV_Reviews::mark_review_helpful($review_id, get_current_user_id());

        wp_send_json_success($result);
    }

    /**
     * AJAX: Contact organizer
     */
    public static function contact_organizer() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $organizer_id = isset($_POST['organizer_id']) ? intval($_POST['organizer_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $sender_id = get_current_user_id();

        // Validate inputs
        if (!$travel_id || !$organizer_id) {
            wp_send_json_error(array('message' => 'Dati non validi'));
        }

        if (empty($message)) {
            wp_send_json_error(array('message' => 'Il messaggio non può essere vuoto'));
        }

        // Check if user is trying to message themselves
        if ($sender_id === $organizer_id) {
            wp_send_json_error(array('message' => 'Non puoi inviare messaggi a te stesso'));
        }

        // Get travel and organizer info
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_type !== 'viaggio') {
            wp_send_json_error(array('message' => 'Viaggio non trovato'));
        }

        $sender = get_userdata($sender_id);
        $organizer = get_userdata($organizer_id);

        if (!$sender || !$organizer) {
            wp_send_json_error(array('message' => 'Utente non trovato'));
        }

        // Create notification for organizer
        if (class_exists('CDV_Notifications')) {
            CDV_Notifications::create(
                $organizer_id,
                'message',
                'Nuovo messaggio',
                sprintf(
                    '%s ti ha inviato un messaggio riguardo "%s": %s',
                    $sender->user_login,
                    $travel->post_title,
                    wp_trim_words($message, 15)
                ),
                get_permalink($travel_id),
                $travel_id
            );
        }

        // Send email to organizer
        $organizer_email = $organizer->user_email;
        $subject = sprintf('[Compagni di Viaggi] Messaggio da %s riguardo "%s"', $sender->user_login, $travel->post_title);

        $email_message = sprintf(
            "Ciao %s,\n\n%s ti ha inviato un messaggio riguardo il viaggio \"%s\":\n\n%s\n\nPuoi rispondere accedendo al tuo account:\n%s\n\nGrazie,\nIl team di Compagni di Viaggi",
            $organizer->user_login,
            $sender->user_login,
            $travel->post_title,
            $message,
            home_url('/dashboard')
        );

        // Try to send email, but don't fail if it doesn't work
        $email_sent = wp_mail($organizer_email, $subject, $email_message);

        // Log if email failed but still return success since notification was created
        if (!$email_sent) {
            error_log('CDV: Failed to send contact organizer email to ' . $organizer_email);
        }

        wp_send_json_success(array(
            'message' => 'Messaggio inviato con successo'
        ));
    }

    /**
     * AJAX: Remove participant (organizer action)
     */
    public static function remove_participant() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $current_user_id = get_current_user_id();

        // Validate inputs
        if (!$travel_id || !$user_id) {
            wp_send_json_error(array('message' => 'Dati non validi'));
        }

        // Check if current user is the organizer
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_type !== 'viaggio') {
            wp_send_json_error(array('message' => 'Viaggio non trovato'));
        }

        if ($travel->post_author != $current_user_id) {
            wp_send_json_error(array('message' => 'Solo l\'organizzatore può rimuovere partecipanti'));
        }

        // Don't allow removing yourself
        if ($user_id == $current_user_id) {
            wp_send_json_error(array('message' => 'Non puoi rimuovere te stesso'));
        }

        // Remove participant
        if (class_exists('CDV_Participants')) {
            $result = CDV_Participants::remove_participant($travel_id, $user_id);

            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }

            // Create notification for removed user
            $removed_user = get_userdata($user_id);
            if ($removed_user && class_exists('CDV_Notifications')) {
                CDV_Notifications::create_notification(
                    $user_id,
                    'travel_update',
                    sprintf(
                        'Sei stato rimosso dal viaggio "%s"',
                        $travel->post_title
                    ),
                    get_permalink($travel_id)
                );
            }

            // Send email notification
            if ($removed_user) {
                $subject = sprintf('[Compagni di Viaggi] Rimosso dal viaggio "%s"', $travel->post_title);
                $message = sprintf(
                    "Ciao %s,\n\nSei stato rimosso dal viaggio \"%s\" dall'organizzatore.\n\nPuoi visualizzare altri viaggi qui:\n%s\n\nGrazie,\nIl team di Compagni di Viaggi",
                    $removed_user->user_login,
                    $travel->post_title,
                    get_post_type_archive_link('viaggio')
                );
                wp_mail($removed_user->user_email, $subject, $message);
            }

            wp_send_json_success(array('message' => 'Partecipante rimosso con successo'));
        }

        wp_send_json_error(array('message' => 'Errore durante la rimozione'));
    }

    /**
     * AJAX: Leave travel (participant action)
     */
    public static function leave_travel() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Devi essere autenticato'));
        }

        $travel_id = isset($_POST['travel_id']) ? intval($_POST['travel_id']) : 0;
        $user_id = get_current_user_id();

        // Validate inputs
        if (!$travel_id) {
            wp_send_json_error(array('message' => 'ID viaggio non valido'));
        }

        // Check if travel exists
        $travel = get_post($travel_id);
        if (!$travel || $travel->post_type !== 'viaggio') {
            wp_send_json_error(array('message' => 'Viaggio non trovato'));
        }

        // Don't allow organizer to leave their own travel
        if ($travel->post_author == $user_id) {
            wp_send_json_error(array('message' => 'L\'organizzatore non può lasciare il proprio viaggio'));
        }

        // Check if user is actually a participant
        if (class_exists('CDV_Participants') && !CDV_Participants::is_participant($travel_id, $user_id, 'accepted')) {
            wp_send_json_error(array('message' => 'Non sei un partecipante di questo viaggio'));
        }

        // Remove participant
        if (class_exists('CDV_Participants')) {
            $result = CDV_Participants::remove_participant($travel_id, $user_id);

            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }

            // Create notification for organizer
            $organizer_id = $travel->post_author;
            $user = get_userdata($user_id);

            if (class_exists('CDV_Notifications')) {
                CDV_Notifications::create_notification(
                    $organizer_id,
                    'travel_update',
                    sprintf(
                        '%s ha lasciato il viaggio "%s"',
                        $user->user_login,
                        $travel->post_title
                    ),
                    get_permalink($travel_id)
                );
            }

            // Send email to organizer
            $organizer = get_userdata($organizer_id);
            if ($organizer) {
                $subject = sprintf('[Compagni di Viaggi] Un partecipante ha lasciato "%s"', $travel->post_title);
                $message = sprintf(
                    "Ciao %s,\n\n%s ha lasciato il viaggio \"%s\".\n\nPuoi visualizzare il viaggio qui:\n%s\n\nGrazie,\nIl team di Compagni di Viaggi",
                    $organizer->user_login,
                    $user->user_login,
                    $travel->post_title,
                    get_permalink($travel_id)
                );
                wp_mail($organizer->user_email, $subject, $message);
            }

            wp_send_json_success(array('message' => 'Hai lasciato il viaggio con successo'));
        }

        wp_send_json_error(array('message' => 'Errore durante l\'uscita dal viaggio'));
    }
}
