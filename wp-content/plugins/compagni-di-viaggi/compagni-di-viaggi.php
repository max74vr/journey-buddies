<?php
/**
 * Plugin Name: Compagni di Viaggi
 * Plugin URI: https://www.compagnidiviaggi.com
 * Description: Piattaforma completa per trovare compagni di viaggio, organizzare avventure e costruire una community di viaggiatori. Include gestione viaggi, profili utente, chat, recensioni e REST API per app mobile.
 * Version: 1.0.0
 * Author: Max74vr
 * Author URI: https://github.com/max74vr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: compagni-di-viaggi
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CDV_VERSION', '1.0.0');
define('CDV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CDV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CDV_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Compagni_Di_Viaggi {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'), 0);
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core
        require_once CDV_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-taxonomy-images.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-user-meta.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-database.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-user-roles.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-registration.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-travel-moderation.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-user-profiles.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-email-verification.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-email-notifications.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-custom-avatars.php';

        // Features
        require_once CDV_PLUGIN_DIR . 'includes/class-chat.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-reviews.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-participants.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-badges.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-travel-stories.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-private-messages.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-group-chat.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-travel-gallery.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-travel-maps.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-wishlist.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-notifications.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-social-sharing.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-referral-system.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-organizer-stats.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-gdpr.php';
        require_once CDV_PLUGIN_DIR . 'includes/class-performance.php';

        // REST API
        require_once CDV_PLUGIN_DIR . 'includes/api/class-rest-api.php';
        require_once CDV_PLUGIN_DIR . 'includes/api/class-jwt-auth.php';

        // Admin
        if (is_admin()) {
            require_once CDV_PLUGIN_DIR . 'admin/class-admin.php';
            require_once CDV_PLUGIN_DIR . 'includes/class-admin-approvals.php';
            require_once CDV_PLUGIN_DIR . 'import-users.php';
        }

        // Ajax handlers
        require_once CDV_PLUGIN_DIR . 'includes/ajax/class-ajax-handlers.php';
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize classes
        CDV_Post_Types::init();
        CDV_Taxonomies::init();
        CDV_Taxonomy_Images::init();
        CDV_User_Meta::init();
        CDV_User_Roles::init();
        CDV_Registration::init();
        CDV_Travel_Moderation::init();
        CDV_User_Profiles::init();
        CDV_Email_Verification::init();
        CDV_Email_Notifications::init();
        CDV_Custom_Avatars::init();
        CDV_Chat::init();
        CDV_Reviews::init();
        CDV_Participants::init();
        CDV_Badges::init();
        CDV_Travel_Stories::init();
        CDV_Private_Messages::init();
        CDV_Travel_Gallery::init();
        CDV_Travel_Maps::init();
        CDV_Wishlist::init();
        CDV_Notifications::init();
        CDV_Social_Sharing::init();
        CDV_Referral_System::init();
        CDV_Organizer_Stats::init();
        CDV_GDPR::init();
        CDV_Performance::init();
        CDV_REST_API::init();
        CDV_Ajax_Handlers::init();

        if (is_admin()) {
            CDV_Admin::init();
            CDV_Admin_Approvals::init();
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('compagni-di-viaggi', false, dirname(CDV_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom database tables
        CDV_Database::create_tables();

        // Register post types and taxonomies
        CDV_Post_Types::init();
        CDV_Taxonomies::init();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        $this->set_default_options();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        CDV_Email_Notifications::deactivate();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'cdv_max_participants' => 10,
            'cdv_min_age' => 18,
            'cdv_chat_enabled' => true,
            'cdv_reviews_enabled' => true,
            'cdv_auto_approve_participants' => false,
            'cdv_jwt_secret' => wp_generate_password(64, true, true),
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

/**
 * Initialize the plugin
 */
function cdv_init() {
    return Compagni_Di_Viaggi::get_instance();
}

// Start the plugin
cdv_init();
