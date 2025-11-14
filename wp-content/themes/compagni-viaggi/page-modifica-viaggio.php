<?php
/**
 * Template Name: Edit Journey
 * Description: Form to edit an existing journey
 */

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// Get travel ID from URL
$travel_id = isset($_GET['travel_id']) ? intval($_GET['travel_id']) : 0;

if (!$travel_id) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Get travel post
$travel = get_post($travel_id);

if (!$travel || $travel->post_type !== 'viaggio') {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Check if current user is the organizer
$user_id = get_current_user_id();
if ($travel->post_author != $user_id) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// Get travel meta data
$destination = get_post_meta($travel_id, 'cdv_destination', true);
$country = get_post_meta($travel_id, 'cdv_country', true);
$start_date = get_post_meta($travel_id, 'cdv_start_date', true);
$end_date = get_post_meta($travel_id, 'cdv_end_date', true);
$date_type = get_post_meta($travel_id, 'cdv_date_type', true) ?: 'precise';
$travel_month = get_post_meta($travel_id, 'cdv_travel_month', true);
$budget = get_post_meta($travel_id, 'cdv_budget', true);
$max_participants = get_post_meta($travel_id, 'cdv_max_participants', true);

// Optional fields
$transport = get_post_meta($travel_id, 'cdv_travel_transport', true);
$accommodation = get_post_meta($travel_id, 'cdv_travel_accommodation', true);
$difficulty = get_post_meta($travel_id, 'cdv_travel_difficulty', true);
$meals = get_post_meta($travel_id, 'cdv_travel_meals', true);
$guide_type = get_post_meta($travel_id, 'cdv_travel_guide_type', true);
$requirements = get_post_meta($travel_id, 'cdv_travel_requirements', true);

// Get travel types
$travel_types = wp_get_post_terms($travel_id, 'tipo_viaggio', array('fields' => 'ids'));

get_header();
?>

<main class="site-main">
    <div class="create-travel-page">
        <div class="container">
            <div class="create-travel-wrapper">
                <div class="page-header">
                    <h1>Edit Journey</h1>
                    <p>Update your journey details</p>
                </div>

                <form id="edit-travel-form" class="travel-form">
                    <input type="hidden" id="travel_id" name="travel_id" value="<?php echo esc_attr($travel_id); ?>">

                    <div class="form-section">
                        <h3>General Information</h3>

                        <div class="form-group">
                            <label for="travel_title">Journey Title <span class="required">*</span></label>
                            <input type="text" id="travel_title" name="travel_title" required placeholder="e.g., Weekend in Venice, Road Trip in Tuscany" value="<?php echo esc_attr($travel->post_title); ?>">
                        </div>

                        <div class="form-group">
                            <label for="travel_description">Description <span class="required">*</span></label>
                            <textarea id="travel_description" name="travel_description" rows="6" required placeholder="Describe your journey: destinations, planned activities, what makes this experience special..."><?php echo esc_textarea($travel->post_content); ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Destination</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_destination">Destination <span class="required">*</span></label>
                                <input type="text" id="travel_destination" name="travel_destination" required placeholder="e.g., Venice, Tuscany" value="<?php echo esc_attr($destination); ?>">
                            </div>

                            <div class="form-group">
                                <label for="travel_country_select">Country <span class="required">*</span></label>
                                <select id="travel_country_select" name="travel_country_select" required>
                                    <option value="">Select a country</option>

                                    <optgroup label="🇪🇺 Europa">
                                        <?php
                                        $european_countries = array('Italia', 'Francia', 'Spagna', 'Germania', 'Regno Unito', 'Portogallo', 'Grecia', 'Paesi Bassi', 'Svizzera', 'Austria', 'Croazia', 'Irlanda', 'Islanda', 'Norvegia', 'Svezia', 'Danimarca', 'Polonia', 'Repubblica Ceca', 'Ungheria', 'Romania', 'Bulgaria', 'Slovenia', 'Montenegro', 'Albania', 'Serbia', 'Bosnia ed Erzegovina', 'Macedonia del Nord', 'Belgio', 'Lussemburgo', 'Finlandia', 'Estonia', 'Lettonia', 'Lituania', 'Slovacchia', 'Malta', 'Cipro');
                                        foreach ($european_countries as $ec) {
                                            $selected = ($country === $ec) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($ec) . '" ' . $selected . '>' . esc_html($ec) . '</option>';
                                        }
                                        ?>
                                    </optgroup>

                                    <optgroup label="🌍 Africa">
                                        <?php
                                        $african_countries = array('Marocco', 'Egitto', 'Tunisia', 'Sudafrica', 'Kenya', 'Tanzania', 'Madagascar', 'Namibia', 'Botswana', 'Zanzibar', 'Mauritius', 'Seychelles', 'Senegal', 'Etiopia');
                                        foreach ($african_countries as $ac) {
                                            $selected = ($country === $ac) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($ac) . '" ' . $selected . '>' . esc_html($ac) . '</option>';
                                        }
                                        ?>
                                    </optgroup>

                                    <optgroup label="🌏 Asia">
                                        <?php
                                        $asian_countries = array('Giappone', 'Thailandia', 'Vietnam', 'Cina', 'India', 'Indonesia', 'Maldive', 'Sri Lanka', 'Emirati Arabi Uniti', 'Giordania', 'Israele', 'Turchia', 'Cambogia', 'Malesia', 'Singapore', 'Filippine', 'Nepal', 'Corea del Sud', 'Oman', 'Qatar', 'Bali');
                                        foreach ($asian_countries as $asc) {
                                            $selected = ($country === $asc) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($asc) . '" ' . $selected . '>' . esc_html($asc) . '</option>';
                                        }
                                        ?>
                                    </optgroup>

                                    <optgroup label="🌎 Americhe">
                                        <?php
                                        $american_countries = array('Stati Uniti', 'Canada', 'Messico', 'Brasile', 'Argentina', 'Perù', 'Cile', 'Colombia', 'Costa Rica', 'Cuba', 'Repubblica Dominicana', 'Ecuador', 'Bolivia', 'Uruguay', 'Panama', 'Guatemala', 'Nicaragua');
                                        foreach ($american_countries as $amc) {
                                            $selected = ($country === $amc) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($amc) . '" ' . $selected . '>' . esc_html($amc) . '</option>';
                                        }
                                        ?>
                                    </optgroup>

                                    <optgroup label="🌏 Oceania">
                                        <?php
                                        $oceania_countries = array('Australia', 'Nuova Zelanda', 'Polinesia Francese', 'Fiji');
                                        foreach ($oceania_countries as $oc) {
                                            $selected = ($country === $oc) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($oc) . '" ' . $selected . '>' . esc_html($oc) . '</option>';
                                        }
                                        ?>
                                    </optgroup>

                                    <option value="altro" <?php echo ($country && !in_array($country, array_merge($european_countries, $african_countries, $asian_countries, $american_countries, $oceania_countries))) ? 'selected' : ''; ?>>📝 Other (specify)</option>
                                </select>

                                <!-- "Other" field that appears when selected -->
                                <input type="text" id="travel_country_other" name="travel_country_other" style="<?php echo ($country && !in_array($country, array_merge($european_countries, $african_countries, $asian_countries, $american_countries, $oceania_countries))) ? '' : 'display: none;'; ?> margin-top: 10px;" placeholder="Specify country" value="<?php echo (!in_array($country, array_merge($european_countries, $african_countries, $asian_countries, $american_countries, $oceania_countries))) ? esc_attr($country) : ''; ?>">

                                <!-- Hidden field che conterrà il valore finale -->
                                <input type="hidden" id="travel_country" name="travel_country" value="<?php echo esc_attr($country); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>When to Depart</h3>

                        <div class="form-group">
                            <label>Date Type <span class="required">*</span></label>
                            <div class="radio-group" style="display: flex; gap: calc(var(--spacing-unit) * 3); margin-bottom: calc(var(--spacing-unit) * 2);">
                                <label style="display: flex; align-items: center; gap: calc(var(--spacing-unit) * 1); cursor: pointer;">
                                    <input type="radio" name="date_type" value="precise" <?php checked($date_type, 'precise'); ?>>
                                    <span>Precise dates</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: calc(var(--spacing-unit) * 1); cursor: pointer;">
                                    <input type="radio" name="date_type" value="month" <?php checked($date_type, 'month'); ?>>
                                    <span>Month only (flexible dates)</span>
                                </label>
                            </div>
                        </div>

                        <div id="precise-dates-container" style="<?php echo ($date_type === 'month') ? 'display: none;' : ''; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="travel_start_date">Start Date <span class="required">*</span></label>
                                    <input type="date" id="travel_start_date" name="travel_start_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo esc_attr($start_date); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="travel_end_date">End Date <span class="required">*</span></label>
                                    <input type="date" id="travel_end_date" name="travel_end_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo esc_attr($end_date); ?>">
                                </div>
                            </div>
                        </div>

                        <div id="month-container" style="<?php echo ($date_type === 'month') ? '' : 'display: none;'; ?>">
                            <div class="form-group">
                                <label for="travel_month">Departure Month <span class="required">*</span></label>
                                <select id="travel_month" name="travel_month">
                                    <option value="">Select month</option>
                                    <?php
                                    $months = array(
                                        '01' => 'January', '02' => 'February', '03' => 'March',
                                        '04' => 'April', '05' => 'May', '06' => 'June',
                                        '07' => 'July', '08' => 'August', '09' => 'September',
                                        '10' => 'October', '11' => 'November', '12' => 'December'
                                    );
                                    $current_month = (int)date('n');
                                    $current_year = (int)date('Y');

                                    // Mostra mesi dell'anno corrente (da questo mese in poi)
                                    for ($i = $current_month; $i <= 12; $i++) {
                                        $month_num = str_pad($i, 2, '0', STR_PAD_LEFT);
                                        $value = $current_year . '-' . $month_num;
                                        $selected = ($travel_month === $value) ? 'selected' : '';
                                        echo '<option value="' . $value . '" ' . $selected . '>' . $months[$month_num] . ' ' . $current_year . '</option>';
                                    }

                                    // Mostra tutti i mesi del prossimo anno
                                    $next_year = $current_year + 1;
                                    foreach ($months as $num => $name) {
                                        $value = $next_year . '-' . $num;
                                        $selected = ($travel_month === $value) ? 'selected' : '';
                                        echo '<option value="' . $value . '" ' . $selected . '>' . $name . ' ' . $next_year . '</option>';
                                    }
                                    ?>
                                </select>
                                <small style="display: block; margin-top: calc(var(--spacing-unit) * 0.5); color: #666;">
                                    The journey will be available for the entire selected month (flexible dates)
                                </small>
                            </div>
                        </div>

                        <h3 style="margin-top: calc(var(--spacing-unit) * 4);">Budget</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_budget">Budget per Person (€) <span class="required">*</span></label>
                                <input type="number" id="travel_budget" name="travel_budget" min="0" required placeholder="500" value="<?php echo esc_attr($budget); ?>">
                            </div>

                            <div class="form-group">
                                <label for="travel_max_participants">Max Participants <span class="required">*</span></label>
                                <input type="number" id="travel_max_participants" name="travel_max_participants" min="2" max="50" required value="<?php echo esc_attr($max_participants); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Journey Type</h3>
                        <div class="checkbox-group">
                            <?php
                            $all_travel_types = get_terms(array(
                                'taxonomy' => 'tipo_viaggio',
                                'hide_empty' => false,
                            ));
                            if (!empty($all_travel_types) && !is_wp_error($all_travel_types)) :
                                foreach ($all_travel_types as $type) :
                                    $checked = in_array($type->term_id, $travel_types) ? 'checked' : '';
                            ?>
                                <label>
                                    <input type="checkbox" name="travel_types[]" value="<?php echo esc_attr($type->term_id); ?>" <?php echo $checked; ?>>
                                    <?php echo esc_html($type->name); ?>
                                </label>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Additional Details <span style="font-weight: normal; font-size: 0.9rem; color: var(--text-medium);">(Optional)</span></h3>
                        <p style="color: var(--text-medium); margin-bottom: calc(var(--spacing-unit) * 3);">These details help travelers better understand the journey</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_transport">🚗 Transportation</label>
                                <div class="checkbox-group" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));">
                                    <?php
                                    $transport_options = array(
                                        'aereo' => '✈️ Plane',
                                        'treno' => '🚂 Train',
                                        'bus' => '🚌 Bus',
                                        'auto_propria' => '🚗 Own car',
                                        'auto_noleggio' => '🚙 Rental car',
                                        'nave' => '🚢 Boat/Ferry'
                                    );
                                    $transport_array = is_array($transport) ? $transport : array();
                                    foreach ($transport_options as $value => $label) :
                                        $checked = in_array($value, $transport_array) ? 'checked' : '';
                                    ?>
                                    <label>
                                        <input type="checkbox" name="travel_transport[]" value="<?php echo esc_attr($value); ?>" <?php echo $checked; ?>>
                                        <?php echo esc_html($label); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_accommodation">🏨 Accommodation Type</label>
                                <select id="travel_accommodation" name="travel_accommodation">
                                    <option value="">Not specified</option>
                                    <?php
                                    $accommodation_options = array(
                                        'hotel' => 'Hotel',
                                        'ostello' => 'Hostel',
                                        'bb' => 'B&B',
                                        'airbnb' => 'Airbnb/Vacation home',
                                        'camping' => 'Camping/Tent',
                                        'rifugio' => 'Mountain hut',
                                        'misto' => 'Mixed',
                                        'altro' => 'Other'
                                    );
                                    foreach ($accommodation_options as $value => $label) {
                                        $selected = ($accommodation === $value) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="travel_difficulty">📈 Difficulty Level</label>
                                <select id="travel_difficulty" name="travel_difficulty">
                                    <option value="">Not specified</option>
                                    <?php
                                    $difficulty_options = array(
                                        'facile' => 'Easy - For everyone',
                                        'moderato' => 'Moderate - Minimal preparation needed',
                                        'impegnativo' => 'Challenging - Requires good physical fitness',
                                        'molto_impegnativo' => 'Very challenging - Experts only'
                                    );
                                    foreach ($difficulty_options as $value => $label) {
                                        $selected = ($difficulty === $value) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_meals">🍽️ Meals</label>
                                <select id="travel_meals" name="travel_meals">
                                    <option value="">Not specified</option>
                                    <?php
                                    $meals_options = array(
                                        'non_inclusi' => 'Not included',
                                        'colazione' => 'Breakfast only included',
                                        'mezza_pensione' => 'Half board',
                                        'pensione_completa' => 'Full board'
                                    );
                                    foreach ($meals_options as $value => $label) {
                                        $selected = ($meals === $value) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="travel_guide_type">👥 Organization</label>
                                <select id="travel_guide_type" name="travel_guide_type">
                                    <option value="">Not specified</option>
                                    <?php
                                    $guide_options = array(
                                        'autonomo' => 'Independent journey',
                                        'guida_locale' => 'With local guide',
                                        'tour_organizzato' => 'Organized tour'
                                    );
                                    foreach ($guide_options as $value => $label) {
                                        $selected = ($guide_type === $value) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="travel_requirements">📝 Requirements and Special Notes</label>
                            <textarea id="travel_requirements" name="travel_requirements" rows="4" placeholder="e.g., Required documents (visa, passport), required vaccinations, special equipment, specific physical requirements..."><?php echo esc_textarea($requirements); ?></textarea>
                            <small style="display: block; margin-top: 8px; color: #666;">Enter any special requirements, necessary documents or important information for participants</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo esc_url(get_permalink($travel_id)); ?>" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary btn-large">Update Journey 💾</button>
                    </div>

                    <div id="form-messages" style="margin-top: 20px;"></div>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
.create-travel-page {
    padding: calc(var(--spacing-unit) * 6) 0;
    background: var(--bg-light);
    min-height: 80vh;
}

.create-travel-wrapper {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    padding: calc(var(--spacing-unit) * 6);
    border-radius: 12px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
}

.page-header {
    text-align: center;
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.page-header h1 {
    margin-bottom: calc(var(--spacing-unit) * 2);
    color: var(--primary-color);
}

.page-header p {
    font-size: 1.1rem;
    color: var(--text-medium);
}

.travel-form .form-section {
    margin-bottom: calc(var(--spacing-unit) * 5);
    padding-bottom: calc(var(--spacing-unit) * 5);
    border-bottom: 1px solid var(--border-color);
}

.travel-form .form-section:last-of-type {
    border-bottom: none;
    padding-bottom: 0;
}

.travel-form .form-section h3 {
    color: var(--text-dark);
    margin-bottom: calc(var(--spacing-unit) * 3);
    font-size: 1.3rem;
}

.required {
    color: var(--error-color);
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: calc(var(--spacing-unit) * 2);
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 1);
    padding: calc(var(--spacing-unit) * 1.5);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.checkbox-group label:hover {
    border-color: var(--primary-color);
    background: rgba(var(--primary-rgb), 0.05);
}

.checkbox-group input[type="checkbox"] {
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: calc(var(--spacing-unit) * 2);
    justify-content: center;
    margin-top: calc(var(--spacing-unit) * 4);
}

.success-message {
    background: var(--success-color);
    color: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: 8px;
    text-align: center;
}

.error-message {
    background: var(--error-color);
    color: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: 8px;
    text-align: center;
}

.info-message {
    background: #3498db;
    color: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: 8px;
    text-align: center;
}

.warning-message {
    background: #f39c12;
    color: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: 8px;
    text-align: center;
}

@media (max-width: 768px) {
    .create-travel-wrapper {
        padding: calc(var(--spacing-unit) * 4);
    }

    .checkbox-group {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle between precise dates and month
    $('input[name="date_type"]').on('change', function() {
        const dateType = $(this).val();

        if (dateType === 'precise') {
            $('#precise-dates-container').show();
            $('#month-container').hide();
            $('#travel_start_date').prop('required', true);
            $('#travel_end_date').prop('required', true);
            $('#travel_month').prop('required', false);
        } else {
            $('#precise-dates-container').hide();
            $('#month-container').show();
            $('#travel_start_date').prop('required', false);
            $('#travel_end_date').prop('required', false);
            $('#travel_month').prop('required', true);
        }
    });

    // Update end date min when start date changes
    $('#travel_start_date').on('change', function() {
        const startDate = $(this).val();
        $('#travel_end_date').attr('min', startDate);
    });

    // Handle country select with "Altro" option
    $('#travel_country_select').on('change', function() {
        const selectedValue = $(this).val();
        const $otherField = $('#travel_country_other');
        const $hiddenField = $('#travel_country');

        if (selectedValue === 'altro') {
            // Show the "other" text field
            $otherField.show().prop('required', true).focus();
            $hiddenField.val(''); // Clear hidden field
        } else {
            // Hide the "other" text field and set hidden field value
            $otherField.hide().prop('required', false).val('');
            $hiddenField.val(selectedValue);
        }
    });

    // Update hidden field when "other" text field changes
    $('#travel_country_other').on('input', function() {
        $('#travel_country').val($(this).val());
    });

    // Initialize on page load
    $('#travel_country_select').trigger('change');

    $('#edit-travel-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $messages = $('#form-messages');

        const dateType = $('input[name="date_type"]:checked').val();
        let dataToSend = {
            action: 'cdv_update_travel',
            nonce: cdvAjax.nonce,
            travel_id: $('#travel_id').val(),
            title: $('#travel_title').val(),
            description: $('#travel_description').val(),
            destination: $('#travel_destination').val(),
            country: $('#travel_country').val(),
            budget: $('#travel_budget').val(),
            max_participants: $('#travel_max_participants').val(),
            travel_types: [],
            travel_transport: [],
            travel_accommodation: $('#travel_accommodation').val(),
            travel_difficulty: $('#travel_difficulty').val(),
            travel_meals: $('#travel_meals').val(),
            travel_guide_type: $('#travel_guide_type').val(),
            travel_requirements: $('#travel_requirements').val()
        };

        // Get travel types
        $('input[name="travel_types[]"]:checked').each(function() {
            dataToSend.travel_types.push($(this).val());
        });

        // Get travel transport methods
        $('input[name="travel_transport[]"]:checked').each(function() {
            dataToSend.travel_transport.push($(this).val());
        });

        // Add date info based on type
        if (dateType === 'precise') {
            const startDate = $('#travel_start_date').val();
            const endDate = $('#travel_end_date').val();

            if (!startDate || !endDate) {
                $messages.html('<div class="error-message">Enter both start and end dates.</div>');
                return;
            }

            if (new Date(endDate) <= new Date(startDate)) {
                $messages.html('<div class="error-message">End date must be after start date.</div>');
                return;
            }

            dataToSend.start_date = startDate;
            dataToSend.end_date = endDate;
            dataToSend.date_type = 'precise';
        } else {
            const monthValue = $('#travel_month').val();

            if (!monthValue) {
                $messages.html('<div class="error-message">Select departure month.</div>');
                return;
            }

            dataToSend.travel_month = monthValue;
            dataToSend.date_type = 'month';
        }

        // Disable submit button
        $submitBtn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: dataToSend,
            success: function(response) {
                if (response.success) {
                    $messages.html('<div class="success-message">' + response.data.message + '</div>');

                    // Redirect to the travel page after 1 second
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    $messages.html('<div class="error-message">' + response.data.message + '</div>');
                    $submitBtn.prop('disabled', false).text('Update Journey 💾');
                }
            },
            error: function() {
                $messages.html('<div class="error-message">An error occurred. Please try again later.</div>');
                $submitBtn.prop('disabled', false).text('Update Journey 💾');
            }
        });
    });
});
</script>

<?php
get_footer();
