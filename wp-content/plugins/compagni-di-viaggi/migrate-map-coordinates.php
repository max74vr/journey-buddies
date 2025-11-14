<?php
/**
 * Migration Script: Add Map Coordinates to Existing Travels
 *
 * This script geocodes all existing travels that don't have coordinates
 * and saves them to the database.
 *
 * USAGE:
 * 1. Upload this file to wp-content/plugins/compagni-di-viaggi/
 * 2. Visit: https://www.compagnidiviaggi.com/wp-content/plugins/compagni-di-viaggi/migrate-map-coordinates.php
 * 3. DELETE this file after use for security
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('⛔ Accesso negato. Devi essere amministratore.');
}

echo '<html><head><meta charset="UTF-8"><title>Migrazione Coordinate Mappe</title>';
echo '<style>
body { font-family: system-ui, -apple-system, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
.success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545; }
.info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #17a2b8; }
.warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107; }
h1 { color: #333; }
.stats { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0; }
</style></head><body>';

echo '<h1>🗺️ Migrazione Coordinate Mappe Viaggi</h1>';

// Get all published travels
$args = array(
    'post_type' => 'viaggio',
    'post_status' => array('publish', 'pending'),
    'posts_per_page' => -1,
    'fields' => 'ids'
);

$travel_ids = get_posts($args);

if (empty($travel_ids)) {
    echo '<div class="warning">⚠️ Nessun viaggio trovato nel database.</div>';
    echo '</body></html>';
    exit;
}

echo '<div class="info">📊 Trovati <strong>' . count($travel_ids) . '</strong> viaggi totali.</div>';

$processed = 0;
$updated = 0;
$skipped = 0;
$errors = 0;

echo '<div class="stats">';
echo '<h2>Elaborazione in corso...</h2>';

foreach ($travel_ids as $travel_id) {
    $processed++;

    $title = get_the_title($travel_id);

    // Check if coordinates already exist
    $lat = get_post_meta($travel_id, 'cdv_map_lat', true);
    $lon = get_post_meta($travel_id, 'cdv_map_lon', true);

    if (!empty($lat) && !empty($lon)) {
        echo '<div class="info">✓ #' . $travel_id . ' - ' . esc_html($title) . ' - Coordinate già presenti (lat: ' . $lat . ', lon: ' . $lon . ')</div>';
        $skipped++;
        continue;
    }

    // Get destination and country
    $destination = get_post_meta($travel_id, 'cdv_destination', true);
    $country = get_post_meta($travel_id, 'cdv_country', true);

    if (empty($destination) || empty($country)) {
        echo '<div class="warning">⚠️ #' . $travel_id . ' - ' . esc_html($title) . ' - Destinazione o paese mancante</div>';
        $errors++;
        continue;
    }

    // Geocode
    $address = $destination . ', ' . $country;
    echo '<div class="info">🔍 #' . $travel_id . ' - ' . esc_html($title) . ' - Geocoding: ' . esc_html($address) . '...</div>';

    $geocoded = CDV_Travel_Maps::geocode($address);

    if ($geocoded && isset($geocoded['lat']) && isset($geocoded['lon'])) {
        // Save coordinates
        update_post_meta($travel_id, 'cdv_map_lat', $geocoded['lat']);
        update_post_meta($travel_id, 'cdv_map_lon', $geocoded['lon']);

        echo '<div class="success">✅ #' . $travel_id . ' - ' . esc_html($title) . ' - Coordinate salvate: ' . $geocoded['lat'] . ', ' . $geocoded['lon'] . '</div>';
        $updated++;

        // Sleep to avoid rate limiting
        sleep(1);
    } else {
        echo '<div class="error">❌ #' . $travel_id . ' - ' . esc_html($title) . ' - Geocoding fallito per: ' . esc_html($address) . '</div>';
        $errors++;
    }

    // Flush output
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

echo '</div>';

echo '<div class="stats">';
echo '<h2>📊 Riepilogo Migrazione</h2>';
echo '<p><strong>Viaggi elaborati:</strong> ' . $processed . '</p>';
echo '<p><strong>Coordinate aggiunte:</strong> ' . $updated . '</p>';
echo '<p><strong>Già presenti (saltati):</strong> ' . $skipped . '</p>';
echo '<p><strong>Errori:</strong> ' . $errors . '</p>';
echo '</div>';

if ($updated > 0) {
    echo '<div class="success">✅ <strong>Migrazione completata!</strong> Le mappe ora dovrebbero funzionare sui viaggi aggiornati.</div>';
}

echo '<div class="warning">⚠️ <strong>IMPORTANTE:</strong> Elimina questo file (migrate-map-coordinates.php) per motivi di sicurezza!</div>';

echo '</body></html>';
