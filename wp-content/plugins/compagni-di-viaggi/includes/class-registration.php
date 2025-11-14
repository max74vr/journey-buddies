<?php
/**
 * Frontend Registration System
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Registration {

    /**
     * Initialize
     */
    public static function init() {
        // Step 1 - only for non-logged users
        add_action('wp_ajax_nopriv_cdv_register_step1', array(__CLASS__, 'ajax_register_step1'));

        // Step 2 - for both (user is auto-logged after step 1)
        add_action('wp_ajax_nopriv_cdv_register_step2', array(__CLASS__, 'ajax_register_step2'));
        add_action('wp_ajax_cdv_register_step2', array(__CLASS__, 'ajax_register_step2'));

        // Step 3 and beyond - user is logged
        add_action('wp_ajax_cdv_update_profile', array(__CLASS__, 'ajax_update_profile'));
        add_action('wp_ajax_cdv_upload_profile_image', array(__CLASS__, 'ajax_upload_profile_image'));
        add_action('wp_ajax_cdv_create_first_travel', array(__CLASS__, 'ajax_create_first_travel'));

        // Frontend login
        add_action('wp_ajax_nopriv_cdv_frontend_login', array(__CLASS__, 'ajax_frontend_login'));

        // Prevent backend registration
        add_filter('register_url', array(__CLASS__, 'custom_register_url'));
        add_filter('login_url', array(__CLASS__, 'custom_login_url'), 10, 3);
    }

    /**
     * Custom registration URL
     */
    public static function custom_register_url($url) {
        return home_url('/registrazione');
    }

    /**
     * Custom login URL
     */
    public static function custom_login_url($login_url, $redirect, $force_reauth) {
        $custom_login = home_url('/accedi');

        if (!empty($redirect)) {
            $custom_login = add_query_arg('redirect_to', urlencode($redirect), $custom_login);
        }

        return $custom_login;
    }

    /**
     * AJAX: Frontend Login
     */
    public static function ajax_frontend_login() {
        try {
            check_ajax_referer('cdv_login_nonce', 'nonce');

            $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';

            // Validation
            if (empty($username) || empty($password)) {
                wp_send_json_error(array('message' => 'Username e password sono obbligatori'));
            }

            // Try to authenticate
            $user = wp_authenticate($username, $password);

            if (is_wp_error($user)) {
                error_log('CDV: Login failed for user: ' . $username . ' - ' . $user->get_error_message());
                wp_send_json_error(array('message' => 'Username o password non corretti'));
            }

            // Check if user email is verified
            $email_verified = get_user_meta($user->ID, 'cdv_email_verified', true);
            if ($email_verified !== 'yes') {
                error_log('CDV: Login blocked - email not verified for user: ' . $username);
                wp_send_json_error(array('message' => 'Devi confermare la tua email prima di accedere. Controlla la tua casella di posta.'));
            }

            // Check if user is approved
            $approved = get_user_meta($user->ID, 'cdv_user_approved', true);
            if ($approved !== '1') {
                error_log('CDV: Login blocked - account not approved for user: ' . $username);
                wp_send_json_error(array('message' => 'Il tuo account è in attesa di approvazione da parte degli amministratori.'));
            }

            // Log user in
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, $remember);

            // Determine redirect URL
            $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : home_url('/dashboard');

            error_log('CDV: Successful login for user: ' . $username);

            wp_send_json_success(array(
                'message' => 'Accesso effettuato con successo!',
                'redirect_url' => $redirect_to,
            ));

        } catch (Exception $e) {
            error_log('CDV: Error in frontend login: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Si è verificato un errore. Riprova.'));
        }
    }

    /**
     * AJAX: Register Step 1 - Account Creation
     */
    public static function ajax_register_step1() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';

        // Create display name from first and last name
        $display_name = trim($first_name . ' ' . $last_name);

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            wp_send_json_error(array('message' => 'Tutti i campi sono obbligatori'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Email non valida'));
        }

        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Username già in uso'));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email già registrata'));
        }

        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'La password deve essere di almeno 8 caratteri'));
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Set role to viaggiatore
        $user = new WP_User($user_id);
        $user->set_role('viaggiatore');

        // Set names
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name,
        ));

        // User starts as NOT approved - will be approved after email confirmation
        update_user_meta($user_id, 'cdv_user_approved', '0');
        update_user_meta($user_id, 'cdv_email_verified', 'no');
        update_user_meta($user_id, 'cdv_registration_date', current_time('mysql'));

        // Invia email di verifica
        $email_sent = CDV_Email_Verification::send_verification_email($user_id);

        // Notifica l'admin della nuova registrazione
        self::notify_admin_new_user($user_id);

        // Auto-login temporaneo per completare la registrazione
        // L'utente può completare il profilo e creare il primo viaggio,
        // ma dopo il logout NON potrà riaccedere finché non conferma l'email
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        // Award early adopter badge
        CDV_Badges::award_badge($user_id, 'early_adopter');

        // Generate new nonce for logged-in user
        $new_nonce = wp_create_nonce('cdv_ajax_nonce');

        $message = 'Account creato con successo!';
        if ($email_sent) {
            $message .= ' <strong>IMPORTANTE:</strong> Ti abbiamo inviato un\'email di verifica. ' .
                       'Controlla la tua casella di posta (anche spam) e clicca sul link per attivare il tuo account. ' .
                       'Puoi completare il profilo ora, ma dovrai confermare l\'email prima di poter accedere nuovamente.';
        }

        wp_send_json_success(array(
            'message' => $message,
            'user_id' => $user_id,
            'email_sent' => $email_sent,
            'new_nonce' => $new_nonce, // Fresh nonce for logged-in user
        ));
    }

    /**
     * AJAX: Register Step 2 - Profile Information
     */
    public static function ajax_register_step2() {
        error_log('CDV: Starting registration step 2');
        error_log('CDV: Is user logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));
        error_log('CDV: Current user ID: ' . get_current_user_id());

        try {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                error_log('CDV: User not logged in');
                wp_send_json_error(array('message' => 'Devi essere autenticato'));
            }

            $user_id = get_current_user_id();
            error_log('CDV: User ID: ' . $user_id);

            // Verify user is in registration process (profile not complete)
            // This is more reliable than nonce verification for users who just auto-logged in
            $profile_completed = get_user_meta($user_id, 'cdv_profile_completed', true);
            if ($profile_completed === '1') {
                error_log('CDV: Profile already completed');
                wp_send_json_error(array('message' => 'Profilo già completato'));
            }

            // Verify nonce (but handle auto-login edge case)
            $nonce_verified = check_ajax_referer('cdv_ajax_nonce', 'nonce', false);
            if (!$nonce_verified) {
                // If nonce fails, check if user was created recently (within last 10 minutes)
                // This handles the auto-login scenario
                $registration_date = get_user_meta($user_id, 'cdv_registration_date', true);
                if ($registration_date) {
                    // Use current_time('timestamp') instead of strtotime('now') to match WordPress timezone
                    $time_diff = current_time('timestamp') - strtotime($registration_date);
                    if ($time_diff > 600) { // More than 10 minutes
                        error_log('CDV: Nonce verification failed and user not recently created');
                        wp_send_json_error(array('message' => 'Sessione scaduta'));
                    }
                    error_log('CDV: Nonce verification bypassed - user recently auto-logged in');
                } else {
                    error_log('CDV: Nonce verification failed');
                    wp_send_json_error(array('message' => 'Verifica di sicurezza fallita'));
                }
            } else {
                error_log('CDV: Nonce verified successfully');
            }

            // Personal Info
            $birth_date = isset($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : '';
            $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
            $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
            $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

            // Bio & Interests
            $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
            $languages = isset($_POST['languages']) ? sanitize_text_field($_POST['languages']) : '';
            $travel_styles = isset($_POST['travel_styles']) ? array_map('sanitize_text_field', $_POST['travel_styles']) : array();
            $interests = isset($_POST['interests']) ? array_map('sanitize_text_field', $_POST['interests']) : array();

            // Travel Preferences
            $budget_range = isset($_POST['budget_range']) ? sanitize_text_field($_POST['budget_range']) : '';
            $travel_frequency = isset($_POST['travel_frequency']) ? sanitize_text_field($_POST['travel_frequency']) : '';
            $accommodation_preference = isset($_POST['accommodation_preference']) ? sanitize_text_field($_POST['accommodation_preference']) : '';
            $travel_pace = isset($_POST['travel_pace']) ? sanitize_text_field($_POST['travel_pace']) : '';

            // Social Links (optional)
            $instagram = isset($_POST['instagram']) ? sanitize_text_field($_POST['instagram']) : '';
            $facebook = isset($_POST['facebook']) ? sanitize_text_field($_POST['facebook']) : '';

            // Privacy Settings
            $show_age = isset($_POST['show_age']) ? 'yes' : 'no';
            $show_phone = isset($_POST['show_phone']) ? 'yes' : 'no';
            $show_email = isset($_POST['show_email']) ? 'yes' : 'no';
            $show_social = isset($_POST['show_social']) ? 'yes' : 'no';

            // Validation
            if (empty($birth_date) || empty($bio) || empty($city) || empty($country)) {
                wp_send_json_error(array('message' => 'Completa tutti i campi obbligatori'));
            }

            // Check age (min 18) with error handling
            try {
                $birth = new DateTime($birth_date);
                $today = new DateTime();
                $age = $today->diff($birth)->y;

                if ($age < 18) {
                    wp_send_json_error(array('message' => 'Devi avere almeno 18 anni'));
                }
            } catch (Exception $e) {
                wp_send_json_error(array('message' => 'Data di nascita non valida'));
            }

            // Save data
            update_user_meta($user_id, 'cdv_birth_date', $birth_date);
            update_user_meta($user_id, 'cdv_gender', $gender);
            update_user_meta($user_id, 'cdv_city', $city);
            update_user_meta($user_id, 'cdv_country', $country);
            update_user_meta($user_id, 'cdv_phone', $phone);
            update_user_meta($user_id, 'cdv_bio', $bio);
            update_user_meta($user_id, 'cdv_languages', $languages);
            update_user_meta($user_id, 'cdv_travel_styles', !empty($travel_styles) ? implode(', ', $travel_styles) : '');
            update_user_meta($user_id, 'cdv_interests', !empty($interests) ? implode(', ', $interests) : '');
            update_user_meta($user_id, 'cdv_budget_range', $budget_range);
            update_user_meta($user_id, 'cdv_travel_frequency', $travel_frequency);
            update_user_meta($user_id, 'cdv_accommodation_preference', $accommodation_preference);
            update_user_meta($user_id, 'cdv_travel_pace', $travel_pace);
            update_user_meta($user_id, 'cdv_instagram', $instagram);
            update_user_meta($user_id, 'cdv_facebook', $facebook);

            // Privacy settings
            update_user_meta($user_id, 'cdv_show_age', $show_age);
            update_user_meta($user_id, 'cdv_show_phone', $show_phone);
            update_user_meta($user_id, 'cdv_show_email', $show_email);
            update_user_meta($user_id, 'cdv_show_social', $show_social);

            // Mark profile as complete
            update_user_meta($user_id, 'cdv_profile_completed', '1');

            error_log('CDV: Registration step 2 completed successfully for user ' . $user_id);

            wp_send_json_success(array(
                'message' => 'Profilo completato con successo!',
                'redirect' => home_url('/dashboard'),
            ));

        } catch (Exception $e) {
            error_log('CDV: Error in registration step 2: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Si è verificato un errore: ' . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX: Upload profile image
     */
    public static function ajax_upload_profile_image() {
        error_log('CDV: Starting profile image upload');
        error_log('CDV: Is user logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));

        try {
            if (!is_user_logged_in()) {
                error_log('CDV: User not logged in');
                wp_send_json_error(array('message' => 'Devi essere autenticato'));
            }

            $user_id = get_current_user_id();
            error_log('CDV: User ID: ' . $user_id);

            // Verify nonce with auto-login bypass
            $nonce_verified = check_ajax_referer('cdv_ajax_nonce', 'nonce', false);
            if (!$nonce_verified) {
                // Check if user was created recently (within last 10 minutes)
                $registration_date = get_user_meta($user_id, 'cdv_registration_date', true);
                if ($registration_date) {
                    $time_diff = strtotime('now') - strtotime($registration_date);
                    if ($time_diff > 600) { // More than 10 minutes
                        error_log('CDV: Nonce verification failed and user not recently created');
                        wp_send_json_error(array('message' => 'Sessione scaduta'));
                    }
                    error_log('CDV: Nonce verification bypassed for profile image upload');
                } else {
                    error_log('CDV: Nonce verification failed');
                    wp_send_json_error(array('message' => 'Verifica di sicurezza fallita'));
                }
            } else {
                error_log('CDV: Nonce verified successfully');
            }

            if (!isset($_FILES['profile_image'])) {
                error_log('CDV: No image file in request');
                wp_send_json_error(array('message' => 'Nessuna immagine caricata'));
            }

            error_log('CDV: Image file received: ' . $_FILES['profile_image']['name']);

            // Validate file
            $allowed_types = array('image/jpeg', 'image/png', 'image/jpg');
            $max_size = 5 * 1024 * 1024; // 5MB

            $file = $_FILES['profile_image'];
            error_log('CDV: Validating file - Type: ' . $file['type'] . ', Size: ' . $file['size']);

            if (!in_array($file['type'], $allowed_types)) {
                error_log('CDV: Invalid file type: ' . $file['type']);
                wp_send_json_error(array('message' => 'Formato immagine non valido. Usa JPG o PNG.'));
            }

            if ($file['size'] > $max_size) {
                error_log('CDV: File too large: ' . $file['size']);
                wp_send_json_error(array('message' => 'Immagine troppo grande. Massimo 5MB.'));
            }

            // Upload file
            error_log('CDV: Preparing to upload file');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $upload = wp_handle_upload($file, array('test_form' => false));

            if (isset($upload['error'])) {
                error_log('CDV: Upload error: ' . $upload['error']);
                wp_send_json_error(array('message' => $upload['error']));
            }

            error_log('CDV: File uploaded successfully to: ' . $upload['file']);

            // Create attachment
            $attachment_id = wp_insert_attachment(array(
                'post_mime_type' => $upload['type'],
                'post_title' => 'Profilo ' . $user_id,
                'post_content' => '',
                'post_status' => 'inherit'
            ), $upload['file']);

            if (is_wp_error($attachment_id)) {
                error_log('CDV: Failed to create attachment: ' . $attachment_id->get_error_message());
                wp_send_json_error(array('message' => 'Errore durante la creazione dell\'allegato'));
            }

            error_log('CDV: Attachment created with ID: ' . $attachment_id);

            // Generate metadata
            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attach_data);

            // Save to user meta
            update_user_meta($user_id, 'cdv_profile_image', $attachment_id);

            error_log('CDV: Profile image upload completed for user ' . $user_id);

            // Get image URL - use medium size for cropped version
            $image_url = wp_get_attachment_image_url($attachment_id, 'medium');

            wp_send_json_success(array(
                'message' => 'Immagine caricata con successo',
                'image_url' => $image_url,
            ));

        } catch (Exception $e) {
            error_log('CDV: Error in profile image upload: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Si è verificato un errore: ' . $e->getMessage()
            ));
        }
    }

    /**
     * Notify admin of new user registration
     */
    private static function notify_admin_new_user($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('CDV: Failed to get user data for admin notification. User ID: ' . $user_id);
            return false;
        }

        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            error_log('CDV: No admin email configured for notifications');
            return false;
        }

        $subject = '[Compagni di Viaggi] Nuova registrazione utente';
        $message = sprintf(
            "Nuovo utente registrato:\n\nNome: %s\nUsername: %s\nEmail: %s\nData: %s\n\nVisualizza utenti: %s",
            $user->display_name,
            $user->user_login,
            $user->user_email,
            current_time('d/m/Y H:i'),
            admin_url('users.php')
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        $result = wp_mail($admin_email, $subject, $message, $headers);

        if (!$result) {
            error_log('CDV: Failed to send admin notification email for user ' . $user_id . ' to ' . $admin_email);
            error_log('CDV: Check WordPress mail configuration or install WP Mail SMTP plugin');
        } else {
            error_log('CDV: Admin notification email sent successfully for user ' . $user_id);
        }

        return $result;
    }

    /**
     * Get public profile fields
     */
    public static function get_public_profile_fields($user_id) {
        $user = get_user_by('id', $user_id);

        $profile = array(
            'id' => $user_id,
            'display_name' => $user->display_name,
            'avatar' => self::get_profile_image_url($user_id),
            'bio' => get_user_meta($user_id, 'cdv_bio', true),
            'city' => get_user_meta($user_id, 'cdv_city', true),
            'country' => get_user_meta($user_id, 'cdv_country', true),
            'languages' => get_user_meta($user_id, 'cdv_languages', true),
            'travel_styles' => get_user_meta($user_id, 'cdv_travel_styles', true),
            'interests' => get_user_meta($user_id, 'cdv_interests', true),
            'verified' => get_user_meta($user_id, 'cdv_verified', true) === '1',
            'reputation' => get_user_meta($user_id, 'cdv_reputation_score', true),
            'total_reviews' => get_user_meta($user_id, 'cdv_total_reviews', true),
        );

        // Conditional fields based on privacy
        if (get_user_meta($user_id, 'cdv_show_age', true) === '1') {
            $profile['age'] = CDV_User_Meta::get_user_age($user_id);
        }

        if (get_user_meta($user_id, 'cdv_show_email', true) === '1') {
            $profile['email'] = $user->user_email;
        }

        if (get_user_meta($user_id, 'cdv_show_phone', true) === '1') {
            $profile['phone'] = get_user_meta($user_id, 'cdv_phone', true);
        }

        if (get_user_meta($user_id, 'cdv_show_social', true) === '1') {
            $profile['instagram'] = get_user_meta($user_id, 'cdv_instagram', true);
            $profile['facebook'] = get_user_meta($user_id, 'cdv_facebook', true);
        }

        return $profile;
    }

    /**
     * Get profile image URL
     */
    public static function get_profile_image_url($user_id, $size = 'thumbnail') {
        $image_id = get_user_meta($user_id, 'cdv_profile_image', true);

        if ($image_id) {
            return wp_get_attachment_image_url($image_id, $size);
        }

        return get_avatar_url($user_id);
    }

    /**
     * AJAX: Create First Travel (during registration)
     */
    public static function ajax_create_first_travel() {
        error_log('CDV: Starting first travel creation');
        error_log('CDV: Is user logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));

        try {
            if (!is_user_logged_in()) {
                error_log('CDV: User not logged in');
                wp_send_json_error(array('message' => 'Devi essere autenticato'));
            }

            $user_id = get_current_user_id();
            error_log('CDV: User ID: ' . $user_id);

            // Verify nonce with auto-login bypass
            $nonce_verified = check_ajax_referer('cdv_ajax_nonce', 'nonce', false);
            if (!$nonce_verified) {
                // Check if user was created recently (within last 10 minutes)
                $registration_date = get_user_meta($user_id, 'cdv_registration_date', true);
                if ($registration_date) {
                    $time_diff = strtotime('now') - strtotime($registration_date);
                    if ($time_diff > 600) { // More than 10 minutes
                        error_log('CDV: Nonce verification failed and user not recently created');
                        wp_send_json_error(array('message' => 'Sessione scaduta'));
                    }
                    error_log('CDV: Nonce verification bypassed for first travel creation');
                } else {
                    error_log('CDV: Nonce verification failed');
                    wp_send_json_error(array('message' => 'Verifica di sicurezza fallita'));
                }
            } else {
                error_log('CDV: Nonce verified successfully');
            }

            $title = isset($_POST['travel_title']) ? sanitize_text_field($_POST['travel_title']) : '';
            $description = isset($_POST['travel_description']) ? sanitize_textarea_field($_POST['travel_description']) : '';
            $destination = isset($_POST['travel_destination']) ? sanitize_text_field($_POST['travel_destination']) : '';
            $country = isset($_POST['travel_country']) ? sanitize_text_field($_POST['travel_country']) : '';
            $budget = isset($_POST['travel_budget']) ? intval($_POST['travel_budget']) : 0;
            $max_participants = isset($_POST['travel_max_participants']) ? intval($_POST['travel_max_participants']) : 0;
            $travel_types = isset($_POST['travel_types']) ? array_map('intval', $_POST['travel_types']) : array();
            $date_type = isset($_POST['travel_date_type']) ? sanitize_text_field($_POST['travel_date_type']) : 'precise';

            error_log('CDV: Travel title: ' . $title);
            error_log('CDV: Date type: ' . $date_type);

            // Validation
            if (empty($title) || empty($description) || empty($destination) || empty($country)) {
                error_log('CDV: Missing required fields');
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
                    error_log('CDV: Missing travel month');
                    wp_send_json_error(array('message' => 'Seleziona il mese del viaggio'));
                }

                // Format: YYYY-MM
                if (!preg_match('/^\d{4}-\d{2}$/', $travel_month)) {
                    error_log('CDV: Invalid month format: ' . $travel_month);
                    wp_send_json_error(array('message' => 'Formato mese non valido'));
                }

                $start_date = $travel_month . '-01';
                $last_day = date('t', strtotime($start_date));
                $end_date = $travel_month . '-' . $last_day;

                error_log('CDV: Converted month ' . $travel_month . ' to dates: ' . $start_date . ' - ' . $end_date);

                if (strtotime($start_date) < strtotime('today')) {
                    error_log('CDV: Start date in the past');
                    wp_send_json_error(array('message' => 'Il mese selezionato deve essere futuro'));
                }
            } else {
                // Precise dates
                $start_date = isset($_POST['travel_start_date']) ? sanitize_text_field($_POST['travel_start_date']) : '';
                $end_date = isset($_POST['travel_end_date']) ? sanitize_text_field($_POST['travel_end_date']) : '';

                if (empty($start_date) || empty($end_date)) {
                    error_log('CDV: Missing travel dates');
                    wp_send_json_error(array('message' => 'Inserisci le date del viaggio'));
                }

                if (strtotime($start_date) < strtotime('today')) {
                    error_log('CDV: Start date in the past');
                    wp_send_json_error(array('message' => 'La data di inizio deve essere futura'));
                }

                if (strtotime($end_date) <= strtotime($start_date)) {
                    error_log('CDV: End date before start date');
                    wp_send_json_error(array('message' => 'La data di fine deve essere dopo la data di inizio'));
                }
            }

            error_log('CDV: Validation passed, creating travel post');

            // Create travel post
            $post_data = array(
                'post_type' => 'viaggio',
                'post_title' => $title,
                'post_content' => $description,
                'post_status' => 'pending', // Will be moderated
                'post_author' => $user_id,
            );

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                error_log('CDV: Failed to create travel post: ' . $post_id->get_error_message());
                wp_send_json_error(array('message' => 'Errore durante la creazione del viaggio'));
            }

            error_log('CDV: Travel post created with ID: ' . $post_id);

            // Add meta data
            update_post_meta($post_id, 'cdv_destination', $destination);
            update_post_meta($post_id, 'cdv_country', $country);
            update_post_meta($post_id, 'cdv_start_date', $start_date);
            update_post_meta($post_id, 'cdv_end_date', $end_date);
            update_post_meta($post_id, 'cdv_travel_month', $travel_month); // Store original month for display
            update_post_meta($post_id, 'cdv_budget', $budget);
            update_post_meta($post_id, 'cdv_max_participants', $max_participants);
            update_post_meta($post_id, 'cdv_travel_status', 'open');
            update_post_meta($post_id, 'cdv_views', 0);

            // Add travel types taxonomy
            if (!empty($travel_types)) {
                wp_set_post_terms($post_id, $travel_types, 'tipo_viaggio');
            }

            // Set destination taxonomy
            if (!empty($destination)) {
                wp_set_post_terms($post_id, array($destination), 'destinazione', false);
            }

            // Award badge for first travel
            CDV_Badges::award_badge($user_id, 'first_travel');

            // Notify admin of new travel pending approval
            self::notify_admin_new_travel($post_id, $user_id);

            error_log('CDV: First travel creation completed successfully');

            wp_send_json_success(array(
                'message' => 'Viaggio creato! Sarà pubblicato dopo l\'approvazione dell\'amministrazione.',
                'travel_id' => $post_id,
            ));

        } catch (Exception $e) {
            error_log('CDV: Error in first travel creation: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Si è verificato un errore: ' . $e->getMessage()
            ));
        }
    }

    /**
     * Notify admin of new travel pending approval
     */
    private static function notify_admin_new_travel($travel_id, $user_id) {
        $travel = get_post($travel_id);
        $user = get_userdata($user_id);

        if (!$travel || !$user) {
            error_log('CDV: Failed to notify admin - travel or user not found');
            return false;
        }

        // Get admin email
        $admin_email = get_option('admin_email');

        if (empty($admin_email)) {
            error_log('CDV: No admin email configured');
            return false;
        }

        $edit_link = admin_url('post.php?post=' . $travel_id . '&action=edit');
        $destination = get_post_meta($travel_id, 'cdv_destination', true);
        $start_date = get_post_meta($travel_id, 'cdv_start_date', true);
        $end_date = get_post_meta($travel_id, 'cdv_end_date', true);

        $subject = '🌍 Nuovo Viaggio da Approvare - ' . $travel->post_title;

        $message = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 20px; }
        .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; }
        .button { display: inline-block; padding: 12px 30px; background: #667eea; color: #ffffff !important; text-decoration: none; border-radius: 5px; margin: 15px 0; font-weight: bold; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">🌍 Nuovo Viaggio in Attesa di Approvazione</h2>
        </div>

        <div class="content">
            <p>Un nuovo viaggio è stato creato sulla piattaforma e richiede la tua approvazione.</p>

            <div class="info-box">
                <h3 style="margin-top: 0;">📋 Dettagli del Viaggio</h3>
                <p><strong>Titolo:</strong> ' . esc_html($travel->post_title) . '</p>
                <p><strong>Destinazione:</strong> ' . esc_html($destination) . '</p>
                <p><strong>Date:</strong> ' . esc_html($start_date) . ' - ' . esc_html($end_date) . '</p>
                <p><strong>Organizzatore:</strong> ' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</p>
            </div>

            <div class="info-box">
                <h3 style="margin-top: 0;">📝 Descrizione</h3>
                <p>' . wp_trim_words($travel->post_content, 50) . '</p>
            </div>

            <p style="text-align: center;">
                <a href="' . esc_url($edit_link) . '" class="button" style="color: #ffffff;">
                    👁️ VISUALIZZA E APPROVA
                </a>
            </p>

            <p style="font-size: 12px; color: #666;">
                Accedi al pannello admin per approvare o rifiutare questo viaggio.<br>
                Il viaggio non sarà visibile pubblicamente fino all\'approvazione.
            </p>
        </div>

        <div class="footer">
            <p>Questa email è stata inviata automaticamente da Compagni di Viaggi</p>
        </div>
    </div>
</body>
</html>
        ';

        $headers = array('Content-Type: text/html; charset=UTF-8');

        $result = wp_mail($admin_email, $subject, $message, $headers);

        if (!$result) {
            error_log('CDV: Failed to send admin notification email for travel ' . $travel_id);
        } else {
            error_log('CDV: Admin notification sent successfully for travel ' . $travel_id);
        }

        return $result;
    }
}
