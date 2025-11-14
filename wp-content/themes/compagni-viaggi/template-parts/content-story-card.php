<?php
/**
 * Template part for displaying story cards
 */

$author_id = get_the_author_meta('ID');
$destination = get_post_meta(get_the_ID(), 'cdv_destination', true);
$travel_date = get_post_meta(get_the_ID(), 'cdv_travel_date', true);
$duration = get_post_meta(get_the_ID(), 'cdv_duration', true);
$stats = CDV_Travel_Stories::get_story_stats(get_the_ID());
$categories = get_the_terms(get_the_ID(), 'categoria_racconto');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('story-card'); ?>>
    <a href="<?php the_permalink(); ?>" class="story-card-link">
        <?php if (has_post_thumbnail()) : ?>
            <div class="story-image">
                <?php the_post_thumbnail('travel-card', array('class' => 'story-img')); ?>
                <?php if (!empty($categories)) : ?>
                    <span class="story-category"><?php echo esc_html($categories[0]->name); ?></span>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="story-image story-image-placeholder" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 4rem;">📖</span>
            </div>
        <?php endif; ?>

        <div class="story-content">
            <h3 class="story-title"><?php the_title(); ?></h3>

            <?php if ($destination) : ?>
                <div class="story-destination">
                    <span class="icon">📍</span>
                    <?php echo esc_html($destination); ?>
                </div>
            <?php endif; ?>

            <div class="story-excerpt">
                <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
            </div>

            <div class="story-meta">
                <div class="story-author">
                    <?php echo get_avatar($author_id, 32); ?>
                    <span><?php echo esc_html(get_the_author()); ?></span>
                </div>

                <div class="story-stats">
                    <?php if ($stats['views'] > 0) : ?>
                        <span class="stat" title="Visualizzazioni">
                            <span class="icon">👁</span>
                            <?php echo number_format_i18n($stats['views']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($stats['comments'] > 0) : ?>
                        <span class="stat" title="Commenti">
                            <span class="icon">💬</span>
                            <?php echo number_format_i18n($stats['comments']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($travel_date || $duration) : ?>
                <div class="story-details">
                    <?php if ($travel_date) : ?>
                        <span class="detail">
                            <span class="icon">📅</span>
                            <?php
                            $date = DateTime::createFromFormat('Y-m', $travel_date);
                            echo $date ? $date->format('F Y') : $travel_date;
                            ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($duration) : ?>
                        <span class="detail">
                            <span class="icon">⏱</span>
                            <?php echo esc_html($duration); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </a>
</article>

<style>
.story-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.story-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.story-card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.story-image {
    position: relative;
    aspect-ratio: 4/3;
    overflow: hidden;
}

.story-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.story-card:hover .story-image img {
    transform: scale(1.05);
}

.story-category {
    position: absolute;
    top: calc(var(--spacing-unit) * 2);
    right: calc(var(--spacing-unit) * 2);
    background: rgba(255,255,255,0.95);
    padding: calc(var(--spacing-unit) * 0.5) calc(var(--spacing-unit) * 1.5);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary-color);
}

.story-content {
    padding: calc(var(--spacing-unit) * 3);
    flex: 1;
    display: flex;
    flex-direction: column;
}

.story-title {
    font-size: 1.3rem;
    margin: 0 0 calc(var(--spacing-unit) * 2) 0;
    color: var(--text-dark);
    line-height: 1.4;
}

.story-destination {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 0.5);
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: calc(var(--spacing-unit) * 2);
    font-size: 0.95rem;
}

.story-excerpt {
    color: var(--text-medium);
    line-height: 1.6;
    margin-bottom: calc(var(--spacing-unit) * 3);
    flex: 1;
}

.story-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: calc(var(--spacing-unit) * 2);
    border-top: 1px solid var(--border-color);
}

.story-author {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 1);
}

.story-author img {
    border-radius: 50%;
}

.story-author span {
    font-size: 0.9rem;
    color: var(--text-medium);
}

.story-stats {
    display: flex;
    gap: calc(var(--spacing-unit) * 2);
}

.stat {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 0.5);
    font-size: 0.9rem;
    color: var(--text-light);
}

.story-details {
    display: flex;
    gap: calc(var(--spacing-unit) * 2);
    margin-top: calc(var(--spacing-unit) * 2);
    padding-top: calc(var(--spacing-unit) * 2);
    border-top: 1px solid var(--border-color);
    font-size: 0.85rem;
    color: var(--text-light);
}

.detail {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 0.5);
}

.icon {
    font-size: 1rem;
}
</style>
