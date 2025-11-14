<?php
/**
 * Custom Avatars System
 *
 * Gestisce avatar personalizzati con:
 * - Immagini profilo approvate dall'admin
 * - Avatar con iniziali e colori random come fallback
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Custom_Avatars {

    /**
     * Initialize
     */
    public static function init() {
        // Override WordPress avatar
        add_filter('get_avatar_url', array(__CLASS__, 'get_custom_avatar_url'), 10, 3);
        add_filter('get_avatar', array(__CLASS__, 'get_custom_avatar'), 10, 6);

        // Add admin column to users list
        add_filter('manage_users_columns', array(__CLASS__, 'add_avatar_approval_column'));
        add_filter('manage_users_custom_column', array(__CLASS__, 'display_avatar_approval_column'), 10, 3);

        // Add bulk actions for approval
        add_filter('bulk_actions-users', array(__CLASS__, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-users', array(__CLASS__, 'handle_bulk_actions'), 10, 3);

        // Add user profile fields
        add_action('show_user_profile', array(__CLASS__, 'add_profile_fields'));
        add_action('edit_user_profile', array(__CLASS__, 'add_profile_fields'));
        add_action('personal_options_update', array(__CLASS__, 'save_profile_fields'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_profile_fields'));

        // Inject CSS for initials avatars
        add_action('wp_head', array(__CLASS__, 'inject_avatar_css'));
        add_action('admin_head', array(__CLASS__, 'inject_avatar_css'));
    }

    /**
     * Get custom avatar URL
     */
    public static function get_custom_avatar_url($url, $id_or_email, $args) {
        $user = self::get_user_from_id_or_email($id_or_email);

        if (!$user) {
            return $url;
        }

        // Check for approved custom profile image
        $image_id = get_user_meta($user->ID, 'cdv_profile_image', true);
        $image_approved = get_user_meta($user->ID, 'cdv_profile_image_approved', true);

        if ($image_id && $image_approved === '1') {
            // Use medium size (300x300) for better quality and cropped display
            $custom_url = wp_get_attachment_image_url($image_id, 'medium');
            if ($custom_url) {
                return $custom_url;
            }
        }

        // Return data URL for initials avatar (will be handled in get_custom_avatar)
        return $url;
    }

    /**
     * Get custom avatar HTML
     */
    public static function get_custom_avatar($avatar, $id_or_email, $size, $default, $alt, $args) {
        $user = self::get_user_from_id_or_email($id_or_email);

        if (!$user) {
            return $avatar;
        }

        // Check for approved custom profile image
        $image_id = get_user_meta($user->ID, 'cdv_profile_image', true);
        $image_approved = get_user_meta($user->ID, 'cdv_profile_image_approved', true);

        if ($image_id && $image_approved === '1') {
            // Use medium size (300x300) for better quality and cropped display
            $custom_url = wp_get_attachment_image_url($image_id, 'medium');
            if ($custom_url) {
                return sprintf(
                    '<img alt="%s" src="%s" class="avatar avatar-%d photo cdv-custom-avatar" height="%d" width="%d" loading="lazy" decoding="async" />',
                    esc_attr($alt),
                    esc_url($custom_url),
                    esc_attr($size),
                    esc_attr($size),
                    esc_attr($size)
                );
            }
        }

        // Fallback to initials avatar
        return self::get_initials_avatar($user, $size, $alt);
    }

    /**
     * Generate initials avatar HTML
     */
    public static function get_initials_avatar($user, $size, $alt = '') {
        $initials = self::get_user_initials($user);
        $color = self::get_user_color($user->ID);

        if (empty($alt)) {
            $alt = $user->display_name;
        }

        return sprintf(
            '<span class="avatar avatar-%d cdv-initials-avatar" style="background-color: %s; width: %dpx; height: %dpx; line-height: %dpx; font-size: %dpx;" data-user-id="%d">%s</span>',
            esc_attr($size),
            esc_attr($color),
            esc_attr($size),
            esc_attr($size),
            esc_attr($size),
            esc_attr($size / 2.5), // Font size proportional to avatar size
            esc_attr($user->ID),
            esc_html($initials)
        );
    }

    /**
     * Get user initials from username or display name
     */
    private static function get_user_initials($user) {
        $name = !empty($user->display_name) ? $user->display_name : $user->user_login;

        // Remove numbers and special chars from beginning
        $name = preg_replace('/^[^a-zA-Z]+/', '', $name);

        // Get first character
        $initial = mb_substr($name, 0, 1);

        return mb_strtoupper($initial);
    }

    /**
     * Get consistent random color for user
     */
    private static function get_user_color($user_id) {
        // Predefined pleasant colors
        $colors = array(
            '#FF6B6B', // Red
            '#4ECDC4', // Teal
            '#45B7D1', // Blue
            '#FFA07A', // Salmon
            '#98D8C8', // Mint
            '#F7DC6F', // Yellow
            '#BB8FCE', // Purple
            '#85C1E2', // Sky Blue
            '#F8B88B', // Peach
            '#A569BD', // Violet
            '#52BE80', // Green
            '#EC7063', // Coral
            '#5DADE2', // Light Blue
            '#F5B041', // Orange
            '#48C9B0', // Turquoise
        );

        // Use user ID to consistently get the same color
        $index = $user_id % count($colors);
        return $colors[$index];
    }

    /**
     * Get user from various input types
     */
    private static function get_user_from_id_or_email($id_or_email) {
        if (is_numeric($id_or_email)) {
            return get_user_by('id', $id_or_email);
        } elseif (is_object($id_or_email)) {
            if (isset($id_or_email->user_id)) {
                return get_user_by('id', $id_or_email->user_id);
            } elseif (isset($id_or_email->ID)) {
                return get_user_by('id', $id_or_email->ID);
            }
        } elseif (is_string($id_or_email) && is_email($id_or_email)) {
            return get_user_by('email', $id_or_email);
        }

        return false;
    }

    /**
     * Inject CSS for initials avatars
     */
    public static function inject_avatar_css() {
        ?>
        <style>
        .cdv-initials-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            text-align: center;
            border-radius: 50%;
            text-transform: uppercase;
            user-select: none;
        }
        .cdv-custom-avatar {
            border-radius: 50%;
            object-fit: cover;
        }
        </style>
        <?php
    }

    /**
     * Add avatar approval column to users list
     */
    public static function add_avatar_approval_column($columns) {
        $columns['avatar_status'] = 'Avatar';
        return $columns;
    }

    /**
     * Display avatar approval column content
     */
    public static function display_avatar_approval_column($value, $column_name, $user_id) {
        if ($column_name === 'avatar_status') {
            $image_id = get_user_meta($user_id, 'cdv_profile_image', true);
            $approved = get_user_meta($user_id, 'cdv_profile_image_approved', true);

            if (!$image_id) {
                return '<span style="color: #999;">Nessuna immagine</span>';
            }

            if ($approved === '1') {
                return '<span style="color: #46b450;">✓ Approvata</span>';
            } else {
                return '<span style="color: #dc3232;">✗ In attesa</span>';
            }
        }
        return $value;
    }

    /**
     * Add bulk actions for avatar approval
     */
    public static function add_bulk_actions($bulk_actions) {
        $bulk_actions['approve_avatar'] = 'Approva Avatar';
        $bulk_actions['reject_avatar'] = 'Rifiuta Avatar';
        return $bulk_actions;
    }

    /**
     * Handle bulk actions
     */
    public static function handle_bulk_actions($redirect_to, $action, $user_ids) {
        if ($action === 'approve_avatar') {
            foreach ($user_ids as $user_id) {
                update_user_meta($user_id, 'cdv_profile_image_approved', '1');
            }
            $redirect_to = add_query_arg('avatars_approved', count($user_ids), $redirect_to);
        } elseif ($action === 'reject_avatar') {
            foreach ($user_ids as $user_id) {
                delete_user_meta($user_id, 'cdv_profile_image_approved');
            }
            $redirect_to = add_query_arg('avatars_rejected', count($user_ids), $redirect_to);
        }
        return $redirect_to;
    }

    /**
     * Add profile fields for avatar approval
     */
    public static function add_profile_fields($user) {
        if (!current_user_can('edit_users')) {
            return;
        }

        $image_id = get_user_meta($user->ID, 'cdv_profile_image', true);
        $approved = get_user_meta($user->ID, 'cdv_profile_image_approved', true);
        ?>
        <h2>Immagine Profilo</h2>
        <table class="form-table">
            <tr>
                <th><label>Immagine Caricata</label></th>
                <td>
                    <?php if ($image_id):
                        $image_url = wp_get_attachment_url($image_id);
                        ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; height: auto; border-radius: 8px;" />
                        </div>
                    <?php else: ?>
                        <p><em>Nessuna immagine caricata</em></p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($image_id): ?>
            <tr>
                <th><label for="cdv_profile_image_approved">Stato Approvazione</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="cdv_profile_image_approved" id="cdv_profile_image_approved" value="1" <?php checked($approved, '1'); ?> />
                        Approva immagine profilo
                    </label>
                    <p class="description">Se non approvata, verrà mostrato un avatar con le iniziali</p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Save profile fields
     */
    public static function save_profile_fields($user_id) {
        if (!current_user_can('edit_users')) {
            return;
        }

        if (isset($_POST['cdv_profile_image_approved'])) {
            update_user_meta($user_id, 'cdv_profile_image_approved', '1');
        } else {
            delete_user_meta($user_id, 'cdv_profile_image_approved');
        }
    }

    /**
     * Get user avatar URL (for API and other uses)
     */
    public static function get_user_avatar_url($user_id, $size = 96) {
        $image_id = get_user_meta($user_id, 'cdv_profile_image', true);
        $approved = get_user_meta($user_id, 'cdv_profile_image_approved', true);

        if ($image_id && $approved === '1') {
            // Use medium size (300x300) for better quality and cropped display
            $custom_url = wp_get_attachment_image_url($image_id, 'medium');
            if ($custom_url) {
                return $custom_url;
            }
        }

        // Return Gravatar as fallback for API
        $user = get_user_by('id', $user_id);
        if ($user) {
            return get_avatar_url($user->user_email, array('size' => $size));
        }

        return '';
    }
}
