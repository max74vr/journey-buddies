<?php
/**
 * Single Post Template
 * For blog posts and articles
 */

get_header();
?>

<main class="site-main single-post">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('post-article'); ?>>
                <header class="post-header">
                    <?php
                    $categories = get_the_category();
                    if (!empty($categories)) :
                    ?>
                        <div class="post-category">
                            <a href="<?php echo esc_url(get_category_link($categories[0]->term_id)); ?>">
                                <?php echo esc_html($categories[0]->name); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <h1 class="post-title"><?php the_title(); ?></h1>

                    <div class="post-meta">
                        <span class="post-author">
                            <?php echo get_avatar(get_the_author_meta('ID'), 32); ?>
                            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                <?php echo get_the_author(); ?>
                            </a>
                        </span>
                        <span class="post-date">
                            <i class="icon-calendar"></i>
                            <?php echo get_the_date('d F Y'); ?>
                        </span>
                        <span class="post-reading-time">
                            <i class="icon-clock"></i>
                            <?php echo cdv_reading_time(); ?> min di lettura
                        </span>
                    </div>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-featured-image">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>

                <div class="post-content">
                    <?php the_content(); ?>

                    <?php
                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . __('Pagine:', 'compagni-di-viaggi'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>

                <footer class="post-footer">
                    <?php if (has_tag()) : ?>
                        <div class="post-tags">
                            <i class="icon-tag"></i>
                            <?php the_tags('', '', ''); ?>
                        </div>
                    <?php endif; ?>

                    <div class="post-share">
                        <span>Condividi:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="share-link facebook">
                            Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" class="share-link twitter">
                            Twitter
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="share-link linkedin">
                            LinkedIn
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>" target="_blank" class="share-link whatsapp">
                            WhatsApp
                        </a>
                    </div>
                </footer>

                <?php
                // Author box
                $author_id = get_the_author_meta('ID');
                $author_description = get_the_author_meta('description');
                if ($author_description) :
                ?>
                    <div class="author-box">
                        <div class="author-avatar">
                            <?php echo get_avatar($author_id, 80); ?>
                        </div>
                        <div class="author-info">
                            <h3 class="author-name">
                                <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>">
                                    <?php echo get_the_author(); ?>
                                </a>
                            </h3>
                            <p class="author-bio"><?php echo esc_html($author_description); ?></p>
                            <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>" class="author-link">
                                Altri articoli di <?php echo get_the_author(); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                // Related posts
                $related_args = array(
                    'post_type' => 'post',
                    'posts_per_page' => 3,
                    'post__not_in' => array(get_the_ID()),
                    'orderby' => 'rand',
                );

                if (!empty($categories)) {
                    $related_args['category__in'] = wp_list_pluck($categories, 'term_id');
                }

                $related_posts = new WP_Query($related_args);

                if ($related_posts->have_posts()) :
                ?>
                    <div class="related-posts">
                        <h3>Potrebbe Interessarti Anche</h3>
                        <div class="related-posts-grid">
                            <?php while ($related_posts->have_posts()) : $related_posts->the_post(); ?>
                                <article class="related-post-card">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <a href="<?php the_permalink(); ?>" class="related-post-image">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <div class="related-post-content">
                                        <h4>
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_title(); ?>
                                            </a>
                                        </h4>
                                        <div class="related-post-meta">
                                            <?php echo get_the_date('d M Y'); ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                // Comments
                if (comments_open() || get_comments_number()) :
                ?>
                    <div class="post-comments">
                        <?php comments_template(); ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<style>
.single-post {
    padding: 3rem 0;
    background: #fff;
}

.post-article {
    max-width: 800px;
    margin: 0 auto;
}

.post-header {
    margin-bottom: 2rem;
}

.post-category {
    margin-bottom: 1rem;
}

.post-category a {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: bold;
}

.post-title {
    font-size: 2.5rem;
    margin: 0 0 1.5rem 0;
    color: #333;
    line-height: 1.2;
}

.post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    align-items: center;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
    color: #666;
}

.post-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.post-author img {
    border-radius: 50%;
}

.post-author a {
    color: #333;
    text-decoration: none;
    font-weight: 500;
}

.post-author a:hover {
    color: var(--primary-color);
}

.post-featured-image {
    margin: 2rem 0;
    border-radius: 12px;
    overflow: hidden;
}

.post-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

.post-content {
    font-size: 1.125rem;
    line-height: 1.8;
    color: #444;
    margin: 2rem 0;
}

.post-content h2 {
    font-size: 2rem;
    margin: 2.5rem 0 1.5rem 0;
    color: #333;
}

.post-content h3 {
    font-size: 1.5rem;
    margin: 2rem 0 1rem 0;
    color: #333;
}

.post-content p {
    margin: 0 0 1.5rem 0;
}

.post-content a {
    color: var(--primary-color);
    text-decoration: underline;
}

.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.post-footer {
    margin: 3rem 0;
    padding: 2rem 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.post-tags {
    margin-bottom: 1.5rem;
}

.post-tags a {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    margin: 0.25rem;
    background: #f8f9fa;
    color: #666;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.875rem;
}

.post-tags a:hover {
    background: var(--primary-color);
    color: white;
}

.post-share {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.post-share span {
    font-weight: bold;
    color: #666;
}

.share-link {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.875rem;
    color: white;
    transition: opacity 0.3s;
}

.share-link:hover {
    opacity: 0.8;
}

.share-link.facebook { background: #3b5998; }
.share-link.twitter { background: #1da1f2; }
.share-link.linkedin { background: #0077b5; }
.share-link.whatsapp { background: #25d366; }

.author-box {
    display: flex;
    gap: 1.5rem;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
    margin: 3rem 0;
}

.author-avatar img {
    border-radius: 50%;
}

.author-info {
    flex: 1;
}

.author-name {
    margin: 0 0 0.5rem 0;
}

.author-name a {
    color: #333;
    text-decoration: none;
}

.author-name a:hover {
    color: var(--primary-color);
}

.author-bio {
    color: #666;
    margin-bottom: 1rem;
}

.author-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.related-posts {
    margin: 4rem 0;
}

.related-posts h3 {
    margin-bottom: 2rem;
    font-size: 1.75rem;
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.related-post-card {
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.related-post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.related-post-image {
    display: block;
}

.related-post-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.related-post-content {
    padding: 1.5rem;
}

.related-post-content h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
}

.related-post-content h4 a {
    color: #333;
    text-decoration: none;
}

.related-post-content h4 a:hover {
    color: var(--primary-color);
}

.related-post-meta {
    color: #999;
    font-size: 0.875rem;
}

.post-comments {
    margin-top: 4rem;
}

@media (max-width: 768px) {
    .single-post {
        padding: 2rem 0;
    }

    .post-title {
        font-size: 2rem;
    }

    .post-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .post-content {
        font-size: 1rem;
    }

    .post-share {
        flex-wrap: wrap;
    }

    .author-box {
        flex-direction: column;
        text-align: center;
    }

    .related-posts-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>
