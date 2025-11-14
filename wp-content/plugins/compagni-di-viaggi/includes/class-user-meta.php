<?php
/**
 * User Meta fields management
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_User_Meta {

    /**
     * Initialize
     */
    public static function init() {
        add_action('show_user_profile', array(__CLASS__, 'add_profile_fields'));
        add_action('edit_user_profile', array(__CLASS__, 'add_profile_fields'));
        add_action('personal_options_update', array(__CLASS__, 'save_profile_fields'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_profile_fields'));
        add_action('rest_api_init', array(__CLASS__, 'register_rest_fields'));
    }

    /**
     * Add custom fields to user profile
     */
    public static function add_profile_fields($user) {
        ?>
        <h2><?php _e('Informazioni Viaggiatore', 'compagni-di-viaggi'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="cdv_bio"><?php _e('Bio', 'compagni-di-viaggi'); ?></label></th>
                <td>
                    <textarea name="cdv_bio" id="cdv_bio" rows="5" cols="30" class="regular-text"><?php echo esc_textarea(get_user_meta($user->ID, 'cdv_bio', true)); ?></textarea>
                    <p class="description"><?php _e('Parlaci di te come viaggiatore', 'compagni-di-viaggi'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cdv_birth_date"><?php _e('Data di Nascita', 'compagni-di-viaggi'); ?></label></th>
                <td>
                    <input type="date" name="cdv_birth_date" id="cdv_birth_date" value="<?php echo esc_attr(get_user_meta($user->ID, 'cdv_birth_date', true)); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="cdv_gender"><?php _e('Genere', 'compagni-di-viaggi'); ?></label></th>
                <td>
                    <select name="cdv_gender" id="cdv_gender">
                        <?php $gender = get_user_meta($user->ID, 'cdv_gender', true); ?>
                        <option value=""><?php _e('Non specificato', 'compagni-di-viaggi'); ?></option>
                        <option value="male" <?php selected($gender, 'male'); ?>><?php _e('Uomo', 'compagni-di-viaggi'); ?></option>
                        <option value="female" <?php selected($gender, 'female'); ?>><?php _e('Donna', 'compagni-di-viaggi'); ?></option>
                        <option value="other" <?php selected($gender, 'other'); ?>><?php _e('Altro', 'compagni-di-viaggi'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="cdv_city"><?php _e('Città', 'compagni-di-viaggi'); ?></label></th>
                <td>
                    <input type="text" name="cdv_city" id="cdv_city" value="<?php echo esc_attr(get_user_meta($user->ID, 'cdv_city', true)); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="cdv_country"><?php _e('Paese', 'compagni-di-viaggi'); ?></label></th>
                <td>
                    <input type="text" name="cdv_country" id="cdv_country" value="<?php echo esc_attr(get_user_meta($user->ID, 'cdv_country', true)); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="cdv_languages"><?php _e('Lingue Parlate', 'compagni-di-viaggi'); ?></label></th>
                <td>
                    <input type="text" name="cdv_languages" id="cdv_languages" value="<?php echo esc_attr(get_user_meta($user->ID, 'cdv_languages', true)); ?>" class="regular-text" />
                    <p class="description"><?php _e('Separate da virgola (es: Italiano, Inglese, Spagnolo)', 'compagni-di-viaggi'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cdv_travel_styles"><?php _e('Stili di Viaggio Preferiti', 'compagni-di-viaggi'); ?></label></th>
                <td>
                    <input type="text" name="cdv_travel_styles" id="cdv_travel_styles" value="<?php echo esc_attr(get_user_meta($user->ID, 'cdv_travel_styles', true)); ?>" class="regular-text" />
                    <p class="description"><?php _e('Separate da virgola (es: Avventura, Cultura, Relax)', 'compagni-di-viaggi'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cdv_verified"><?php _e('Account Verificato', 'compagni-di-viaggi'); ?></label></th>
                <td>
                    <input type="checkbox" name="cdv_verified" id="cdv_verified" value="1" <?php checked(get_user_meta($user->ID, 'cdv_verified', true), '1'); ?> />
                    <label for="cdv_verified"><?php _e('L\'utente ha verificato la sua identità', 'compagni-di-viaggi'); ?></label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save custom profile fields
     */
    public static function save_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        $fields = array(
            'cdv_bio',
            'cdv_birth_date',
            'cdv_gender',
            'cdv_city',
            'cdv_country',
            'cdv_languages',
            'cdv_travel_styles',
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Checkbox field
        if (isset($_POST['cdv_verified'])) {
            update_user_meta($user_id, 'cdv_verified', '1');
        } else {
            update_user_meta($user_id, 'cdv_verified', '0');
        }

        // Calculate and update reputation
        self::update_user_reputation($user_id);
    }

    /**
     * Register custom fields in REST API
     */
    public static function register_rest_fields() {
        $fields = array(
            'cdv_bio',
            'cdv_birth_date',
            'cdv_gender',
            'cdv_city',
            'cdv_country',
            'cdv_languages',
            'cdv_travel_styles',
            'cdv_verified',
            'cdv_reputation_score',
        );

        foreach ($fields as $field) {
            register_rest_field('user', $field, array(
                'get_callback' => function($user) use ($field) {
                    return get_user_meta($user['id'], $field, true);
                },
                'update_callback' => function($value, $user) use ($field) {
                    return update_user_meta($user->ID, $field, $value);
                },
                'schema' => array(
                    'type' => 'string',
                    'context' => array('view', 'edit'),
                ),
            ));
        }
    }

    /**
     * Update user reputation score
     */
    public static function update_user_reputation($user_id) {
        global $wpdb;

        $table_reviews = $wpdb->prefix . 'cdv_reviews';

        $avg_scores = $wpdb->get_row($wpdb->prepare(
            "SELECT
                AVG(punctuality) as avg_punctuality,
                AVG(group_spirit) as avg_group_spirit,
                AVG(respect) as avg_respect,
                AVG(adaptability) as avg_adaptability,
                COUNT(*) as total_reviews
            FROM $table_reviews
            WHERE reviewed_id = %d",
            $user_id
        ));

        if ($avg_scores && $avg_scores->total_reviews > 0) {
            $reputation = (
                $avg_scores->avg_punctuality +
                $avg_scores->avg_group_spirit +
                $avg_scores->avg_respect +
                $avg_scores->avg_adaptability
            ) / 4;

            update_user_meta($user_id, 'cdv_reputation_score', round($reputation, 2));
            update_user_meta($user_id, 'cdv_total_reviews', $avg_scores->total_reviews);
        } else {
            update_user_meta($user_id, 'cdv_reputation_score', 0);
            update_user_meta($user_id, 'cdv_total_reviews', 0);
        }
    }

    /**
     * Get user age
     */
    public static function get_user_age($user_id) {
        $birth_date = get_user_meta($user_id, 'cdv_birth_date', true);

        if (empty($birth_date)) {
            return null;
        }

        $birth = new DateTime($birth_date);
        $today = new DateTime();
        $age = $today->diff($birth);

        return $age->y;
    }
}
