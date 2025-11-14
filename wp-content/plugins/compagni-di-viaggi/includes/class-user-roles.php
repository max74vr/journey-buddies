<?php
/**
 * Custom User Roles and Capabilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_User_Roles {

    /**
     * Initialize
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_roles'));

        // Block backend access for viaggiatore
        add_action('admin_init', array(__CLASS__, 'block_admin_access'));

        // Hide admin bar for viaggiatore
        add_filter('show_admin_bar', array(__CLASS__, 'hide_admin_bar'));
        add_action('after_setup_theme', array(__CLASS__, 'hide_admin_bar_theme'));

        // Allow viaggiatori to appear in author dropdown for viaggio post type
        add_filter('wp_dropdown_users_args', array(__CLASS__, 'add_viaggiatori_to_author_dropdown'), 10, 2);

        // Also filter for ajax requests (Quick Edit uses AJAX)
        add_action('wp_ajax_inline-save', array(__CLASS__, 'ajax_inline_save_allow_viaggiatori'), 0);

        // Replace author metabox for viaggio post type
        add_action('add_meta_boxes', array(__CLASS__, 'replace_author_metabox'));
        add_action('save_post_viaggio', array(__CLASS__, 'save_custom_author'), 10, 2);
    }

    /**
     * Check if current user is a viaggiatore
     */
    public static function is_viaggiatore($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        return in_array('viaggiatore', (array) $user->roles);
    }

    /**
     * Register custom user roles
     */
    public static function register_roles() {
        // Add Viaggiatore role on plugin activation
        if (!get_role('viaggiatore')) {
            add_role(
                'viaggiatore',
                __('Viaggiatore', 'compagni-di-viaggi'),
                array(
                    'read' => true,
                    'level_0' => true, // Required for appearing in author dropdown
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'publish_posts' => false,
                    'upload_files' => true,

                    // Custom capabilities
                    'create_viaggi' => true,
                    'edit_own_viaggi' => true,
                    'delete_own_viaggi' => true,
                    'join_viaggi' => true,
                    'use_chat' => true,
                    'leave_reviews' => true,
                )
            );
        } else {
            // Update existing role to add level_0 if missing
            $role = get_role('viaggiatore');
            if ($role && !$role->has_cap('level_0')) {
                $role->add_cap('level_0');
            }
        }

        // Add custom capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            // Admin-specific capabilities
            $admin_role->add_cap('approve_users');
            $admin_role->add_cap('approve_viaggi');
            $admin_role->add_cap('moderate_chat');
            $admin_role->add_cap('manage_viaggiatori');

            // Viaggiatore capabilities (so admins can do everything viaggiatori can)
            $admin_role->add_cap('create_viaggi');
            $admin_role->add_cap('edit_own_viaggi');
            $admin_role->add_cap('delete_own_viaggi');
            $admin_role->add_cap('join_viaggi');
            $admin_role->add_cap('use_chat');
            $admin_role->add_cap('leave_reviews');
        }
    }

    /**
     * Get user approval status
     */
    public static function get_user_approval_status($user_id) {
        return get_user_meta($user_id, 'cdv_user_approved', true);
    }

    /**
     * Check if user is approved
     */
    public static function is_user_approved($user_id) {
        return get_user_meta($user_id, 'cdv_user_approved', true) === '1';
    }

    /**
     * Approve user
     */
    public static function approve_user($user_id) {
        update_user_meta($user_id, 'cdv_user_approved', '1');
        update_user_meta($user_id, 'cdv_user_approved_date', current_time('mysql'));

        // Send approval email
        self::send_approval_email($user_id);

        do_action('cdv_user_approved', $user_id);
    }

    /**
     * Reject user
     */
    public static function reject_user($user_id, $reason = '') {
        update_user_meta($user_id, 'cdv_user_approved', '0');
        update_user_meta($user_id, 'cdv_user_rejected_date', current_time('mysql'));

        if ($reason) {
            update_user_meta($user_id, 'cdv_user_rejection_reason', $reason);
        }

        do_action('cdv_user_rejected', $user_id, $reason);
    }

    /**
     * Send approval email
     */
    private static function send_approval_email($user_id) {
        $user = get_user_by('id', $user_id);

        $subject = 'Il tuo account è stato approvato!';
        $message = sprintf(
            "Ciao %s,\n\nIl tuo account su Compagni di Viaggi è stato approvato!\n\nOra puoi:\n- Cercare viaggi\n- Creare i tuoi viaggi\n- Richiedere di partecipare\n- Usare la chat di gruppo\n\nAccedi qui: %s\n\nBuon viaggio!\nIl team di Compagni di Viaggi",
            $user->display_name,
            wp_login_url()
        );

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Check if user can create travel posts
     */
    public static function can_create_travel($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Must be approved
        if (!self::is_user_approved($user_id)) {
            return false;
        }

        // Must have capability
        return user_can($user_id, 'create_viaggi');
    }

    /**
     * Get pending users count
     */
    public static function get_pending_users_count() {
        $args = array(
            'role' => 'viaggiatore',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'cdv_user_approved',
                    'value' => 'pending',
                    'compare' => '='
                ),
                array(
                    'key' => 'cdv_user_approved',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'fields' => 'ID',
        );

        $users = get_users($args);
        return count($users);
    }

    /**
     * Get pending users
     */
    public static function get_pending_users() {
        $args = array(
            'role' => 'viaggiatore',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'cdv_user_approved',
                    'value' => 'pending',
                    'compare' => '='
                ),
                array(
                    'key' => 'cdv_user_approved',
                    'compare' => 'NOT EXISTS'
                )
            ),
        );

        return get_users($args);
    }

    /**
     * Get profile completion percentage
     */
    public static function get_profile_completion($user_id) {
        $required_fields = array(
            'cdv_bio',
            'cdv_birth_date',
            'cdv_gender',
            'cdv_city',
            'cdv_country',
            'cdv_languages',
            'cdv_travel_styles',
        );

        $completed = 0;
        foreach ($required_fields as $field) {
            $value = get_user_meta($user_id, $field, true);
            if (!empty($value)) {
                $completed++;
            }
        }

        // Check avatar
        if (get_user_meta($user_id, 'cdv_profile_image', true)) {
            $completed++;
        }

        $total = count($required_fields) + 1; // +1 for avatar
        return round(($completed / $total) * 100);
    }

    /**
     * Block admin access for viaggiatore role
     */
    public static function block_admin_access() {
        if (self::is_viaggiatore() && !wp_doing_ajax()) {
            wp_redirect(home_url('/dashboard'));
            exit;
        }
    }

    /**
     * Hide admin bar for viaggiatore role (filter)
     */
    public static function hide_admin_bar($show_admin_bar) {
        if (self::is_viaggiatore()) {
            return false;
        }
        return $show_admin_bar;
    }

    /**
     * Hide admin bar for viaggiatore role (action)
     */
    public static function hide_admin_bar_theme() {
        if (self::is_viaggiatore()) {
            show_admin_bar(false);
        }
    }

    /**
     * Allow viaggiatori for ajax inline save
     */
    public static function ajax_inline_save_allow_viaggiatori() {
        if (isset($_POST['post_type']) && $_POST['post_type'] === 'viaggio') {
            add_filter('wp_dropdown_users_args', array(__CLASS__, 'force_viaggiatori_in_dropdown'), 999, 2);
        }
    }

    /**
     * Force viaggiatori in dropdown (high priority)
     */
    public static function force_viaggiatori_in_dropdown($query_args, $parsed_args) {
        unset($query_args['who']);
        $query_args['role__in'] = array('administrator', 'editor', 'viaggiatore');
        return $query_args;
    }

    /**
     * Add viaggiatori to author dropdown in backend for viaggio post type
     */
    public static function add_viaggiatori_to_author_dropdown($query_args, $parsed_args) {
        global $pagenow, $typenow;

        // Debug: log the context
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CDV: wp_dropdown_users_args called - pagenow: ' . $pagenow . ', typenow: ' . $typenow);
        }

        // Check if we're on the right screen
        $is_viaggio_screen = false;

        // Check for new post screen
        if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'viaggio') {
            $is_viaggio_screen = true;
        }

        // Check for edit post screen
        if ($pagenow === 'post.php' && isset($_GET['post']) && !empty($_GET['post'])) {
            $post = get_post(intval($_GET['post']));
            if ($post && $post->post_type === 'viaggio') {
                $is_viaggio_screen = true;
            }
        }

        // Check for list screen (quick edit)
        if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'viaggio') {
            $is_viaggio_screen = true;
        }

        // Also check global $typenow
        if ($typenow === 'viaggio') {
            $is_viaggio_screen = true;
        }

        // Check screen object as fallback
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->post_type === 'viaggio') {
            $is_viaggio_screen = true;
        }

        // Only modify query for viaggio post type
        if (is_admin() && $is_viaggio_screen) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CDV: Modifying wp_dropdown_users query for viaggio');
            }

            // Remove the default capability requirement
            unset($query_args['who']);

            // Include viaggiatori role
            $query_args['role__in'] = array('administrator', 'editor', 'viaggiatore');
        }

        return $query_args;
    }

    /**
     * Replace default author metabox with custom one for viaggio
     */
    public static function replace_author_metabox() {
        remove_meta_box('authordiv', 'viaggio', 'normal');

        add_meta_box(
            'cdv_authordiv',
            __('Autore', 'compagni-di-viaggi'),
            array(__CLASS__, 'render_author_metabox'),
            'viaggio',
            'side',
            'default'
        );
    }

    /**
     * Render custom author metabox
     */
    public static function render_author_metabox($post) {
        global $user_ID;

        // Get all users with appropriate roles
        $users = get_users(array(
            'role__in' => array('administrator', 'editor', 'viaggiatore'),
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));

        ?>
        <label class="screen-reader-text" for="post_author_override"><?php _e('Autore'); ?></label>
        <?php
        wp_dropdown_users(array(
            'who' => 'all',
            'name' => 'post_author_override',
            'selected' => empty($post->ID) ? $user_ID : $post->post_author,
            'include_selected' => true,
            'show' => 'display_name_with_login',
            'role__in' => array('administrator', 'editor', 'viaggiatore'),
        ));
    }

    /**
     * Save custom author field
     */
    public static function save_custom_author($post_id, $post) {
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if post_author_override is set
        if (isset($_POST['post_author_override'])) {
            $author_id = absint($_POST['post_author_override']);

            // Verify the user exists and has appropriate role
            $user = get_userdata($author_id);
            if ($user && array_intersect($user->roles, array('administrator', 'editor', 'viaggiatore'))) {
                // Update post author
                remove_action('save_post_viaggio', array(__CLASS__, 'save_custom_author'), 10);
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_author' => $author_id,
                ));
                add_action('save_post_viaggio', array(__CLASS__, 'save_custom_author'), 10, 2);
            }
        }
    }
}
