<?php
/**
 * Debug script for participation system
 *
 * Visita: yoursite.com/wp-content/plugins/compagni-di-viaggi/debug-participations.php
 * (Solo per debugging - rimuovere in produzione)
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Accesso negato. Solo amministratori.');
}

header('Content-Type: text/html; charset=utf-8');

echo '<h1>Debug Sistema Partecipazioni</h1>';
echo '<style>body { font-family: monospace; } table { border-collapse: collapse; width: 100%; } td, th { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #667eea; color: white; } .success { color: green; } .error { color: red; } pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }</style>';

global $wpdb;

// 1. Check if table exists
echo '<h2>1. Verifica Tabella Database</h2>';
$table_name = $wpdb->prefix . 'cdv_travel_participants';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo '<p class="success">✓ Tabella exists: ' . $table_name . '</p>';

    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo '<h3>Struttura Tabella:</h3>';
    echo '<table><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>';
    foreach ($columns as $col) {
        echo "<tr><td>{$col->Field}</td><td>{$col->Type}</td><td>{$col->Null}</td><td>{$col->Default}</td></tr>";
    }
    echo '</table>';

    // Count records
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo '<p>Record totali nella tabella: <strong>' . $count . '</strong></p>';

    // Show all records
    if ($count > 0) {
        $records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY requested_at DESC LIMIT 20");
        echo '<h3>Ultimi 20 record:</h3>';
        echo '<table><tr><th>ID</th><th>Travel ID</th><th>User ID</th><th>Status</th><th>Is Organizer</th><th>Requested At</th><th>Message</th></tr>';
        foreach ($records as $rec) {
            echo "<tr>";
            echo "<td>{$rec->id}</td>";
            echo "<td>{$rec->travel_id}</td>";
            echo "<td>{$rec->user_id}</td>";
            echo "<td><strong>{$rec->status}</strong></td>";
            echo "<td>" . (isset($rec->is_organizer) ? $rec->is_organizer : 'N/A') . "</td>";
            echo "<td>" . (isset($rec->requested_at) ? $rec->requested_at : isset($rec->created_at) ? $rec->created_at : 'N/A') . "</td>";
            echo "<td>" . (isset($rec->message) ? substr($rec->message, 0, 50) : '') . "</td>";
            echo "</tr>";
        }
        echo '</table>';
    }
} else {
    echo '<p class="error">✗ Tabella NON trovata: ' . $table_name . '</p>';
    echo '<p>Prova a riattivare il plugin per creare la tabella.</p>';
}

// 2. Check AJAX handlers registration
echo '<h2>2. Verifica Registrazione AJAX Handlers</h2>';
global $wp_filter;
$ajax_actions = [
    'wp_ajax_cdv_join_travel',
    'wp_ajax_cdv_accept_participant',
    'wp_ajax_cdv_reject_participant',
    'wp_ajax_cdv_get_user_conversations',
    'wp_ajax_cdv_get_conversation',
    'wp_ajax_cdv_send_message',
];

foreach ($ajax_actions as $action) {
    if (isset($wp_filter[$action])) {
        echo '<p class="success">✓ Registrato: ' . $action . '</p>';
    } else {
        echo '<p class="error">✗ NON registrato: ' . $action . '</p>';
    }
}

// 3. Check if classes exist
echo '<h2>3. Verifica Classi Plugin</h2>';
$classes = ['CDV_Participants', 'CDV_Private_Messages', 'CDV_Chat'];
foreach ($classes as $class) {
    if (class_exists($class)) {
        echo '<p class="success">✓ Classe exists: ' . $class . '</p>';
    } else {
        echo '<p class="error">✗ Classe NON trovata: ' . $class . '</p>';
    }
}

// 4. Test query for current user
echo '<h2>4. Test Query Dashboard (Current User)</h2>';
$current_user = wp_get_current_user();
if ($current_user->ID > 0) {
    echo '<p>User ID: ' . $current_user->ID . ' (' . $current_user->user_login . ')</p>';

    // Test participated travels query
    echo '<h3>A. Viaggi a cui partecipo (accepted):</h3>';
    $participated_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT travel_id FROM $table_name WHERE user_id = %d AND status = 'accepted'",
        $current_user->ID
    ));
    echo '<p>IDs trovati: ' . (count($participated_ids) > 0 ? implode(', ', $participated_ids) : 'Nessuno') . '</p>';

    // Test pending requests SENT
    echo '<h3>B. Richieste INVIATE pendenti:</h3>';
    $my_pending = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, t.post_title
        FROM $table_name p
        LEFT JOIN {$wpdb->posts} t ON p.travel_id = t.ID
        WHERE p.user_id = %d AND p.status = 'pending'
        ORDER BY p.requested_at DESC",
        $current_user->ID
    ));
    if (!empty($my_pending)) {
        echo '<table><tr><th>Travel ID</th><th>Titolo</th><th>Status</th><th>Data</th></tr>';
        foreach ($my_pending as $req) {
            echo "<tr><td>{$req->travel_id}</td><td>{$req->post_title}</td><td>{$req->status}</td><td>" .
                 (isset($req->requested_at) ? $req->requested_at : (isset($req->created_at) ? $req->created_at : 'N/A')) .
                 "</td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p>Nessuna richiesta inviata pendente</p>';
    }

    // Test pending requests RECEIVED (as organizer)
    echo '<h3>C. Richieste RICEVUTE pendenti (come organizzatore):</h3>';
    $pending_received = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, t.post_title, u.user_login
        FROM $table_name p
        LEFT JOIN {$wpdb->posts} t ON p.travel_id = t.ID
        LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
        WHERE t.post_author = %d AND p.status = 'pending'
        ORDER BY p.requested_at DESC",
        $current_user->ID
    ));
    if (!empty($pending_received)) {
        echo '<table><tr><th>Travel</th><th>User</th><th>Status</th><th>Data</th></tr>';
        foreach ($pending_received as $req) {
            echo "<tr><td>{$req->post_title}</td><td>{$req->user_login}</td><td>{$req->status}</td><td>" .
                 (isset($req->requested_at) ? $req->requested_at : (isset($req->created_at) ? $req->created_at : 'N/A')) .
                 "</td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p>Nessuna richiesta ricevuta pendente</p>';
    }

    // Show last SQL query for debugging
    if ($wpdb->last_error) {
        echo '<h3 class="error">Ultimo Errore SQL:</h3>';
        echo '<pre>' . $wpdb->last_error . '</pre>';
    }
    echo '<h3>Ultima Query SQL:</h3>';
    echo '<pre>' . $wpdb->last_query . '</pre>';

} else {
    echo '<p class="error">Nessun utente loggato</p>';
}

// 5. List all travels
echo '<h2>5. Elenco Viaggi</h2>';
$travels = $wpdb->get_results("
    SELECT ID, post_title, post_author, post_status
    FROM {$wpdb->posts}
    WHERE post_type = 'viaggio'
    ORDER BY ID DESC
    LIMIT 10
");
if (!empty($travels)) {
    echo '<table><tr><th>ID</th><th>Titolo</th><th>Autore ID</th><th>Status</th><th>Partecipanti</th></tr>';
    foreach ($travels as $travel) {
        $parts_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE travel_id = %d",
            $travel->ID
        ));
        echo "<tr><td>{$travel->ID}</td><td>{$travel->post_title}</td><td>{$travel->post_author}</td><td>{$travel->post_status}</td><td>{$parts_count}</td></tr>";
    }
    echo '</table>';
} else {
    echo '<p>Nessun viaggio trovato</p>';
}

// 6. Test AJAX nonce
echo '<h2>6. Test AJAX Nonce</h2>';
$nonce = wp_create_nonce('cdv_ajax_nonce');
echo '<p>Nonce generato: <code>' . $nonce . '</code></p>';
$verify = wp_verify_nonce($nonce, 'cdv_ajax_nonce');
echo '<p>Verifica nonce: ' . ($verify ? '<span class="success">✓ Valido</span>' : '<span class="error">✗ Invalido</span>') . '</p>';

echo '<hr>';
echo '<p><strong>Debug completato!</strong> Se vedi errori in rosso, quelli sono i problemi da risolvere.</p>';
echo '<p><a href="' . admin_url() . '">← Torna alla Dashboard</a></p>';
