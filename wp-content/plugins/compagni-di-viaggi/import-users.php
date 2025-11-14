<?php
/**
 * User Import Script
 *
 * Usage: wp-admin → Tools → Import Users
 * Or run via WP-CLI: wp eval-file import-users.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to load WordPress if run directly
    require_once('../../../wp-load.php');
}

/**
 * Import users from JSON file
 */
function cdv_import_users_from_json() {
    $json_file = dirname(dirname(dirname(dirname(__FILE__)))) . '/import-users.json';

    if (!file_exists($json_file)) {
        return new WP_Error('file_not_found', 'File import-users.json not found');
    }

    $json_content = file_get_contents($json_file);
    $users_data = json_decode($json_content, true);

    if (!is_array($users_data)) {
        return new WP_Error('invalid_json', 'Invalid JSON format');
    }

    $imported = 0;
    $skipped = 0;
    $errors = array();

    foreach ($users_data as $user_data) {
        // Check if user already exists
        if (username_exists($user_data['username'])) {
            $skipped++;
            $errors[] = "Username {$user_data['username']} già esistente";
            continue;
        }

        if (email_exists($user_data['email'])) {
            $skipped++;
            $errors[] = "Email {$user_data['email']} già esistente";
            continue;
        }

        // Create user
        $user_id = wp_create_user(
            $user_data['username'],
            $user_data['password'],
            $user_data['email']
        );

        if (is_wp_error($user_id)) {
            $skipped++;
            $errors[] = "Errore creazione {$user_data['username']}: " . $user_id->get_error_message();
            continue;
        }

        // Update user data
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'display_name' => $user_data['display_name'],
        ));

        // Set role
        $user = new WP_User($user_id);
        $user->set_role($user_data['role']);

        // Add meta data
        if (isset($user_data['meta']) && is_array($user_data['meta'])) {
            foreach ($user_data['meta'] as $meta_key => $meta_value) {
                update_user_meta($user_id, $meta_key, $meta_value);
            }
        }

        $imported++;
    }

    return array(
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors,
    );
}

/**
 * Admin page for user import
 */
function cdv_import_users_admin_page() {
    add_submenu_page(
        'tools.php',
        'Importa Utenti',
        'Importa Utenti',
        'manage_options',
        'cdv-import-users',
        'cdv_import_users_page_content'
    );
}
add_action('admin_menu', 'cdv_import_users_admin_page');

/**
 * Import page content
 */
function cdv_import_users_page_content() {
    ?>
    <div class="wrap">
        <h1>Importa Utenti da JSON</h1>

        <?php
        if (isset($_POST['import_users']) && check_admin_referer('cdv_import_users')) {
            $result = cdv_import_users_from_json();

            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p>' . $result->get_error_message() . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>';
                echo '<strong>Importazione completata!</strong><br>';
                echo 'Importati: ' . $result['imported'] . '<br>';
                echo 'Saltati: ' . $result['skipped'] . '<br>';
                if (!empty($result['errors'])) {
                    echo '<br><strong>Errori:</strong><br>';
                    foreach ($result['errors'] as $error) {
                        echo '- ' . esc_html($error) . '<br>';
                    }
                }
                echo '</p></div>';
            }
        }
        ?>

        <div class="card">
            <h2>Importa 20 Utenti Demo</h2>
            <p>Questo strumento importerà 20 utenti viaggiatori dal file <code>import-users.json</code>.</p>

            <p><strong>Utenti da importare:</strong></p>
            <ul style="columns: 2;">
                <li>Marco Rossi - Milano</li>
                <li>Laura Bianchi - Roma</li>
                <li>Giuseppe Verdi - Torino</li>
                <li>Sofia Russo - Napoli</li>
                <li>Alessandro Ferrari - Genova</li>
                <li>Chiara Marino - Bologna</li>
                <li>Luca Colombo - Firenze</li>
                <li>Valentina Ricci - Palermo</li>
                <li>Matteo Costa - Verona</li>
                <li>Francesca Galli - Cagliari</li>
                <li>Andrea Moretti - Venezia</li>
                <li>Elena Barbieri - Trieste</li>
                <li>Roberto Fontana - Trento</li>
                <li>Giulia Santoro - Bari</li>
                <li>Davide Leone - Perugia</li>
                <li>Silvia Morelli - Catania</li>
                <li>Federico Greco - Siena</li>
                <li>Martina Conti - Padova</li>
                <li>Simone De Luca - Brescia</li>
                <li>Anna Marchetti - Pisa</li>
            </ul>

            <p><strong>Password temporanea per tutti:</strong> <code>TempPassword123!</code></p>

            <p style="color: #d63638;"><strong>Attenzione:</strong> Gli utenti verranno creati solo se username e email non esistono già.</p>

            <form method="post">
                <?php wp_nonce_field('cdv_import_users'); ?>
                <p>
                    <button type="submit" name="import_users" class="button button-primary button-large">
                        🚀 Importa Utenti
                    </button>
                </p>
            </form>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h3>Informazioni File JSON</h3>
            <p>Il file deve trovarsi in: <code><?php echo dirname(dirname(dirname(dirname(__FILE__)))) . '/import-users.json'; ?></code></p>
            <p>Formato richiesto:</p>
            <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;">
[
  {
    "username": "username",
    "email": "email@example.com",
    "first_name": "Nome",
    "last_name": "Cognome",
    "display_name": "Nome Display",
    "role": "viaggiatore",
    "password": "password",
    "meta": {
      "cdv_bio": "Bio text",
      "cdv_city": "City",
      ...
    }
  }
]
            </pre>
        </div>
    </div>
    <?php
}
