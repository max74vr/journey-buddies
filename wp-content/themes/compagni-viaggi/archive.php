<?php
/**
 * Archive Template
 * For categories, tags, dates, and other archives
 */

get_header();
?>

<main class="site-main blog-archive">
    <div class="container">
        <header class="archive-header">
            <?php
            // Archive title and description
            if (is_category()) :
                $category = get_queried_object();
                echo '<div class="archive-icon">📁</div>';
                echo '<h1 class="archive-title">Categoria: ' . esc_html($category->name) . '</h1>';
                if ($category->description) :
                    echo '<p class="archive-description">' . esc_html($category->description) . '</p>';
                endif;
            elseif (is_tag()) :
                $tag = get_queried_object();
                echo '<div class="archive-icon">🏷️</div>';
                echo '<h1 class="archive-title">Tag: ' . esc_html($tag->name) . '</h1>';
                if ($tag->description) :
                    echo '<p class="archive-description">' . esc_html($tag->description) . '</p>';
                endif;
            elseif (is_author()) :
                $author = get_queried_object();
                echo '<div class="author-archive-header">';
                echo get_avatar($author->ID, 120);
                echo '<div class="author-info">';
                echo '<h1 class="archive-title">Articoli di ' . esc_html($author->user_login) . '</h1>';
                if ($author->description) :
                    echo '<p class="archive-description">' . esc_html($author->description) . '</p>';
                endif;
                echo '</div>';
                echo '</div>';
            elseif (is_date()) :
                echo '<div class="archive-icon">📅</div>';
                if (is_day()) :
                    echo '<h1 class="archive-title">Archivio: ' . get_the_date('d F Y') . '</h1>';
                elseif (is_month()) :
                    echo '<h1 class="archive-title">Archivio: ' . get_the_date('F Y') . '</h1>';
                elseif (is_year()) :
                    echo '<h1 class="archive-title">Archivio: ' . get_the_date('Y') . '</h1>';
                endif;
            else :
                echo '<h1 class="archive-title">' . post_type_archive_title('', false) . '</h1>';
            endif;
            ?>

            <div class="archive-meta">
                <?php
                $total_posts = $wp_query->found_posts;
                echo '<span class="post-count">' . $total_posts . ' articol' . ($total_posts != 1 ? 'i' : 'o') . '</span>';
                ?>
            </div>
        </header>

        <?php if (have_posts()) : ?>
            <div class="posts-grid">
                <?php while (have_posts()) : the_post(); ?>
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

                            <h2 class="post-card-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

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
                                Leggi l'articolo →
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '← Precedente',
                'next_text' => 'Successivo →',
                'class' => 'blog-pagination',
            ));
            ?>

        <?php else : ?>
            <div class="no-posts">
                <div class="no-posts-icon">📝</div>
                <h2>Nessun articolo trovato</h2>
                <p>Non ci sono ancora articoli in questo archivio.</p>
                <a href="<?php echo home_url(); ?>" class="btn btn-primary">
                    Torna alla Home
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_sidebar(); ?>

<style>
.blog-archive {
    padding: 3rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.archive-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 3rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.archive-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.author-archive-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    text-align: left;
}

.author-archive-header img {
    border-radius: 50%;
    border: 4px solid var(--primary-color);
}

.archive-title {
    font-size: 2.5rem;
    margin: 0 0 1rem 0;
    color: #333;
}

.archive-description {
    font-size: 1.125rem;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
}

.archive-meta {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.post-count {
    color: #999;
    font-size: 0.875rem;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
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
    height: 250px;
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
    padding: 2rem;
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
    font-size: 1.5rem;
    line-height: 1.3;
}

.post-card-title a {
    color: #333;
    text-decoration: none;
}

.post-card-title a:hover {
    color: var(--primary-color);
}

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
    transition: gap 0.3s;
}

.read-more:hover {
    text-decoration: underline;
}

.blog-pagination {
    margin-top: 3rem;
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

.no-posts {
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

@media (max-width: 768px) {
    .blog-archive {
        padding: 2rem 0;
    }

    .archive-header {
        padding: 2rem 1.5rem;
    }

    .author-archive-header {
        flex-direction: column;
        text-align: center;
    }

    .archive-title {
        font-size: 2rem;
    }

    .posts-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .post-card-title {
        font-size: 1.25rem;
    }

    .post-card-meta {
        font-size: 0.8rem;
    }
}
</style>

<?php get_footer(); ?>
