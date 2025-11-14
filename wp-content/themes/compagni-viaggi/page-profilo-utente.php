<?php
/**
 * Template Profilo Utente Pubblico
 * Mostra il profilo pubblico di un viaggiatore
 */

get_header();

$username = get_query_var('cdv_user_profile');
$user = get_user_by('login', $username);

if (!$user) {
    get_template_part('404');
    get_footer();
    exit;
}

$profile = CDV_User_Profiles::get_public_profile($user->ID);
$stats = CDV_User_Profiles::get_user_stats($user->ID);
$travels = CDV_User_Profiles::get_user_travels($user->ID);
$is_own_profile = is_user_logged_in() && get_current_user_id() == $user->ID;
?>

<main class="site-main user-profile">
    <div class="container">
        <!-- Header Profilo -->
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="<?php echo esc_url($profile['avatar_url']); ?>" alt="<?php echo esc_attr($profile['username']); ?>">
            </div>

            <div class="profile-info">
                <h1><?php echo esc_html($profile['username']); ?></h1>

                <?php if (!empty($profile['city']) || !empty($profile['country'])) : ?>
                    <p class="profile-location">
                        <i class="icon-location"></i>
                        <?php
                        $location = array_filter(array($profile['city'], $profile['country']));
                        echo esc_html(implode(', ', $location));
                        ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($profile['age'])) : ?>
                    <p class="profile-age">
                        <i class="icon-user"></i>
                        <?php echo esc_html($profile['age']); ?> anni
                    </p>
                <?php endif; ?>

                <p class="profile-member-since">
                    Membro da <?php echo date_i18n('F Y', strtotime($profile['member_since'])); ?>
                </p>

                <?php if ($is_own_profile) : ?>
                    <a href="<?php echo home_url('/dashboard/'); ?>" class="btn btn-secondary">
                        <i class="icon-settings"></i> Gestisci Profilo
                    </a>
                <?php endif; ?>
            </div>

            <!-- Statistiche -->
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['organized']; ?></span>
                    <span class="stat-label">Viaggi Organizzati</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['participated']; ?></span>
                    <span class="stat-label">Partecipazioni</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">
                        <?php if ($stats['avg_rating'] > 0) : ?>
                            <i class="icon-star"></i> <?php echo $stats['avg_rating']; ?>
                        <?php else : ?>
                            -
                        <?php endif; ?>
                    </span>
                    <span class="stat-label">
                        <?php echo $stats['reviews_count']; ?> Recensioni
                    </span>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="profile-tabs">
            <button class="tab-button active" data-tab="about">Chi Sono</button>
            <button class="tab-button" data-tab="travels">Viaggi</button>
            <button class="tab-button" data-tab="badges">Badge</button>
            <button class="tab-button" data-tab="reviews">Recensioni</button>
        </div>

        <!-- Tab: Chi Sono -->
        <div class="tab-content active" id="tab-about">
            <div class="profile-section">
                <?php if (!empty($profile['bio'])) : ?>
                    <div class="bio-section">
                        <h3>Bio</h3>
                        <p><?php echo nl2br(esc_html($profile['bio'])); ?></p>
                    </div>
                <?php endif; ?>

                <div class="profile-details-grid">
                    <?php if (!empty($profile['languages'])) : ?>
                        <div class="detail-item">
                            <h4><i class="icon-language"></i> Lingue Parlate</h4>
                            <p><?php echo esc_html($profile['languages']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['travel_styles'])) : ?>
                        <div class="detail-item">
                            <h4><i class="icon-compass"></i> Stile di Viaggio</h4>
                            <div class="tags">
                                <?php
                                $styles = is_array($profile['travel_styles']) ? $profile['travel_styles'] : array($profile['travel_styles']);
                                foreach ($styles as $style) :
                                ?>
                                    <span class="tag"><?php echo esc_html($style); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['interests'])) : ?>
                        <div class="detail-item">
                            <h4><i class="icon-heart"></i> Interessi</h4>
                            <div class="tags">
                                <?php
                                $interests = is_array($profile['interests']) ? $profile['interests'] : array($profile['interests']);
                                foreach ($interests as $interest) :
                                ?>
                                    <span class="tag"><?php echo esc_html($interest); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Contatti (se pubblici) -->
                <?php if (!empty($profile['email']) || !empty($profile['phone']) || !empty($profile['instagram']) || !empty($profile['facebook'])) : ?>
                    <div class="contact-section">
                        <h3>Contatti</h3>
                        <div class="contact-links">
                            <?php if (!empty($profile['email'])) : ?>
                                <a href="mailto:<?php echo esc_attr($profile['email']); ?>" class="contact-link">
                                    <i class="icon-email"></i> <?php echo esc_html($profile['email']); ?>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($profile['phone'])) : ?>
                                <a href="tel:<?php echo esc_attr($profile['phone']); ?>" class="contact-link">
                                    <i class="icon-phone"></i> <?php echo esc_html($profile['phone']); ?>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($profile['instagram'])) : ?>
                                <a href="https://instagram.com/<?php echo esc_attr($profile['instagram']); ?>" class="contact-link" target="_blank">
                                    <i class="icon-instagram"></i> @<?php echo esc_html($profile['instagram']); ?>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($profile['facebook'])) : ?>
                                <a href="<?php echo esc_url($profile['facebook']); ?>" class="contact-link" target="_blank">
                                    <i class="icon-facebook"></i> Facebook
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Viaggi -->
        <div class="tab-content" id="tab-travels">
            <?php if ($travels->have_posts()) : ?>
                <div class="travels-grid">
                    <?php while ($travels->have_posts()) : $travels->the_post(); ?>
                        <?php get_template_part('template-parts/content', 'travel-card'); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="no-content">Nessun viaggio organizzato ancora.</p>
            <?php endif; ?>
        </div>

        <!-- Tab: Badge -->
        <div class="tab-content" id="tab-badges">
            <?php if (!empty($profile['badges']) && count($profile['badges']) > 0) : ?>
                <div class="badges-grid">
                    <?php foreach ($profile['badges'] as $badge) : ?>
                        <div class="badge-item">
                            <div class="badge-icon"><?php echo esc_html($badge['icon']); ?></div>
                            <h4><?php echo esc_html($badge['name']); ?></h4>
                            <p><?php echo esc_html($badge['description']); ?></p>
                            <span class="badge-date">
                                <?php echo date_i18n('d F Y', strtotime($badge['earned_at'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="no-content">Nessun badge guadagnato ancora.</p>
            <?php endif; ?>
        </div>

        <!-- Tab: Recensioni -->
        <div class="tab-content" id="tab-reviews">
            <?php
            global $wpdb;
            $reviews_table = $wpdb->prefix . 'cdv_reviews';
            $reviews = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, u.display_name as reviewer_name
                FROM $reviews_table r
                LEFT JOIN {$wpdb->users} u ON r.reviewer_id = u.ID
                WHERE r.reviewed_user_id = %d
                ORDER BY r.created_at DESC
                LIMIT 20",
                $user->ID
            ));

            if ($reviews) : ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review) : ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-author">
                                    <?php echo get_avatar($review->reviewer_id, 40); ?>
                                    <div>
                                        <strong><?php echo esc_html($review->reviewer_name); ?></strong>
                                        <span class="review-date">
                                            <?php echo human_time_diff(strtotime($review->created_at), current_time('timestamp')); ?> fa
                                        </span>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <i class="icon-star <?php echo $i <= $review->rating ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if (!empty($review->comment)) : ?>
                                <p class="review-comment"><?php echo esc_html($review->comment); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="no-content">Nessuna recensione ricevuta ancora.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.user-profile {
    padding: 3rem 0;
}

