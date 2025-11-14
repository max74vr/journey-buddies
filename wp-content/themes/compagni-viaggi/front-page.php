<?php
/**
 * Homepage Template
 */

get_header();
?>

<main class="site-main">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1><?php echo esc_html(get_theme_mod('cdv_hero_title', 'Find Your Travel Buddies')); ?></h1>
                <p><?php echo esc_html(get_theme_mod('cdv_hero_subtitle', 'Connect with travelers who share your passions. Organize unforgettable adventures together.')); ?></p>

                <!-- Search Box -->
                <div class="search-box">
                    <form class="search-form" action="<?php echo esc_url(home_url('/')); ?>" method="get">
                        <input type="hidden" name="post_type" value="viaggio">

                        <div class="form-group">
                            <label for="destination">Destination</label>
                            <input type="text" id="destination" name="s" placeholder="Where do you want to go?">
                        </div>

                        <div class="form-group">
                            <label for="travel_type">Journey Type</label>
                            <select id="travel_type" name="tipo_viaggio">
                                <option value="">All types</option>
                                <?php
                                $types = get_terms(array(
                                    'taxonomy' => 'tipo_viaggio',
                                    'hide_empty' => false,
                                ));
                                foreach ($types as $type) {
                                    echo '<option value="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-search">Search Journeys</button>
                    </form>

                    <!-- CTA Button -->
                    <div class="hero-cta" style="text-align: center; margin-top: calc(var(--spacing-unit) * 4);">
                        <a href="<?php echo esc_url(get_theme_mod('cdv_hero_button_url', '/crea-viaggio')); ?>" class="btn-primary btn-large" style="font-size: 1.1rem; padding: calc(var(--spacing-unit) * 2) calc(var(--spacing-unit) * 4); display: inline-flex; align-items: center; gap: calc(var(--spacing-unit) * 1); box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                            <?php echo esc_html(get_theme_mod('cdv_hero_button_text', 'Post Your Listing')); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Travels -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2><?php echo esc_html(get_theme_mod('cdv_travels_title', 'Journey Proposals')); ?></h2>
                <p><?php echo esc_html(get_theme_mod('cdv_travels_subtitle', 'Discover upcoming adventures and join travelers')); ?></p>
            </div>

            <div class="grid">
                <?php
                $featured_travels = new WP_Query(array(
                    'post_type' => 'viaggio',
                    'posts_per_page' => 6,
                    'meta_query' => array(
                        array(
                            'key' => 'cdv_travel_status',
                            'value' => 'open',
                            'compare' => '=',
                        ),
                        array(
                            'key' => 'cdv_end_date',
                            'value' => date('Y-m-d'),
                            'compare' => '>=',
                            'type' => 'DATE',
                        ),
                    ),
                ));

                if ($featured_travels->have_posts()) :
                    while ($featured_travels->have_posts()) : $featured_travels->the_post();
                        get_template_part('template-parts/content', 'travel-card');
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="no-travels">
                        <p>No journeys available at the moment. <?php if (is_user_logged_in()) : ?><a href="<?php echo esc_url(home_url('/crea-viaggio')); ?>">Create the first listing!</a><?php endif; ?></p>
                    </div>
                    <?php
                endif;
                ?>
            </div>

            <div class="text-center mt-3">
                <a href="<?php echo esc_url(get_post_type_archive_link('viaggio')); ?>" class="btn-primary">
                    <?php echo esc_html(get_theme_mod('cdv_travels_button_text', 'View All Journeys')); ?> →
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="section how-it-works-section">
        <div class="container">
            <div class="section-title">
                <h2><?php echo esc_html(get_theme_mod('cdv_how_title', 'How It Works')); ?></h2>
                <p class="subtitle"><?php echo esc_html(get_theme_mod('cdv_how_subtitle', 'In a few simple steps you can find your travel buddies')); ?></p>
            </div>

            <div class="grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3><?php echo esc_html(get_theme_mod('cdv_step1_title', '1. Create Your Profile')); ?></h3>
                    <p><?php echo esc_html(get_theme_mod('cdv_step1_text', 'Sign up and complete your profile with interests, languages spoken and preferred travel styles.')); ?></p>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3><?php echo esc_html(get_theme_mod('cdv_step2_title', '2. Search or Create a Journey')); ?></h3>
                    <p><?php echo esc_html(get_theme_mod('cdv_step2_text', 'Search among available journeys or create your own and wait for other travelers to join.')); ?></p>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3><?php echo esc_html(get_theme_mod('cdv_step3_title', '3. Connect and Organize')); ?></h3>
                    <p><?php echo esc_html(get_theme_mod('cdv_step3_text', 'Use the group chat to get to know travel buddies and organize details together.')); ?></p>
                </div>
            </div>

            <style>
                .step-card {
                    text-align: center;
                    padding: calc(var(--spacing-unit) * 4);
                    border-radius: 12px;
                    transition: transform 0.3s ease;
                }
                .step-card:hover {
                    transform: translateY(-5px);
                }
                .step-number {
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    font-weight: bold;
                    margin: 0 auto calc(var(--spacing-unit) * 2);
                }
                .step-card h3 {
                    margin-bottom: calc(var(--spacing-unit) * 2);
                }
            </style>
        </div>
    </section>

    <!-- Travel Stories Section -->
    <section class="section" style="background-color: white;">
        <div class="container">
            <div class="section-title">
                <h2>📖 <?php echo esc_html(get_theme_mod('cdv_stories_title', 'Travel Stories')); ?></h2>
                <p><?php echo esc_html(get_theme_mod('cdv_stories_subtitle', 'Get inspired by our travelers' experiences')); ?></p>
            </div>

            <div class="stories-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: calc(var(--spacing-unit) * 4);">
                <?php
                $recent_stories = new WP_Query(array(
                    'post_type' => 'racconto',
                    'posts_per_page' => 3,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));

                if ($recent_stories->have_posts()) :
                    while ($recent_stories->have_posts()) : $recent_stories->the_post();
                        get_template_part('template-parts/content', 'story-card');
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="no-stories" style="grid-column: 1 / -1; text-align: center; padding: calc(var(--spacing-unit) * 4) 0;">
                        <p style="color: var(--text-medium);">No stories available at the moment.</p>
                    </div>
                    <?php
                endif;
                ?>
            </div>

            <?php if ($recent_stories->found_posts > 0) : ?>
                <div class="text-center mt-3">
                    <a href="<?php echo esc_url(home_url('/racconti')); ?>" class="btn-primary">
                        <?php echo esc_html(get_theme_mod('cdv_stories_button_text', 'View All Stories')); ?> →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section">
        <div class="container">
            <div class="stats-grid">
                <?php
                global $wpdb;
                $total_travels = wp_count_posts('viaggio')->publish;
                $total_users = count_users()['total_users'];
                $total_participants = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cdv_travel_participants WHERE status = 'accepted'");
                ?>

                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_travels; ?></div>
                    <div class="stat-label">Published Journeys</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Travelers</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_participants; ?></div>
                    <div class="stat-label">Participations</div>
                </div>

                <div class="stat-item">
                    <div class="stat-number">4.8</div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>

            <style>
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: calc(var(--spacing-unit) * 4);
                    text-align: center;
                }
                .stat-number {
                    font-size: 3rem;
                    font-weight: 700;
                    color: var(--primary-color);
                    margin-bottom: calc(var(--spacing-unit) * 1);
                }
                .stat-label {
                    font-size: 1.1rem;
                    color: var(--text-medium);
                }
            </style>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section cta-section" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
        <div class="container text-center">
            <h2 style="color: white;">Ready for Your Next Adventure?</h2>
            <p style="font-size: 1.2rem; margin-bottom: calc(var(--spacing-unit) * 4); opacity: 0.95;">
                Join thousands of travelers who have already found their perfect travel buddies.
            </p>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(home_url('/crea-viaggio')); ?>" class="btn-primary">
                    Create Your Listing
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url(home_url('/registrazione')); ?>" class="btn-primary">
                    Sign Up Free
                </a>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
get_footer();
