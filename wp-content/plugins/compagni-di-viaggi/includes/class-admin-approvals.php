<?php
/**
 * Admin Approval Panels
 *
 * Gestisce i pannelli di amministrazione per approvare:
 * - Immagini profilo
 * - Viaggi
 * - Recensioni
 */

class CDV_Admin_Approvals {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_post_cdv_approve_image', array(__CLASS__, 'handle_approve_image'));
        add_action('admin_post_cdv_reject_image', array(__CLASS__, 'handle_reject_image'));
        add_action('admin_post_cdv_approve_travel', array(__CLASS__, 'handle_approve_travel'));
        add_action('admin_post_cdv_reject_travel', array(__CLASS__, 'handle_reject_travel'));
        add_action('admin_post_cdv_approve_review', array(__CLASS__, 'handle_approve_review'));
        add_action('admin_post_cdv_reject_review', array(__CLASS__, 'handle_reject_review'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_styles'));
    }

    /**
     * Aggiunge menu di amministrazione
     */
    public static function add_admin_menu() {
        // Menu principale
        add_menu_page(
            'Approvazioni CDV',
            'Approvazioni',
            'manage_options',
            'cdv-approvals',
            array(__CLASS__, 'render_travels_page'),
            'dashicons-yes-alt',
            30
        );

        // Sottomenu: Viaggi da approvare (stesso slug del menu principale)
        add_submenu_page(
            'cdv-approvals',
            'Viaggi da Approvare',
            'Viaggi',
            'manage_options',
            'cdv-approvals',
            array(__CLASS__, 'render_travels_page')
        );

        // Sottomenu: Immagini da approvare
        add_submenu_page(
            'cdv-approvals',
            'Immagini da Approvare',
            'Immagini Profilo',
            'manage_options',
            'cdv-approvals-images',
            array(__CLASS__, 'render_images_page')
        );

        // Sottomenu: Recensioni da approvare
        add_submenu_page(
            'cdv-approvals',
            'Recensioni da Approvare',
            'Recensioni',
            'manage_options',
            'cdv-approvals-reviews',
            array(__CLASS__, 'render_reviews_page')
        );
    }

    /**
     * Enqueue admin styles
     */
    public static function enqueue_admin_styles($hook) {
        if (strpos($hook, 'cdv-approvals') === false) {
            return;
        }
        ?>
        <style>
            .cdv-approval-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .cdv-approval-table th {
                background: #f0f0f1;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                border-bottom: 2px solid #c3c4c7;
            }
            .cdv-approval-table td {
                padding: 12px;
                border-bottom: 1px solid #dcdcde;
            }
            .cdv-approval-table tr:hover {
                background: #f6f7f7;
            }
            .cdv-user-avatar {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                object-fit: cover;
            }
            .cdv-initials-avatar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                color: white;
                font-weight: 600;
            }
            .cdv-actions {
                display: flex;
                gap: 8px;
            }
            .cdv-approve-btn {
                background: #00a32a;
                color: white;
                padding: 6px 12px;
                border-radius: 3px;
                text-decoration: none;
                font-size: 13px;
            }
            .cdv-approve-btn:hover {
                background: #008a20;
                color: white;
            }
            .cdv-reject-btn {
                background: #d63638;
                color: white;
                padding: 6px 12px;
                border-radius: 3px;
                text-decoration: none;
                font-size: 13px;
            }
            .cdv-reject-btn:hover {
                background: #b32d2e;
                color: white;
            }
            .cdv-pending-badge {
                background: #dba617;
                color: white;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            .cdv-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }
            .cdv-stat-box {
                background: white;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                flex: 1;
            }
            .cdv-stat-number {
                font-size: 32px;
                font-weight: 600;
                color: #2271b1;
            }
            .cdv-stat-label {
                color: #646970;
                font-size: 14px;
                margin-top: 4px;
            }
            .cdv-no-items {
                background: white;
                padding: 40px;
                text-align: center;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                margin-top: 20px;
            }
            .cdv-no-items-icon {
                font-size: 48px;
                margin-bottom: 16px;
                opacity: 0.5;
            }
        </style>
        <?php
    }

