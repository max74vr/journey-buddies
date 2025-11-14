<?php
/**
 * Compagni di Viaggi Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme version
define('CDV_THEME_VERSION', '1.0.0');

/**
 * Theme setup
 */
function cdv_theme_setup() {
    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages
    add_theme_support('post-thumbnails');
    set_post_thumbnail_size(800, 450, true);
    add_image_size('travel-card', 400, 300, true);
    add_image_size('travel-hero', 1200, 600, true);

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'compagni-viaggi'),
        'mobile' => __('Mobile Menu', 'compagni-viaggi'),
        'footer' => __('Footer Menu', 'compagni-viaggi'),
    ));

    // Switch default core markup to output valid HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Add theme support for selective refresh for widgets
    add_theme_support('customize-selective-refresh-widgets');

    // Add support for editor styles
    add_theme_support('editor-styles');

    // Add support for responsive embeds
    add_theme_support('responsive-embeds');

    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
        'header-text' => array('site-name', 'site-description'),
    ));
}
add_action('after_setup_theme', 'cdv_theme_setup');

/**
 * Enqueue scripts and styles
 */
function cdv_enqueue_scripts() {
    // Theme stylesheet
    wp_enqueue_style('cdv-style', get_stylesheet_uri(), array(), CDV_THEME_VERSION);

    // Main JavaScript
    wp_enqueue_script('cdv-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), CDV_THEME_VERSION, true);

    // Localize script for AJAX
    wp_localize_script('cdv-main', 'cdvAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cdv_ajax_nonce'),
        'restUrl' => rest_url('cdv/v1/'),
    ));

    // Comment reply script
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'cdv_enqueue_scripts');

/**
 * Register widget areas
 */
function cdv_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar', 'compagni-viaggi'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here to appear in the sidebar.', 'compagni-viaggi'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Footer 1', 'compagni-viaggi'),
        'id'            => 'footer-1',
        'description'   => __('First footer column.', 'compagni-viaggi'),
        'before_widget' => '<div class="footer-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Footer 2', 'compagni-viaggi'),
        'id'            => 'footer-2',
        'description'   => __('Second footer column.', 'compagni-viaggi'),
        'before_widget' => '<div class="footer-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Footer 3', 'compagni-viaggi'),
        'id'            => 'footer-3',
        'description'   => __('Third footer column.', 'compagni-viaggi'),
        'before_widget' => '<div class="footer-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'cdv_widgets_init');

/**
 * Custom template tags
 */

/**
 * Display travel meta information
 */
