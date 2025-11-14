<?php
/**
 * Archive template for Travel Stories (Racconti)
 */

get_header();
?>

<main class="main-content">
    <!-- Hero Section -->
    <section class="page-hero" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: calc(var(--spacing-unit) * 8) 0; text-align: center;">
        <div class="container">
            <h1 style="color: white; margin-bottom: calc(var(--spacing-unit) * 2);">📖 Racconti di Viaggio</h1>
            <p style="font-size: 1.2rem; max-width: 700px; margin: 0 auto calc(var(--spacing-unit) * 4); opacity: 0.95;">
                Scopri le avventure dei nostri viaggiatori, leggi i loro consigli e lasciati ispirare per il tuo prossimo viaggio.
            </p>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(home_url('/racconta-viaggio')); ?>" class="btn-primary" style="background: white !important; color: var(--primary-color) !important; border: 2px solid white;">
                    ✍️ Racconta il Tuo Viaggio
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
                $categories = get_terms(array(
                    'taxonomy' => 'categoria_racconto',
                    'hide_empty' => true,
                ));

                if (!empty($categories)) :
                ?>
                    <div class="filter-group">
                        <label for="story-category-filter" style="font-weight: 600; margin-right: calc(var(--spacing-unit) * 1);">Categoria:</label>
                        <select id="story-category-filter" class="form-control" style="display: inline-block; width: auto;">
                            <option value="">Tutte</option>
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($current_category, $cat->slug); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="filter-group" style="margin-left: auto;">
                    <label for="story-sort-filter" style="font-weight: 600; margin-right: calc(var(--spacing-unit) * 1);">Ordina:</label>
                    <select id="story-sort-filter" class="form-control" style="display: inline-block; width: auto;">
                        <option value="date">Più recenti</option>
                        <option value="views">Più visti</option>
                        <option value="comments">Più commentati</option>
                    </select>
                </div>
            </div>

            <!-- Stories Grid -->
            <div class="stories-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: calc(var(--spacing-unit) * 4);">
                <?php
                if (have_posts()) :
                    while (have_posts()) : the_post();
                        get_template_part('template-parts/content', 'story-card');
                    endwhile;
                else :
                ?>
                    <div class="no-stories" style="grid-column: 1 / -1; text-align: center; padding: calc(var(--spacing-unit) * 8) 0;">
                        <p style="font-size: 1.2rem; color: var(--text-medium);">
                            Nessun racconto disponibile al momento.
                        </p>
                        <?php if (is_user_logged_in()) : ?>
                            <a href="<?php echo esc_url(home_url('/racconta-viaggio')); ?>" class="btn-primary" style="margin-top: calc(var(--spacing-unit) * 3);">
                                Sii il primo a raccontare!
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php
            if (function_exists('the_posts_pagination')) {
                the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => '← Precedente',
                    'next_text' => 'Successivo →',
                    'before_page_number' => '<span class="screen-reader-text">Pagina </span>',
                ));
            }
            ?>
        </div>
    </div>
</main>

<script>
jQuery(document).ready(function($) {
    $('#story-category-filter').on('change', function() {
        const category = $(this).val();
        let url = '<?php echo esc_url(get_post_type_archive_link('racconto')); ?>';
        if (category) {
            url += '?categoria=' + category;
        }
        window.location.href = url;
    });

    $('#story-sort-filter').on('change', function() {
        const sort = $(this).val();
        let url = new URL(window.location.href);
        url.searchParams.set('orderby', sort);
        window.location.href = url.toString();
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
