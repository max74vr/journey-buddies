<?php
/**
 * Template Name: Racconti
 * Description: Pagina archivio racconti di viaggio
 */

get_header();
?>

<main class="main-content">
    <!-- Hero Section -->
    <section class="page-hero" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: calc(var(--spacing-unit) * 8) 0; text-align: center;">
        <div class="container">
            <h1 style="color: white; margin-bottom: calc(var(--spacing-unit) * 2);">📖 Travel Stories</h1>
            <p style="font-size: 1.2rem; max-width: 700px; margin: 0 auto calc(var(--spacing-unit) * 4); opacity: 0.95;">
                Discover our travelers adventures, read their tips and get inspired for your next journey.
            </p>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(home_url('/racconta-viaggio')); ?>" class="btn-primary" style="background: white !important; color: var(--primary-color) !important; border: 2px solid white;">
                    ✍️ Tell Your Story
                </a>
            <?php endif; ?>
        </div>
    </section>

    <div class="container">
        <!-- Filters -->
        <div class="section" style="padding-top: calc(var(--spacing-unit) * 5);">
            <div class="stories-filters" style="display: flex; gap: calc(var(--spacing-unit) * 2); flex-wrap: wrap; margin-bottom: calc(var(--spacing-unit) * 4);">
                <?php
                $current_category = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
                $current_sort = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';

                $categories = get_terms(array(
                    'taxonomy' => 'categoria_racconto',
                    'hide_empty' => true,
                ));

                if (!empty($categories)) :
                ?>
                    <div class="filter-group">
                        <label for="story-category-filter" style="font-weight: 600; margin-right: calc(var(--spacing-unit) * 1);">Category:</label>
                        <select id="story-category-filter" class="form-control" style="display: inline-block; width: auto;">
                            <option value="">All</option>
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($current_category, $cat->slug); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="filter-group" style="margin-left: auto;">
                    <label for="story-sort-filter" style="font-weight: 600; margin-right: calc(var(--spacing-unit) * 1);">Sort by:</label>
                    <select id="story-sort-filter" class="form-control" style="display: inline-block; width: auto;">
                        <option value="date" <?php selected($current_sort, 'date'); ?>>Most recent</option>
                        <option value="views" <?php selected($current_sort, 'views'); ?>>Most viewed</option>
                        <option value="comments" <?php selected($current_sort, 'comments'); ?>>Most commented</option>
                    </select>
                </div>
            </div>

            <!-- Stories Grid -->
            <div class="stories-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: calc(var(--spacing-unit) * 4);">
                <?php
                // Build query args
                $args = array(
                    'post_type' => 'racconto',
                    'posts_per_page' => 12,
                    'post_status' => 'publish',
                    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                );

                // Filter by category
                if ($current_category) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'categoria_racconto',
                            'field' => 'slug',
                            'terms' => $current_category,
                        ),
                    );
                }

                // Sort by
                switch ($current_sort) {
                    case 'views':
                        $args['meta_key'] = 'cdv_story_views';
                        $args['orderby'] = 'meta_value_num';
                        $args['order'] = 'DESC';
                        break;
                    case 'comments':
                        $args['orderby'] = 'comment_count';
                        $args['order'] = 'DESC';
                        break;
                    default:
                        $args['orderby'] = 'date';
                        $args['order'] = 'DESC';
                        break;
                }

                $stories_query = new WP_Query($args);

                if ($stories_query->have_posts()) :
                    while ($stories_query->have_posts()) : $stories_query->the_post();
                        get_template_part('template-parts/content', 'story-card');
                    endwhile;
                else :
                ?>
                    <div class="no-stories" style="grid-column: 1 / -1; text-align: center; padding: calc(var(--spacing-unit) * 8) 0;">
                        <p style="font-size: 1.2rem; color: var(--text-medium);">
                            No stories available at the moment.
                        </p>
                        <?php if (is_user_logged_in()) : ?>
                            <a href="<?php echo esc_url(home_url('/racconta-viaggio')); ?>" class="btn-primary" style="margin-top: calc(var(--spacing-unit) * 3);">
                                Be the first to tell your story!
                            </a>
                        <?php endif; ?>
                    </div>
                <?php
                endif;
                ?>
            </div>

            <!-- Pagination -->
            <?php
            if ($stories_query->max_num_pages > 1) :
                echo '<div class="pagination" style="display: flex; justify-content: center; gap: calc(var(--spacing-unit) * 1); margin-top: calc(var(--spacing-unit) * 5); list-style: none; padding: 0;">';
                echo paginate_links(array(
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $stories_query->max_num_pages,
                    'prev_text' => '← Previous',
                    'next_text' => 'Next →',
                    'type' => 'list',
                ));
                echo '</div>';
            endif;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</main>

<script>
jQuery(document).ready(function($) {
    $('#story-category-filter').on('change', function() {
        const category = $(this).val();
        const sort = $('#story-sort-filter').val();
        let url = window.location.pathname;
        const params = new URLSearchParams();

        if (category) params.set('categoria', category);
        if (sort && sort !== 'date') params.set('orderby', sort);

        const queryString = params.toString();
        window.location.href = url + (queryString ? '?' + queryString : '');
    });

    $('#story-sort-filter').on('change', function() {
        const sort = $(this).val();
        const category = $('#story-category-filter').val();
        let url = window.location.pathname;
        const params = new URLSearchParams();

        if (category) params.set('categoria', category);
        if (sort && sort !== 'date') params.set('orderby', sort);

        const queryString = params.toString();
        window.location.href = url + (queryString ? '?' + queryString : '');
    });
});
</script>

<style>
.stories-grid {
    margin-bottom: calc(var(--spacing-unit) * 5);
}

.form-control {
    padding: calc(var(--spacing-unit) * 1) calc(var(--spacing-unit) * 1.5);
    border: 2px solid var(--border-color);
    border-radius: 6px;
    font-size: 1rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: calc(var(--spacing-unit) * 1);
    margin-top: calc(var(--spacing-unit) * 5);
    list-style: none;
    padding: 0;
}

.pagination .page-numbers {
    padding: calc(var(--spacing-unit) * 1.5) calc(var(--spacing-unit) * 2);
    border: 2px solid var(--border-color);
    border-radius: 6px;
    text-decoration: none;
    color: var(--text-dark);
    transition: all 0.2s;
}

.pagination .page-numbers:hover,
.pagination .page-numbers.current {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

@media (max-width: 768px) {
    .stories-grid {
        grid-template-columns: 1fr !important;
    }

    .stories-filters {
        flex-direction: column;
    }

    .filter-group {
        margin-left: 0 !important;
    }
}
</style>

<?php
get_footer();
?>
