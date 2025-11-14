<?php
/**
 * Theme Customizer
 *
 * Manages all theme customizations
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register customizer settings
 */
function cdv_customize_register($wp_customize) {

    // ========================================
    // SECTION: Colors
    // ========================================
    $wp_customize->add_section('cdv_colors', array(
        'title'    => 'Site Colors',
        'priority' => 30,
    ));

    // Primary Color
    $wp_customize->add_setting('cdv_primary_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cdv_primary_color', array(
        'label'    => 'Primary Color',
        'section'  => 'cdv_colors',
        'settings' => 'cdv_primary_color',
    )));

    // Secondary Color
    $wp_customize->add_setting('cdv_secondary_color', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cdv_secondary_color', array(
        'label'    => 'Secondary Color',
        'section'  => 'cdv_colors',
        'settings' => 'cdv_secondary_color',
    )));

    // Text Color
    $wp_customize->add_setting('cdv_text_color', array(
        'default'           => '#2c3e50',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cdv_text_color', array(
        'label'    => 'Text Color',
        'section'  => 'cdv_colors',
        'settings' => 'cdv_text_color',
    )));

    // Link Color
    $wp_customize->add_setting('cdv_link_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cdv_link_color', array(
        'label'    => 'Link Color',
        'section'  => 'cdv_colors',
        'settings' => 'cdv_link_color',
    )));

    // ========================================
    // SECTION: Typography / Fonts
    // ========================================
    $wp_customize->add_section('cdv_typography', array(
        'title'       => 'Typography and Fonts',
        'description' => 'Customize site fonts by choosing from Google Fonts',
        'priority'    => 32,
    ));

    // Available Google Fonts
    $google_fonts = array(
        'Poppins' => 'Poppins',
        'Roboto' => 'Roboto',
        'Open Sans' => 'Open Sans',
        'Lato' => 'Lato',
        'Montserrat' => 'Montserrat',
        'Raleway' => 'Raleway',
        'Playfair Display' => 'Playfair Display',
        'Merriweather' => 'Merriweather',
        'Nunito' => 'Nunito',
        'Inter' => 'Inter',
        'Work Sans' => 'Work Sans',
        'DM Sans' => 'DM Sans',
        'Outfit' => 'Outfit',
        'Plus Jakarta Sans' => 'Plus Jakarta Sans',
        'Manrope' => 'Manrope',
        'Space Grotesk' => 'Space Grotesk',
        'Quicksand' => 'Quicksand',
        'Josefin Sans' => 'Josefin Sans',
        'PT Sans' => 'PT Sans',
        'Ubuntu' => 'Ubuntu',
    );

    // Body Font
    $wp_customize->add_setting('cdv_body_font', array(
        'default'           => 'Poppins',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_body_font', array(
        'label'       => 'Body Font',
        'description' => 'Main font for site content',
        'section'     => 'cdv_typography',
        'settings'    => 'cdv_body_font',
        'type'        => 'select',
        'choices'     => $google_fonts,
    ));

    // Heading Font
    $wp_customize->add_setting('cdv_heading_font', array(
        'default'           => 'Poppins',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_heading_font', array(
        'label'       => 'Heading Font (H1-H6)',
        'description' => 'Font for all site headings',
        'section'     => 'cdv_typography',
        'settings'    => 'cdv_heading_font',
        'type'        => 'select',
        'choices'     => $google_fonts,
    ));

    // Menu Font
    $wp_customize->add_setting('cdv_menu_font', array(
        'default'           => 'Poppins',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_menu_font', array(
        'label'       => 'Menu Font',
        'description' => 'Font for navigation menu',
        'section'     => 'cdv_typography',
        'settings'    => 'cdv_menu_font',
        'type'        => 'select',
        'choices'     => $google_fonts,
    ));

    // Button Font
    $wp_customize->add_setting('cdv_button_font', array(
        'default'           => 'Poppins',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_button_font', array(
        'label'       => 'Button Font',
        'description' => 'Font for all buttons',
        'section'     => 'cdv_typography',
        'settings'    => 'cdv_button_font',
        'type'        => 'select',
        'choices'     => $google_fonts,
    ));

    // Body Font Size
    $wp_customize->add_setting('cdv_body_font_size', array(
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_body_font_size', array(
        'label'       => 'Body Font Size (px)',
        'description' => 'Base text size (recommended 14-18px)',
        'section'     => 'cdv_typography',
        'settings'    => 'cdv_body_font_size',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ));

    // Heading Font Weight
    $wp_customize->add_setting('cdv_heading_font_weight', array(
        'default'           => '700',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_heading_font_weight', array(
        'label'    => 'Heading Font Weight',
        'section'  => 'cdv_typography',
        'settings' => 'cdv_heading_font_weight',
        'type'     => 'select',
        'choices'  => array(
            '300' => 'Light (300)',
            '400' => 'Normal (400)',
            '500' => 'Medium (500)',
            '600' => 'Semi-bold (600)',
            '700' => 'Bold (700)',
            '800' => 'Extra-bold (800)',
        ),
    ));

    // ========================================
    // SECTION: Logo & Header
    // ========================================
    $wp_customize->add_section('cdv_header', array(
        'title'    => 'Logo & Header',
        'priority' => 35,
    ));

    // Logo Height
    $wp_customize->add_setting('cdv_logo_height', array(
        'default'           => '50',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_logo_height', array(
        'label'       => 'Logo Height (px)',
        'description' => 'Set logo height in pixels',
        'section'     => 'cdv_header',
        'settings'    => 'cdv_logo_height',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 30,
            'max'  => 150,
            'step' => 5,
        ),
    ));

    // Header Background Color
    $wp_customize->add_setting('cdv_header_bg_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cdv_header_bg_color', array(
        'label'    => 'Header Background Color',
        'section'  => 'cdv_header',
        'settings' => 'cdv_header_bg_color',
    )));

    // ========================================
    // SECTION: Hero Homepage
    // ========================================
    $wp_customize->add_section('cdv_hero', array(
        'title'       => 'Hero Section (Homepage)',
        'description' => 'Customize the main homepage section',
        'priority'    => 40,
    ));

    // Hero Title
    $wp_customize->add_setting('cdv_hero_title', array(
        'default'           => 'Find Your Travel Buddies',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_hero_title', array(
        'label'    => 'Hero Title',
        'section'  => 'cdv_hero',
        'settings' => 'cdv_hero_title',
        'type'     => 'text',
    ));

    // Hero Subtitle
    $wp_customize->add_setting('cdv_hero_subtitle', array(
        'default'           => 'Connect with travelers who share your passions. Organize unforgettable adventures together.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_hero_subtitle', array(
        'label'    => 'Hero Subtitle',
        'section'  => 'cdv_hero',
        'settings' => 'cdv_hero_subtitle',
        'type'     => 'textarea',
    ));

    // Hero Button Text
    $wp_customize->add_setting('cdv_hero_button_text', array(
        'default'           => 'Post Your Listing',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_hero_button_text', array(
        'label'    => 'Hero Button Text',
        'section'  => 'cdv_hero',
        'settings' => 'cdv_hero_button_text',
        'type'     => 'text',
    ));

    // Hero Button URL
    $wp_customize->add_setting('cdv_hero_button_url', array(
        'default'           => '/create-journey',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_hero_button_url', array(
        'label'    => 'Hero Button URL',
        'section'  => 'cdv_hero',
        'settings' => 'cdv_hero_button_url',
        'type'     => 'url',
    ));

    // Hero Background Image
    $wp_customize->add_setting('cdv_hero_bg_image', array(
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'cdv_hero_bg_image', array(
        'label'       => 'Hero Background Image',
        'description' => 'Upload a background image for the hero section',
        'section'     => 'cdv_hero',
        'settings'    => 'cdv_hero_bg_image',
        'mime_type'   => 'image',
    )));

    // Hero Overlay Color
    $wp_customize->add_setting('cdv_hero_overlay_color', array(
        'default'           => '#000000',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cdv_hero_overlay_color', array(
        'label'       => 'Hero Overlay Color',
        'description' => 'Overlay color on the image',
        'section'     => 'cdv_hero',
        'settings'    => 'cdv_hero_overlay_color',
    )));

    // Hero Overlay Opacity
    $wp_customize->add_setting('cdv_hero_overlay_opacity', array(
        'default'           => '0.5',
        'sanitize_callback' => 'cdv_sanitize_float',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_hero_overlay_opacity', array(
        'label'       => 'Hero Overlay Opacity (0-1)',
        'description' => '0 = transparent, 1 = completely dark',
        'section'     => 'cdv_hero',
        'settings'    => 'cdv_hero_overlay_opacity',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => '0',
            'max'  => '1',
            'step' => '0.1',
        ),
    ));

    // ========================================
    // SECTION: Journeys Section
    // ========================================
    $wp_customize->add_section('cdv_travels_section', array(
        'title'       => 'Journeys Section (Homepage)',
        'description' => 'Customize the featured journeys section',
        'priority'    => 45,
    ));

    // Travels Section Title
    $wp_customize->add_setting('cdv_travels_title', array(
        'default'           => 'Journey Proposals',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_travels_title', array(
        'label'    => 'Section Title',
        'section'  => 'cdv_travels_section',
        'settings' => 'cdv_travels_title',
        'type'     => 'text',
    ));

    // Travels Section Subtitle
    $wp_customize->add_setting('cdv_travels_subtitle', array(
        'default'           => 'Discover upcoming adventures and join travelers',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_travels_subtitle', array(
        'label'    => 'Section Subtitle',
        'section'  => 'cdv_travels_section',
        'settings' => 'cdv_travels_subtitle',
        'type'     => 'text',
    ));

    // Travels Button Text
    $wp_customize->add_setting('cdv_travels_button_text', array(
        'default'           => 'See All Journeys',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_travels_button_text', array(
        'label'    => 'Button Text',
        'section'  => 'cdv_travels_section',
        'settings' => 'cdv_travels_button_text',
        'type'     => 'text',
    ));

    // ========================================
    // SECTION: How It Works
    // ========================================
    $wp_customize->add_section('cdv_how_it_works', array(
        'title'       => 'How It Works Section',
        'description' => 'Customize the how it works section',
        'priority'    => 50,
    ));

    // How it Works Background Color
    $wp_customize->add_setting('cdv_how_bg_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cdv_how_bg_color', array(
        'label'       => 'Section Background Color',
        'description' => 'Colored background with white text',
        'section'     => 'cdv_how_it_works',
        'settings'    => 'cdv_how_bg_color',
    )));

    // How it Works Title
    $wp_customize->add_setting('cdv_how_title', array(
        'default'           => 'How It Works',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_how_title', array(
        'label'    => 'Section Title',
        'section'  => 'cdv_how_it_works',
        'settings' => 'cdv_how_title',
        'type'     => 'text',
    ));

    // How it Works Subtitle
    $wp_customize->add_setting('cdv_how_subtitle', array(
        'default'           => 'In a few simple steps you can find your travel buddies',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_how_subtitle', array(
        'label'    => 'Section Subtitle',
        'section'  => 'cdv_how_it_works',
        'settings' => 'cdv_how_subtitle',
        'type'     => 'text',
    ));

    // Step 1
    $wp_customize->add_setting('cdv_step1_title', array(
        'default'           => '1. Create Your Profile',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_step1_title', array(
        'label'    => 'Step 1 Title',
        'section'  => 'cdv_how_it_works',
        'settings' => 'cdv_step1_title',
        'type'     => 'text',
    ));

    $wp_customize->add_setting('cdv_step1_text', array(
        'default'           => 'Register and complete your profile with interests, languages spoken and preferred travel styles.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_step1_text', array(
        'label'    => 'Step 1 Text',
        'section'  => 'cdv_how_it_works',
        'settings' => 'cdv_step1_text',
        'type'     => 'textarea',
    ));

    // Step 2
    $wp_customize->add_setting('cdv_step2_title', array(
        'default'           => '2. Search or Create a Journey',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_step2_title', array(
        'label'    => 'Step 2 Title',
        'section'  => 'cdv_how_it_works',
        'settings' => 'cdv_step2_title',
        'type'     => 'text',
    ));

    $wp_customize->add_setting('cdv_step2_text', array(
        'default'           => 'Search among available journeys or create your own and wait for other travelers to join.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_step2_text', array(
        'label'    => 'Step 2 Text',
        'section'  => 'cdv_how_it_works',
        'settings' => 'cdv_step2_text',
        'type'     => 'textarea',
    ));

    // Step 3
    $wp_customize->add_setting('cdv_step3_title', array(
        'default'           => '3. Connect and Organize',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_step3_title', array(
        'label'    => 'Step 3 Title',
        'section'  => 'cdv_how_it_works',
        'settings' => 'cdv_step3_title',
        'type'     => 'text',
    ));

    $wp_customize->add_setting('cdv_step3_text', array(
        'default'           => 'Use group chat to get to know travel buddies and organize details together.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_step3_text', array(
        'label'    => 'Step 3 Text',
        'section'  => 'cdv_how_it_works',
        'settings' => 'cdv_step3_text',
        'type'     => 'textarea',
    ));

    // ========================================
    // SECTION: Stories Section
    // ========================================
    $wp_customize->add_section('cdv_stories_section', array(
        'title'       => 'Stories Section (Homepage)',
        'description' => 'Customize the travel stories section',
        'priority'    => 55,
    ));

    // Stories Section Title
    $wp_customize->add_setting('cdv_stories_title', array(
        'default'           => 'Travel Stories',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_stories_title', array(
        'label'    => 'Section Title',
        'section'  => 'cdv_stories_section',
        'settings' => 'cdv_stories_title',
        'type'     => 'text',
    ));

    // Stories Section Subtitle
    $wp_customize->add_setting('cdv_stories_subtitle', array(
        'default'           => 'Be inspired by the experiences of our travelers',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_stories_subtitle', array(
        'label'    => 'Section Subtitle',
        'section'  => 'cdv_stories_section',
        'settings' => 'cdv_stories_subtitle',
        'type'     => 'text',
    ));

    // Stories Button Text
    $wp_customize->add_setting('cdv_stories_button_text', array(
        'default'           => 'See All Stories',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_stories_button_text', array(
        'label'    => 'Button Text',
        'section'  => 'cdv_stories_section',
        'settings' => 'cdv_stories_button_text',
        'type'     => 'text',
    ));

    // ========================================
    // SECTION: Footer
    // ========================================
    $wp_customize->add_section('cdv_footer', array(
        'title'       => 'Footer',
        'description' => 'Customize the site footer',
        'priority'    => 60,
    ));

    // Footer Logo
    $wp_customize->add_setting('cdv_footer_logo', array(
        'default'           => '',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'cdv_footer_logo', array(
        'label'       => 'Footer Logo',
        'description' => 'Upload a logo for the footer (replaces site title)',
        'section'     => 'cdv_footer',
        'settings'    => 'cdv_footer_logo',
        'mime_type'   => 'image',
    )));

    // Footer Logo Height
    $wp_customize->add_setting('cdv_footer_logo_height', array(
        'default'           => '50',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_footer_logo_height', array(
        'label'       => 'Footer Logo Height (px)',
        'description' => 'Set logo height in pixels',
        'section'     => 'cdv_footer',
        'settings'    => 'cdv_footer_logo_height',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 30,
            'max'  => 150,
            'step' => 5,
        ),
    ));

    // Footer Copyright Text
    $wp_customize->add_setting('cdv_footer_copyright', array(
        'default'           => '© ' . date('Y') . ' Journey Buddies. All rights reserved.',
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('cdv_footer_copyright', array(
        'label'       => 'Copyright Text',
        'description' => 'Copyright text in footer. You can use HTML.',
        'section'     => 'cdv_footer',
        'settings'    => 'cdv_footer_copyright',
        'type'        => 'textarea',
    ));
}
add_action('customize_register', 'cdv_customize_register');

/**
 * Sanitize float value
 */
function cdv_sanitize_float($value) {
    return floatval($value);
}

/**
 * Enqueue Google Fonts
 */
function cdv_enqueue_google_fonts() {
    $body_font = get_theme_mod('cdv_body_font', 'Poppins');
    $heading_font = get_theme_mod('cdv_heading_font', 'Poppins');
    $menu_font = get_theme_mod('cdv_menu_font', 'Poppins');
    $button_font = get_theme_mod('cdv_button_font', 'Poppins');

    // Collect unique fonts
    $fonts = array_unique(array($body_font, $heading_font, $menu_font, $button_font));

    // Build Google Fonts URL
    $font_families = array();
    foreach ($fonts as $font) {
        // Request multiple weights for each font
        $font_families[] = str_replace(' ', '+', $font) . ':wght@300;400;500;600;700;800';
    }

    if (!empty($font_families)) {
        $fonts_url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $font_families) . '&display=swap';
        wp_enqueue_style('cdv-google-fonts', $fonts_url, array(), null);
    }
}
add_action('wp_enqueue_scripts', 'cdv_enqueue_google_fonts', 5);

/**
 * Output custom CSS
 */
function cdv_customizer_css() {
    $primary_color = get_theme_mod('cdv_primary_color', '#667eea');
    $secondary_color = get_theme_mod('cdv_secondary_color', '#764ba2');
    $text_color = get_theme_mod('cdv_text_color', '#2c3e50');
    $link_color = get_theme_mod('cdv_link_color', '#667eea');
    $logo_height = get_theme_mod('cdv_logo_height', '50');
    $header_bg_color = get_theme_mod('cdv_header_bg_color', '#667eea');

    // Font settings
    $body_font = get_theme_mod('cdv_body_font', 'Poppins');
    $heading_font = get_theme_mod('cdv_heading_font', 'Poppins');
    $menu_font = get_theme_mod('cdv_menu_font', 'Poppins');
    $button_font = get_theme_mod('cdv_button_font', 'Poppins');
    $body_font_size = get_theme_mod('cdv_body_font_size', '16');
    $heading_font_weight = get_theme_mod('cdv_heading_font_weight', '700');

    // Hero settings
    $hero_bg_image_id = get_theme_mod('cdv_hero_bg_image', '');
    $hero_overlay_color = get_theme_mod('cdv_hero_overlay_color', '#000000');
    $hero_overlay_opacity = get_theme_mod('cdv_hero_overlay_opacity', '0.5');

    // Footer settings
    $footer_logo_height = get_theme_mod('cdv_footer_logo_height', '50');

    // How it works settings
    $how_bg_color = get_theme_mod('cdv_how_bg_color', '#667eea');
    ?>
    <style type="text/css">
        :root {
            --primary-color: <?php echo esc_attr($primary_color); ?>;
            --secondary-color: <?php echo esc_attr($secondary_color); ?>;
            --text-dark: <?php echo esc_attr($text_color); ?>;
            --link-color: <?php echo esc_attr($link_color); ?>;
            --body-font: '<?php echo esc_attr($body_font); ?>', sans-serif;
            --heading-font: '<?php echo esc_attr($heading_font); ?>', sans-serif;
            --menu-font: '<?php echo esc_attr($menu_font); ?>', sans-serif;
            --button-font: '<?php echo esc_attr($button_font); ?>', sans-serif;
        }

        /* Body and Base Typography */
        body {
            font-family: var(--body-font);
            font-size: <?php echo esc_attr($body_font_size); ?>px;
            color: <?php echo esc_attr($text_color); ?>;
        }

        /* Headings */
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--heading-font);
            font-weight: <?php echo esc_attr($heading_font_weight); ?>;
        }

        /* Navigation Menu */
        .main-nav,
        .main-nav a,
        .mobile-nav,
        .mobile-nav a {
            font-family: var(--menu-font);
        }

        /* Buttons */
        .btn-primary,
        .btn-secondary,
        .btn-header,
        .btn-header-primary,
        .btn-header-secondary,
        .btn-header-ghost,
        button,
        input[type="submit"],
        input[type="button"] {
            font-family: var(--button-font);
        }

        /* Header */
        .site-header {
            background: <?php echo esc_attr($header_bg_color); ?>;
        }

        .custom-logo {
            max-height: <?php echo esc_attr($logo_height); ?>px;
            width: auto;
        }

        /* Links */
        a {
            color: <?php echo esc_attr($link_color); ?>;
        }

        /* Buttons Colors */
        .btn-primary,
        .btn-header-primary {
            background: <?php echo esc_attr($primary_color); ?>;
        }

        .btn-primary:hover,
        .btn-header-primary:hover {
            background: <?php echo esc_attr($secondary_color); ?>;
        }

        /* Hero Section Background */
        <?php if ($hero_bg_image_id) :
            $hero_bg_url = wp_get_attachment_url($hero_bg_image_id);
            if ($hero_bg_url) :
        ?>
        .hero-section {
            background-image: url('<?php echo esc_url($hero_bg_url); ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: <?php echo esc_attr($hero_overlay_color); ?>;
            opacity: <?php echo esc_attr($hero_overlay_opacity); ?>;
            z-index: 1;
        }

        .hero-section .container {
            position: relative;
            z-index: 2;
        }

        .hero-section h1,
        .hero-section p,
        .hero-section label {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        <?php
            endif;
        endif; ?>

        /* Footer Logo */
        .footer-logo-img {
            max-height: <?php echo esc_attr($footer_logo_height); ?>px;
            width: auto;
            height: auto;
        }

        /* Come Funziona Section - Negative Colors */
        .how-it-works-section {
            background: <?php echo esc_attr($how_bg_color); ?> !important;
            color: #ffffff !important;
        }

        .how-it-works-section h2,
        .how-it-works-section h3,
        .how-it-works-section h4,
        .how-it-works-section p,
        .how-it-works-section .subtitle {
            color: #ffffff !important;
        }

        .how-it-works-section .step-card {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .how-it-works-section .step-number {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'cdv_customizer_css');
