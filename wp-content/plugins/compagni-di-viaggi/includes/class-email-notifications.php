<?php
/**
 * Email Notifications System
 *
 * Manages all email notifications for the platform
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Email_Notifications {

    /**
     * Initialize hooks
     */
    public static function init() {
        // Participation request notifications
        add_action('cdv_participant_requested', array(__CLASS__, 'notify_organizer_new_request'), 10, 3);
        add_action('cdv_participant_status_changed', array(__CLASS__, 'notify_participant_status_change'), 10, 3);

        // Schedule review reminder checks (daily cron)
        if (!wp_next_scheduled('cdv_check_review_reminders')) {
            wp_schedule_event(time(), 'daily', 'cdv_check_review_reminders');
        }
        add_action('cdv_check_review_reminders', array(__CLASS__, 'send_review_reminders'));
    }

    /**
     * Get email template wrapper
     */
    private static function get_email_template($content, $title = '') {
        $site_name = get_bloginfo('name');
        $site_url = home_url();

        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px 30px;
        }
        .button {
            display: inline-block;
            padding: 14px 30px;
            background: #667eea;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }
        .button:hover {
            background: #5568d3;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        .email-footer a {
            color: #667eea;
            text-decoration: none;
        }
        h2 {
            color: #2d3748;
            font-size: 20px;
            margin: 0 0 15px 0;
        }
        p {
            margin: 0 0 15px 0;
            color: #4a5568;
        }
        .travel-info {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .travel-info h3 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 18px;
        }
        .travel-detail {
            display: flex;
            margin: 8px 0;
            font-size: 14px;
        }
        .travel-detail strong {
            min-width: 100px;
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>' . esc_html($site_name) . '</h1>
            ' . (!empty($title) ? '<p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">' . esc_html($title) . '</p>' : '') . '
        </div>
        <div class="email-body">
            ' . $content . '
        </div>
        <div class="email-footer">
            <p style="margin: 0 0 10px 0;">
                <strong>' . esc_html($site_name) . '</strong><br>
                La piattaforma per trovare compagni di viaggio
            </p>
            <p style="margin: 0; font-size: 12px;">
                <a href="' . esc_url($site_url) . '">Visita il sito</a> |
                <a href="' . esc_url(home_url('/dashboard')) . '">Dashboard</a> |
                <a href="' . esc_url(home_url('/privacy')) . '">Privacy</a>
            </p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Send email notification to organizer when someone requests to join
     */
    public static function notify_organizer_new_request($travel_id, $user_id, $organizer_id) {
        $travel = get_post($travel_id);
        $organizer = get_userdata($organizer_id);
        $requester = get_userdata($user_id);

        if (!$travel || !$organizer || !$requester) {
            return false;
        }

        $travel_url = get_permalink($travel_id);
        $dashboard_url = home_url('/dashboard');

        $content = '
            <h2>Ciao ' . esc_html($organizer->display_name) . ',</h2>

            <p><strong>' . esc_html($requester->display_name) . '</strong> ha richiesto di partecipare al tuo viaggio!</p>

            <div class="travel-info">
                <h3>📍 ' . esc_html($travel->post_title) . '</h3>
                <div class="travel-detail">
                    <strong>Richiedente:</strong>
                    <span>' . esc_html($requester->display_name) . ' (' . esc_html($requester->user_email) . ')</span>
                </div>
            </div>

            <div class="info-box">
                <p style="margin: 0;"><strong>💡 Cosa fare ora?</strong></p>
                <p style="margin: 5px 0 0 0;">Accedi alla tua dashboard per visualizzare il profilo del richiedente e decidere se accettare o rifiutare la richiesta.</p>
            </div>

            <p style="text-align: center;">
                <a href="' . esc_url($dashboard_url) . '" class="button">
                    Vai alla Dashboard
                </a>
            </p>

            <p style="font-size: 14px; color: #6c757d;">
                Riceverai anche un messaggio privato con i dettagli della richiesta.
            </p>
        ';

        $subject = 'Nuova richiesta di partecipazione - ' . $travel->post_title;
        $message = self::get_email_template($content, 'Nuova Richiesta di Partecipazione');
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($organizer->user_email, $subject, $message, $headers);
    }

    /**
     * Notify participant when their request is accepted or rejected
     */
    public static function notify_participant_status_change($travel_id, $user_id, $status) {
        $travel = get_post($travel_id);
        $user = get_userdata($user_id);
        $organizer = get_userdata($travel->post_author);

        if (!$travel || !$user || !$organizer) {
            return false;
        }

        $travel_url = get_permalink($travel_id);
        $dashboard_url = home_url('/dashboard');

        if ($status === 'accepted') {
            $content = '
                <h2>Ottima notizia, ' . esc_html($user->display_name) . '! 🎉</h2>

                <div class="success-box">
                    <p style="margin: 0;"><strong>La tua richiesta è stata accettata!</strong></p>
                </div>

                <p>Sei stato accettato per il viaggio:</p>

                <div class="travel-info">
                    <h3>📍 ' . esc_html($travel->post_title) . '</h3>
                    <div class="travel-detail">
                        <strong>Organizzatore:</strong>
                        <span>' . esc_html($organizer->display_name) . '</span>
                    </div>
                    <div class="travel-detail">
                        <strong>Date:</strong>
                        <span>' . self::format_travel_dates($travel_id) . '</span>
                    </div>
                    <div class="travel-detail">
                        <strong>Destinazione:</strong>
                        <span>' . esc_html(get_post_meta($travel_id, 'cdv_destination', true)) . '</span>
                    </div>
                </div>

                <div class="info-box">
                    <p style="margin: 0;"><strong>💡 Prossimi passi:</strong></p>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>Ora hai accesso alla chat di gruppo del viaggio</li>
                        <li>Puoi coordinare i dettagli con l\'organizzatore e gli altri partecipanti</li>
                        <li>Controlla la dashboard per tutti i dettagli</li>
                    </ul>
                </div>

                <p style="text-align: center;">
                    <a href="' . esc_url($travel_url) . '" class="button">
                        Vai al Viaggio
                    </a>
                </p>

                <p style="font-size: 14px; color: #6c757d;">
                    Buon viaggio! 🌍
                </p>
            ';

            $subject = 'Richiesta Accettata - ' . $travel->post_title;
            $title = 'Richiesta Accettata!';

        } else { // rejected
            $content = '
                <h2>Ciao ' . esc_html($user->display_name) . ',</h2>

                <div class="warning-box">
                    <p style="margin: 0;">Ci dispiace, ma la tua richiesta per partecipare al viaggio <strong>' . esc_html($travel->post_title) . '</strong> non è stata accettata.</p>
                </div>

                <p>Non preoccuparti! Ci sono molti altri viaggi disponibili sulla piattaforma.</p>

                <p style="text-align: center;">
                    <a href="' . esc_url(home_url()) . '" class="button">
                        Scopri Altri Viaggi
                    </a>
                </p>

                <p style="font-size: 14px; color: #6c757d;">
                    Continua a cercare il viaggio perfetto per te!
                </p>
            ';

            $subject = 'Aggiornamento Richiesta - ' . $travel->post_title;
            $title = 'Aggiornamento Richiesta';
        }

        $message = self::get_email_template($content, $title);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Send review reminders for completed travels
     * Runs daily via cron
     */
    public static function send_review_reminders() {
        global $wpdb;

        $table = $wpdb->prefix . 'cdv_travel_participants';
        $reviews_table = $wpdb->prefix . 'cdv_reviews';

        // Get all completed travels with accepted participants
        $participants = $wpdb->get_results("
            SELECT p.*, t.post_author as organizer_id
            FROM $table p
            INNER JOIN {$wpdb->posts} t ON p.travel_id = t.ID
            WHERE p.status = 'accepted'
            AND t.post_type = 'viaggio'
            AND t.post_status = 'publish'
        ");

        foreach ($participants as $participant) {
            $travel_id = $participant->travel_id;
            $user_id = $participant->user_id;
            $organizer_id = $participant->organizer_id;

            // Get travel end date
            $end_date = get_post_meta($travel_id, 'cdv_end_date', true);
            if (empty($end_date)) {
                continue;
            }

            $end_timestamp = strtotime($end_date);
            $now = current_time('timestamp');
            $days_since_end = floor(($now - $end_timestamp) / DAY_IN_SECONDS);

            // Skip if travel hasn't ended yet
            if ($days_since_end < 0) {
                continue;
            }

            // Check if user already reviewed the organizer
            $existing_review = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $reviews_table
                WHERE reviewer_id = %d
                AND reviewed_id = %d
                AND travel_id = %d",
                $user_id,
                $organizer_id,
                $travel_id
            ));

            if ($existing_review) {
                continue; // Already reviewed
            }

            // Check if we already sent a reminder
            $reminder_15_sent = get_user_meta($user_id, "cdv_review_reminder_15_{$travel_id}", true);
            $reminder_30_sent = get_user_meta($user_id, "cdv_review_reminder_30_{$travel_id}", true);

            // Send 15-day reminder
            if ($days_since_end >= 15 && $days_since_end < 16 && !$reminder_15_sent) {
                self::send_review_reminder_email($user_id, $travel_id, 15);
                update_user_meta($user_id, "cdv_review_reminder_15_{$travel_id}", current_time('mysql'));
            }

            // Send 30-day reminder
            if ($days_since_end >= 30 && $days_since_end < 31 && !$reminder_30_sent) {
                self::send_review_reminder_email($user_id, $travel_id, 30);
                update_user_meta($user_id, "cdv_review_reminder_30_{$travel_id}", current_time('mysql'));
            }
        }
    }

    /**
     * Send review reminder email
     */
    private static function send_review_reminder_email($user_id, $travel_id, $days) {
        $user = get_userdata($user_id);
        $travel = get_post($travel_id);
        $organizer = get_userdata($travel->post_author);

        if (!$user || !$travel || !$organizer) {
            return false;
        }

        $travel_url = get_permalink($travel_id);
        $review_url = home_url('/dashboard?action=write_review&travel_id=' . $travel_id);

        if ($days === 15) {
            $intro = 'Sono passate due settimane dal tuo viaggio';
            $cta = 'Condividi la tua esperienza!';
        } else {
            $intro = 'È passato un mese dal tuo viaggio';
            $cta = 'Non dimenticare di lasciare una recensione!';
        }

        $content = '
            <h2>Ciao ' . esc_html($user->display_name) . ',</h2>

            <p>' . esc_html($intro) . ' <strong>' . esc_html($travel->post_title) . '</strong>.</p>

            <div class="info-box">
                <p style="margin: 0 0 10px 0;"><strong>⭐ Lascia una recensione!</strong></p>
                <p style="margin: 0;">La tua opinione è preziosa per aiutare altri viaggiatori a fare scelte consapevoli. Ci vorranno solo 2 minuti!</p>
            </div>

            <div class="travel-info">
                <h3>📍 ' . esc_html($travel->post_title) . '</h3>
                <div class="travel-detail">
                    <strong>Organizzatore:</strong>
                    <span>' . esc_html($organizer->display_name) . '</span>
                </div>
                <div class="travel-detail">
                    <strong>Date:</strong>
                    <span>' . self::format_travel_dates($travel_id) . '</span>
                </div>
            </div>

            <p style="text-align: center;">
                <a href="' . esc_url($review_url) . '" class="button">
                    ' . esc_html($cta) . '
                </a>
            </p>

            <p style="font-size: 14px; color: #6c757d;">
                Le recensioni aiutano a mantenere la community sicura e affidabile.
            </p>
        ';

        $subject = 'Lascia una recensione per il viaggio: ' . $travel->post_title;
        $message = self::get_email_template($content, 'Scrivi una Recensione');
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $result = wp_mail($user->user_email, $subject, $message, $headers);

        if ($result) {
            error_log("CDV: Sent {$days}-day review reminder to user {$user_id} for travel {$travel_id}");
        }

        return $result;
    }

    /**
     * Format travel dates for display
     */
    private static function format_travel_dates($travel_id) {
        $start_date = get_post_meta($travel_id, 'cdv_start_date', true);
        $end_date = get_post_meta($travel_id, 'cdv_end_date', true);
        $date_type = get_post_meta($travel_id, 'cdv_date_type', true);

        if (empty($start_date) || empty($end_date)) {
            return 'Date da definire';
        }

        $start = date_i18n('d/m/Y', strtotime($start_date));
        $end = date_i18n('d/m/Y', strtotime($end_date));

        // If month-based, show only month
        if ($date_type === 'month') {
            $month = get_post_meta($travel_id, 'cdv_travel_month', true);
            if ($month) {
                return date_i18n('F Y', strtotime($month . '-01'));
            }
        }

        return $start . ' - ' . $end;
    }

    /**
     * Send custom notification email
     */
    public static function send_custom_notification($user_id, $subject, $content, $title = '') {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $message = self::get_email_template($content, $title);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Clean up on deactivation
     */
    public static function deactivate() {
        $timestamp = wp_next_scheduled('cdv_check_review_reminders');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'cdv_check_review_reminders');
        }
    }
}
