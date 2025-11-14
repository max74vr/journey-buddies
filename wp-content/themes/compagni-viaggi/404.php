<?php
/**
 * 404 Error Page Template
 */

get_header();
?>

<main class="site-main error-404-page">
    <div class="container">
        <div class="error-404-content">
            <div class="error-icon">🧭</div>

            <h1 class="error-title">404 - Page Not Found</h1>

            <p class="error-message">
                Oops! The page you're looking for seems to have disappeared like a traveler on an expedition.
            </p>

            <div class="error-suggestions">
                <h2>What you can do:</h2>

                <div class="suggestions-grid">
                    <div class="suggestion-card">
                        <div class="suggestion-icon">🏠</div>
                        <h3>Go to Homepage</h3>
                        <p>Start from the homepage and discover our journeys</p>
                        <a href="<?php echo home_url(); ?>" class="btn btn-primary">
                            Go to Home
                        </a>
                    </div>

                    <div class="suggestion-card">
                        <div class="suggestion-icon">🌍</div>
                        <h3>Explore Journeys</h3>
                        <p>Find companions for your next adventure</p>
                        <a href="<?php echo get_post_type_archive_link('viaggio'); ?>" class="btn btn-secondary">
                            Discover Journeys
                        </a>
                    </div>

                    <div class="suggestion-card">
                        <div class="suggestion-icon">🔍</div>
                        <h3>Search</h3>
                        <p>Use search to find what you're looking for</p>
                        <form role="search" method="get" class="search-form" action="<?php echo home_url('/'); ?>">
                            <input type="search" class="search-field" placeholder="Search..." name="s">
                            <button type="submit" class="search-submit">Search</button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (is_user_logged_in()) : ?>
                <div class="user-actions">
                    <p>Or go to your dashboard:</p>
                    <a href="<?php echo home_url('/dashboard/'); ?>" class="btn btn-outline">
                        Go to Dashboard
                    </a>
                </div>
            <?php else : ?>
                <div class="user-actions">
                    <p>Don't have an account yet?</p>
                    <a href="<?php echo home_url('/registrazione/'); ?>" class="btn btn-outline">
                        Register for Free
                    </a>
                </div>
            <?php endif; ?>

            <!-- Popular Links -->
            <div class="popular-links">
                <h3>Popular Links</h3>
                <ul>
                    <li><a href="<?php echo home_url(); ?>">Homepage</a></li>
                    <li><a href="<?php echo get_post_type_archive_link('viaggio'); ?>">All Journeys</a></li>
                    <?php if (!is_user_logged_in()) : ?>
                        <li><a href="<?php echo home_url('/registrazione/'); ?>">Registration</a></li>
                        <li><a href="<?php echo wp_login_url(); ?>">Log In</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo home_url('/chi-siamo/'); ?>">About Us</a></li>
                    <li><a href="<?php echo home_url('/contatti/'); ?>">Contact</a></li>
                </ul>
            </div>
        </div>
    </div>
</main>

<style>
.error-404-page {
    padding: 4rem 0;
    min-height: calc(100vh - 200px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.error-404-content {
    background: white;
    border-radius: 16px;
    padding: 4rem 3rem;
    max-width: 1000px;
    margin: 0 auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    text-align: center;
}

.error-icon {
    font-size: 6rem;
    margin-bottom: 2rem;
    animation: spin 3s ease-in-out infinite;
}

@keyframes spin {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(10deg); }
    75% { transform: rotate(-10deg); }
}

.error-title {
    font-size: 3rem;
    margin: 0 0 1rem 0;
    color: #333;
}

.error-message {
    font-size: 1.25rem;
    color: #666;
    margin-bottom: 3rem;
}

.error-suggestions {
    margin: 3rem 0;
}

.error-suggestions h2 {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    color: #333;
}

.suggestions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.suggestion-card {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 12px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.suggestion-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.suggestion-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.suggestion-card h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.suggestion-card p {
    color: #666;
    margin-bottom: 1.5rem;
}

.search-form {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.search-field {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.search-submit {
    padding: 0.75rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s;
}

.search-submit:hover {
    background: var(--accent-color);
}

.user-actions {
    margin: 3rem 0;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.user-actions p {
    margin-bottom: 1rem;
    color: #666;
}

.btn-outline {
    background: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
}

.popular-links {
    margin-top: 3rem;
    padding-top: 3rem;
    border-top: 2px solid #eee;
    text-align: left;
}

.popular-links h3 {
    margin-bottom: 1rem;
    color: #333;
}

.popular-links ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.popular-links li {
    margin: 0;
}

.popular-links a {
    display: block;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: background 0.3s, color 0.3s;
}

.popular-links a:hover {
    background: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .error-404-page {
        padding: 2rem 0;
    }

    .error-404-content {
        padding: 3rem 2rem;
    }

    .error-icon {
        font-size: 4rem;
    }

    .error-title {
        font-size: 2rem;
    }

    .error-message {
        font-size: 1.125rem;
    }

    .suggestions-grid {
        grid-template-columns: 1fr;
    }

    .search-form {
        flex-direction: column;
    }

    .popular-links ul {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>
