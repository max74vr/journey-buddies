<?php
/**
 * Taxonomy template for Tipo Viaggio
 */

get_header();

// Get current taxonomy term
$term = get_queried_object();
?>

<main class="site-main">
    <div class="page-header">
        <div class="container">
            <?php if (is_search() && get_search_query()) : ?>
                <h1>Results for: "<?php echo esc_html(get_search_query()); ?>"</h1>
                <p>Found <strong><?php echo $wp_query->found_posts; ?></strong> journey<?php if ($wp_query->found_posts != 1) : ?>s<?php endif; ?></p>
            <?php else : ?>
                <h1><?php echo esc_html($term->name); ?></h1>
                <?php if ($term->description) : ?>
                    <p><?php echo esc_html($term->description); ?></p>
                <?php else : ?>
                    <p>Explore all <?php echo esc_html(strtolower($term->name)); ?> journeys</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="archive-layout">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar">
                <h3>Filter Journeys</h3>

                <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="filters-form">
                    <!-- Keep post_type during search -->
                    <input type="hidden" name="post_type" value="viaggio">

                    <div class="filters-form-scroll">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="s" value="<?php echo get_search_query(); ?>" placeholder="Destination...">
                        </div>

                    <div class="filter-group">
                        <label for="tipo_viaggio">Journey Type</label>
                        <select id="tipo_viaggio" name="tipo_viaggio">
                            <option value="">All</option>
                            <?php
                            $types = get_terms(array(
                                'taxonomy' => 'tipo_viaggio',
                                'hide_empty' => false,
                            ));
                            foreach ($types as $type) {
                                $selected = isset($_GET['tipo_viaggio']) && $_GET['tipo_viaggio'] === $type->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($type->slug) . '" ' . $selected . '>' . esc_html($type->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date_from">Starting from</label>
                        <input type="month" id="date_from" name="date_from" value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>" placeholder="Select month">
                    </div>

                    <div class="filter-group">
                        <label for="travel_status">Journey Status</label>
                        <select id="travel_status" name="travel_status">
                            <option value="">All</option>
                            <option value="open" <?php selected(isset($_GET['travel_status']) && $_GET['travel_status'] === 'open'); ?>>Open</option>
                            <option value="full" <?php selected(isset($_GET['travel_status']) && $_GET['travel_status'] === 'full'); ?>>Full</option>
                            <option value="closed" <?php selected(isset($_GET['travel_status']) && $_GET['travel_status'] === 'closed'); ?>>Closed</option>
                        </select>
                    </div>

                    <!-- Advanced Filters Section -->
                    <div class="filter-group">
                        <button type="button" class="filter-toggle-btn" id="toggle-advanced-filters">
                            <span>🔧 Advanced Filters</span>
                            <span class="toggle-icon">▼</span>
                        </button>
                    </div>

                    <div class="advanced-filters" id="advanced-filters-section" style="display: none;">
                        <div class="filter-group">
                            <label>Transportation</label>
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="transport[]" value="aereo" <?php checked(isset($_GET['transport']) && in_array('aereo', (array)$_GET['transport'])); ?>>
                                    ✈️ Plane
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="transport[]" value="treno" <?php checked(isset($_GET['transport']) && in_array('treno', (array)$_GET['transport'])); ?>>
                                    🚂 Train
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="transport[]" value="auto" <?php checked(isset($_GET['transport']) && in_array('auto', (array)$_GET['transport'])); ?>>
                                    🚗 Car
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="transport[]" value="bus" <?php checked(isset($_GET['transport']) && in_array('bus', (array)$_GET['transport'])); ?>>
                                    🚌 Bus
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="transport[]" value="nave" <?php checked(isset($_GET['transport']) && in_array('nave', (array)$_GET['transport'])); ?>>
                                    🚢 Ship
                                </label>
                            </div>
                        </div>

                        <div class="filter-group">
                            <label for="accommodation">Accommodation</label>
                            <select id="accommodation" name="accommodation">
                                <option value="">All</option>
                                <option value="hotel" <?php selected(isset($_GET['accommodation']) && $_GET['accommodation'] === 'hotel'); ?>>🏨 Hotel</option>
                                <option value="hostel" <?php selected(isset($_GET['accommodation']) && $_GET['accommodation'] === 'hostel'); ?>>🏠 Hostel</option>
                                <option value="appartamento" <?php selected(isset($_GET['accommodation']) && $_GET['accommodation'] === 'appartamento'); ?>>🏢 Apartment</option>
                                <option value="campeggio" <?php selected(isset($_GET['accommodation']) && $_GET['accommodation'] === 'campeggio'); ?>>⛺ Camping</option>
                                <option value="altro" <?php selected(isset($_GET['accommodation']) && $_GET['accommodation'] === 'altro'); ?>>Other</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="difficulty">Difficulty</label>
                            <select id="difficulty" name="difficulty">
                                <option value="">All</option>
                                <option value="facile" <?php selected(isset($_GET['difficulty']) && $_GET['difficulty'] === 'facile'); ?>>😊 Easy</option>
                                <option value="media" <?php selected(isset($_GET['difficulty']) && $_GET['difficulty'] === 'media'); ?>>😐 Medium</option>
                                <option value="difficile" <?php selected(isset($_GET['difficulty']) && $_GET['difficulty'] === 'difficile'); ?>>😓 Hard</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="meals">Meals Included</label>
                            <select id="meals" name="meals">
                                <option value="">All</option>
                                <option value="nessuno" <?php selected(isset($_GET['meals']) && $_GET['meals'] === 'nessuno'); ?>>None</option>
                                <option value="colazione" <?php selected(isset($_GET['meals']) && $_GET['meals'] === 'colazione'); ?>>Breakfast Only</option>
                                <option value="mezza_pensione" <?php selected(isset($_GET['meals']) && $_GET['meals'] === 'mezza_pensione'); ?>>Half Board</option>
                                <option value="pensione_completa" <?php selected(isset($_GET['meals']) && $_GET['meals'] === 'pensione_completa'); ?>>Full Board</option>
                                <option value="all_inclusive" <?php selected(isset($_GET['meals']) && $_GET['meals'] === 'all_inclusive'); ?>>All Inclusive</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="guide">Guide Type</label>
                            <select id="guide" name="guide">
                                <option value="">All</option>
                                <option value="nessuna" <?php selected(isset($_GET['guide']) && $_GET['guide'] === 'nessuna'); ?>>No Guide</option>
                                <option value="locale" <?php selected(isset($_GET['guide']) && $_GET['guide'] === 'locale'); ?>>Local Guide</option>
                                <option value="italiana" <?php selected(isset($_GET['guide']) && $_GET['guide'] === 'italiana'); ?>>Italian Guide</option>
                                <option value="organizzatore" <?php selected(isset($_GET['guide']) && $_GET['guide'] === 'organizzatore'); ?>>Organizer</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Budget per Person (€)</label>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <input type="number" name="budget_min" value="<?php echo isset($_GET['budget_min']) ? esc_attr($_GET['budget_min']) : ''; ?>" placeholder="Min €" min="0" style="width: 100%;">
                                <input type="number" name="budget_max" value="<?php echo isset($_GET['budget_max']) ? esc_attr($_GET['budget_max']) : ''; ?>" placeholder="Max €" min="0" style="width: 100%;">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label for="max_participants">Number of Participants</label>
                            <select id="max_participants" name="max_participants">
                                <option value="">All</option>
                                <option value="2-5" <?php selected(isset($_GET['max_participants']) && $_GET['max_participants'] === '2-5'); ?>>2-5 people</option>
                                <option value="6-10" <?php selected(isset($_GET['max_participants']) && $_GET['max_participants'] === '6-10'); ?>>6-10 people</option>
                                <option value="11-20" <?php selected(isset($_GET['max_participants']) && $_GET['max_participants'] === '11-20'); ?>>11-20 people</option>
                                <option value="20+" <?php selected(isset($_GET['max_participants']) && $_GET['max_participants'] === '20+'); ?>>More than 20</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Organizer Rating</label>
                            <select name="min_rating">
                                <option value="">All</option>
                                <option value="4.5" <?php selected(isset($_GET['min_rating']) && $_GET['min_rating'] === '4.5'); ?>>⭐ 4.5+ stars</option>
                                <option value="4.0" <?php selected(isset($_GET['min_rating']) && $_GET['min_rating'] === '4.0'); ?>>⭐ 4+ stars</option>
                                <option value="3.5" <?php selected(isset($_GET['min_rating']) && $_GET['min_rating'] === '3.5'); ?>>⭐ 3.5+ stars</option>
                                <option value="3.0" <?php selected(isset($_GET['min_rating']) && $_GET['min_rating'] === '3.0'); ?>>⭐ 3+ stars</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Journey Duration</label>
                            <select name="duration">
                                <option value="">All</option>
                                <option value="1-3" <?php selected(isset($_GET['duration']) && $_GET['duration'] === '1-3'); ?>>1-3 days</option>
                                <option value="4-7" <?php selected(isset($_GET['duration']) && $_GET['duration'] === '4-7'); ?>>4-7 days</option>
                                <option value="8-14" <?php selected(isset($_GET['duration']) && $_GET['duration'] === '8-14'); ?>>1-2 weeks</option>
                                <option value="15+" <?php selected(isset($_GET['duration']) && $_GET['duration'] === '15+'); ?>>More than 2 weeks</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="solo_posti_disponibili" value="1" <?php checked(isset($_GET['solo_posti_disponibili'])); ?>>
                                Only journeys with available spots
                            </label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label for="orderby">Sort by</label>
                        <select id="orderby" name="orderby">
                            <option value="date" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'date'); ?>>Most Recent</option>
                            <option value="start_date" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'start_date'); ?>>Departure Date</option>
                            <option value="budget_asc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'budget_asc'); ?>>Budget: Low → High</option>
                            <option value="budget_desc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'budget_desc'); ?>>Budget: High → Low</option>
                            <option value="participants" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'participants'); ?>>Available Spots</option>
                            <option value="rating" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'rating'); ?>>Organizer Rating</option>
                        </select>
                    </div>
                    </div>

                    <div class="filters-form-actions">
                        <button type="submit" class="btn-primary" style="width: 100%;">Apply Filters</button>

                        <?php if (!empty($_GET['s']) || !empty($_GET['tipo_viaggio']) || !empty($_GET['date_from']) ||
                                  !empty($_GET['budget_min']) || !empty($_GET['budget_max']) || !empty($_GET['max_participants']) ||
                                  !empty($_GET['travel_status']) || !empty($_GET['transport']) || !empty($_GET['accommodation']) ||
                                  !empty($_GET['difficulty']) || !empty($_GET['meals']) || !empty($_GET['guide']) ||
                                  !empty($_GET['min_rating']) || !empty($_GET['duration']) || !empty($_GET['solo_posti_disponibili']) ||
                                  (isset($_GET['orderby']) && $_GET['orderby'] !== 'date')) : ?>
                            <a href="<?php echo esc_url(get_post_type_archive_link('viaggio')); ?>" class="btn-secondary" style="width: 100%; text-align: center;">
                                Reset Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </aside>

            <!-- Travels Grid -->
            <div class="travels-content">
                <?php
                // Separate active and expired travels
                $active_travels = array();
                $expired_travels = array();
                $today = date('Y-m-d');

                if (have_posts()) :
                    while (have_posts()) : the_post();
                        $end_date = get_post_meta(get_the_ID(), 'cdv_end_date', true);
                        if ($end_date && $end_date < $today) {
                            $expired_travels[] = $post;
                        } else {
                            $active_travels[] = $post;
                        }
                    endwhile;
                    wp_reset_postdata();

                    // Get total count from query (not just current page)
                    global $wp_query;
                    $total_travels = $wp_query->found_posts;
                    ?>
                    <div class="results-header">
                        <p>
                            <?php echo $total_travels . ' ' . ($total_travels === 1 ? 'journey found' : 'journeys found'); ?>
                        </p>
                    </div>

                    <div class="grid">
                        <?php
                        // Show active travels first
                        foreach ($active_travels as $post) :
                            setup_postdata($post);
                            get_template_part('template-parts/content', 'travel-card');
                        endforeach;

                        // Show expired travels with badge
                        foreach ($expired_travels as $post) :
                            setup_postdata($post);
                            set_query_var('is_expired', true);
                            get_template_part('template-parts/content', 'travel-card');
                            set_query_var('is_expired', false);
                        endforeach;
                        wp_reset_postdata();
                        ?>
                    </div>

                    <?php cdv_pagination(); ?>

                <?php else : ?>
                    <div class="no-results">
                        <h2>No journeys found</h2>
                        <p>Try adjusting the search filters or <a href="<?php echo esc_url(get_post_type_archive_link('viaggio')); ?>">view all journeys</a>.</p>
                        <?php if (is_user_logged_in()) : ?>
                            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=viaggio')); ?>" class="btn-primary">
                                Create the First Journey
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
.page-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: calc(var(--spacing-unit) * 6) 0;
    text-align: center;
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.page-header h1 {
    color: white;
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.page-header p {
    font-size: 1.1rem;
    opacity: 0.95;
}

.archive-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: calc(var(--spacing-unit) * 4);
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.filters-sidebar {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: calc(var(--spacing-unit) * 10);
    max-height: calc(100vh - calc(var(--spacing-unit) * 12));
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.filters-sidebar h3 {
    margin: 0;
    padding: calc(var(--spacing-unit) * 3);
    padding-bottom: calc(var(--spacing-unit) * 2);
    border-bottom: 2px solid var(--primary-color);
    background: white;
    flex-shrink: 0;
}

.filters-form {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
}

.filters-form-scroll {
    flex: 1;
    overflow-y: auto;
    padding: calc(var(--spacing-unit) * 3);
    padding-bottom: calc(var(--spacing-unit) * 2);
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 2);
}

.filters-form-scroll::-webkit-scrollbar {
    width: 6px;
}

.filters-form-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.filters-form-scroll::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.filters-form-scroll::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.filters-form-actions {
    padding: calc(var(--spacing-unit) * 2) calc(var(--spacing-unit) * 3);
    background: white;
    border-top: 1px solid var(--border-color);
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 1.5);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 1);
}

