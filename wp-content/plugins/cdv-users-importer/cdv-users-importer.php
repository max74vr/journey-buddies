<?php
/**
 * Plugin Name: Compagni di Viaggi - Users Importer
 * Plugin URI: https://compagnidiviaggi.it
 * Description: Importa utenti da file JSON con profili completi
 * Version: 1.0.0
 * Author: Compagni di Viaggi
 * Text Domain: cdv-users-importer
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Users_Importer {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_cdv_import_users', array($this, 'handle_import'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Importa Utenti JSON',
            'Importa Utenti JSON',
            'manage_options',
            'cdv-users-importer',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'tools_page_cdv-users-importer') {
            return;
        }

        wp_enqueue_style(
            'cdv-users-importer',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Show messages
        if (isset($_GET['success'])) {
            $imported = isset($_GET['imported']) ? intval($_GET['imported']) : 0;
            $updated = isset($_GET['updated']) ? intval($_GET['updated']) : 0;
            $skipped = isset($_GET['skipped']) ? intval($_GET['skipped']) : 0;
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Importazione completata!</strong></p>
                <div class="cdv-import-stats">
                    <div class="cdv-import-stat success">
                        <h3><?php echo $imported; ?></h3>
                        <p>Utenti importati</p>
                    </div>
                    <div class="cdv-import-stat info">
                        <h3><?php echo $updated; ?></h3>
                        <p>Utenti aggiornati</p>
                    </div>
                    <div class="cdv-import-stat warning">
                        <h3><?php echo $skipped; ?></h3>
                        <p>Utenti saltati</p>
                    </div>
                </div>
            </div>
            <?php
            if (isset($_GET['errors'])) {
                $errors = json_decode(base64_decode($_GET['errors']), true);
                if (!empty($errors)) {
                    ?>
                    <div class="notice notice-warning">
                        <p><strong>Alcuni utenti non sono stati importati:</strong></p>
                        <div class="error-list">
                            <ul>
                                <?php foreach ($errors as $error) : ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                }
            }
        }

        if (isset($_GET['error'])) {
            $error_type = $_GET['error'];
            $error_messages = array(
                'upload' => 'Errore durante il caricamento del file',
                'json' => 'File JSON non valido o corrotto',
                'format' => 'Il file non contiene un array di utenti valido',
            );
            $error_message = isset($error_messages[$error_type]) ? $error_messages[$error_type] : 'Errore sconosciuto';
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Errore:</strong> <?php echo esc_html($error_message); ?></p>
            </div>
            <?php
        }
        ?>
        <div class="wrap cdv-importer-wrap">
            <h1>Importa Utenti da File JSON</h1>

            <div class="cdv-importer-card">
                <h2>Carica File JSON</h2>
                <p>Seleziona un file JSON contenente gli utenti da importare. Il file deve seguire la struttura corretta con username, email, password, e metadati utente.</p>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="cdv_import_users">
                    <?php wp_nonce_field('cdv_import_users', 'cdv_import_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="json_file">File JSON</label>
                            </th>
                            <td>
                                <input type="file" name="json_file" id="json_file" accept=".json" required>
                                <p class="description">Carica un file JSON con la struttura degli utenti</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="send_notification">Notifiche Email</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="send_notification" id="send_notification" value="1">
                                    Invia email di benvenuto agli utenti importati
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="skip_existing">Gestione Duplicati</label>
                            </th>
                            <td>
                                <label>
                                    <input type="radio" name="skip_existing" value="skip" checked>
                                    Salta utenti esistenti
                                </label><br>
                                <label>
                                    <input type="radio" name="skip_existing" value="update">
                                    Aggiorna utenti esistenti
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-hero">
                            Importa Utenti
                        </button>
                    </p>
                </form>
            </div>

            <div class="cdv-importer-card">
                <h2>Formato JSON Richiesto</h2>
                <p>Il file JSON deve contenere un array di oggetti utente con la seguente struttura:</p>
                <pre><code>[
  {
    "username": "mario_rossi",
    "email": "mario.rossi@example.com",
    "password": "Password123!",
    "first_name": "Mario",
    "last_name": "Rossi",
    "display_name": "Mario Rossi",
    "role": "viaggiatore",
    "meta": {
      "cdv_bio": "Breve biografia...",
      "cdv_birth_date": "1990-05-15",
      "cdv_city": "Roma",
      "cdv_gender": "M",
      "cdv_languages": "Italiano, Inglese",
      "cdv_travel_style": "Avventura",
      "cdv_user_approved": "1",
      "cdv_email_verified": "yes"
    }
  }
]</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Handle import
     */
    public function handle_import() {
        // Check nonce
        if (!isset($_POST['cdv_import_nonce']) || !wp_verify_nonce($_POST['cdv_import_nonce'], 'cdv_import_users')) {
            wp_die('Nonce non valido');
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }

        // Check file upload
        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg(array('page' => 'cdv-users-importer', 'error' => 'upload'), admin_url('tools.php')));
            exit;
        }

        $file = $_FILES['json_file'];

        // Validate JSON
        $json_content = file_get_contents($file['tmp_name']);
        $users_data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_redirect(add_query_arg(array('page' => 'cdv-users-importer', 'error' => 'json'), admin_url('tools.php')));
            exit;
        }

        if (!is_array($users_data)) {
            wp_redirect(add_query_arg(array('page' => 'cdv-users-importer', 'error' => 'format'), admin_url('tools.php')));
            exit;
        }

        // Import settings
        $send_notification = isset($_POST['send_notification']) && $_POST['send_notification'] === '1';
        $skip_existing = isset($_POST['skip_existing']) ? $_POST['skip_existing'] : 'skip';

        // Import users
        $imported = 0;
        $skipped = 0;
        $updated = 0;
        $errors = array();

        foreach ($users_data as $index => $user_data) {
            // Validate required fields
            if (empty($user_data['username']) || empty($user_data['email'])) {
                $errors[] = "Riga $index: username o email mancante";
                continue;
            }

            // Check if user exists
            $existing_user = get_user_by('login', $user_data['username']);
            if (!$existing_user) {
                $existing_user = get_user_by('email', $user_data['email']);
            }

            if ($existing_user) {
                if ($skip_existing === 'skip') {
                    $skipped++;
                    continue;
                } elseif ($skip_existing === 'update') {
                    // Update existing user
                    $user_id = $existing_user->ID;

                    // Update user data
                    $update_data = array('ID' => $user_id);
                    if (!empty($user_data['first_name'])) $update_data['first_name'] = $user_data['first_name'];
                    if (!empty($user_data['last_name'])) $update_data['last_name'] = $user_data['last_name'];
                    if (!empty($user_data['display_name'])) $update_data['display_name'] = $user_data['display_name'];

                    wp_update_user($update_data);

                    // Update role if specified
                    if (!empty($user_data['role'])) {
                        $user = new WP_User($user_id);
                        $user->set_role($user_data['role']);
                    }

                    $updated++;
                } else {
                    $skipped++;
                    continue;
                }
            } else {
                // Create new user
                $userdata = array(
                    'user_login' => $user_data['username'],
                    'user_email' => $user_data['email'],
                    'user_pass' => isset($user_data['password']) ? $user_data['password'] : wp_generate_password(),
                    'first_name' => isset($user_data['first_name']) ? $user_data['first_name'] : '',
                    'last_name' => isset($user_data['last_name']) ? $user_data['last_name'] : '',
                    'display_name' => isset($user_data['display_name']) ? $user_data['display_name'] : $user_data['username'],
                    'role' => isset($user_data['role']) ? $user_data['role'] : 'subscriber',
                );

                $user_id = wp_insert_user($userdata);

                if (is_wp_error($user_id)) {
                    $errors[] = "Riga $index: " . $user_id->get_error_message();
                    continue;
                }

                $imported++;

                // Send notification if requested
                if ($send_notification) {
                    wp_new_user_notification($user_id, null, 'both');
                }
            }

            // Update meta data
            if (!empty($user_data['meta']) && is_array($user_data['meta'])) {
                foreach ($user_data['meta'] as $meta_key => $meta_value) {
                    update_user_meta($user_id, $meta_key, $meta_value);
                }
            }
        }

        // Redirect with results
        $redirect_args = array(
            'page' => 'cdv-users-importer',
            'success' => '1',
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        );

        if (!empty($errors)) {
            $redirect_args['errors'] = base64_encode(json_encode($errors));
        }

        wp_redirect(add_query_arg($redirect_args, admin_url('tools.php')));
        exit;
    }
}

// Initialize plugin
new CDV_Users_Importer();