function cdv_travel_meta($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $start_date = get_post_meta($post_id, 'cdv_start_date', true);
    $end_date = get_post_meta($post_id, 'cdv_end_date', true);
    $date_type = get_post_meta($post_id, 'cdv_date_type', true);
    $destination = get_post_meta($post_id, 'cdv_destination', true);
    $country = get_post_meta($post_id, 'cdv_country', true);
    $budget = get_post_meta($post_id, 'cdv_budget', true);
    $max_participants = get_post_meta($post_id, 'cdv_max_participants', true);
    $current_participants = CDV_Participants::get_participant_count($post_id);

    ?>
    <div class="travel-meta">
        <?php if ($destination) : ?>
            <span class="meta-item">
                <span class="icon">📍</span>
                <?php echo esc_html($destination); ?><?php echo $country ? ', ' . esc_html($country) : ''; ?>
            </span>
        <?php endif; ?>

        <?php if ($start_date) : ?>
            <span class="meta-item">
                <span class="icon">📅</span>
                <?php
                if ($date_type === 'month') {
                    // Show only the month
                    echo date_i18n('F Y', strtotime($start_date));
                } else {
                    // Show precise dates
                    echo date_i18n('d/m/Y', strtotime($start_date));
                    if ($end_date) echo ' - ' . date_i18n('d/m/Y', strtotime($end_date));
                }
                ?>
            </span>
        <?php endif; ?>

        <?php if ($budget) : ?>
            <span class="meta-item">
                <span class="icon">💰</span>
                €<?php echo number_format($budget, 0, ',', '.'); ?>
            </span>
        <?php endif; ?>

        <?php if ($max_participants) : ?>
            <span class="meta-item">
                <span class="icon">👥</span>
                <?php echo $current_participants; ?>/<?php echo $max_participants; ?> participants
            </span>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Display organizer info
 */
function cdv_organizer_info($author_id = null) {
    if (!$author_id) {
        $author_id = get_the_author_meta('ID');
    }

    $author = get_user_by('id', $author_id);
    $reputation = get_user_meta($author_id, 'cdv_reputation_score', true);
    $verified = get_user_meta($author_id, 'cdv_verified', true);

    $profile_url = CDV_User_Profiles::get_profile_url($author_id);
    ?>
    <a href="<?php echo esc_url($profile_url); ?>" class="organizer-info">
        <?php echo get_avatar($author_id, 40, '', '', array('class' => 'organizer-avatar')); ?>
        <div class="organizer-details">
            <div class="organizer-name">
                <?php echo esc_html($author->user_login); ?>
                <?php if ($verified === '1') : ?>
                    <span class="verified-badge" title="Verified">✓</span>
                <?php endif; ?>
            </div>
            <?php if ($reputation) : ?>
                <div class="organizer-reputation">
                    <?php cdv_display_stars($reputation); ?>
                </div>
            <?php endif; ?>
        </div>
    </a>
    <?php
}

/**
 * Display star rating
 */
function cdv_display_stars($rating, $max = 5) {
    $rating = round($rating * 2) / 2; // Round to nearest 0.5
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max - $full_stars - ($half_star ? 1 : 0);

    echo '<div class="star-rating">';

    for ($i = 0; $i < $full_stars; $i++) {
        echo '<span class="star full">★</span>';
    }

    if ($half_star) {
        echo '<span class="star half">★</span>';
    }

    for ($i = 0; $i < $empty_stars; $i++) {
        echo '<span class="star empty">☆</span>';
    }

    echo '<span class="rating-value">(' . number_format($rating, 1) . ')</span>';
    echo '</div>';
}

/**
 * Display travel type badges
 */
function cdv_travel_type_badges($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $types = wp_get_post_terms($post_id, 'tipo_viaggio');

    if (empty($types) || is_wp_error($types)) {
        return;
    }

    echo '<div class="travel-types">';
    foreach ($types as $type) {
        echo '<span class="badge badge-primary">' . esc_html($type->name) . '</span>';
    }
    echo '</div>';
}

/**
 * Get travel status label
 */
function cdv_get_travel_status_label($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $status = get_post_meta($post_id, 'cdv_travel_status', true);

    $labels = array(
        'open' => array('label' => 'Open', 'class' => 'success'),
        'full' => array('label' => 'Full', 'class' => 'warning'),
        'in_progress' => array('label' => 'In Progress', 'class' => 'info'),
        'completed' => array('label' => 'Completed', 'class' => 'secondary'),
        'cancelled' => array('label' => 'Cancelled', 'class' => 'error'),
    );

    if (empty($status)) {
        $status = 'open';
    }

    if (isset($labels[$status])) {
        return '<span class="badge badge-' . $labels[$status]['class'] . '">' . $labels[$status]['label'] . '</span>';
    }

    return '';
}

/**
 * Pagination
 */
function cdv_pagination() {
    the_posts_pagination(array(
        'mid_size' => 2,
        'prev_text' => '<span class="pagination-arrow">←</span> <span class="pagination-text">Previous</span>',
        'next_text' => '<span class="pagination-text">Next</span> <span class="pagination-arrow">→</span>',
        'before_page_number' => '<span class="screen-reader-text">Page </span>',
        'class' => 'cdv-pagination',
    ));
}

/**
 * Custom excerpt length - max 3 lines
 */
function cdv_excerpt_length($length) {
    return 15;
}
add_filter('excerpt_length', 'cdv_excerpt_length');

/**
 * Custom excerpt more
 */
function cdv_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'cdv_excerpt_more');