.filter-group label {
    font-weight: 500;
    color: var(--text-medium);
    font-size: 0.9rem;
}

.results-header {
    margin-bottom: calc(var(--spacing-unit) * 3);
    padding-bottom: calc(var(--spacing-unit) * 2);
    border-bottom: 1px solid var(--border-color);
}

.results-header p {
    color: var(--text-medium);
    font-weight: 500;
}

.no-results {
    text-align: center;
    padding: calc(var(--spacing-unit) * 8) calc(var(--spacing-unit) * 3);
    background: white;
    border-radius: var(--border-radius);
}

@media (max-width: 768px) {
    .archive-layout {
        grid-template-columns: 1fr;
    }

    .filters-sidebar {
        position: static;
    }
}

/* Advanced Filters Styling */
.filter-toggle-btn {
    width: 100%;
    padding: calc(var(--spacing-unit) * 1.5);
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.filter-toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.filter-toggle-btn .toggle-icon {
    font-size: 0.8rem;
    transition: transform 0.3s ease;
}

.filter-toggle-btn.active .toggle-icon {
    transform: rotate(180deg);
}

.advanced-filters {
    margin-top: calc(var(--spacing-unit) * 2);
    padding-top: calc(var(--spacing-unit) * 2);
    border-top: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 2);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 1);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 1);
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--text-dark);
    padding: calc(var(--spacing-unit) * 0.5);
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