.profile-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 2rem;
    margin-bottom: 2rem;
    align-items: start;
}

.profile-avatar img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary-color);
}

.profile-info h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
}

.profile-location, .profile-age, .profile-member-since {
    margin: 0.25rem 0;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.profile-stats {
    display: flex;
    gap: 2rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
}

.stat-label {
    display: block;
    font-size: 0.875rem;
    color: #666;
}

.profile-tabs {
    display: flex;
    gap: 1rem;
    border-bottom: 2px solid #eee;
    margin-bottom: 2rem;
}

.tab-button {
    padding: 1rem 2rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 1rem;
    color: #666;
    transition: all 0.3s;
}

.tab-button.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.profile-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.bio-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.profile-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.detail-item h4 {
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-color);
}

.tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag {
    background: #f0f0f0;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
}

.contact-section {
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.contact-links {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.contact-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f8f8f8;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: background 0.3s;
}

.contact-link:hover {
    background: #e8e8e8;
}

.travels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 2rem;
}

.badge-item {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.badge-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.badge-date {
    display: block;
    font-size: 0.75rem;
    color: #999;
    margin-top: 0.5rem;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-item {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.review-author {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.review-author img {
    border-radius: 50%;
}

.review-date {
    display: block;
    font-size: 0.875rem;
    color: #999;
}

.review-rating {
    display: flex;
    gap: 0.25rem;
}

.icon-star {
    color: #ddd;
}

.icon-star.filled {
    color: #ffc107;
}

.no-content {
    text-align: center;
    padding: 3rem;
    color: #999;
}

@media (max-width: 768px) {
    .profile-header {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .profile-avatar img {
        margin: 0 auto;
    }

    .profile-stats {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        });
    });
});
</script>

<?php get_footer(); ?>