/**
 * Add body classes
 */
function cdv_body_classes($classes) {
    if (is_user_logged_in()) {
        $classes[] = 'logged-in';
    } else {
        $classes[] = 'logged-out';
    }

    if (is_post_type_archive('viaggio') || is_singular('viaggio')) {
        $classes[] = 'viaggio-page';
    }

    return $classes;
}
add_filter('body_class', 'cdv_body_classes');

/**
 * Calculate reading time for post
 */
function cdv_reading_time($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Average reading speed: 200 words per minute

    return max($reading_time, 1); // Minimum 1 minute
}

/**
 * Include Theme Customizer
 */
require_once get_template_directory() . '/inc/customizer.php';

/**
 * Add Custom CSS meta box for pages
 */
function cdv_add_custom_css_meta_box() {
    add_meta_box(
        'cdv_custom_css',
        'Custom CSS',
        'cdv_custom_css_meta_box_callback',
        array('page', 'post', 'viaggio'),
        'normal',
        'low'
    );
}
add_action('add_meta_boxes', 'cdv_add_custom_css_meta_box');

/**
 * Custom CSS meta box callback
 */
function cdv_custom_css_meta_box_callback($post) {
    wp_nonce_field('cdv_custom_css_nonce', 'cdv_custom_css_nonce_field');
    $custom_css = get_post_meta($post->ID, '_cdv_custom_css', true);
    ?>
    <p>
        <label for="cdv_custom_css"><strong>CSS for this page:</strong></label>
        <small style="display: block; margin-top: 5px; color: #666;">
            No need to add &lt;style&gt; tags. CSS will be applied only to this page.
        </small>
    </p>
    <textarea id="cdv_custom_css" name="cdv_custom_css" rows="10" style="width: 100%; font-family: monospace; font-size: 13px;"><?php echo esc_textarea($custom_css); ?></textarea>
    <?php
}

/**
 * Save Custom CSS meta box
 */
