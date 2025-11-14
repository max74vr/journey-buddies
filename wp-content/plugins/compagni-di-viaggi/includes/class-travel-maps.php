<?php
/**
 * Travel Maps Integration
 *
 * Integrates OpenStreetMap (via Leaflet) for travel destinations
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Travel_Maps {

    /**
     * Initialize
     */
    public static function init() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));

        // AJAX handlers for geocoding
        add_action('wp_ajax_cdv_geocode_address', array(__CLASS__, 'ajax_geocode_address'));
    }

    /**
     * Enqueue Leaflet scripts and styles
     */
    public static function enqueue_scripts() {
        // Enqueue on single travel pages, travel creation pages, and calendar
        if (is_singular('viaggio') || is_page(array('crea-viaggio', 'registrazione', 'dashboard', 'calendario-viaggi')) || is_post_type_archive('viaggio')) {
            // Leaflet CSS
            wp_enqueue_style(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                array(),
                '1.9.4'
            );

            // Leaflet JS - load in footer
            wp_enqueue_script(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                array(),
                '1.9.4',
                true // Load in footer
            );

            // Add integrity check
            add_filter('script_loader_tag', function($tag, $handle) {
                if ('leaflet' === $handle) {
                    $tag = str_replace(' src', ' crossorigin="anonymous" src', $tag);
                }
                return $tag;
            }, 10, 2);
        }
    }

    /**
     * Geocode an address using Nominatim (OpenStreetMap)
     */
    public static function ajax_geocode_address() {
        check_ajax_referer('cdv_ajax_nonce', 'nonce');

        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';

        if (empty($address)) {
            wp_send_json_error(array('message' => 'Indirizzo non valido'));
        }

        $result = self::geocode($address);

        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => 'Impossibile geocodificare l\'indirizzo'));
        }
    }

    /**
     * Geocode using Nominatim API
     */
    public static function geocode($address) {
        // Check cache first
        $cache_key = 'cdv_geocode_' . md5($address);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Build API request
        $url = add_query_arg(array(
            'q' => urlencode($address),
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1
        ), 'https://nominatim.openstreetmap.org/search');

        // Make request
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Compagni di Viaggi WordPress Plugin'
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !is_array($data)) {
            return false;
        }

        $result = array(
            'lat' => floatval($data[0]['lat']),
            'lon' => floatval($data[0]['lon']),
            'display_name' => sanitize_text_field($data[0]['display_name']),
            'address' => isset($data[0]['address']) ? $data[0]['address'] : array()
        );

        // Cache for 30 days
        set_transient($cache_key, $result, 30 * DAY_IN_SECONDS);

        return $result;
    }

    /**
     * Reverse geocode (coordinates to address)
     */
    public static function reverse_geocode($lat, $lon) {
        // Check cache first
        $cache_key = 'cdv_reverse_' . md5($lat . '_' . $lon);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Build API request
        $url = add_query_arg(array(
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'addressdetails' => 1
        ), 'https://nominatim.openstreetmap.org/reverse');

        // Make request
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Compagni di Viaggi WordPress Plugin'
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data)) {
            return false;
        }

        $result = array(
            'display_name' => sanitize_text_field($data['display_name']),
            'address' => isset($data['address']) ? $data['address'] : array()
        );

        // Cache for 30 days
        set_transient($cache_key, $result, 30 * DAY_IN_SECONDS);

        return $result;
    }

    /**
     * Save map coordinates for travel
     */
    public static function save_travel_coordinates($travel_id, $lat, $lon) {
        update_post_meta($travel_id, 'cdv_map_lat', floatval($lat));
        update_post_meta($travel_id, 'cdv_map_lon', floatval($lon));
    }

    /**
     * Get travel coordinates
     */
    public static function get_travel_coordinates($travel_id) {
        $lat = get_post_meta($travel_id, 'cdv_map_lat', true);
        $lon = get_post_meta($travel_id, 'cdv_map_lon', true);

        if (empty($lat) || empty($lon)) {
            return false;
        }

        return array(
            'lat' => floatval($lat),
            'lon' => floatval($lon)
        );
    }

    /**
     * Generate map HTML for a travel
     */
    public static function get_map_html($travel_id, $height = '400px') {
        $coords = self::get_travel_coordinates($travel_id);

        if (!$coords) {
            // Try to geocode the destination
            $destination = get_post_meta($travel_id, 'cdv_destination', true);
            $country = get_post_meta($travel_id, 'cdv_country', true);

            if ($destination && $country) {
                $address = $destination . ', ' . $country;
                $geocoded = self::geocode($address);

                if ($geocoded) {
                    $coords = array(
                        'lat' => $geocoded['lat'],
                        'lon' => $geocoded['lon']
                    );
                    self::save_travel_coordinates($travel_id, $coords['lat'], $coords['lon']);
                }
            }
        }

        if (!$coords) {
            return '<div class="map-placeholder" style="height: ' . esc_attr($height) . '; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                <p style="color: #999;">📍 Mappa non disponibile</p>
            </div>';
        }

        $map_id = 'travel-map-' . $travel_id;

        ob_start();
        ?>
        <div id="<?php echo esc_attr($map_id); ?>" style="height: <?php echo esc_attr($height); ?>; border-radius: 8px; overflow: hidden; background: #f0f0f0;"></div>
        <script>
        (function() {
            // Wait for Leaflet to be loaded
            function initMap() {
                if (typeof L === 'undefined') {
                    console.error('Leaflet library not loaded');
                    document.getElementById('<?php echo $map_id; ?>').innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #999;">📍 Impossibile caricare la mappa</div>';
                    return;
                }

                try {
                    const map = L.map('<?php echo $map_id; ?>').setView([<?php echo $coords['lat']; ?>, <?php echo $coords['lon']; ?>], 10);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(map);

                    const marker = L.marker([<?php echo $coords['lat']; ?>, <?php echo $coords['lon']; ?>]).addTo(map);
                    marker.bindPopup('<strong><?php echo esc_js(get_the_title($travel_id)); ?></strong><br><?php echo esc_js(get_post_meta($travel_id, 'cdv_destination', true)); ?>').openPopup();
                } catch (error) {
                    console.error('Error initializing map:', error);
                    document.getElementById('<?php echo $map_id; ?>').innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #999;">📍 Errore nel caricamento della mappa</div>';
                }
            }

            // Initialize when DOM and Leaflet are ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    // Give Leaflet a moment to initialize
                    setTimeout(initMap, 100);
                });
            } else {
                setTimeout(initMap, 100);
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
