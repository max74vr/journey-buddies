<?php
/**
 * Template for standard pages
 */

get_header();
?>

<main class="site-main page-content">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('page-article'); ?>>
                <header class="page-header">
                    <h1 class="page-title"><?php the_title(); ?></h1>

                    <?php if (get_the_excerpt()) : ?>
                        <div class="page-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="page-featured-image">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>

                <div class="page-content-body">
                    <?php the_content(); ?>

                    <?php
                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . __('Pages:', 'compagni-di-viaggi'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <?php if (comments_open() || get_comments_number()) : ?>
                    <div class="page-comments">
                        <?php comments_template(); ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<style>
.page-content {
    padding: 3rem 0;
    background: #fff;
}

.page-article {
    max-width: 900px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.page-title {
    font-size: 2.5rem;
    margin: 0 0 1rem 0;
    color: #333;
    line-height: 1.2;
}

.page-excerpt {
    font-size: 1.25rem;
    color: #666;
    line-height: 1.6;
}

.page-featured-image {
    margin-bottom: 2rem;
    border-radius: 12px;
    overflow: hidden;
}

.page-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

.page-content-body {
    font-size: 1.125rem;
    line-height: 1.8;
    color: #444;
}

.page-content-body h2 {
    font-size: 2rem;
    margin: 2.5rem 0 1.5rem 0;
    color: #333;
}

.page-content-body h3 {
    font-size: 1.5rem;
    margin: 2rem 0 1rem 0;
    color: #333;
}

.page-content-body h4 {
    font-size: 1.25rem;
    margin: 1.5rem 0 1rem 0;
    color: #333;
}

.page-content-body p {
    margin: 0 0 1.5rem 0;
}

.page-content-body ul,
.page-content-body ol {
    margin: 0 0 1.5rem 2rem;
    padding: 0;
}

.page-content-body li {
    margin-bottom: 0.5rem;
}

.page-content-body blockquote {
    margin: 2rem 0;
    padding: 1.5rem 2rem;
    background: #f8f9fa;
    border-left: 4px solid var(--primary-color);
    font-style: italic;
    color: #666;
}

.page-content-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.page-content-body a {
    color: var(--primary-color);
    text-decoration: underline;
}

.page-content-body a:hover {
    color: var(--accent-color);
}

.page-content-body table {
    width: 100%;
    margin: 2rem 0;
    border-collapse: collapse;
}

.page-content-body table th,
.page-content-body table td {
    padding: 0.75rem;
    border: 1px solid #ddd;
    text-align: left;
}

.page-content-body table th {
    background: #f8f9fa;
    font-weight: bold;
}

.page-content-body code {
    background: #f4f4f4;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.9em;
}

.page-content-body pre {
    background: #f4f4f4;
    padding: 1.5rem;
    border-radius: 8px;
    overflow-x: auto;
    margin: 1.5rem 0;
}

.page-content-body pre code {
    background: none;
    padding: 0;
}

.page-links {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.page-links a {
    display: inline-block;
    padding: 0.5rem 1rem;
    margin: 0.25rem;
    background: #f8f9fa;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
}

.page-links a:hover {
    background: var(--primary-color);
    color: white;
}

.page-comments {
    margin-top: 4rem;
    padding-top: 3rem;
    border-top: 2px solid #eee;
}

/* Responsive */
@media (max-width: 768px) {
    .page-content {
        padding: 2rem 0;
    }

    .page-title {
        font-size: 2rem;
    }

    .page-excerpt {
        font-size: 1.125rem;
    }

    .page-content-body {
        font-size: 1rem;
    }

    .page-content-body h2 {
        font-size: 1.75rem;
    }

    .page-content-body h3 {
        font-size: 1.35rem;
    }
}

/* Sidebar layout (optional - can be enabled with custom fields) */
body.page-template-default.has-sidebar .page-article {
    max-width: 100%;
}

body.page-template-default.has-sidebar .page-content > .container {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 3rem;
    max-width: 1200px;
}

@media (max-width: 992px) {
    body.page-template-default.has-sidebar .page-content > .container {
        grid-template-columns: 1fr;
    }
}

/* Special page styling for content types */
.page-content-body .button,
.page-content-body .btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: background 0.3s;
}

.page-content-body .button:hover,
.page-content-body .btn:hover {
    background: var(--accent-color);
}

.page-content-body .info-box {
    background: #e7f3ff;
    border-left: 4px solid #2196f3;
    padding: 1.5rem;
    margin: 2rem 0;
    border-radius: 8px;
}

.page-content-body .warning-box {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 1.5rem;
    margin: 2rem 0;
    border-radius: 8px;
}

.page-content-body .success-box {
    background: #d4edda;
    border-left: 4px solid #28a745;
    padding: 1.5rem;
    margin: 2rem 0;
    border-radius: 8px;
}

/* WordPress Blocks Support */
.page-content-body .wp-block-image {
    margin: 2rem 0;
}

.page-content-body .wp-block-quote {
    margin: 2rem 0;
    padding: 1.5rem 2rem;
    background: #f8f9fa;
    border-left: 4px solid var(--primary-color);
}

.page-content-body .wp-block-columns {
    margin: 2rem 0;
}

.page-content-body .wp-block-button__link {
    background: var(--primary-color);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
}

/* Alignment classes */
.page-content-body .alignleft {
    float: left;
    margin: 0.5rem 2rem 1rem 0;
}

.page-content-body .alignright {
    float: right;
    margin: 0.5rem 0 1rem 2rem;
}

.page-content-body .aligncenter {
    display: block;
    margin-left: auto;
    margin-right: auto;
}

.page-content-body .alignwide {
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
}

.page-content-body .alignfull {
    width: 100vw;
    max-width: 100vw;
    margin-left: calc(50% - 50vw);
}
</style>

<?php get_footer(); ?>
