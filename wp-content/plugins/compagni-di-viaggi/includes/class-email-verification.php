<?php
/**
 * Email Verification
 *
 * Gestisce la verifica dell'email per nuovi utenti
 */

class CDV_Email_Verification {

    public static function init() {
        add_action('template_redirect', array(__CLASS__, 'handle_verification'));
        add_filter('authenticate', array(__CLASS__, 'block_unverified_login'), 30, 3);
    }

    /**
     * Genera e invia token di verifica email
     */
    public static function send_verification_email($user_id) {
        global $wpdb;

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // Verifica se la tabella esiste
        $table_name = $wpdb->prefix . 'cdv_email_verification';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if (!$table_exists) {
            // Tabella non esiste, salta verifica email ma non bloccare registrazione
            error_log('CDV: Email verification table does not exist. Skipping email verification.');
            return true; // Return true per non bloccare la registrazione
        }

        // Genera token unico
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Salva token nel database
        $result = $wpdb->insert($table_name, array(
            'user_id' => $user_id,
            'token' => $token,
            'expires_at' => $expires_at,
        ));

        if (!$result) {
            error_log('CDV: Failed to insert email verification token for user ' . $user_id);
            return true; // Return true comunque per non bloccare
        }

        // Crea link di verifica
        $verification_link = home_url('/conferma-email/?token=' . $token);

        // Invia email HTML
        $subject = 'Conferma il tuo account - Compagni di Viaggi';
        $message = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 15px 30px; background: #667eea; color: #ffffff !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ciao ' . esc_html($user->display_name) . ',</h2>

        <p>Grazie per esserti registrato su <strong>Compagni di Viaggi</strong>!</p>

        <p>Per completare la registrazione e attivare il tuo account, clicca sul pulsante qui sotto:</p>

        <p style="text-align: center;">
            <a href="' . esc_url($verification_link) . '" class="button" style="color: #ffffff;">
                ✓ CONFERMA IL TUO ACCOUNT
            </a>
        </p>

        <p>Oppure copia e incolla questo link nel tuo browser:</p>
        <p style="background: #f5f5f5; padding: 10px; word-break: break-all; font-size: 12px;">
            ' . esc_url($verification_link) . '
        </p>

        <p><strong>Questo link è valido per 24 ore.</strong></p>

        <p>Una volta confermata l\'email potrai accedere alla piattaforma e iniziare a organizzare i tuoi viaggi!</p>

        <p>Se non hai richiesto questa registrazione, ignora questa email.</p>

        <div class="footer">
            <p>A presto,<br>
            Il team di Compagni di Viaggi</p>
        </div>
    </div>
</body>
</html>
        ';

        $headers = array('Content-Type: text/html; charset=UTF-8');

        $result = wp_mail($user->user_email, $subject, $message, $headers);

        if (!$result) {
            error_log('CDV: Failed to send verification email to user ' . $user_id . ' (' . $user->user_email . ')');
            error_log('CDV: WordPress wp_mail() returned false. Possible causes:');
            error_log('CDV: 1. Server mail() function not configured');
            error_log('CDV: 2. No SMTP plugin installed (recommended: WP Mail SMTP)');
            error_log('CDV: 3. Email address or domain blocked by hosting provider');
        } else {
            error_log('CDV: Verification email sent successfully to user ' . $user_id . ' (' . $user->user_email . ')');
        }

        return $result;
    }

    /**
     * Verifica token e attiva account
     */
    public static function verify_token($token) {
        global $wpdb;

        if (empty($token)) {
            error_log('CDV: Empty token provided to verify_token()');
            return new WP_Error('invalid_token', 'Token non valido');
        }

        $table_name = $wpdb->prefix . 'cdv_email_verification';

        // Verifica che la tabella esista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log('CDV: Email verification table does not exist!');
            return new WP_Error('system_error', 'Errore di sistema. Contatta l\'amministratore.');
        }

