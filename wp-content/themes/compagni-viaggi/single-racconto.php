<?php
/**
 * Single Story (Racconto) Template
 */

get_header();

while (have_posts()) : the_post();
    // Increment views
    CDV_Travel_Stories::increment_views(get_the_ID());

    $author_id = get_the_author_meta('ID');
    $destination = get_post_meta(get_the_ID(), 'cdv_destination', true);
    $travel_date = get_post_meta(get_the_ID(), 'cdv_travel_date', true);
    $duration = get_post_meta(get_the_ID(), 'cdv_duration', true);
    $stats = CDV_Travel_Stories::get_story_stats(get_the_ID());
    $categories = get_the_terms(get_the_ID(), 'categoria_racconto');
    $tags = get_the_terms(get_the_ID(), 'tag_racconto');
    $profile_url = CDV_User_Profiles::get_profile_url($author_id);
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('single-story'); ?>>
        <!-- Hero Image -->
        <?php if (has_post_thumbnail()) : ?>
            <div class="story-hero" style="height: 500px; position: relative; overflow: hidden;">
                <?php the_post_thumbnail('full', array('style' => 'width: 100%; height: 100%; object-fit: cover;')); ?>
                <div class="story-hero-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.6));"></div>

                <div class="story-hero-content" style="position: absolute; bottom: 0; left: 0; right: 0; padding: calc(var(--spacing-unit) * 5) 0;">
                    <div class="container">
                        <?php if (!empty($categories)) : ?>
                            <span class="story-category-badge" style="display: inline-block; background: white; color: var(--primary-color); padding: calc(var(--spacing-unit) * 0.75) calc(var(--spacing-unit) * 2); border-radius: 20px; font-weight: 600; margin-bottom: calc(var(--spacing-unit) * 2);">
                                <?php echo esc_html($categories[0]->name); ?>
                            </span>
                        <?php endif; ?>
                        <h1 style="color: white; margin-bottom: calc(var(--spacing-unit) * 2); font-size: 3rem; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                            <?php the_title(); ?>
                        </h1>
                        <?php if ($destination) : ?>
                            <p style="color: white; font-size: 1.3rem; margin: 0; opacity: 0.95;">
                                📍 <?php echo esc_html($destination); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="story-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); padding: calc(var(--spacing-unit) * 8) 0; color: white; text-align: center;">
                <div class="container">
                    <?php if (!empty($categories)) : ?>
                        <span class="story-category-badge" style="display: inline-block; background: white; color: var(--primary-color); padding: calc(var(--spacing-unit) * 0.75) calc(var(--spacing-unit) * 2); border-radius: 20px; font-weight: 600; margin-bottom: calc(var(--spacing-unit) * 2);">
                            <?php echo esc_html($categories[0]->name); ?>
                        </span>
                    <?php endif; ?>
                    <h1 style="color: white;"><?php the_title(); ?></h1>
                    <?php if ($destination) : ?>
                        <p style="color: white; font-size: 1.3rem; margin: calc(var(--spacing-unit) * 2) 0 0;">
                            📍 <?php echo esc_html($destination); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="container" style="max-width: 850px;">
            <div class="section">
                <!-- Author & Meta -->
                <div class="story-meta-header" style="display: flex; justify-content: space-between; align-items: center; padding: calc(var(--spacing-unit) * 3) 0; border-bottom: 2px solid var(--border-color);">
                    <a href="<?php echo esc_url($profile_url); ?>" class="author-info" style="display: flex; align-items: center; gap: calc(var(--spacing-unit) * 2); text-decoration: none; color: inherit;">
                        <?php echo get_avatar($author_id, 60, '', '', array('style' => 'border-radius: 50%;')); ?>
                        <div>
                            <div style="font-weight: 600; font-size: 1.1rem; color: var(--text-dark);">
                                <?php echo esc_html(get_the_author()); ?>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-light);">
                                <?php echo get_the_date(); ?>
                            </div>
                        </div>
                    </a>

                    <div class="story-stats" style="display: flex; gap: calc(var(--spacing-unit) * 3); color: var(--text-light);">
                        <span title="Views">
                            👁 <?php echo number_format_i18n($stats['views']); ?>
                        </span>
                        <span title="Comments">
                            💬 <?php echo number_format_i18n($stats['comments']); ?>
                        </span>
                    </div>
                </div>

                <!-- Trip Details -->
                <?php if ($travel_date || $duration) : ?>
                    <div class="trip-details" style="display: flex; gap: calc(var(--spacing-unit) * 4); padding: calc(var(--spacing-unit) * 3) 0; background: #f8f9fa; border-radius: 8px; margin: calc(var(--spacing-unit) * 4) 0; padding: calc(var(--spacing-unit) * 3);">
                        <?php if ($travel_date) : ?>
                            <div>
                                <div style="color: var(--text-light); font-size: 0.9rem; margin-bottom: calc(var(--spacing-unit) * 0.5);">When</div>
                                <div style="font-weight: 600; font-size: 1.1rem;">
                                    📅 <?php
                                    $date = DateTime::createFromFormat('Y-m', $travel_date);
                                    echo $date ? $date->format('F Y') : $travel_date;
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($duration) : ?>
                            <div>
                                <div style="color: var(--text-light); font-size: 0.9rem; margin-bottom: calc(var(--spacing-unit) * 0.5);">Duration</div>
                                <div style="font-weight: 600; font-size: 1.1rem;">
                                    ⏱ <?php echo esc_html($duration); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Story Content -->
                <div class="story-content" style="font-size: 1.1rem; line-height: 1.8; color: var(--text-dark); margin: calc(var(--spacing-unit) * 4) 0;">
                    <?php the_content(); ?>
                </div>

                <!-- Tags -->
                <?php if (!empty($tags)) : ?>
                    <div class="story-tags" style="padding: calc(var(--spacing-unit) * 3) 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                        <strong style="margin-right: calc(var(--spacing-unit) * 2);">Tags:</strong>
                        <?php foreach ($tags as $tag) : ?>
                            <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="tag-badge" style="display: inline-block; background: #f0f0f0; color: var(--text-dark); padding: calc(var(--spacing-unit) * 0.75) calc(var(--spacing-unit) * 1.5); border-radius: 20px; text-decoration: none; margin: calc(var(--spacing-unit) * 0.5); font-size: 0.9rem;">
                                #<?php echo esc_html($tag->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Edit/Delete for Author -->
                <?php if (get_current_user_id() == $author_id || current_user_can('edit_others_posts')) : ?>
                    <div class="story-actions" style="margin: calc(var(--spacing-unit) * 4) 0; padding: calc(var(--spacing-unit) * 3); background: #f8f9fa; border-radius: 8px; display: flex; gap: calc(var(--spacing-unit) * 2);">
                        <a href="<?php echo esc_url(add_query_arg('story_id', get_the_ID(), home_url('/racconta-viaggio'))); ?>" class="btn-secondary">
                            ✏️ Edit Story
                        </a>
                        <button type="button" id="delete-story-btn" class="btn-danger" data-story-id="<?php echo esc_attr(get_the_ID()); ?>">
                            🗑️ Delete Story
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Comments -->
                <?php
                if (comments_open() || get_comments_number()) {
                    comments_template();
                }
                ?>

                <!-- Related Stories -->
                <?php
                $related_args = array(
                    'post_type' => 'racconto',
                    'posts_per_page' => 3,
                    'post__not_in' => array(get_the_ID()),
                    'orderby' => 'rand',
                );

                if ($destination) {
                    $related_args['meta_query'] = array(
                        array(
                            'key' => 'cdv_destination',
                            'value' => $destination,
                            'compare' => '=',
                        ),
                    );
                }

                $related_stories = new WP_Query($related_args);

                if ($related_stories->have_posts()) :
                ?>
                    <div class="related-stories" style="margin-top: calc(var(--spacing-unit) * 8); padding-top: calc(var(--spacing-unit) * 4); border-top: 2px solid var(--border-color);">
                        <h2 style="margin-bottom: calc(var(--spacing-unit) * 4);">Other Stories You Might Like</h2>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: calc(var(--spacing-unit) * 3);">
                            <?php
                            while ($related_stories->have_posts()) : $related_stories->the_post();
                                get_template_part('template-parts/content', 'story-card');
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </article>

    <script>
    jQuery(document).ready(function($) {
        $('#delete-story-btn').on('click', function() {
            if (!confirm('Are you sure you want to delete this story? This action cannot be undone.')) {
                return;
            }

            const storyId = $(this).data('story-id');
            const btn = $(this);

            btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'cdv_delete_story',
                    nonce: '<?php echo wp_create_nonce('cdv_ajax_nonce'); ?>',
                    story_id: storyId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.href = '<?php echo esc_url(home_url('/dashboard')); ?>';
                    } else {
                        alert(response.data.message);
                        btn.prop('disabled', false).text('🗑️ Delete Story');
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                    btn.prop('disabled', false).text('🗑️ Delete Story');
                }
            });
        });
    });
    </script>

    <style>
    .btn-danger {
        background: #dc3545;
        color: white;
        border: none;
        padding: calc(var(--spacing-unit) * 1.5) calc(var(--spacing-unit) * 3);
        border-radius: 6px;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    @media (max-width: 768px) {
        .story-hero {
            height: 350px !important;
        }

        h1 {
            font-size: 2rem !important;
        }

        .story-meta-header {
            flex-direction: column !important;
            gap: calc(var(--spacing-unit) * 2);
            align-items: flex-start !important;
        }

        .trip-details {
            flex-direction: column !important;
        }
    }
    </style>

    <?php
endwhile;

get_footer();
?>
