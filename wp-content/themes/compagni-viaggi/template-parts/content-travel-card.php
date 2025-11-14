<?php
/**
 * Template part for displaying travel cards
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('card card-text-only'); ?>>

    <div class="card-content">
        <div class="card-header">
            <div class="card-badges">
                <?php
                $is_expired = get_query_var('is_expired', false);
                if ($is_expired) :
                ?>
                    <span class="badge badge-expired" style="background: #dc3545; color: white; padding: calc(var(--spacing-unit) * 0.5) calc(var(--spacing-unit) * 1.5); border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                        Scaduto
                    </span>
                <?php endif; ?>
                <?php cdv_travel_type_badges(); ?>
                <?php if (!$is_expired) echo cdv_get_travel_status_label(); ?>
            </div>

            <?php if (is_user_logged_in()) : ?>
                <button class="wishlist-btn-inline <?php echo CDV_Wishlist::is_in_wishlist(get_current_user_id(), get_the_ID()) ? 'active' : ''; ?>"
                        data-travel-id="<?php the_ID(); ?>"
                        title="<?php echo CDV_Wishlist::is_in_wishlist(get_current_user_id(), get_the_ID()) ? 'Rimuovi dalla wishlist' : 'Aggiungi alla wishlist'; ?>">
                    <span class="wishlist-icon"><?php echo CDV_Wishlist::is_in_wishlist(get_current_user_id(), get_the_ID()) ? '❤️' : '🤍'; ?></span>
                </button>
            <?php endif; ?>
        </div>

        <h3 class="card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <?php cdv_travel_meta(); ?>

        <div class="card-excerpt">
            <?php the_excerpt(); ?>
        </div>

        <div class="card-footer">
            <?php cdv_organizer_info(get_the_author_meta('ID')); ?>

            <a href="<?php the_permalink(); ?>" class="travel-details-link">
                Vedi Dettagli →
            </a>
        </div>
    </div>
</article>

<style>
/* Text-only card styles */
.card-text-only .card-content {
    padding: calc(var(--spacing-unit) * 3);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: calc(var(--spacing-unit) * 2);
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.card-badges {
    display: flex;
    gap: calc(var(--spacing-unit) * 1);
    flex-wrap: wrap;
    flex: 1;
}

/* Wishlist button inline style */
.wishlist-btn-inline {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.wishlist-btn-inline:hover {
    transform: scale(1.1);
    border-color: #f56565;
    background: #fff5f5;
}

.wishlist-btn-inline.active {
    border-color: #f56565;
    background: #fff5f5;
}

.wishlist-icon {
    font-size: 1.2rem;
    line-height: 1;
    transition: transform 0.2s ease;
}

.wishlist-btn-inline:active .wishlist-icon {
    transform: scale(0.9);
}

.wishlist-btn-inline.active .wishlist-icon {
    animation: heartBeat 0.5s ease;
}

@keyframes heartBeat {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(1.1); }
    75% { transform: scale(1.2); }
}

.travel-meta {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 1);
    margin-bottom: calc(var(--spacing-unit) * 2);
    font-size: 0.9rem;
    color: var(--text-medium);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 1);
}

.meta-item .icon {
    font-size: 1.1rem;
}

.travel-types {
    display: flex;
    gap: calc(var(--spacing-unit) * 1);
    flex-wrap: wrap;
}

.btn-sm {
    padding: calc(var(--spacing-unit) * 1) calc(var(--spacing-unit) * 2);
    font-size: 0.9rem;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background-color: var(--success-color);
    color: white;
    border-radius: 50%;
    font-size: 0.7rem;
    margin-left: calc(var(--spacing-unit) * 0.5);
}

.star-rating {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 0.9rem;
}

.star {
    color: var(--warning-color);
}

.star.empty {
    color: var(--border-color);
}

.rating-value {
    margin-left: calc(var(--spacing-unit) * 0.5);
    color: var(--text-light);
    font-size: 0.85rem;
}

.organizer-details {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 0.5);
}

.organizer-name {
    display: flex;
    align-items: center;
}

.organizer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
    transition: opacity 0.2s;
}

.organizer-info:hover {
    opacity: 0.8;
}

.organizer-info:hover .organizer-name {
    color: var(--primary-color);
}

.travel-details-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.travel-details-link:hover {
    color: var(--secondary-color);
    text-decoration: underline;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Wishlist toggle - delegate to handle dynamically loaded cards
    $(document).on('click', '.wishlist-btn, .wishlist-btn-inline', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(this);
        const travelId = $btn.data('travel-id');
        const isActive = $btn.hasClass('active');

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
                    // Toggle icon and class
                    if (isActive) {
                        $btn.removeClass('active');
                        $btn.find('.wishlist-icon').text('🤍');
                        $btn.attr('title', 'Aggiungi alla wishlist');
                    } else {
                        $btn.addClass('active');
                        $btn.find('.wishlist-icon').text('❤️');
                        $btn.attr('title', 'Rimuovi dalla wishlist');
                    }

                    // Show brief feedback
                    const message = isActive ? 'Rimosso dalla wishlist' : 'Aggiunto alla wishlist';
                    if (typeof cdv_show_notification === 'function') {
                        cdv_show_notification(message, 'success');
                    }
                }
            },
            error: function() {
                alert('Errore durante l\'operazione. Riprova.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
