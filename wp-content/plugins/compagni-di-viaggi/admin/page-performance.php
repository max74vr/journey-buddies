<?php
/**
 * Performance Monitoring Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only admins
if (!current_user_can('manage_options')) {
    wp_die('Non hai i permessi necessari');
}

// Handle actions
if (isset($_POST['action']) && check_admin_referer('cdv_performance_action')) {
    $action = sanitize_text_field($_POST['action']);

    switch ($action) {
        case 'refresh_caches':
            $count = CDV_Performance::refresh_all_travel_caches();
            $message = "Cache aggiornate per {$count} viaggi";
            break;

        case 'cleanup_transients':
            CDV_Performance::cleanup_expired_transients();
            $message = "Transient scaduti eliminati";
            break;

        case 'optimize_tables':
            CDV_Performance::optimize_database_tables();
            $message = "Tabelle database ottimizzate";
            break;

        case 'ensure_indexes':
            CDV_Performance::ensure_database_indexes();
            $message = "Indici database verificati e creati se necessari";
            break;
    }
}

// Get performance report
$report = CDV_Performance::get_performance_report();
$stats = CDV_Performance::get_site_statistics();
?>

<div class="wrap">
    <h1>⚡ Performance Optimization</h1>

    <?php if (isset($message)) : ?>
        <div class="notice notice-success">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Performance Metrics -->
    <div class="cdv-performance-grid">
        <!-- Database Metrics -->
        <div class="cdv-performance-card">
            <h2>🗄️ Database</h2>
            <div class="metric">
                <span class="metric-label">Dimensione Totale:</span>
                <span class="metric-value"><?php echo number_format($report['database_size'], 2); ?> MB</span>
            </div>
            <div class="metric">
                <span class="metric-label">Autoload Size:</span>
                <span class="metric-value"><?php echo number_format($report['autoload_size'], 2); ?> MB</span>
            </div>
            <div class="metric">
                <span class="metric-label">Transient Attivi:</span>
                <span class="metric-value"><?php echo number_format($report['transients_count']); ?></span>
            </div>
            <div class="actions">
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('cdv_performance_action'); ?>
                    <input type="hidden" name="action" value="optimize_tables">
                    <button type="submit" class="button button-primary">Ottimizza Tabelle</button>
                </form>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('cdv_performance_action'); ?>
                    <input type="hidden" name="action" value="cleanup_transients">
                    <button type="submit" class="button">Pulisci Transient</button>
                </form>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('cdv_performance_action'); ?>
                    <input type="hidden" name="action" value="ensure_indexes">
                    <button type="submit" class="button">Verifica Indici</button>
                </form>
            </div>
        </div>

        <!-- Content Metrics -->
        <div class="cdv-performance-card">
            <h2>📊 Contenuti</h2>
            <div class="metric">
                <span class="metric-label">Viaggi Pubblicati:</span>
                <span class="metric-value"><?php echo number_format($stats['total_travels']); ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Viaggi Attivi:</span>
                <span class="metric-value"><?php echo number_format($stats['active_travels']); ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Utenti Totali:</span>
                <span class="metric-value"><?php echo number_format($stats['total_users']); ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Recensioni:</span>
                <span class="metric-value"><?php echo number_format($stats['total_reviews']); ?></span>
            </div>
        </div>

        <!-- Server Metrics -->
        <div class="cdv-performance-card">
            <h2>🖥️ Server</h2>
            <div class="metric">
                <span class="metric-label">PHP Version:</span>
                <span class="metric-value"><?php echo esc_html($report['php_version']); ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">WordPress Version:</span>
                <span class="metric-value"><?php echo esc_html($report['wp_version']); ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Memory Limit:</span>
                <span class="metric-value"><?php echo esc_html($report['memory_limit']); ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Max Execution Time:</span>
                <span class="metric-value"><?php echo esc_html($report['max_execution_time']); ?>s</span>
            </div>
        </div>

        <!-- Cache Management -->
        <div class="cdv-performance-card">
            <h2>🚀 Cache Management</h2>
            <p>Gestisci la cache dell'applicazione per migliorare le performance.</p>
            <div class="actions">
                <form method="post">
                    <?php wp_nonce_field('cdv_performance_action'); ?>
                    <input type="hidden" name="action" value="refresh_caches">
                    <button type="submit" class="button button-primary">Aggiorna Cache Viaggi</button>
                </form>
            </div>
            <div style="margin-top: 15px;">
                <strong>Cache Attive:</strong>
                <ul style="margin-top: 10px; padding-left: 20px;">
                    <li>✓ Object Cache (WP Cache)</li>
                    <li>✓ Transient Cache (DB)</li>
                    <li>✓ Geocoding Cache (30 giorni)</li>
                    <li>✓ Query Result Cache (1 ora)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Optimization Tips -->
    <div class="cdv-performance-card" style="margin-top: 20px;">
        <h2>💡 Suggerimenti per l'Ottimizzazione</h2>

        <div class="optimization-tips">
            <div class="tip">
                <h3>✅ Implementato</h3>
                <ul>
                    <li><strong>Indici Database:</strong> Indici ottimizzati su tutte le tabelle principali</li>
                    <li><strong>Query Optimization:</strong> Query ottimizzate con SQL_CALC_FOUND_ROWS</li>
                    <li><strong>Object Caching:</strong> Cache in memoria per dati frequenti</li>
                    <li><strong>Transient Caching:</strong> Cache persistente per query costose</li>
                    <li><strong>Lazy Loading:</strong> Immagini caricate solo quando visibili</li>
                    <li><strong>Asset Optimization:</strong> Rimozione query strings, cache busting</li>
                    <li><strong>Cleanup Automatico:</strong> Pulizia giornaliera dei transient scaduti</li>
                    <li><strong>Database Optimization:</strong> Ottimizzazione settimanale automatica</li>
                </ul>
            </div>

            <div class="tip">
                <h3>🔧 Raccomandazioni Aggiuntive</h3>
                <ul>
                    <li><strong>Caching Plugin:</strong> Considera l'installazione di WP Super Cache o W3 Total Cache</li>
                    <li><strong>CDN:</strong> Utilizza un CDN (CloudFlare, StackPath) per contenuti statici</li>
                    <li><strong>Image Optimization:</strong> Installa un plugin di ottimizzazione immagini (Smush, ShortPixel)</li>
                    <li><strong>PHP Version:</strong> Aggiorna a PHP 8.1+ per migliori performance</li>
                    <li><strong>Object Cache Backend:</strong> Installa Redis o Memcached per performance ottimali</li>
                    <li><strong>Database Tuning:</strong> Ottimizza my.cnf con configurazioni appropriate</li>
                </ul>
            </div>

            <div class="tip">
                <h3>📈 Monitoraggio</h3>
                <ul>
                    <li><strong>Query Monitor:</strong> Installa Query Monitor plugin per analisi dettagliate</li>
                    <li><strong>New Relic:</strong> Integra New Relic APM per monitoraggio real-time</li>
                    <li><strong>GTmetrix/Pingdom:</strong> Monitora regolarmente le performance del sito</li>
                    <li><strong>Error Log:</strong> Controlla regolarmente i log PHP per errori</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Performance Benchmarks -->
    <div class="cdv-performance-card" style="margin-top: 20px;">
        <h2>📊 Performance Benchmarks</h2>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Metrica</th>
                    <th>Target</th>
                    <th>Attuale</th>
                    <th>Stato</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Database Size</td>
                    <td>&lt; 500 MB</td>
                    <td><?php echo number_format($report['database_size'], 2); ?> MB</td>
                    <td>
                        <?php if ($report['database_size'] < 500) : ?>
                            <span style="color: green;">✓ OK</span>
                        <?php else : ?>
                            <span style="color: orange;">⚠ Attenzione</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Autoload Size</td>
                    <td>&lt; 1 MB</td>
                    <td><?php echo number_format($report['autoload_size'], 2); ?> MB</td>
                    <td>
                        <?php if ($report['autoload_size'] < 1) : ?>
                            <span style="color: green;">✓ OK</span>
                        <?php else : ?>
                            <span style="color: red;">✗ Critico</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td>&gt;= 8.0</td>
                    <td><?php echo esc_html($report['php_version']); ?></td>
                    <td>
                        <?php if (version_compare($report['php_version'], '8.0', '>=')) : ?>
                            <span style="color: green;">✓ OK</span>
                        <?php else : ?>
                            <span style="color: orange;">⚠ Aggiorna</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Memory Limit</td>
                    <td>&gt;= 256M</td>
                    <td><?php echo esc_html($report['memory_limit']); ?></td>
                    <td>
                        <?php
                        $memory_mb = intval($report['memory_limit']);
                        if ($memory_mb >= 256) : ?>
                            <span style="color: green;">✓ OK</span>
                        <?php else : ?>
                            <span style="color: orange;">⚠ Aumenta</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.cdv-performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.cdv-performance-card {
    background: white;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cdv-performance-card h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.3rem;
    color: #2c3e50;
}

.metric {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.metric:last-of-type {
    border-bottom: none;
}

.metric-label {
    font-weight: 500;
    color: #555;
}

.metric-value {
    font-weight: bold;
    color: #2c3e50;
}

.actions {
    margin-top: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.optimization-tips {
    display: grid;
    gap: 20px;
}

.tip {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #3498db;
}

.tip h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #2c3e50;
}

.tip ul {
    margin: 0;
    padding-left: 20px;
}

.tip li {
    margin-bottom: 8px;
    line-height: 1.6;
}
</style>
