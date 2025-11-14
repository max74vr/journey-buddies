<?php
/**
 * Social Sharing System
 * Gestisce la condivisione sui social media
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Social_Sharing {

    /**
     * Initialize
     */
    public static function init() {
        // Add Open Graph meta tags
        add_action('wp_head', array(__CLASS__, 'add_og_meta_tags'), 5);

        // Add Twitter Card meta tags
        add_action('wp_head', array(__CLASS__, 'add_twitter_card_meta_tags'), 5);

        // Enqueue social sharing scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }

    /**
     * Enqueue scripts and styles
     */
    public static function enqueue_scripts() {
        if (is_singular('viaggio')) {
            wp_enqueue_style('cdv-social-sharing', CDV_PLUGIN_URL . 'assets/css/social-sharing.css', array(), CDV_VERSION);
            wp_enqueue_script('cdv-social-sharing', CDV_PLUGIN_URL . 'assets/js/social-sharing.js', array('jquery'), CDV_VERSION, true);
        }
    }

    /**
     * Add Open Graph meta tags
     */
    public static function add_og_meta_tags() {
        if (!is_singular('viaggio')) {
            return;
        }

        global $post;

        $title = get_the_title();
        $description = self::get_share_description($post->ID);
        $url = get_permalink();
        $image = self::get_share_image($post->ID);
        $site_name = get_bloginfo('name');

        echo "\n<!-- Open Graph Meta Tags -->\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";

        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
            echo '<meta property="og:image:width" content="1200" />' . "\n";
            echo '<meta property="og:image:height" content="630" />' . "\n";
        }

        // Travel specific metadata
        $start_date = get_post_meta($post->ID, 'cdv_start_date', true);
        $destination = get_post_meta($post->ID, 'cdv_destination', true);
        $country = get_post_meta($post->ID, 'cdv_country', true);

        if ($destination) {
            echo '<meta property="og:locality" content="' . esc_attr($destination) . '" />' . "\n";
        }
        if ($country) {
            echo '<meta property="og:country-name" content="' . esc_attr($country) . '" />' . "\n";
        }
    }

    /**
     * Add Twitter Card meta tags
     */
    public static function add_twitter_card_meta_tags() {
        if (!is_singular('viaggio')) {
            return;
        }

        global $post;

        $title = get_the_title();
        $description = self::get_share_description($post->ID);
        $image = self::get_share_image($post->ID);

        echo "\n<!-- Twitter Card Meta Tags -->\n";
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";

        if ($image) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
        }
    }

    /**
     * Get share description
     */
    private static function get_share_description($post_id) {
        $destination = get_post_meta($post_id, 'cdv_destination', true);
        $country = get_post_meta($post_id, 'cdv_country', true);
        $start_date = get_post_meta($post_id, 'cdv_start_date', true);
        $budget = get_post_meta($post_id, 'cdv_budget', true);

        $parts = array();

        if ($destination) {
            $location = $destination;
            if ($country) {
                $location .= ', ' . $country;
            }
            $parts[] = '📍 ' . $location;
        }

        if ($start_date) {
            $parts[] = '📅 ' . date_i18n('F Y', strtotime($start_date));
        }

        if ($budget) {
            $parts[] = '💰 €' . number_format($budget, 0, ',', '.');
        }

        $description = 'Unisciti a questo viaggio! ' . implode(' • ', $parts);

        // Fallback to excerpt if no meta
        if (empty($parts)) {
            $description = get_the_excerpt($post_id);
            if (strlen($description) > 160) {
                $description = substr($description, 0, 157) . '...';
            }
        }

        return $description;
    }

    /**
     * Get share image
     */
    private static function get_share_image($post_id) {
        // Try featured image first
        if (has_post_thumbnail($post_id)) {
            $image_id = get_post_thumbnail_id($post_id);
            $image = wp_get_attachment_image_src($image_id, 'large');
            if ($image) {
                return $image[0];
            }
        }

        // Try gallery images
        $gallery = get_post_meta($post_id, 'cdv_gallery', true);
        if (!empty($gallery) && is_array($gallery)) {
            $first_image_id = $gallery[0];
            $image = wp_get_attachment_image_src($first_image_id, 'large');
            if ($image) {
                return $image[0];
            }
        }

        // Fallback to default image
        $default_image = get_theme_file_uri('assets/images/default-travel-share.jpg');
        if (file_exists(get_theme_file_path('assets/images/default-travel-share.jpg'))) {
            return $default_image;
        }

        return '';
    }

    /**
     * Render share buttons
     */
    public static function render_share_buttons($post_id = null, $style = 'default') {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $url = get_permalink($post_id);
        $title = get_the_title($post_id);
        $description = self::get_share_description($post_id);

        // Encode for URL
        $url_encoded = urlencode($url);
        $title_encoded = urlencode($title);
        $description_encoded = urlencode($description);

        // Share URLs
        $facebook_url = 'https://www.facebook.com/sharer/sharer.php?u=' . $url_encoded;
        $twitter_url = 'https://twitter.com/intent/tweet?url=' . $url_encoded . '&text=' . $title_encoded;
        $whatsapp_url = 'https://wa.me/?text=' . $title_encoded . '%20' . $url_encoded;
        $linkedin_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url_encoded;
        $telegram_url = 'https://t.me/share/url?url=' . $url_encoded . '&text=' . $title_encoded;
        $email_url = 'mailto:?subject=' . $title_encoded . '&body=' . $description_encoded . '%0A%0A' . $url_encoded;

        ob_start();
        ?>
        <div class="cdv-share-buttons cdv-share-<?php echo esc_attr($style); ?>">
            <span class="share-label">Condividi:</span>

            <a href="<?php echo esc_url($facebook_url); ?>"
               class="share-btn share-facebook"
               target="_blank"
               rel="noopener noreferrer"
               title="Condividi su Facebook"
               data-platform="facebook">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span>Facebook</span>
            </a>

            <a href="<?php echo esc_url($twitter_url); ?>"
               class="share-btn share-twitter"
               target="_blank"
               rel="noopener noreferrer"
               title="Condividi su Twitter"
               data-platform="twitter">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
                <span>Twitter</span>
            </a>

            <a href="<?php echo esc_url($whatsapp_url); ?>"
               class="share-btn share-whatsapp"
               target="_blank"
               rel="noopener noreferrer"
               title="Condividi su WhatsApp"
               data-platform="whatsapp">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                <span>WhatsApp</span>
            </a>

            <a href="<?php echo esc_url($linkedin_url); ?>"
               class="share-btn share-linkedin"
               target="_blank"
               rel="noopener noreferrer"
               title="Condividi su LinkedIn"
               data-platform="linkedin">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
                <span>LinkedIn</span>
            </a>

            <a href="<?php echo esc_url($telegram_url); ?>"
               class="share-btn share-telegram"
               target="_blank"
               rel="noopener noreferrer"
               title="Condividi su Telegram"
               data-platform="telegram">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
                <span>Telegram</span>
            </a>

            <a href="<?php echo esc_url($email_url); ?>"
               class="share-btn share-email"
               title="Condividi via Email"
               data-platform="email">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M0 3v18h24v-18h-24zm6.623 7.929l-4.623 5.712v-9.458l4.623 3.746zm-4.141-5.929h19.035l-9.517 7.713-9.518-7.713zm5.694 7.188l3.824 3.099 3.83-3.104 5.612 6.817h-18.779l5.513-6.812zm9.208-1.264l4.616-3.741v9.348l-4.616-5.607z"/>
                </svg>
                <span>Email</span>
            </a>

            <button class="share-btn share-copy"
                    data-url="<?php echo esc_url($url); ?>"
                    title="Copia Link"
                    data-platform="copy">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                </svg>
                <span>Copia Link</span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render compact share buttons (icons only)
     */
    public static function render_compact_share_buttons($post_id = null) {
        return self::render_share_buttons($post_id, 'compact');
    }
}
