<?php
/**
 * Performance Optimization System
 *
 * Implements various performance improvements:
 * - Database query optimization
 * - Object caching
 * - Transient caching
 * - Query monitoring
 * - Asset optimization
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Performance {

    /**
     * Cache groups
     */
    const CACHE_GROUP = 'cdv';
    const CACHE_EXPIRATION = 3600; // 1 hour

    /**
     * Initialize
     */
    public static function init() {
        // Add database indexes
        add_action('admin_init', array(__CLASS__, 'ensure_database_indexes'));

        // Optimize queries
        add_filter('posts_clauses', array(__CLASS__, 'optimize_travel_queries'), 10, 2);

        // Image lazy loading
        add_filter('the_content', array(__CLASS__, 'add_lazy_loading'));
        add_filter('post_thumbnail_html', array(__CLASS__, 'add_lazy_loading_to_thumbnails'), 10, 5);

        // Disable unnecessary features for performance
        add_action('init', array(__CLASS__, 'disable_unnecessary_features'));

        // Clean up transients periodically
        add_action('cdv_cleanup_transients', array(__CLASS__, 'cleanup_expired_transients'));
        if (!wp_next_scheduled('cdv_cleanup_transients')) {
            wp_schedule_event(time(), 'daily', 'cdv_cleanup_transients');
        }

        // Performance monitoring (admin only)
        if (is_admin() && current_user_can('manage_options')) {
            add_action('admin_footer', array(__CLASS__, 'show_query_stats'));
        }

        // Add cache busting for assets
        add_filter('script_loader_tag', array(__CLASS__, 'add_cache_busting'), 10, 2);
        add_filter('style_loader_tag', array(__CLASS__, 'add_cache_busting_css'), 10, 2);

        // Optimize database cleanup
        add_action('cdv_optimize_database', array(__CLASS__, 'optimize_database_tables'));
        if (!wp_next_scheduled('cdv_optimize_database')) {
            wp_schedule_event(time(), 'weekly', 'cdv_optimize_database');
        }
    }

    /**
     * Ensure database has proper indexes for performance
     */
    public static function ensure_database_indexes() {
        global $wpdb;

        // Check if indexes exist, if not create them
        $indexes_to_create = array(
            // Travel meta indexes
            array(
                'table' => $wpdb->postmeta,
                'index' => 'cdv_destination_idx',
                'columns' => '(meta_key(50), meta_value(50))',
                'check_query' => "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'cdv_destination_idx'"
            ),
            array(
                'table' => $wpdb->postmeta,
                'index' => 'cdv_dates_idx',
                'columns' => '(meta_key(50), meta_value(20))',
                'check_query' => "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'cdv_dates_idx'"
            ),
            // Custom tables indexes
            array(
                'table' => $wpdb->prefix . 'cdv_participants',
                'index' => 'user_travel_idx',
                'columns' => '(user_id, travel_id)',
                'check_query' => "SHOW INDEX FROM {$wpdb->prefix}cdv_participants WHERE Key_name = 'user_travel_idx'"
            ),
            array(
                'table' => $wpdb->prefix . 'cdv_participants',
                'index' => 'status_idx',
                'columns' => '(status(10))',
                'check_query' => "SHOW INDEX FROM {$wpdb->prefix}cdv_participants WHERE Key_name = 'status_idx'"
            ),
            array(
                'table' => $wpdb->prefix . 'cdv_messages',
                'index' => 'recipient_read_idx',
                'columns' => '(recipient_id, is_read)',
                'check_query' => "SHOW INDEX FROM {$wpdb->prefix}cdv_messages WHERE Key_name = 'recipient_read_idx'"
            ),
            array(
                'table' => $wpdb->prefix . 'cdv_messages',
                'index' => 'sent_at_idx',
                'columns' => '(sent_at)',
                'check_query' => "SHOW INDEX FROM {$wpdb->prefix}cdv_messages WHERE Key_name = 'sent_at_idx'"
            ),
            array(
                'table' => $wpdb->prefix . 'cdv_reviews',
                'index' => 'user_travel_idx',
                'columns' => '(user_id, travel_id)',
                'check_query' => "SHOW INDEX FROM {$wpdb->prefix}cdv_reviews WHERE Key_name = 'user_travel_idx'"
            ),
            array(
                'table' => $wpdb->prefix . 'cdv_group_messages',
                'index' => 'travel_created_idx',
                'columns' => '(travel_id, created_at)',
                'check_query' => "SHOW INDEX FROM {$wpdb->prefix}cdv_group_messages WHERE Key_name = 'travel_created_idx'"
            )
        );

        foreach ($indexes_to_create as $index_data) {
            $exists = $wpdb->get_results($index_data['check_query']);

            if (empty($exists)) {
                $sql = "ALTER TABLE {$index_data['table']} ADD INDEX {$index_data['index']} {$index_data['columns']}";
                $wpdb->query($sql);
            }
        }
    }

    /**
     * Optimize travel queries
     */
    public static function optimize_travel_queries($clauses, $query) {
        global $wpdb;

        // Only optimize main query for viaggi post type
        if (!$query->is_main_query() || $query->get('post_type') !== 'viaggio') {
            return $clauses;
        }

        // Add SQL_CALC_FOUND_ROWS only when needed
        if (!empty($clauses['limits'])) {
            $clauses['fields'] = 'SQL_CALC_FOUND_ROWS ' . $clauses['fields'];
        }

        return $clauses;
    }

    /**
     * Get cached travel data
     */
    public static function get_cached_travel_data($travel_id, $force_refresh = false) {
        $cache_key = 'travel_data_' . $travel_id;

        if (!$force_refresh) {
            $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Build travel data
        $data = array(
            'id' => $travel_id,
            'title' => get_the_title($travel_id),
            'content' => get_post_field('post_content', $travel_id),
            'author_id' => get_post_field('post_author', $travel_id),
            'destination' => get_post_meta($travel_id, 'cdv_destination', true),
            'country' => get_post_meta($travel_id, 'cdv_country', true),
            'start_date' => get_post_meta($travel_id, 'cdv_start_date', true),
            'end_date' => get_post_meta($travel_id, 'cdv_end_date', true),
            'budget' => get_post_meta($travel_id, 'cdv_budget', true),
            'max_participants' => get_post_meta($travel_id, 'cdv_max_participants', true),
            'travel_status' => get_post_meta($travel_id, 'cdv_travel_status', true),
            'thumbnail_url' => get_the_post_thumbnail_url($travel_id, 'medium')
        );

        // Cache for 1 hour
        wp_cache_set($cache_key, $data, self::CACHE_GROUP, self::CACHE_EXPIRATION);

        return $data;
    }

    /**
     * Clear travel cache
     */
    public static function clear_travel_cache($travel_id) {
        $cache_key = 'travel_data_' . $travel_id;
        wp_cache_delete($cache_key, self::CACHE_GROUP);

        // Also clear related caches
        delete_transient('cdv_featured_travels');
        delete_transient('cdv_recent_travels');
    }

    /**
     * Get cached user profile data
     */
    public static function get_cached_user_profile($user_id, $force_refresh = false) {
        $cache_key = 'user_profile_' . $user_id;

        if (!$force_refresh) {
            $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
            if ($cached !== false) {
                return $cached;
            }
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $data = array(
            'id' => $user_id,
            'username' => $user->user_login,
            'display_name' => $user->display_name,
            'avatar_url' => get_avatar_url($user_id),
            'bio' => get_user_meta($user_id, 'cdv_bio', true),
            'location' => get_user_meta($user_id, 'cdv_location', true),
            'verified' => get_user_meta($user_id, 'cdv_verified', true),
            'reputation_score' => get_user_meta($user_id, 'cdv_reputation_score', true),
            'travel_count' => count_user_posts($user_id, 'viaggio')
        );

        wp_cache_set($cache_key, $data, self::CACHE_GROUP, self::CACHE_EXPIRATION);

        return $data;
    }

    /**
     * Clear user cache
     */
    public static function clear_user_cache($user_id) {
        $cache_key = 'user_profile_' . $user_id;
        wp_cache_delete($cache_key, self::CACHE_GROUP);
    }

    /**
     * Get popular destinations with caching
     */
    public static function get_popular_destinations($limit = 10) {
        $cache_key = 'popular_destinations_' . $limit;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT pm.meta_value as destination, COUNT(*) as count
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = 'cdv_destination'
             AND p.post_type = 'viaggio'
             AND p.post_status = 'publish'
             AND pm.meta_value != ''
             GROUP BY pm.meta_value
             ORDER BY count DESC
             LIMIT %d",
            $limit
        ));

        // Cache for 6 hours
        set_transient($cache_key, $results, 6 * HOUR_IN_SECONDS);

        return $results;
    }

    /**
     * Get statistics with caching
     */
    public static function get_site_statistics() {
        $cache_key = 'site_statistics';
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;

        $stats = array(
            'total_travels' => wp_count_posts('viaggio')->publish,
            'total_users' => count_users()['total_users'],
            'total_reviews' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cdv_reviews"),
            'active_travels' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID)
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'viaggio'
                 AND p.post_status = 'publish'
                 AND pm.meta_key = 'cdv_start_date'
                 AND pm.meta_value >= %s",
                date('Y-m-d')
            ))
        );

        // Cache for 1 hour
        set_transient($cache_key, $stats, HOUR_IN_SECONDS);

        return $stats;
    }

    /**
     * Add lazy loading to images
     */
    public static function add_lazy_loading($content) {
        if (is_admin() || is_feed() || wp_doing_ajax()) {
            return $content;
        }

        // Add loading="lazy" to img tags
        $content = preg_replace('/<img((?![^>]*loading=)[^>]*)>/i', '<img$1 loading="lazy">', $content);

        return $content;
    }

    /**
     * Add lazy loading to post thumbnails
     */
    public static function add_lazy_loading_to_thumbnails($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (is_admin() || is_feed()) {
            return $html;
        }

        // Add loading="lazy" if not present
        if (strpos($html, 'loading=') === false) {
            $html = str_replace('<img', '<img loading="lazy"', $html);
        }

        return $html;
    }

    /**
     * Disable unnecessary WordPress features
     */
    public static function disable_unnecessary_features() {
        // Disable emojis
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');

        // Disable embeds
        add_filter('embed_oembed_discover', '__return_false');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');

        // Remove query strings from static resources
        add_filter('script_loader_src', array(__CLASS__, 'remove_query_strings'), 15);
        add_filter('style_loader_src', array(__CLASS__, 'remove_query_strings'), 15);
    }

    /**
     * Remove query strings from static resources
     */
    public static function remove_query_strings($src) {
        if (strpos($src, '?ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    /**
     * Add cache busting for custom scripts
     */
    public static function add_cache_busting($tag, $handle) {
        if (strpos($handle, 'cdv-') === 0) {
            // Add version based on file modification time
            $tag = str_replace("ver=" . CDV_VERSION, "ver=" . CDV_VERSION . '.' . time(), $tag);
        }
        return $tag;
    }

    /**
     * Add cache busting for custom styles
     */
    public static function add_cache_busting_css($tag, $handle) {
        if (strpos($handle, 'cdv-') === 0) {
            $tag = str_replace("ver=" . CDV_VERSION, "ver=" . CDV_VERSION . '.' . time(), $tag);
        }
        return $tag;
    }

    /**
     * Cleanup expired transients
     */
    public static function cleanup_expired_transients() {
        global $wpdb;

        // Delete expired transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_timeout_%'
             AND option_value < UNIX_TIMESTAMP()"
        );

        // Delete orphaned transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_%'
             AND option_name NOT LIKE '_transient_timeout_%'
             AND option_name NOT IN (
                 SELECT REPLACE(option_name, '_transient_timeout_', '_transient_')
                 FROM {$wpdb->options}
                 WHERE option_name LIKE '_transient_timeout_%'
             )"
        );
    }

    /**
     * Optimize database tables
     */
    public static function optimize_database_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->users,
            $wpdb->usermeta,
            $wpdb->prefix . 'cdv_participants',
            $wpdb->prefix . 'cdv_messages',
            $wpdb->prefix . 'cdv_reviews',
            $wpdb->prefix . 'cdv_group_messages'
        );

        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }

    /**
     * Show query statistics in admin footer
     */
    public static function show_query_stats() {
        if (!defined('SAVEQUERIES') || !SAVEQUERIES) {
            return;
        }

        global $wpdb;

        $total_queries = count($wpdb->queries);
        $total_time = 0;

        foreach ($wpdb->queries as $query) {
            $total_time += $query[1];
        }

        echo '<div style="position: fixed; bottom: 0; right: 0; background: #23282d; color: #fff; padding: 10px 15px; z-index: 9999; font-size: 12px; border-radius: 4px 0 0 0;">';
        echo '<strong>Performance:</strong> ';
        echo $total_queries . ' queries in ' . number_format($total_time, 4) . 's';
        echo ' | Peak Memory: ' . size_format(memory_get_peak_usage(true));
        echo '</div>';
    }

    /**
     * Preload critical resources
     */
    public static function preload_critical_resources() {
        // Preload critical CSS
        echo '<link rel="preload" href="' . get_stylesheet_uri() . '" as="style">';

        // Preload critical fonts
        // Add your font preloading here

        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//unpkg.com">';
        echo '<link rel="dns-prefetch" href="//nominatim.openstreetmap.org">';
    }

    /**
     * Batch process to update all travel caches
     */
    public static function refresh_all_travel_caches() {
        $travels = get_posts(array(
            'post_type' => 'viaggio',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        foreach ($travels as $travel_id) {
            self::get_cached_travel_data($travel_id, true);
        }

        return count($travels);
    }

    /**
     * Get performance report
     */
    public static function get_performance_report() {
        global $wpdb;

        return array(
            'database_size' => $wpdb->get_var("SELECT SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = DATABASE()"),
            'total_posts' => wp_count_posts('viaggio')->publish,
            'total_users' => count_users()['total_users'],
            'transients_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'"),
            'autoload_size' => $wpdb->get_var("SELECT SUM(LENGTH(option_value)) / 1024 / 1024 FROM {$wpdb->options} WHERE autoload = 'yes'"),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'memory_limit' => WP_MEMORY_LIMIT,
            'max_execution_time' => ini_get('max_execution_time')
        );
    }
}
