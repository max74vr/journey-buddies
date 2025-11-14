<?php
/**
 * Template Name: Crea Viaggio
 * Description: Form per creare un nuovo viaggio
 */

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

// Check if user has capability to create travels
$user_id = get_current_user_id();
if (!current_user_can('create_viaggi')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

get_header();
?>

<main class="site-main">
    <div class="create-travel-page">
        <div class="container">
            <div class="create-travel-wrapper">
                <div class="page-header">
                    <h1>Crea un Nuovo Viaggio</h1>
                    <p>Compila il form per proporre il tuo viaggio e trovare compagni di avventura!</p>
                </div>

                <form id="create-travel-form" class="travel-form">
                    <div class="form-section">
                        <h3>Informazioni Generali</h3>

                        <div class="form-group">
                            <label for="travel_title">Titolo del Viaggio <span class="required">*</span></label>
                            <input type="text" id="travel_title" name="travel_title" required placeholder="Es: Weekend a Venezia, Road Trip in Toscana">
                        </div>

                        <div class="form-group">
                            <label for="travel_description">Descrizione <span class="required">*</span></label>
                            <textarea id="travel_description" name="travel_description" rows="6" required placeholder="Descrivi il tuo viaggio: destinazioni, attività previste, cosa rende speciale questa esperienza..."></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Destinazione</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_destination">Destinazione <span class="required">*</span></label>
                                <input type="text" id="travel_destination" name="travel_destination" required placeholder="Es: Venezia, Toscana">
                            </div>

                            <div class="form-group">
                                <label for="travel_country_select">Paese <span class="required">*</span></label>
                                <select id="travel_country_select" name="travel_country_select" required>
                                    <option value="">Seleziona un paese</option>

                                    <optgroup label="🇪🇺 Europa">
                                        <option value="Italia">Italia</option>
                                        <option value="Francia">Francia</option>
                                        <option value="Spagna">Spagna</option>
                                        <option value="Germania">Germania</option>
                                        <option value="Regno Unito">Regno Unito</option>
                                        <option value="Portogallo">Portogallo</option>
                                        <option value="Grecia">Grecia</option>
                                        <option value="Paesi Bassi">Paesi Bassi</option>
                                        <option value="Svizzera">Svizzera</option>
                                        <option value="Austria">Austria</option>
                                        <option value="Croazia">Croazia</option>
                                        <option value="Irlanda">Irlanda</option>
                                        <option value="Islanda">Islanda</option>
                                        <option value="Norvegia">Norvegia</option>
                                        <option value="Svezia">Svezia</option>
                                        <option value="Danimarca">Danimarca</option>
                                        <option value="Polonia">Polonia</option>
                                        <option value="Repubblica Ceca">Repubblica Ceca</option>
                                        <option value="Ungheria">Ungheria</option>
                                        <option value="Romania">Romania</option>
                                        <option value="Bulgaria">Bulgaria</option>
                                        <option value="Slovenia">Slovenia</option>
                                        <option value="Montenegro">Montenegro</option>
                                        <option value="Albania">Albania</option>
                                        <option value="Serbia">Serbia</option>
                                        <option value="Bosnia ed Erzegovina">Bosnia ed Erzegovina</option>
                                        <option value="Macedonia del Nord">Macedonia del Nord</option>
                                        <option value="Belgio">Belgio</option>
                                        <option value="Lussemburgo">Lussemburgo</option>
                                        <option value="Finlandia">Finlandia</option>
                                        <option value="Estonia">Estonia</option>
                                        <option value="Lettonia">Lettonia</option>
                                        <option value="Lituania">Lituania</option>
                                        <option value="Slovacchia">Slovacchia</option>
                                        <option value="Malta">Malta</option>
                                        <option value="Cipro">Cipro</option>
                                    </optgroup>

                                    <optgroup label="🌍 Africa">
                                        <option value="Marocco">Marocco</option>
                                        <option value="Egitto">Egitto</option>
                                        <option value="Tunisia">Tunisia</option>
                                        <option value="Sudafrica">Sudafrica</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Tanzania">Tanzania</option>
                                        <option value="Madagascar">Madagascar</option>
                                        <option value="Namibia">Namibia</option>
                                        <option value="Botswana">Botswana</option>
                                        <option value="Zanzibar">Zanzibar</option>
                                        <option value="Mauritius">Mauritius</option>
                                        <option value="Seychelles">Seychelles</option>
                                        <option value="Senegal">Senegal</option>
                                        <option value="Etiopia">Etiopia</option>
                                    </optgroup>

                                    <optgroup label="🌏 Asia">
                                        <option value="Giappone">Giappone</option>
                                        <option value="Thailandia">Thailandia</option>
                                        <option value="Vietnam">Vietnam</option>
                                        <option value="Cina">Cina</option>
                                        <option value="India">India</option>
                                        <option value="Indonesia">Indonesia</option>
                                        <option value="Maldive">Maldive</option>
                                        <option value="Sri Lanka">Sri Lanka</option>
                                        <option value="Emirati Arabi Uniti">Emirati Arabi Uniti</option>
                                        <option value="Giordania">Giordania</option>
                                        <option value="Israele">Israele</option>
                                        <option value="Turchia">Turchia</option>
                                        <option value="Cambogia">Cambogia</option>
                                        <option value="Malesia">Malesia</option>
                                        <option value="Singapore">Singapore</option>
                                        <option value="Filippine">Filippine</option>
                                        <option value="Nepal">Nepal</option>
                                        <option value="Corea del Sud">Corea del Sud</option>
                                        <option value="Oman">Oman</option>
                                        <option value="Qatar">Qatar</option>
                                        <option value="Bali">Bali</option>
                                    </optgroup>

                                    <optgroup label="🌎 Americhe">
                                        <option value="Stati Uniti">Stati Uniti</option>
                                        <option value="Canada">Canada</option>
                                        <option value="Messico">Messico</option>
                                        <option value="Brasile">Brasile</option>
                                        <option value="Argentina">Argentina</option>
                                        <option value="Perù">Perù</option>
                                        <option value="Cile">Cile</option>
                                        <option value="Colombia">Colombia</option>
                                        <option value="Costa Rica">Costa Rica</option>
                                        <option value="Cuba">Cuba</option>
                                        <option value="Repubblica Dominicana">Repubblica Dominicana</option>
                                        <option value="Ecuador">Ecuador</option>
                                        <option value="Bolivia">Bolivia</option>
                                        <option value="Uruguay">Uruguay</option>
                                        <option value="Panama">Panama</option>
                                        <option value="Guatemala">Guatemala</option>
                                        <option value="Nicaragua">Nicaragua</option>
                                    </optgroup>

                                    <optgroup label="🌏 Oceania">
                                        <option value="Australia">Australia</option>
                                        <option value="Nuova Zelanda">Nuova Zelanda</option>
                                        <option value="Polinesia Francese">Polinesia Francese</option>
                                        <option value="Fiji">Fiji</option>
                                    </optgroup>

                                    <option value="altro">📝 Altro (specifica)</option>
                                </select>

                                <!-- Campo "Altro" che appare quando selezionato -->
                                <input type="text" id="travel_country_other" name="travel_country_other" style="display: none; margin-top: 10px;" placeholder="Specifica il paese">

                                <!-- Hidden field che conterrà il valore finale -->
                                <input type="hidden" id="travel_country" name="travel_country">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Quando Partire</h3>

                        <div class="form-group">
                            <label>Tipo di Data <span class="required">*</span></label>
                            <div class="radio-group" style="display: flex; gap: calc(var(--spacing-unit) * 3); margin-bottom: calc(var(--spacing-unit) * 2);">
                                <label style="display: flex; align-items: center; gap: calc(var(--spacing-unit) * 1); cursor: pointer;">
                                    <input type="radio" name="date_type" value="precise" checked>
                                    <span>Date precise</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: calc(var(--spacing-unit) * 1); cursor: pointer;">
                                    <input type="radio" name="date_type" value="month">
                                    <span>Solo mese (date flessibili)</span>
                                </label>
                            </div>
                        </div>

                        <div id="precise-dates-container">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="travel_start_date">Data Inizio <span class="required">*</span></label>
                                    <input type="date" id="travel_start_date" name="travel_start_date" min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="travel_end_date">Data Fine <span class="required">*</span></label>
                                    <input type="date" id="travel_end_date" name="travel_end_date" min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>

                        <div id="month-container" style="display: none;">
                            <div class="form-group">
                                <label for="travel_month">Mese di Partenza <span class="required">*</span></label>
                                <select id="travel_month" name="travel_month">
                                    <option value="">Seleziona il mese</option>
                                    <?php
                                    $months = array(
                                        '01' => 'Gennaio', '02' => 'Febbraio', '03' => 'Marzo',
                                        '04' => 'Aprile', '05' => 'Maggio', '06' => 'Giugno',
                                        '07' => 'Luglio', '08' => 'Agosto', '09' => 'Settembre',
                                        '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
                                    );
                                    $current_month = (int)date('n');
                                    $current_year = (int)date('Y');

                                    // Mostra mesi dell'anno corrente (da questo mese in poi)
                                    for ($i = $current_month; $i <= 12; $i++) {
                                        $month_num = str_pad($i, 2, '0', STR_PAD_LEFT);
                                        echo '<option value="' . $current_year . '-' . $month_num . '">' . $months[$month_num] . ' ' . $current_year . '</option>';
                                    }

                                    // Mostra tutti i mesi del prossimo anno
                                    $next_year = $current_year + 1;
                                    foreach ($months as $num => $name) {
                                        echo '<option value="' . $next_year . '-' . $num . '">' . $name . ' ' . $next_year . '</option>';
                                    }
                                    ?>
                                </select>
                                <small style="display: block; margin-top: calc(var(--spacing-unit) * 0.5); color: #666;">
                                    Il viaggio sarà disponibile per tutto il mese selezionato (date flessibili)
                                </small>
                            </div>
                        </div>

                        <h3 style="margin-top: calc(var(--spacing-unit) * 4);">Budget</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_budget">Budget per Persona (€) <span class="required">*</span></label>
                                <input type="number" id="travel_budget" name="travel_budget" min="0" required placeholder="500">
                            </div>

                            <div class="form-group">
                                <label for="travel_max_participants">Max Partecipanti <span class="required">*</span></label>
                                <input type="number" id="travel_max_participants" name="travel_max_participants" min="2" max="50" value="5" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Tipo di Viaggio</h3>
                        <div class="checkbox-group">
                            <?php
                            $travel_types = get_terms(array(
                                'taxonomy' => 'tipo_viaggio',
                                'hide_empty' => false,
                            ));
                            if (!empty($travel_types) && !is_wp_error($travel_types)) :
                                foreach ($travel_types as $type) :
                            ?>
                                <label>
                                    <input type="checkbox" name="travel_types[]" value="<?php echo esc_attr($type->term_id); ?>">
                                    <?php echo esc_html($type->name); ?>
                                </label>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Dettagli Aggiuntivi <span style="font-weight: normal; font-size: 0.9rem; color: var(--text-medium);">(Facoltativi)</span></h3>
                        <p style="color: var(--text-medium); margin-bottom: calc(var(--spacing-unit) * 3);">Questi dettagli aiutano i viaggiatori a capire meglio il viaggio</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_transport">🚗 Mezzi di Trasporto</label>
                                <div class="checkbox-group" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));">
                                    <label>
                                        <input type="checkbox" name="travel_transport[]" value="aereo">
                                        ✈️ Aereo
                                    </label>
                                    <label>
                                        <input type="checkbox" name="travel_transport[]" value="treno">
                                        🚂 Treno
                                    </label>
                                    <label>
                                        <input type="checkbox" name="travel_transport[]" value="bus">
                                        🚌 Bus
                                    </label>
                                    <label>
                                        <input type="checkbox" name="travel_transport[]" value="auto_propria">
                                        🚗 Auto propria
                                    </label>
                                    <label>
                                        <input type="checkbox" name="travel_transport[]" value="auto_noleggio">
                                        🚙 Auto a noleggio
                                    </label>
                                    <label>
                                        <input type="checkbox" name="travel_transport[]" value="nave">
                                        🚢 Nave/Traghetto
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_accommodation">🏨 Tipologia Alloggio</label>
                                <select id="travel_accommodation" name="travel_accommodation">
                                    <option value="">Non specificato</option>
                                    <option value="hotel">Hotel</option>
                                    <option value="ostello">Ostello</option>
                                    <option value="bb">B&B</option>
                                    <option value="airbnb">Airbnb/Casa vacanze</option>
                                    <option value="camping">Camping/Tenda</option>
                                    <option value="rifugio">Rifugio</option>
                                    <option value="misto">Misto</option>
                                    <option value="altro">Altro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="travel_difficulty">📈 Livello di Difficoltà</label>
                                <select id="travel_difficulty" name="travel_difficulty">
                                    <option value="">Non specificato</option>
                                    <option value="facile">Facile - Per tutti</option>
                                    <option value="moderato">Moderato - Serve minima preparazione</option>
                                    <option value="impegnativo">Impegnativo - Richiede buona forma fisica</option>
                                    <option value="molto_impegnativo">Molto impegnativo - Solo esperti</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_meals">🍽️ Pasti</label>
                                <select id="travel_meals" name="travel_meals">
                                    <option value="">Non specificato</option>
                                    <option value="non_inclusi">Non inclusi</option>
                                    <option value="colazione">Solo colazione inclusa</option>
                                    <option value="mezza_pensione">Mezza pensione</option>
                                    <option value="pensione_completa">Pensione completa</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="travel_guide_type">👥 Organizzazione</label>
                                <select id="travel_guide_type" name="travel_guide_type">
                                    <option value="">Non specificato</option>
                                    <option value="autonomo">Viaggio autonomo</option>
                                    <option value="guida_locale">Con guida locale</option>
                                    <option value="tour_organizzato">Tour organizzato</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="travel_requirements">📝 Requisiti e Note Particolari</label>
                            <textarea id="travel_requirements" name="travel_requirements" rows="4" placeholder="Es: Documenti necessari (visto, passaporto), vaccinazioni richieste, equipaggiamento speciale, requisiti fisici specifici..."></textarea>
                            <small style="display: block; margin-top: 8px; color: #666;">Inserisci qui eventuali requisiti particolari, documenti necessari o informazioni importanti per i partecipanti</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="disclaimer-box" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <h4 style="margin-top: 0; color: #856404;">⚠️ Informativa Importante</h4>
                            <p style="margin-bottom: 15px;">Pubblicando questo viaggio, dichiari di comprendere e accettare che:</p>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">La piattaforma <strong>facilita l'incontro tra viaggiatori</strong> ma non organizza materialmente i viaggi</li>
                                <li style="margin-bottom: 8px;">Sei <strong>l'unico responsabile</strong> per l'organizzazione, la sicurezza e la gestione del viaggio</li>
                                <li style="margin-bottom: 8px;">Devi verificare <strong>personalmente</strong> l'identità e l'affidabilità dei partecipanti</li>
                                <li style="margin-bottom: 8px;">La piattaforma <strong>non è responsabile</strong> per comportamenti, danni, cancellazioni o disservizi</li>
                                <li style="margin-bottom: 8px;">Tutte le <strong>questioni economiche e logistiche</strong> sono gestite direttamente tra te e i partecipanti</li>
                                <li style="margin-bottom: 8px;">Devi rispettare tutte le <strong>leggi locali e internazionali</strong> applicabili al viaggio</li>
                            </ul>
                            <div style="margin-top: 15px;">
                                <label style="display: flex; align-items: start; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" id="accept_travel_disclaimer" name="accept_travel_disclaimer" required style="margin-top: 4px;">
                                    <span>Ho letto e accetto l'informativa. Comprendo che sono l'unico responsabile per questo viaggio e sollevo la piattaforma da ogni responsabilità.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="btn-secondary">Annulla</a>
                        <button type="submit" class="btn-primary btn-large">Crea Annuncio 🚀</button>
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

    $('#create-travel-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $messages = $('#form-messages');

        const dateType = $('input[name="date_type"]:checked').val();
        let dataToSend = {
            action: 'cdv_create_travel',
            nonce: cdvAjax.nonce,
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
                $messages.html('<div class="error-message">Inserisci sia la data di inizio che di fine.</div>');
                return;
            }

            if (new Date(endDate) <= new Date(startDate)) {
                $messages.html('<div class="error-message">La data di fine deve essere successiva alla data di inizio.</div>');
                return;
            }

            dataToSend.start_date = startDate;
            dataToSend.end_date = endDate;
            dataToSend.date_type = 'precise';
        } else {
            const monthValue = $('#travel_month').val();

            if (!monthValue) {
                $messages.html('<div class="error-message">Seleziona il mese di partenza.</div>');
                return;
            }

            dataToSend.travel_month = monthValue;
            dataToSend.date_type = 'month';
        }

        // Disable submit button and show validation message
        $submitBtn.prop('disabled', true).text('Validazione indirizzo...');
        $messages.html('<div class="info-message">🔍 Verifica che l\'indirizzo esista...</div>');

        // First, validate the address with geocoding
        $.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cdv_validate_address',
                nonce: cdvAjax.nonce,
                destination: dataToSend.destination,
                country: dataToSend.country
            },
            success: function(validationResponse) {
                if (validationResponse.success) {
                    // Address is valid, proceed with travel creation
                    $submitBtn.text('Creazione in corso...');
                    $messages.html('<div class="success-message">✅ Indirizzo valido! Creazione viaggio in corso...</div>');

                    $.ajax({
                        url: cdvAjax.ajaxurl,
                        type: 'POST',
                        data: dataToSend,
                        success: function(response) {
                            if (response.success) {
                                $messages.html('<div class="success-message">' + response.data.message + '</div>');

                                // Redirect to the travel page after 1.5 seconds
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 1500);
                            } else {
                                $messages.html('<div class="error-message">' + response.data.message + '</div>');
                                $submitBtn.prop('disabled', false).text('Crea Annuncio 🚀');
                            }
                        },
                        error: function() {
                            $messages.html('<div class="error-message">Si è verificato un errore. Riprova più tardi.</div>');
                            $submitBtn.prop('disabled', false).text('Crea Annuncio 🚀');
                        }
                    });
                } else {
                    // Address validation failed
                    $messages.html('<div class="error-message">❌ ' + validationResponse.data.message + '</div>');
                    $submitBtn.prop('disabled', false).text('Crea Annuncio 🚀');
                }
            },
            error: function() {
                // Validation request failed, but allow creation anyway (fallback)
                console.warn('Address validation failed, proceeding anyway');
                $submitBtn.text('Creazione in corso...');
                $messages.html('<div class="warning-message">⚠️ Impossibile validare l\'indirizzo, ma procedo comunque...</div>');

                $.ajax({
                    url: cdvAjax.ajaxurl,
                    type: 'POST',
                    data: dataToSend,
                    success: function(response) {
                        if (response.success) {
                            $messages.html('<div class="success-message">' + response.data.message + '</div>');

                            // Redirect to the travel page after 1.5 seconds
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 1500);
                        } else {
                            $messages.html('<div class="error-message">' + response.data.message + '</div>');
                            $submitBtn.prop('disabled', false).text('Crea Annuncio 🚀');
                        }
                    },
                    error: function() {
                        $messages.html('<div class="error-message">Si è verificato un errore. Riprova più tardi.</div>');
                        $submitBtn.prop('disabled', false).text('Crea Annuncio 🚀');
                    }
                });
            }
        });
    });
});
</script>

<?php
get_footer();
