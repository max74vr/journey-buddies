<?php
/**
 * Template Name: Wishlist Viaggi
 *
 * Displays user's saved travels
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();
$wishlist_travels = CDV_Wishlist::get_wishlist_travels($user_id);
?>

<main class="site-main wishlist-page">
    <div class="page-header">
        <div class="container">
            <h1>💝 My Wishlist</h1>
            <p>Journeys you saved for later</p>
        </div>
    </div>

    <div class="container">
        <div class="wishlist-content">

            <?php if ($wishlist_travels->have_posts()) : ?>

                <div class="wishlist-count">
                    <p><?php echo $wishlist_travels->post_count; ?> <?php echo $wishlist_travels->post_count === 1 ? 'saved journey' : 'saved journeys'; ?></p>
                </div>

                <div class="travels-grid">
                    <?php
                    while ($wishlist_travels->have_posts()) : $wishlist_travels->the_post();
                        $travel_id = get_the_ID();
                        $author_id = get_the_author_meta('ID');
                        $destination = get_post_meta($travel_id, 'cdv_destination', true);
                        $country = get_post_meta($travel_id, 'cdv_country', true);
                        $start_date = get_post_meta($travel_id, 'cdv_start_date', true);
                        $budget = get_post_meta($travel_id, 'cdv_budget', true);
                        $max_participants = get_post_meta($travel_id, 'cdv_max_participants', true);
                        $participants_count = CDV_Participants::get_participants_count($travel_id, 'accepted');
                        $status = get_post_meta($travel_id, 'cdv_travel_status', true);
                        ?>

                        <article class="travel-card">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="travel-card-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                    <button class="wishlist-btn active" data-travel-id="<?php echo $travel_id; ?>" title="Remove from wishlist">
                                        <span class="wishlist-icon">❤️</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <div class="travel-card-content">
                                <div class="travel-card-badges">
                                    <?php cdv_travel_type_badges(); ?>
                                    <?php echo cdv_get_travel_status_label(); ?>
                                </div>

                                <h3 class="travel-card-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>

                                <div class="travel-card-meta">
                                    <?php if ($destination) : ?>
                                        <span class="meta-item">
                                            <strong>📍</strong> <?php echo esc_html($destination); ?><?php echo $country ? ', ' . esc_html($country) : ''; ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($start_date) : ?>
                                        <span class="meta-item">
                                            <strong>📅</strong> <?php echo date_i18n('d M Y', strtotime($start_date)); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($budget) : ?>
                                        <span class="meta-item">
                                            <strong>💰</strong> €<?php echo number_format($budget, 0, ',', '.'); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($max_participants) : ?>
                                        <span class="meta-item">
                                            <strong>👥</strong> <?php echo $participants_count; ?>/<?php echo $max_participants; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="travel-card-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                </div>

                                <div class="travel-card-footer">
                                    <div class="organizer-info">
                                        <?php echo get_avatar($author_id, 32); ?>
                                        <span><?php echo esc_html(get_the_author_meta('display_name')); ?></span>
                                    </div>

                                    <a href="<?php the_permalink(); ?>" class="btn-primary btn-small">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </article>

                    <?php endwhile; wp_reset_postdata(); ?>
                </div>

            <?php else : ?>

                <div class="wishlist-empty">
                    <div class="empty-state">
                        <span class="empty-icon">💝</span>
                        <h2>Your wishlist is empty</h2>
                        <p>You havent saved any journeys to your wishlist yet.</p>
                        <p>Explore available journeys and save the ones that interest you to find them easily!</p>
                        <a href="<?php echo get_post_type_archive_link('viaggio'); ?>" class="btn-primary">
                            Explore Journeys
                        </a>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</main>

<style>
.wishlist-page {
    background: var(--bg-light);
    min-height: 80vh;
    padding-bottom: calc(var(--spacing-unit) * 8);
}

.page-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: calc(var(--spacing-unit) * 6) 0;
    text-align: center;
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.page-header h1 {
    color: white;
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.page-header p {
    font-size: 1.1rem;
    opacity: 0.95;
}

.wishlist-count {
    background: white;
    padding: calc(var(--spacing-unit) * 2) calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    margin-bottom: calc(var(--spacing-unit) * 4);
    box-shadow: var(--shadow-sm);
}

.wishlist-count p {
    margin: 0;
    font-weight: 500;
    color: var(--text-dark);
}

.wishlist-empty {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.empty-state {
    text-align: center;
    max-width: 500px;
    background: white;
    padding: calc(var(--spacing-unit) * 6);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
}

.empty-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: calc(var(--spacing-unit) * 3);
}

.empty-state h2 {
    margin-bottom: calc(var(--spacing-unit) * 2);
    color: var(--text-dark);
}

.empty-state p {
    color: var(--text-medium);
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.empty-state .btn-primary {
    margin-top: calc(var(--spacing-unit) * 3);
}

.wishlist-btn {
    position: absolute;
    top: calc(var(--spacing-unit) * 2);
    right: calc(var(--spacing-unit) * 2);
    background: white;
    border: none;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: all 0.3s;
    z-index: 10;
}

.wishlist-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.wishlist-icon {
    font-size: 1.3rem;
    line-height: 1;
}

.wishlist-btn.active .wishlist-icon {
    animation: heartBeat 0.3s ease;
}

@keyframes heartBeat {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(1.1); }
}

@media (max-width: 768px) {
    .empty-state {
        padding: calc(var(--spacing-unit) * 4);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Wishlist toggle
    $('.wishlist-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(this);
        const travelId = $btn.data('travel-id');

        $.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_toggle_wishlist',
                nonce: cdvAjax.nonce,
                travel_id: travelId
            },
            beforeSend: function() {
                $btn.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Remove card from wishlist page with animation
                    $btn.closest('.travel-card').fadeOut(400, function() {
                        $(this).remove();

                        // Check if wishlist is now empty
                        if ($('.travel-card').length === 0) {
                            location.reload();
                        } else {
                            // Update count
                            const remaining = $('.travel-card').length;
                            $('.wishlist-count p').text(remaining + (remaining === 1 ? ' saved journey' : ' saved journeys'));
                        }
                    });
                }
            },
            error: function() {
                alert('Error removing from wishlist');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>

<?php get_footer(); ?>
