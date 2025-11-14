<?php
/**
 * Search Results Template
 */

get_header();

$search_query = get_search_query();
?>

<main class="site-main search-results">
    <div class="container">
        <header class="search-header">
            <h1 class="search-title">
                Results for: "<span class="search-query"><?php echo esc_html($search_query); ?></span>"
            </h1>

            <?php if (have_posts()) : ?>
                <p class="search-count">
                    Found <strong><?php echo $wp_query->found_posts; ?></strong> result<?php echo $wp_query->found_posts != 1 ? 's' : ''; ?>
                </p>
            <?php endif; ?>
        </header>

        <?php if (have_posts()) : ?>

            <!-- Filters -->
            <div class="search-filters">
                <span class="filter-label">Filter by type:</span>
                <button class="filter-btn active" data-type="all">All</button>
                <button class="filter-btn" data-type="post">Posts</button>
                <button class="filter-btn" data-type="viaggio">Journeys</button>
                <button class="filter-btn" data-type="page">Pages</button>
            </div>

            <div class="search-results-list">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('search-result-item'); ?> data-post-type="<?php echo get_post_type(); ?>">
                        <div class="result-layout">
                            <?php
                            $has_thumbnail = has_post_thumbnail();
                            $taxonomy_image_url = false;

                            // Se non ha immagine e è un viaggio, cerca l'immagine del tipo di viaggio
                            if (!$has_thumbnail && get_post_type() === 'viaggio' && class_exists('CDV_Taxonomy_Images')) {
                                $travel_types = wp_get_post_terms(get_the_ID(), 'tipo_viaggio', array('fields' => 'ids'));
                                if (!empty($travel_types)) {
                                    // Ottieni immagine random se ci sono più tipi
                                    $taxonomy_image_url = CDV_Taxonomy_Images::get_random_term_image($travel_types, 'medium');
                                }
                            }
                            ?>

                            <?php if ($has_thumbnail) : ?>
                                <a href="<?php the_permalink(); ?>" class="result-thumbnail">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            <?php elseif ($taxonomy_image_url) : ?>
                                <a href="<?php the_permalink(); ?>" class="result-thumbnail">
                                    <img src="<?php echo esc_url($taxonomy_image_url); ?>" alt="<?php the_title_attribute(); ?>" />
                                </a>
                            <?php elseif (get_post_type() === 'viaggio') : ?>
                                <a href="<?php the_permalink(); ?>" class="result-thumbnail result-thumbnail-placeholder">
                                    <div class="placeholder-content">
                                        <span class="placeholder-icon">✈️</span>
                                    </div>
                                </a>
                            <?php endif; ?>

                            <div class="result-content">
                                <div class="result-header">
                                    <span class="result-type">
                                        <?php
                                        $post_type = get_post_type();
                                        $type_labels = array(
                                            'post' => '📝 Post',
                                            'viaggio' => '✈️ Journey',
                                            'page' => '📄 Page',
                                        );
                                        echo isset($type_labels[$post_type]) ? $type_labels[$post_type] : '📌 ' . ucfirst($post_type);
                                        ?>
                                    </span>

                                    <?php if (get_post_type() === 'post') : ?>
                                        <?php
                                        $categories = get_the_category();
                                        if (!empty($categories)) :
                                        ?>
                                            <span class="result-category">
                                                <a href="<?php echo esc_url(get_category_link($categories[0]->term_id)); ?>">
                                                    <?php echo esc_html($categories[0]->name); ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <h2 class="result-title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php
                                        // Highlight search term in title
                                        $title = get_the_title();
                                        if ($search_query) {
                                            $title = preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<mark>$1</mark>', $title);
                                        }
                                        echo $title;
                                        ?>
                                    </a>
                                </h2>

                                <div class="result-meta">
                                    <?php if (get_post_type() === 'post') : ?>
                                        <span class="result-author">
                                            <?php echo get_avatar(get_the_author_meta('ID'), 24); ?>
                                            <?php echo get_the_author(); ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="result-date">
                                        <?php echo get_the_date('d M Y'); ?>
                                    </span>

                                    <?php if (get_post_type() === 'viaggio') : ?>
                                        <?php
                                        $destination = get_post_meta(get_the_ID(), 'cdv_destination', true);
                                        if ($destination) :
                                        ?>
                                            <span class="result-destination">
                                                📍 <?php echo esc_html($destination); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="result-excerpt">
                                    <?php
                                    $excerpt = get_the_excerpt();
                                    // Highlight search term in excerpt
                                    if ($search_query) {
                                        $excerpt = preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<mark>$1</mark>', $excerpt);
                                    }
                                    echo wp_trim_words($excerpt, 30, '...');
                                    ?>
                                </div>

                                <a href="<?php the_permalink(); ?>" class="result-link">
                                    <?php echo get_post_type() === 'viaggio' ? 'View journey' : 'Read more'; ?> →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '← Previous',
                'next_text' => 'Next →',
                'class' => 'search-pagination',
            ));
            ?>

        <?php else : ?>
            <div class="no-results">
                <div class="no-results-icon">🤔</div>
                <h2>No Results Found</h2>
                <p>The search for "<strong><?php echo esc_html($search_query); ?></strong>" produced no results.</p>

                <div class="search-suggestions">
                    <h3>Suggestions:</h3>
                    <ul>
                        <li>Check that you've spelled the search terms correctly</li>
                        <li>Try different or more general keywords</li>
                        <li>Use fewer words in your search</li>
                        <li>Search for destinations or journey types</li>
                    </ul>
                </div>

                <div class="search-alternatives">
                    <h3>Or explore:</h3>
                    <div class="alternatives-grid">
                        <a href="<?php echo get_post_type_archive_link('viaggio'); ?>" class="alternative-card">
                            <span class="alternative-icon">✈️</span>
                            <h4>All Journeys</h4>
                            <p>Discover all available destinations</p>
                        </a>

                        <a href="<?php echo home_url('/blog/'); ?>" class="alternative-card">
                            <span class="alternative-icon">📝</span>
                            <h4>Blog</h4>
                            <p>Read articles and travel tips</p>
                        </a>

                        <a href="<?php echo home_url(); ?>" class="alternative-card">
                            <span class="alternative-icon">🏠</span>
                            <h4>Homepage</h4>
                            <p>Go back to the main page</p>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.search-results {
    padding: 3rem 0;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.search-header {
    padding: 2rem 0 1.5rem 0;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e0e0e0;
}

.search-title {
    font-size: 1.75rem;
    margin: 0 0 0.5rem 0;
    color: #333;
}

.search-query {
    color: var(--primary-color);
    font-weight: bold;
}

.search-count {
    color: #666;
    margin: 0;
    font-size: 0.95rem;
}

/* Filters */
.search-filters {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.filter-label {
    font-weight: 600;
    color: #666;
}

.filter-btn {
    padding: 0.5rem 1rem;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.3s;
}

.filter-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.filter-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Results List */
.search-results-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.search-result-item {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.search-result-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.result-layout {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 2rem;
}

.result-thumbnail {
    display: block;
    width: 200px;
    height: 200px;
    overflow: hidden;
}

.result-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.search-result-item:hover .result-thumbnail img {
    transform: scale(1.1);
}

.result-thumbnail-placeholder {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.result-thumbnail-placeholder .placeholder-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.result-thumbnail-placeholder .placeholder-icon {
    font-size: 3rem;
    opacity: 0.8;
}

/* Adatta layout quando non c'è thumbnail */
.result-layout:has(> :first-child:not(.result-thumbnail)) {
    grid-template-columns: 1fr;
}

.search-result-item:not(:has(.result-thumbnail)) .result-content {
    padding: 1.5rem 2rem;
}

.result-content {
    padding: 1.5rem 2rem 1.5rem 0;
}

.result-header {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 0.75rem;
}

.result-type {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #f0f0f0;
    color: #666;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
}

.result-category a {
    padding: 0.25rem 0.75rem;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
}

.result-title {
    margin: 0 0 0.75rem 0;
    font-size: 1.5rem;
    line-height: 1.3;
}

.result-title a {
    color: #333;
    text-decoration: none;
}

.result-title a:hover {
    color: var(--primary-color);
}

.result-title mark {
    background: #fff3cd;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
}

.result-meta {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    margin-bottom: 1rem;
    color: #999;
    font-size: 0.875rem;
}

.result-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.result-author img {
    border-radius: 50%;
}

.result-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.result-excerpt mark {
    background: #fff3cd;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
    font-weight: 600;
}

.result-link {
    display: inline-block;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.result-link:hover {
    text-decoration: underline;
}

/* No Results */
.no-results {
    background: white;
    border-radius: 12px;
    padding: 4rem 3rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.no-results-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
}

.no-results h2 {
    margin: 0 0 1rem 0;
    color: #333;
}

.no-results > p {
    color: #666;
    margin-bottom: 3rem;
    font-size: 1.125rem;
}

.search-suggestions {
    text-align: left;
    max-width: 600px;
    margin: 0 auto 3rem;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.search-suggestions h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.search-suggestions ul {
    margin: 0;
    padding-left: 2rem;
    color: #666;
}

.search-suggestions li {
    margin-bottom: 0.5rem;
}

.search-alternatives h3 {
    margin: 0 0 1.5rem 0;
    color: #333;
}

.alternatives-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    max-width: 800px;
    margin: 0 auto;
}

.alternative-card {
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
    text-decoration: none;
    color: #333;
    transition: transform 0.3s, box-shadow 0.3s;
}

.alternative-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.alternative-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 1rem;
}

.alternative-card h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.alternative-card p {
    margin: 0;
    color: #666;
    font-size: 0.875rem;
}

/* Pagination */
.search-pagination {
    margin-top: 3rem;
    text-align: center;
}

.search-pagination .nav-links {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.search-pagination a,
.search-pagination .current {
    display: inline-block;
    padding: 0.75rem 1.25rem;
    background: white;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: background 0.3s, color 0.3s;
}

.search-pagination a:hover,
.search-pagination .current {
    background: var(--primary-color);
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .search-results {
        padding: 1.5rem 0;
    }

    .search-header {
        padding: 1.5rem 0 1rem 0;
    }

    .search-title {
        font-size: 1.35rem;
    }

    .result-layout {
        grid-template-columns: 1fr;
    }

    .result-thumbnail {
        width: 100%;
        height: 200px;
    }

    .result-content {
        padding: 1.5rem;
    }

    .result-title {
        font-size: 1.25rem;
    }

    .result-meta {
        flex-wrap: wrap;
        gap: 1rem;
    }

    .alternatives-grid {
        grid-template-columns: 1fr;
    }

    .search-filters {
        justify-content: flex-start;
    }

    .filter-label {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const resultItems = document.querySelectorAll('.search-result-item');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filterType = this.dataset.type;

            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter results
            resultItems.forEach(item => {
                if (filterType === 'all') {
                    item.style.display = 'block';
                } else {
                    const itemType = item.dataset.postType;
                    item.style.display = itemType === filterType ? 'block' : 'none';
                }
            });
        });
    });
});
</script>

<?php get_footer(); ?>