        // Cerca il token
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE token = %s AND verified_at IS NULL",
            $token
        ));

        if (!$record) {
            error_log('CDV: Token not found or already verified: ' . substr($token, 0, 10) . '...');
            // Controlla se il token esiste ma è già stato verificato
            $verified_record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE token = %s",
                $token
            ));
            if ($verified_record && $verified_record->verified_at) {
                return new WP_Error('already_verified', 'Questo link è già stato utilizzato. Il tuo account è già attivo.');
            }
            return new WP_Error('invalid_token', 'Token non valido o già utilizzato');
        }

        error_log('CDV: Token found for user ID: ' . $record->user_id);

        // Controlla scadenza
        if (strtotime($record->expires_at) < current_time('timestamp')) {
            error_log('CDV: Token expired. Expires at: ' . $record->expires_at . ', Current time: ' . current_time('mysql'));
            return new WP_Error('expired_token', 'Token scaduto. Richiedi una nuova email di conferma.');
        }

        // Marca come verificato
        $updated = $wpdb->update(
            $table_name,
            array('verified_at' => current_time('mysql')),
            array('id' => $record->id),
            array('%s'),
            array('%d')
        );

        if ($updated === false) {
            error_log('CDV: Failed to update verification record in database');
            return new WP_Error('database_error', 'Errore durante l\'aggiornamento. Riprova.');
        }

        error_log('CDV: Successfully marked token as verified');

        // Aggiorna user meta e approva l'utente
        $user = get_userdata($record->user_id);
        if (!$user) {
            error_log('CDV: User not found with ID: ' . $record->user_id);
            return new WP_Error('user_not_found', 'Utente non trovato.');
        }

        update_user_meta($record->user_id, 'cdv_email_verified', 'yes');
        update_user_meta($record->user_id, 'cdv_user_approved', '1');
        update_user_meta($record->user_id, 'cdv_email_verified_date', current_time('mysql'));

        error_log('CDV: Successfully activated user account: ' . $user->user_login . ' (ID: ' . $record->user_id . ')');

        return $record->user_id;
    }

    /**
     * Controlla se un utente ha verificato l'email
     */
    public static function is_email_verified($user_id) {
        return get_user_meta($user_id, 'cdv_email_verified', true) === 'yes';
    }

    /**
     * Blocca login per utenti non verificati
     */
    public static function block_unverified_login($user, $username, $password) {
        // Se è un errore, passalo avanti
        if (is_wp_error($user)) {
            return $user;
        }

        // Se non è un oggetto User, ritorna
        if (!is_a($user, 'WP_User')) {
            return $user;
        }

        // Gli amministratori e i ruoli privilegiati possono sempre fare login
        if (user_can($user, 'manage_options') ||
            in_array('administrator', (array) $user->roles) ||
            in_array('editor', (array) $user->roles)) {
            // Assicurati che abbiano i meta necessari settati
            if (get_user_meta($user->ID, 'cdv_email_verified', true) !== 'yes') {
                update_user_meta($user->ID, 'cdv_email_verified', 'yes');
                update_user_meta($user->ID, 'cdv_email_verified_date', current_time('mysql'));
            }
            if (get_user_meta($user->ID, 'cdv_user_approved', true) !== '1') {
                update_user_meta($user->ID, 'cdv_user_approved', '1');
            }
            return $user;
        }

        // Controlla se l'email è verificata
        $email_verified = get_user_meta($user->ID, 'cdv_email_verified', true);
        $user_approved = get_user_meta($user->ID, 'cdv_user_approved', true);

        if ($email_verified !== 'yes' || $user_approved !== '1') {
            return new WP_Error(
                'email_not_verified',
                '<strong>Errore:</strong> Devi confermare il tuo indirizzo email prima di poter accedere. ' .
                'Controlla la tua casella di posta (anche spam) per il link di conferma.'
            );
        }

        return $user;
    }

    /**
     * Gestisce la verifica via URL
     */
    public static function handle_verification() {
        // Controlla se c'è un token nella query string
        if (!isset($_GET['token'])) {
            return;
        }

        // Controlla se siamo sulla pagina di conferma email
        // Supporta sia slug che template name
        if (!is_page('conferma-email') && !is_page_template('page-conferma-email.php')) {
            return;
        }

        $token = sanitize_text_field($_GET['token']);

        error_log('CDV: Processing email verification for token: ' . substr($token, 0, 10) . '...');

        $result = self::verify_token($token);

        if (is_wp_error($result)) {
            error_log('CDV: Email verification failed: ' . $result->get_error_message());
            // Redirect with error message in query var
            $redirect_url = add_query_arg('verification_error', urlencode($result->get_error_message()), home_url('/conferma-email/'));
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            error_log('CDV: Email verification successful for user ID: ' . $result);
            // Redirect with success message in query var
            $redirect_url = add_query_arg('verification_success', '1', home_url('/conferma-email/'));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Reinvia email di verifica
     */
    public static function resend_verification_email($user_id) {
        global $wpdb;

        // Elimina token precedenti non verificati
        $table_name = $wpdb->prefix . 'cdv_email_verification';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE user_id = %d AND verified_at IS NULL",
            $user_id
        ));

        // Invia nuovo token
        return self::send_verification_email($user_id);
    }
}