    /**
     * Pagina viaggi da approvare
     */
    public static function render_travels_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi per accedere a questa pagina.');
        }

        // Get pending travels
        $pending_travels = get_posts(array(
            'post_type' => 'viaggio',
            'post_status' => 'pending',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        ?>
        <div class="wrap">
            <h1>🌍 Viaggi da Approvare</h1>

            <div class="cdv-stats">
                <div class="cdv-stat-box">
                    <div class="cdv-stat-number"><?php echo count($pending_travels); ?></div>
                    <div class="cdv-stat-label">Viaggi in Attesa</div>
                </div>
            </div>

            <?php if (empty($pending_travels)) : ?>
                <div class="cdv-no-items">
                    <div class="cdv-no-items-icon">✓</div>
                    <h2>Nessun viaggio da approvare</h2>
                    <p>Tutti i viaggi sono stati approvati o non ci sono nuove richieste.</p>
                </div>
            <?php else : ?>
                <table class="cdv-approval-table">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Organizzatore</th>
                            <th>Destinazione</th>
                            <th>Date</th>
                            <th>Data Invio</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_travels as $travel) : ?>
                            <?php
                            $organizer = get_userdata($travel->post_author);
                            $destination = get_post_meta($travel->ID, '_cdv_destination', true);
                            $start_date = get_post_meta($travel->ID, '_cdv_start_date', true);
                            $end_date = get_post_meta($travel->ID, '_cdv_end_date', true);

                            $approve_url = wp_nonce_url(
                                admin_url('admin-post.php?action=cdv_approve_travel&travel_id=' . $travel->ID),
                                'approve_travel_' . $travel->ID
                            );

                            $reject_url = wp_nonce_url(
                                admin_url('admin-post.php?action=cdv_reject_travel&travel_id=' . $travel->ID),
                                'reject_travel_' . $travel->ID
                            );

                            $edit_url = admin_url('post.php?post=' . $travel->ID . '&action=edit');
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($travel->post_title); ?></strong>
                                    <br>
                                    <a href="<?php echo esc_url($edit_url); ?>">Visualizza/Modifica</a>
                                </td>
                                <td>
                                    <?php echo esc_html($organizer->display_name); ?>
                                    <br>
                                    <small><?php echo esc_html($organizer->user_email); ?></small>
                                </td>
                                <td><?php echo esc_html($destination); ?></td>
                                <td>
                                    <?php
                                    if ($start_date && $end_date) {
                                        echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($travel->post_date)); ?></td>
                                <td>
                                    <div class="cdv-actions">
                                        <a href="<?php echo esc_url($approve_url); ?>" class="cdv-approve-btn">
                                            ✓ Approva
                                        </a>
                                        <a href="<?php echo esc_url($reject_url); ?>" class="cdv-reject-btn"
                                           onclick="return confirm('Sei sicuro di voler rifiutare questo viaggio?');">
                                            ✗ Rifiuta
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Pagina immagini da approvare
     */
    public static function render_images_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi per accedere a questa pagina.');
        }

        // Get users with unapproved images
        $users_with_images = get_users(array(
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'cdv_profile_image',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => 'cdv_image_approved',
                    'value' => '1',
                    'compare' => '!=',
                ),
            ),
        ));

        ?>
        <div class="wrap">
            <h1>🖼️ Immagini Profilo da Approvare</h1>

            <div class="cdv-stats">
                <div class="cdv-stat-box">
                    <div class="cdv-stat-number"><?php echo count($users_with_images); ?></div>
                    <div class="cdv-stat-label">Immagini in Attesa</div>
                </div>
            </div>

            <?php if (empty($users_with_images)) : ?>
                <div class="cdv-no-items">
                    <div class="cdv-no-items-icon">✓</div>
                    <h2>Nessuna immagine da approvare</h2>
                    <p>Tutte le immagini profilo sono state approvate o non ci sono nuove richieste.</p>
                </div>
            <?php else : ?>
                <table class="cdv-approval-table">
                    <thead>
                        <tr>
                            <th>Immagine</th>
                            <th>Utente</th>
                            <th>Email</th>
                            <th>Data Registrazione</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_with_images as $user) : ?>
                            <?php
                            $image_id = get_user_meta($user->ID, 'cdv_profile_image', true);
                            $image_url = wp_get_attachment_url($image_id);

                            $approve_url = wp_nonce_url(
                                admin_url('admin-post.php?action=cdv_approve_image&user_id=' . $user->ID),
                                'approve_image_' . $user->ID
                            );

                            $reject_url = wp_nonce_url(
                                admin_url('admin-post.php?action=cdv_reject_image&user_id=' . $user->ID),
                                'reject_image_' . $user->ID
                            );

                            $edit_url = admin_url('user-edit.php?user_id=' . $user->ID);
                            ?>
                            <tr>
                                <td>
                                    <?php if ($image_url) : ?>
                                        <img src="<?php echo esc_url($image_url); ?>" class="cdv-user-avatar" alt="">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($user->display_name); ?></strong>
                                    <br>
                                    <small>@<?php echo esc_html($user->user_login); ?></small>
                                    <br>
                                    <a href="<?php echo esc_url($edit_url); ?>">Modifica utente</a>
                                </td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user->user_registered)); ?></td>
                                <td>
                                    <div class="cdv-actions">
                                        <a href="<?php echo esc_url($approve_url); ?>" class="cdv-approve-btn">
                                            ✓ Approva
                                        </a>
                                        <a href="<?php echo esc_url($reject_url); ?>" class="cdv-reject-btn"
                                           onclick="return confirm('Sei sicuro di voler rifiutare questa immagine?');">
                                            ✗ Rifiuta
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Pagina recensioni da approvare
     */
    public static function render_reviews_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Non hai i permessi per accedere a questa pagina.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_reviews';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            ?>
            <div class="wrap">
                <h1>⭐ Recensioni da Approvare</h1>
                <div class="cdv-no-items">
                    <div class="cdv-no-items-icon">ℹ️</div>
                    <h2>Sistema recensioni non ancora attivo</h2>
                    <p>La tabella delle recensioni non è stata ancora creata.</p>
                </div>
            </div>
            <?php
            return;
        }

        // Get pending reviews
        $pending_reviews = $wpdb->get_results(
            "SELECT r.*, u.display_name as reviewer_name, u.user_email as reviewer_email,
                    p.post_title as travel_title
             FROM $table_name r
             LEFT JOIN {$wpdb->users} u ON r.reviewer_id = u.ID
             LEFT JOIN {$wpdb->posts} p ON r.travel_id = p.ID
             WHERE r.status = 'pending'
             ORDER BY r.created_at DESC"
        );

        ?>
        <div class="wrap">
            <h1>⭐ Recensioni da Approvare</h1>

            <div class="cdv-stats">
                <div class="cdv-stat-box">
                    <div class="cdv-stat-number"><?php echo count($pending_reviews); ?></div>
                    <div class="cdv-stat-label">Recensioni in Attesa</div>
                </div>
            </div>

            <?php if (empty($pending_reviews)) : ?>
                <div class="cdv-no-items">
                    <div class="cdv-no-items-icon">✓</div>
                    <h2>Nessuna recensione da approvare</h2>
                    <p>Tutte le recensioni sono state approvate o non ci sono nuove richieste.</p>
                </div>
            <?php else : ?>
                <table class="cdv-approval-table">
                    <thead>
                        <tr>
                            <th>Viaggio</th>
                            <th>Recensore</th>
                            <th>Valutazione</th>
                            <th>Recensione</th>
                            <th>Data</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_reviews as $review) : ?>
                            <?php
                            $approve_url = wp_nonce_url(
                                admin_url('admin-post.php?action=cdv_approve_review&review_id=' . $review->id),
                                'approve_review_' . $review->id
                            );

                            $reject_url = wp_nonce_url(
                                admin_url('admin-post.php?action=cdv_reject_review&review_id=' . $review->id),
                                'reject_review_' . $review->id
                            );

                            $travel_url = get_permalink($review->travel_id);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($review->travel_title); ?></strong>
                                    <br>
                                    <a href="<?php echo esc_url($travel_url); ?>" target="_blank">Visualizza viaggio</a>
                                </td>
                                <td>
                                    <?php echo esc_html($review->reviewer_name); ?>
                                    <br>
                                    <small><?php echo esc_html($review->reviewer_email); ?></small>
                                </td>
                                <td>
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review->rating ? '⭐' : '☆';
                                    }
                                    ?>
                                    <br>
                                    <small><?php echo $review->rating; ?>/5</small>
                                </td>
                                <td>
                                    <?php echo esc_html(substr($review->review_text, 0, 100)); ?>
                                    <?php if (strlen($review->review_text) > 100) echo '...'; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($review->created_at)); ?></td>
                                <td>
                                    <div class="cdv-actions">
                                        <a href="<?php echo esc_url($approve_url); ?>" class="cdv-approve-btn">
                                            ✓ Approva
                                        </a>
                                        <a href="<?php echo esc_url($reject_url); ?>" class="cdv-reject-btn"
                                           onclick="return confirm('Sei sicuro di voler rifiutare questa recensione?');">
                                            ✗ Rifiuta
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Gestisce approvazione immagine
     */
    public static function handle_approve_image() {
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }

        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        if (!wp_verify_nonce($_GET['_wpnonce'], 'approve_image_' . $user_id)) {
            wp_die('Richiesta non valida');
        }

        // Approve image
        update_user_meta($user_id, 'cdv_image_approved', '1');

        // Redirect back
        wp_safe_redirect(admin_url('admin.php?page=cdv-approvals-images&approved=1'));
        exit;
    }

    /**
     * Gestisce rifiuto immagine
     */
    public static function handle_reject_image() {
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }

        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        if (!wp_verify_nonce($_GET['_wpnonce'], 'reject_image_' . $user_id)) {
            wp_die('Richiesta non valida');
        }

        // Remove image and approval meta
        delete_user_meta($user_id, 'cdv_profile_image');
        delete_user_meta($user_id, 'cdv_image_approved');

        // Redirect back
        wp_safe_redirect(admin_url('admin.php?page=cdv-approvals-images&rejected=1'));
        exit;
    }

    /**
     * Gestisce approvazione viaggio
     */
    public static function handle_approve_travel() {
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }

        $travel_id = isset($_GET['travel_id']) ? intval($_GET['travel_id']) : 0;

        if (!wp_verify_nonce($_GET['_wpnonce'], 'approve_travel_' . $travel_id)) {
            wp_die('Richiesta non valida');
        }

        // Publish travel
        wp_update_post(array(
            'ID' => $travel_id,
            'post_status' => 'publish',
        ));

        // Notifica l'organizzatore
        $travel = get_post($travel_id);
        $organizer = get_userdata($travel->post_author);

        $subject = '✅ Il tuo viaggio è stato approvato - ' . $travel->post_title;
        $message = '
        <!DOCTYPE html>
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #00a32a;">✅ Viaggio Approvato!</h2>
                <p>Ciao ' . esc_html($organizer->display_name) . ',</p>
                <p>Il tuo viaggio "<strong>' . esc_html($travel->post_title) . '</strong>" è stato approvato e ora è visibile sulla piattaforma.</p>
                <p>Gli altri viaggiatori possono ora visualizzarlo e richiedere di partecipare.</p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="' . esc_url(get_permalink($travel_id)) . '"
                       style="display: inline-block; padding: 15px 30px; background: #667eea;
                              color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        Visualizza il tuo viaggio
                    </a>
                </p>
                <p>Buon viaggio!<br>Il team di Compagni di Viaggi</p>
            </div>
        </body>
        </html>';

        wp_mail($organizer->user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));

        // Redirect back
        wp_safe_redirect(admin_url('admin.php?page=cdv-approvals&approved=1'));
        exit;
    }

    /**
     * Gestisce rifiuto viaggio
     */
    public static function handle_reject_travel() {
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }

        $travel_id = isset($_GET['travel_id']) ? intval($_GET['travel_id']) : 0;

        if (!wp_verify_nonce($_GET['_wpnonce'], 'reject_travel_' . $travel_id)) {
            wp_die('Richiesta non valida');
        }

        // Move to trash
        wp_trash_post($travel_id);

        // Redirect back
        wp_safe_redirect(admin_url('admin.php?page=cdv-approvals&rejected=1'));
        exit;
    }

    /**
     * Gestisce approvazione recensione
     */
    public static function handle_approve_review() {
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }

        $review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;

        if (!wp_verify_nonce($_GET['_wpnonce'], 'approve_review_' . $review_id)) {
            wp_die('Richiesta non valida');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_reviews';

        // Approve review
        $wpdb->update(
            $table_name,
            array('status' => 'approved'),
            array('id' => $review_id)
        );

        // Redirect back
        wp_safe_redirect(admin_url('admin.php?page=cdv-approvals-reviews&approved=1'));
        exit;
    }

    /**
     * Gestisce rifiuto recensione
     */
    public static function handle_reject_review() {
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }

        $review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;

        if (!wp_verify_nonce($_GET['_wpnonce'], 'reject_review_' . $review_id)) {
            wp_die('Richiesta non valida');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cdv_reviews';

        // Reject review
        $wpdb->update(
            $table_name,
            array('status' => 'rejected'),
            array('id' => $review_id)
        );

        // Redirect back
        wp_safe_redirect(admin_url('admin.php?page=cdv-approvals-reviews&rejected=1'));
        exit;
    }
}
