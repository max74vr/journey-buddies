<?php
/**
 * The main template file
 */

get_header();
?>

<main class="site-main">
    <div class="container">
        <?php if (have_posts()) : ?>
            <header class="page-header">
                <?php
                the_archive_title('<h1 class="page-title">', '</h1>');
                the_archive_description('<div class="archive-description">', '</div>');
                ?>
            </header>

            <div class="grid">
                <?php
                while (have_posts()) : the_post();
                    if (get_post_type() === 'viaggio') {
                        get_template_part('template-parts/content', 'travel-card');
                    } else {
                        get_template_part('template-parts/content', get_post_type());
                    }
                endwhile;
                ?>
            </div>

            <?php cdv_pagination(); ?>

        <?php else : ?>
            <section class="no-results not-found">
                <header class="page-header">
                    <h1 class="page-title"><?php esc_html_e('No Results Found', 'compagni-viaggi'); ?></h1>
                </header>

                <div class="page-content">
                    <p><?php esc_html_e('Nothing was found. Try a different search.', 'compagni-viaggi'); ?></p>
                    <?php get_search_form(); ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