.checkbox-label:hover {
    background-color: #f5f5f5;
}

.checkbox-label input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-advanced-filters');
    const advancedSection = document.getElementById('advanced-filters-section');

    if (toggleBtn && advancedSection) {
        toggleBtn.addEventListener('click', function() {
            const isVisible = advancedSection.style.display !== 'none';

            if (isVisible) {
                advancedSection.style.display = 'none';
                toggleBtn.classList.remove('active');
            } else {
                advancedSection.style.display = 'block';
                toggleBtn.classList.add('active');
            }
        });

        // Check if any advanced filters are active on page load
        const urlParams = new URLSearchParams(window.location.search);
        const advancedFilters = ['transport[]', 'accommodation', 'difficulty', 'meals', 'guide', 'min_rating', 'duration', 'solo_posti_disponibili'];
        const hasActiveAdvancedFilters = advancedFilters.some(filter => {
            if (filter.includes('[]')) {
                const filterName = filter.replace('[]', '');
                return urlParams.getAll(filterName + '[]').length > 0;
            }
            return urlParams.has(filter);
        });

        // Auto-expand if advanced filters are active
        if (hasActiveAdvancedFilters) {
            advancedSection.style.display = 'block';
            toggleBtn.classList.add('active');
        }
    }
});
</script>

<?php
get_footer();
