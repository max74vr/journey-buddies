<?php
/**
 * Template Name: Calendario Viaggi
 * Template per visualizzare i viaggi in formato calendario
 */

get_header();

// Get current month/year or from query params
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Ensure valid month/year
$current_month = max(1, min(12, $current_month));
$current_year = max(2020, min(2030, $current_year));

// Get travels for this month
global $wpdb;
$first_day = sprintf('%04d-%02d-01', $current_year, $current_month);
$last_day = date('Y-m-t', strtotime($first_day));

$travels = $wpdb->get_results($wpdb->prepare("
    SELECT p.ID, p.post_title, pm1.meta_value as start_date, pm2.meta_value as end_date,
           pm3.meta_value as destination, pm4.meta_value as travel_status
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'cdv_start_date'
    LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'cdv_end_date'
    LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'cdv_destination'
    LEFT JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = 'cdv_travel_status'
    WHERE p.post_type = 'viaggio'
    AND p.post_status = 'publish'
    AND (
        (pm1.meta_value >= %s AND pm1.meta_value <= %s)
        OR (pm2.meta_value >= %s AND pm2.meta_value <= %s)
        OR (pm1.meta_value < %s AND pm2.meta_value > %s)
    )
    ORDER BY pm1.meta_value ASC
", $first_day, $last_day, $first_day, $last_day, $first_day, $last_day));

// Organize travels by date
$travels_by_date = array();
foreach ($travels as $travel) {
    if (empty($travel->start_date)) continue;

    $date = date('Y-m-d', strtotime($travel->start_date));
    if (!isset($travels_by_date[$date])) {
        $travels_by_date[$date] = array();
    }
    $travels_by_date[$date][] = $travel;
}

// Calendar generation
$days_in_month = date('t', strtotime($first_day));
$first_day_of_week = date('N', strtotime($first_day)); // 1 (Monday) to 7 (Sunday)
$month_name = date_i18n('F Y', strtotime($first_day));

// Navigation
$prev_month = $current_month == 1 ? 12 : $current_month - 1;
$prev_year = $current_month == 1 ? $current_year - 1 : $current_year;
$next_month = $current_month == 12 ? 1 : $current_month + 1;
$next_year = $current_month == 12 ? $current_year + 1 : $current_year;

$prev_url = add_query_arg(array('month' => $prev_month, 'year' => $prev_year));
$next_url = add_query_arg(array('month' => $next_month, 'year' => $next_year));
$today_url = remove_query_arg(array('month', 'year'));
?>

<main class="site-main calendar-page">
    <div class="page-header">
        <div class="container">
            <h1>📅 Calendario Viaggi</h1>
            <p>Esplora i viaggi disponibili per data</p>
        </div>
    </div>

    <div class="container">
        <!-- Calendar Navigation -->
        <div class="calendar-nav">
            <a href="<?php echo esc_url($prev_url); ?>" class="btn btn-secondary">
                ← <?php echo date_i18n('F', strtotime($prev_year . '-' . $prev_month . '-01')); ?>
            </a>
            <h2><?php echo esc_html($month_name); ?></h2>
            <a href="<?php echo esc_url($next_url); ?>" class="btn btn-secondary">
                <?php echo date_i18n('F', strtotime($next_year . '-' . $next_month . '-01')); ?> →
            </a>
        </div>

        <div class="calendar-actions">
            <a href="<?php echo esc_url($today_url); ?>" class="btn btn-primary">Oggi</a>
            <a href="<?php echo get_post_type_archive_link('viaggio'); ?>" class="btn btn-secondary">Vista Lista</a>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid">
            <!-- Day headers -->
            <div class="calendar-day-header">Lun</div>
            <div class="calendar-day-header">Mar</div>
            <div class="calendar-day-header">Mer</div>
            <div class="calendar-day-header">Gio</div>
            <div class="calendar-day-header">Ven</div>
            <div class="calendar-day-header">Sab</div>
            <div class="calendar-day-header">Dom</div>

            <?php
            // Empty cells before first day
            for ($i = 1; $i < $first_day_of_week; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }

            // Days of month
            for ($day = 1; $day <= $days_in_month; $day++) {
                $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                $is_today = ($date == date('Y-m-d'));
                $is_past = (strtotime($date) < strtotime('today'));
                $has_travels = isset($travels_by_date[$date]);

                $classes = array('calendar-day');
                if ($is_today) $classes[] = 'today';
                if ($is_past) $classes[] = 'past';
                if ($has_travels) $classes[] = 'has-travels';

                echo '<div class="' . implode(' ', $classes) . '">';
                echo '<div class="day-number">' . $day . '</div>';

                if ($has_travels) {
                    echo '<div class="day-travels">';
                    $count = count($travels_by_date[$date]);
                    foreach ($travels_by_date[$date] as $index => $travel) {
                        if ($index < 2) { // Show max 2 travels per day
                            $status_class = 'status-' . ($travel->travel_status ?: 'open');
                            echo '<a href="' . get_permalink($travel->ID) . '" class="day-travel-item ' . $status_class . '" title="' . esc_attr($travel->post_title) . '">';
                            echo '<span class="travel-destination">' . esc_html($travel->destination ?: 'Viaggio') . '</span>';
                            echo '</a>';
                        }
                    }
                    if ($count > 2) {
                        echo '<div class="day-travel-more">+' . ($count - 2) . ' altri</div>';
                    }
                    echo '</div>';
                }

                echo '</div>';
            }

            // Fill remaining cells
            $total_cells = $first_day_of_week + $days_in_month - 1;
            $remaining = (7 - ($total_cells % 7)) % 7;
            for ($i = 0; $i < $remaining; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }
            ?>
        </div>

        <!-- Legend -->
        <div class="calendar-legend">
            <h3>Legenda</h3>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="legend-color today-color"></span> Oggi
                </div>
                <div class="legend-item">
                    <span class="legend-color has-travels-color"></span> Giorni con viaggi
                </div>
                <div class="legend-item">
                    <span class="legend-color status-open"></span> Aperto
                </div>
                <div class="legend-item">
                    <span class="legend-color status-full"></span> Completo
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.calendar-page {
    padding: 2rem 0 4rem;
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.page-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 3rem 0;
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    color: white;
    margin-bottom: 0.5rem;
}

.page-header p {
    font-size: 1.1rem;
    opacity: 0.95;
}

.calendar-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.calendar-nav h2 {
    margin: 0;
    color: #2d3748;
    font-size: 1.75rem;
}

.calendar-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.calendar-day-header {
    padding: 1rem;
    text-align: center;
    font-weight: 600;
    color: #667eea;
    background: #f7fafc;
    border-radius: 8px;
}

.calendar-day {
    min-height: 120px;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    transition: all 0.3s;
}

.calendar-day.empty {
    background: #f7fafc;
    border-color: transparent;
}

.calendar-day.past {
    opacity: 0.6;
}

.calendar-day.today {
    border-color: #667eea;
    background: #eef2ff;
}

.calendar-day.has-travels {
    background: #f0fff4;
    border-color: #48bb78;
}

.calendar-day:not(.empty):hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.day-number {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.day-travels {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.day-travel-item {
    display: block;
    padding: 4px 8px;
    background: #667eea;
    color: white;
    font-size: 0.75rem;
    border-radius: 4px;
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background 0.2s;
}

.day-travel-item:hover {
    background: #5568d3;
}

.day-travel-item.status-full {
    background: #f56565;
}

.day-travel-item.status-closed {
    background: #718096;
}

.day-travel-more {
    font-size: 0.7rem;
    color: #718096;
    text-align: center;
    margin-top: 4px;
}

.calendar-legend {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.calendar-legend h3 {
    margin: 0 0 1rem 0;
    color: #2d3748;
}

.legend-items {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 2px solid #e2e8f0;
}

.legend-color.today-color {
    background: #eef2ff;
    border-color: #667eea;
}

.legend-color.has-travels-color {
    background: #f0fff4;
    border-color: #48bb78;
}

.legend-color.status-open {
    background: #667eea;
}

.legend-color.status-full {
    background: #f56565;
}

@media (max-width: 768px) {
    .calendar-grid {
        gap: 5px;
        padding: 1rem;
    }

    .calendar-day {
        min-height: 80px;
        padding: 0.5rem;
    }

    .calendar-day-header {
        padding: 0.5rem;
        font-size: 0.85rem;
    }

    .day-travel-item {
        font-size: 0.65rem;
        padding: 3px 6px;
    }

    .calendar-nav {
        flex-direction: column;
        gap: 1rem;
    }

    .legend-items {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<?php
get_footer();