function cdv_save_custom_css_meta_box($post_id) {
    // Check nonce
    if (!isset($_POST['cdv_custom_css_nonce_field']) ||
        !wp_verify_nonce($_POST['cdv_custom_css_nonce_field'], 'cdv_custom_css_nonce')) {
        return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save CSS
    if (isset($_POST['cdv_custom_css'])) {
        update_post_meta($post_id, '_cdv_custom_css', wp_kses_post($_POST['cdv_custom_css']));
    }
}
add_action('save_post', 'cdv_save_custom_css_meta_box');

/**
 * Output custom CSS for current page
 */
function cdv_output_page_custom_css() {
    if (is_singular()) {
        $custom_css = get_post_meta(get_the_ID(), '_cdv_custom_css', true);
        if (!empty($custom_css)) {
            echo '<style type="text/css" id="page-custom-css">' . wp_kses_post($custom_css) . '</style>';
        }
    }
}
add_action('wp_head', 'cdv_output_page_custom_css', 100);

/**
 * Advanced Search and Filters for Viaggi Archive
 */
function cdv_filter_viaggi_archive($query) {
    // Only modify main query on viaggio archive pages OR search with post_type=viaggio
    $is_viaggio_query = is_post_type_archive('viaggio') ||
                        (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'viaggio');

    if (!is_admin() && $query->is_main_query() && $is_viaggio_query) {

        // Force post_type to viaggio for search queries
        if (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'viaggio') {
            $query->set('post_type', 'viaggio');
        }

        // Meta query array
        $meta_query = array('relation' => 'AND');

        // Filter by start date (from)
        if (!empty($_GET['date_from'])) {
            $date_from = sanitize_text_field($_GET['date_from']) . '-01'; // YYYY-MM-01
            $meta_query[] = array(
                'key' => 'cdv_start_date',
                'value' => $date_from,
                'compare' => '>=',
                'type' => 'DATE'
            );
        }

        // Filter by budget range
        if (!empty($_GET['budget_min'])) {
            $meta_query[] = array(
                'key' => 'cdv_budget',
                'value' => intval($_GET['budget_min']),
                'compare' => '>=',
                'type' => 'NUMERIC'
            );
        }

        if (!empty($_GET['budget_max'])) {
            $meta_query[] = array(
                'key' => 'cdv_budget',
                'value' => intval($_GET['budget_max']),
                'compare' => '<=',
                'type' => 'NUMERIC'
            );
        }

        // Filter by number of participants
        if (!empty($_GET['max_participants'])) {
            $participants_filter = sanitize_text_field($_GET['max_participants']);

            switch ($participants_filter) {
                case '2-5':
                    $meta_query[] = array(
                        'key' => 'cdv_max_participants',
                        'value' => array(2, 5),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    );
                    break;
                case '6-10':
                    $meta_query[] = array(
                        'key' => 'cdv_max_participants',
                        'value' => array(6, 10),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    );
                    break;
                case '11-20':
                    $meta_query[] = array(
                        'key' => 'cdv_max_participants',
                        'value' => array(11, 20),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    );
                    break;
                case '20+':
                    $meta_query[] = array(
                        'key' => 'cdv_max_participants',
                        'value' => 20,
                        'compare' => '>',
                        'type' => 'NUMERIC'
                    );
                    break;
            }
        }

        // Filter by travel status
        if (!empty($_GET['travel_status'])) {
            $meta_query[] = array(
                'key' => 'cdv_travel_status',
                'value' => sanitize_text_field($_GET['travel_status']),
                'compare' => '='
            );
        }

        // Advanced Filters

        // Filter by transport methods
        if (!empty($_GET['transport']) && is_array($_GET['transport'])) {
            $transport_query = array('relation' => 'OR');
            foreach ($_GET['transport'] as $transport) {
                $transport_query[] = array(
                    'key' => 'cdv_transport',
                    'value' => sanitize_text_field($transport),
                    'compare' => 'LIKE'
                );
            }
            $meta_query[] = $transport_query;
        }

        // Filter by accommodation
        if (!empty($_GET['accommodation'])) {
            $meta_query[] = array(
                'key' => 'cdv_accommodation',
                'value' => sanitize_text_field($_GET['accommodation']),
                'compare' => '='
            );
        }

        // Filter by difficulty
        if (!empty($_GET['difficulty'])) {
            $meta_query[] = array(
                'key' => 'cdv_difficulty',
                'value' => sanitize_text_field($_GET['difficulty']),
                'compare' => '='
            );
        }

        // Filter by meals
        if (!empty($_GET['meals'])) {
            $meta_query[] = array(
                'key' => 'cdv_meals',
                'value' => sanitize_text_field($_GET['meals']),
                'compare' => '='
            );
        }

        // Filter by guide type
        if (!empty($_GET['guide'])) {
            $meta_query[] = array(
                'key' => 'cdv_guide',
                'value' => sanitize_text_field($_GET['guide']),
                'compare' => '='
            );
        }

        // Filter by organizer rating
        if (!empty($_GET['min_rating'])) {
            $min_rating = floatval($_GET['min_rating']);
            // This will be a custom query - we need to join with user meta
            // For now, we'll add it as a meta_query and handle it via filter
            add_filter('posts_where', function($where) use ($min_rating) {
                global $wpdb;
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_author IN (
                    SELECT user_id FROM {$wpdb->usermeta}
                    WHERE meta_key = 'cdv_reputation_score'
                    AND CAST(meta_value AS DECIMAL(3,2)) >= %f
                )", $min_rating);
                return $where;
            });
        }

        // Filter by trip duration
        if (!empty($_GET['duration'])) {
            $duration = sanitize_text_field($_GET['duration']);

            // Calculate duration in days between start and end date
            switch ($duration) {
                case '1-3':
                    $meta_query[] = array(
                        'key' => 'cdv_duration_days',
                        'value' => array(1, 3),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    );
                    break;
                case '4-7':
                    $meta_query[] = array(
                        'key' => 'cdv_duration_days',
                        'value' => array(4, 7),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    );
                    break;
                case '8-14':
                    $meta_query[] = array(
                        'key' => 'cdv_duration_days',
                        'value' => array(8, 14),
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    );
                    break;
                case '15+':
                    $meta_query[] = array(
                        'key' => 'cdv_duration_days',
                        'value' => 15,
                        'compare' => '>=',
                        'type' => 'NUMERIC'
                    );
                    break;
            }
        }

        // Filter: Only travels with available spots
        if (!empty($_GET['solo_posti_disponibili'])) {
            // This requires custom SQL to compare current participants vs max participants
            add_filter('posts_where', function($where) {
                global $wpdb;
                $participants_table = $wpdb->prefix . 'cdv_participants';

                $where .= " AND {$wpdb->posts}.ID IN (
                    SELECT p.ID
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'cdv_max_participants'
                    LEFT JOIN (
                        SELECT travel_id, COUNT(*) as participant_count
                        FROM {$participants_table}
                        WHERE status = 'accepted'
                        GROUP BY travel_id
                    ) pt ON p.ID = pt.travel_id
                    WHERE CAST(pm.meta_value AS UNSIGNED) > COALESCE(pt.participant_count, 0)
                )";

                return $where;
            });
        }

        // Apply meta query if we have filters
        if (count($meta_query) > 1) {
            $query->set('meta_query', $meta_query);
        }

        // Taxonomy filters (already handled by WordPress, but we make them explicit)
        if (!empty($_GET['tipo_viaggio'])) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'tipo_viaggio',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_GET['tipo_viaggio'])
                )
            ));
        }

        // Sorting
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';

        switch ($orderby) {
            case 'start_date':
                $query->set('meta_key', 'cdv_start_date');
                $query->set('orderby', 'meta_value');
                $query->set('meta_type', 'DATE');
                $query->set('order', 'ASC');
                break;

            case 'budget_asc':
                $query->set('meta_key', 'cdv_budget');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'ASC');
                break;

            case 'budget_desc':
                $query->set('meta_key', 'cdv_budget');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                break;

            case 'participants':
                $query->set('meta_key', 'cdv_max_participants');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                break;

            case 'rating':
                // Sort by organizer reputation score
                add_filter('posts_orderby', function($orderby) {
                    global $wpdb;
                    return "(
                        SELECT CAST(meta_value AS DECIMAL(3,2))
                        FROM {$wpdb->usermeta}
                        WHERE user_id = {$wpdb->posts}.post_author
                        AND meta_key = 'cdv_reputation_score'
                        LIMIT 1
                    ) DESC";
                });
                break;

            default: // 'date'
                $query->set('orderby', 'date');
                $query->set('order', 'DESC');
                break;
        }
    }
}
add_action('pre_get_posts', 'cdv_filter_viaggi_archive');

/**
 * Force use of archive-viaggio.php template for viaggio searches
 * This ensures searches from hero section and travels page show results
 * in the travels archive page instead of the generic search page
 */
function cdv_force_viaggio_archive_template($template) {
    // Check if this is a search with post_type=viaggio
    if (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'viaggio') {
        // Get the archive-viaggio.php template
        $archive_template = locate_template('archive-viaggio.php');
        if ($archive_template) {
            return $archive_template;
        }
    }

    return $template;
}
add_filter('template_include', 'cdv_force_viaggio_archive_template');
