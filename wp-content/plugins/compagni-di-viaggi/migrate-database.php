<?php
/**
 * Database Migration Script
 *
 * Visita: yoursite.com/wp-content/plugins/compagni-di-viaggi/migrate-database.php
 * Questo script aggiunge la colonna is_organizer se non esiste
 *
 * IMPORTANTE: Cancella questo file dopo l'esecuzione!
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Accesso negato. Solo amministratori.');
}

header('Content-Type: text/html; charset=utf-8');

echo '<h1>Migrazione Database - Sistema Partecipazioni</h1>';
echo '<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
pre { background: #f4f4f4; padding: 10px; overflow-x: auto; border-left: 3px solid #667eea; }
h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
</style>';

global $wpdb;
$table_name = $wpdb->prefix . 'cdv_travel_participants';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if (!$table_exists) {
    echo '<div class="error"><strong>ERRORE:</strong> La tabella ' . $table_name . ' non esiste!</div>';
    echo '<p>Prova a riattivare il plugin "Compagni di Viaggi".</p>';
    exit;
}

echo '<div class="info"><strong>Tabella trovata:</strong> ' . $table_name . '</div>';

// Get current columns
$columns = $wpdb->get_results("DESCRIBE $table_name");
$column_names = array_column($columns, 'Field');

echo '<h2>1. Verifica Colonne Attuali</h2>';
echo '<p>Colonne presenti: <code>' . implode(', ', $column_names) . '</code></p>';

// Check if is_organizer exists
$needs_is_organizer = !in_array('is_organizer', $column_names);

// Check if we're using requested_at or created_at
$has_requested_at = in_array('requested_at', $column_names);
$has_created_at = in_array('created_at', $column_names);

echo '<h2>2. Migrazioni Necessarie</h2>';

$migrations = array();

if ($needs_is_organizer) {
    $migrations[] = array(
        'name' => 'Aggiungi colonna is_organizer',
        'sql' => "ALTER TABLE $table_name ADD COLUMN is_organizer tinyint(1) NOT NULL DEFAULT 0 AFTER message",
        'critical' => true
    );
}

if (!$has_requested_at && $has_created_at) {
    $migrations[] = array(
        'name' => 'Rinomina created_at in requested_at',
        'sql' => "ALTER TABLE $table_name CHANGE created_at requested_at datetime DEFAULT CURRENT_TIMESTAMP",
        'critical' => false
    );
}

if (empty($migrations)) {
    echo '<div class="success"><strong>✓ NESSUNA MIGRAZIONE NECESSARIA</strong><br>La struttura del database è già aggiornata!</div>';
} else {
    echo '<p>Trovate <strong>' . count($migrations) . '</strong> migrazioni da applicare:</p>';
    echo '<ul>';
    foreach ($migrations as $m) {
        echo '<li>' . $m['name'] . ($m['critical'] ? ' <strong style="color:red;">(CRITICO)</strong>' : '') . '</li>';
    }
    echo '</ul>';

    // Execute migrations
    echo '<h2>3. Esecuzione Migrazioni</h2>';

    foreach ($migrations as $migration) {
        echo '<div class="info">';
        echo '<strong>Esecuzione:</strong> ' . $migration['name'] . '<br>';
        echo '<strong>SQL:</strong> <pre>' . $migration['sql'] . '</pre>';

        $result = $wpdb->query($migration['sql']);

        if ($result === false) {
            echo '<div class="error">✗ ERRORE: ' . $wpdb->last_error . '</div>';
        } else {
            echo '<div class="success">✓ Migrazione completata con successo!</div>';
        }
        echo '</div>';
    }
}

// Verify final structure
echo '<h2>4. Verifica Struttura Finale</h2>';
$columns_after = $wpdb->get_results("DESCRIBE $table_name");

echo '<table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse;">';
echo '<tr style="background: #667eea; color: white;">
<th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th><th>Extra</th>
</tr>';

foreach ($columns_after as $col) {
    $is_new = !in_array($col->Field, $column_names);
    $row_style = $is_new ? 'background: #d4edda;' : '';
    echo "<tr style='$row_style'>";
    echo "<td><strong>{$col->Field}</strong></td>";
    echo "<td>{$col->Type}</td>";
    echo "<td>{$col->Null}</td>";
    echo "<td>" . ($col->Default ?? 'NULL') . "</td>";
    echo "<td>{$col->Extra}</td>";
    echo "</tr>";
}
echo '</table>';

// Check if fix is working
echo '<h2>5. Test Funzionalità</h2>';

// Try a test insert (will be rolled back)
$wpdb->query('START TRANSACTION');

$test_insert = $wpdb->insert(
    $table_name,
    array(
        'travel_id' => 999999,
        'user_id' => 1,
        'status' => 'pending',
        'message' => 'Test migration',
    ),
    array('%d', '%d', '%s', '%s')
);

if ($test_insert) {
    echo '<div class="success">✓ Test INSERT funziona correttamente!</div>';
    $inserted_row = $wpdb->get_row("SELECT * FROM $table_name WHERE travel_id = 999999");
    echo '<p>Valori inseriti:</p>';
    echo '<pre>' . print_r($inserted_row, true) . '</pre>';

    // Check is_organizer default value
    if (isset($inserted_row->is_organizer)) {
        if ($inserted_row->is_organizer == 0) {
            echo '<div class="success">✓ is_organizer ha il valore default corretto (0)</div>';
        } else {
            echo '<div class="error">✗ is_organizer ha valore errato: ' . $inserted_row->is_organizer . '</div>';
        }
    }

    // Check requested_at
    if (isset($inserted_row->requested_at)) {
        echo '<div class="success">✓ requested_at generato automaticamente: ' . $inserted_row->requested_at . '</div>';
    } elseif (isset($inserted_row->created_at)) {
        echo '<div class="info">ℹ Usa created_at invece di requested_at (vecchio schema)</div>';
    }
} else {
    echo '<div class="error">✗ Test INSERT fallito: ' . $wpdb->last_error . '</div>';
}

// Rollback test
$wpdb->query('ROLLBACK');
echo '<p><em>Test completato e annullato (nessun dato salvato).</em></p>';

// Final summary
echo '<hr>';
echo '<h2>✅ Riepilogo</h2>';

if (empty($migrations)) {
    echo '<div class="success">';
    echo '<h3>Database già aggiornato!</h3>';
    echo '<p>Puoi procedere con il test della funzionalità di partecipazione.</p>';
    echo '</div>';
} elseif ($test_insert) {
    echo '<div class="success">';
    echo '<h3>Migrazione completata con successo!</h3>';
    echo '<p>Il database è stato aggiornato e le funzionalità di partecipazione dovrebbero ora funzionare correttamente.</p>';
    echo '<p><strong>Prossimi passi:</strong></p>';
    echo '<ul>';
    echo '<li>Testa il sistema creando una richiesta di partecipazione</li>';
    echo '<li>Verifica che le richieste appaiano nella dashboard</li>';
    echo '<li><strong>CANCELLA questo file (migrate-database.php) per sicurezza!</strong></li>';
    echo '</ul>';
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<h3>Ci sono ancora problemi</h3>';
    echo '<p>Controlla gli errori sopra e prova a risolverli manualmente.</p>';
    echo '</div>';
}

echo '<hr>';
echo '<p><a href="' . admin_url() . '" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Torna alla Dashboard WordPress</a></p>';
echo '<p><a href="debug-participations.php" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Esegui Debug Script</a></p>';
