<?php
/**
 * Blog Home Template
 * Main blog page (when "Posts page" is set in Reading Settings)
 */

get_header();
?>

<main class="site-main blog-home">
    <div class="container">
        <header class="blog-header">
            <h1 class="blog-title">Blog</h1>
            <p class="blog-description">
                Tips, stories and inspiration for your next journeys
            </p>
        </header>

        <?php if (have_posts()) : ?>

            <?php
            // Featured post (first post)
            $first_post = true;
            while (have_posts()) : the_post();
                if ($first_post) :
                    $first_post = false;
            ?>
                    <!-- Featured Post -->
                    <article id="post-<?php the_ID(); ?>" <?php post_class('featured-post'); ?>>
                        <div class="featured-post-layout">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>" class="featured-post-image">
                                    <?php the_post_thumbnail('large'); ?>
                                </a>
                            <?php endif; ?>

                            <div class="featured-post-content">
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

                                <h2 class="featured-post-title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <div class="post-meta">
                                    <span class="post-author">
                                        <?php echo get_avatar(get_the_author_meta('ID'), 32); ?>
                                        <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                            <?php echo get_the_author(); ?>
                                        </a>
                                    </span>
                                    <span class="post-date">
                                        <?php echo get_the_date('d F Y'); ?>
                                    </span>
                                    <span class="post-reading-time">
                                        <?php echo cdv_reading_time(); ?> min read
                                    </span>
                                </div>

                                <div class="featured-post-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 40, '...'); ?>
                                </div>

                                <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                                    Read the full article →
                                </a>
                            </div>
                        </div>
                    </article>

                    <div class="section-divider">
                        <h3>Latest Articles</h3>
                    </div>

                    <div class="posts-grid">
            <?php
                else :
                    // Regular posts
            ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>" class="post-thumbnail">
                                <?php the_post_thumbnail('medium_large'); ?>
                            </a>
                        <?php endif; ?>

                        <div class="post-card-content">
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

                            <h3 class="post-card-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h3>

                            <div class="post-card-meta">
                                <span class="post-author">
                                    <?php echo get_avatar(get_the_author_meta('ID'), 24); ?>
                                    <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                        <?php echo get_the_author(); ?>
                                    </a>
                                </span>
                                <span class="post-date">
                                    <?php echo get_the_date('d M Y'); ?>
                                </span>
                                <span class="post-reading-time">
                                    <?php echo cdv_reading_time(); ?> min
                                </span>
                            </div>

                            <div class="post-excerpt">
                                <?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?>
                            </div>

                            <a href="<?php the_permalink(); ?>" class="read-more">
                                Read more →
                            </a>
                        </div>
                    </article>
            <?php
                endif;
            endwhile;
            ?>
            </div> <!-- .posts-grid -->

            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '← Newer articles',
                'next_text' => 'Older articles →',
                'class' => 'blog-pagination',
            ));
            ?>

        <?php else : ?>
            <div class="no-posts">
                <div class="no-posts-icon">✍️</div>
                <h2>The blog is coming soon!</h2>
                <p>We're preparing interesting content for you. Come back soon!</p>
                <a href="<?php echo home_url(); ?>" class="btn btn-primary">
                    Back to Home
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Categoriess Sidebar -->
    <aside class="blog-sidebar">
        <div class="sidebar-widget">
            <h3 class="widget-title">Categories</h3>
            <ul class="category-list">
                <?php
                $categories = get_categories(array(
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'hide_empty' => true,
                    'number' => 10,
                ));

                foreach ($categories as $category) :
                ?>
                    <li>
                        <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>">
                            <?php echo esc_html($category->name); ?>
                            <span class="category-count">(<?php echo $category->count; ?>)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="sidebar-widget">
            <h3 class="widget-title">Popular Tags</h3>
            <div class="tag-cloud">
                <?php
                $tags = get_tags(array(
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'number' => 15,
                ));

                foreach ($tags as $tag) :
                ?>
                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" class="tag-link">
                        <?php echo esc_html($tag->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</main>

<style>
.blog-home {
    padding: 3rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.blog-home .container {
    max-width: 1400px;
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 3rem;
}

.blog-header {
    grid-column: 1 / -1;
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.blog-title {
    font-size: 3rem;
    margin: 0 0 1rem 0;
    color: #333;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.blog-description {
    font-size: 1.25rem;
    color: #666;
    margin: 0;
}

/* Featured Post */
.featured-post {
    grid-column: 1 / -1;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 3rem;
}

.featured-post-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
}

.featured-post-image {
    display: block;
    height: 100%;
    min-height: 400px;
    overflow: hidden;
}

.featured-post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.featured-post:hover .featured-post-image img {
    transform: scale(1.05);
}

.featured-post-content {
    padding: 3rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.featured-post-title {
    font-size: 2.25rem;
    margin: 0 0 1.5rem 0;
    line-height: 1.3;
}

.featured-post-title a {
    color: #333;
    text-decoration: none;
}

.featured-post-title a:hover {
    color: var(--primary-color);
}

.featured-post-excerpt {
    font-size: 1.125rem;
    color: #666;
    line-height: 1.7;
    margin-bottom: 2rem;
}

.section-divider {
    grid-column: 1 / -1;
    text-align: center;
    margin: 2rem 0;
    position: relative;
}

.section-divider h3 {
    display: inline-block;
    padding: 0 2rem;
    background: #f8f9fa;
    position: relative;
    z-index: 1;
    font-size: 1.5rem;
    color: #666;
}

.section-divider::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 2px;
    background: #e0e0e0;
}

/* Posts Grid */
.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.post-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.post-thumbnail {
    display: block;
    overflow: hidden;
    height: 220px;
}

.post-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.post-card:hover .post-thumbnail img {
    transform: scale(1.05);
}

.post-card-content {
    padding: 1.75rem;
}

.post-category {
    margin-bottom: 1rem;
}

.post-category a {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: bold;
    text-transform: uppercase;
}

.post-card-title {
    margin: 0 0 1rem 0;
    font-size: 1.35rem;
    line-height: 1.3;
}

.post-card-title a {
    color: #333;
    text-decoration: none;
}

.post-card-title a:hover {
    color: var(--primary-color);
}

.post-meta,
.post-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
    color: #999;
    font-size: 0.875rem;
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
    color: #666;
    text-decoration: none;
}

.post-author a:hover {
    color: var(--primary-color);
}

.post-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.read-more {
    display: inline-block;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.read-more:hover {
    text-decoration: underline;
}

/* Sidebar */
.blog-sidebar {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.sidebar-widget {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.widget-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    color: #333;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f0f0f0;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    margin-bottom: 0.75rem;
}

.category-list a {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    color: #666;
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.3s, color 0.3s;
}

.category-list a:hover {
    background: #f8f9fa;
    color: var(--primary-color);
}

.category-count {
    color: #999;
    font-size: 0.875rem;
}

.tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag-link {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    background: #f8f9fa;
    color: #666;
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.875rem;
    transition: background 0.3s, color 0.3s;
}

.tag-link:hover {
    background: var(--primary-color);
    color: white;
}

/* Pagination */
.blog-pagination {
    grid-column: 1 / -1;
    margin-top: 2rem;
    text-align: center;
}

.blog-pagination .nav-links {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.blog-pagination a,
.blog-pagination .current {
    display: inline-block;
    padding: 0.75rem 1.25rem;
    background: white;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: background 0.3s, color 0.3s;
}

.blog-pagination a:hover,
.blog-pagination .current {
    background: var(--primary-color);
    color: white;
}

/* No Posts */
.no-posts {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.no-posts-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
}

.no-posts h2 {
    margin: 0 0 1rem 0;
    color: #333;
}

.no-posts p {
    color: #666;
    margin-bottom: 2rem;
}

/* Responsive */
@media (max-width: 992px) {
    .blog-home .container {
        grid-template-columns: 1fr;
    }

    .featured-post-layout {
        grid-template-columns: 1fr;
    }

    .featured-post-image {
        min-height: 300px;
    }

    .featured-post-content {
        padding: 2rem;
    }

    .featured-post-title {
        font-size: 1.75rem;
    }

    .blog-sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .blog-title {
        font-size: 2.25rem;
    }

    .blog-description {
        font-size: 1.125rem;
    }

    .posts-grid {
        grid-template-columns: 1fr;
    }

    .featured-post-image {
        min-height: 250px;
    }

    .featured-post-content {
        padding: 1.5rem;
    }
}
</style>

<?php get_footer(); ?>
