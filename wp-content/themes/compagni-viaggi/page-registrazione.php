<?php
/**
 * Template Name: Registrazione
 *
 * Multi-step registration form
 */

// Redirect if already logged in AND profile is complete
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $profile_completed = get_user_meta($user_id, 'cdv_profile_completed', true);

    // Only redirect if registration is fully complete
    if ($profile_completed === '1') {
        wp_redirect(home_url('/dashboard'));
        exit;
    }
}

get_header();
?>

<main class="site-main registration-page">
    <div class="container">
        <div class="registration-wrapper">
            <div class="registration-header">
                <h1>Join Journey Buddies</h1>
                <p>Create your account and start finding travel companions</p>
            </div>

            <!-- Progress Steps -->
            <div class="registration-steps">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Account</div>
                </div>
                <div class="step-connector"></div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Profile</div>
                </div>
                <div class="step-connector"></div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Photo</div>
                </div>
                <div class="step-connector"></div>
                <div class="step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">Journey (Optional)</div>
                </div>
            </div>

            <div class="registration-form-container">
                <!-- Step 1: Account Creation -->
                <form id="registration-step-1" class="registration-step active">
                    <h2>Create Your Account</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username <span class="required">*</span></label>
                            <input type="text" id="username" name="username" required>
                            <small>Only letters, numbers and underscore</small>
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" required minlength="8">
                            <div class="password-strength-meter">
                                <div class="password-strength-bar"></div>
                            </div>
                            <small id="password-strength-text">Password must contain at least 8 characters, uppercase letters, lowercase letters, numbers and symbols</small>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Confirm Password <span class="required">*</span></label>
                            <input type="password" id="password_confirm" name="password_confirm" required>
                        </div>
                    </div>

                    <div class="disclaimer-box">
                        <h4>⚠️ Important Notice</h4>
                        <p>
                            By registering, you understand and accept that:
                        </p>
                        <ul>
                            <li>The platform <strong>facilitates meetings between travelers</strong> but does not materially organize journeys</li>
                            <li>You are <strong>solely responsible</strong> for the content you publish (texts, photos, reviews)</li>
                            <li>You are <strong>responsible</strong> for the information provided in your profile and for your behavior</li>
                            <li>The platform <strong>does not verify the identity</strong> of users beyond email and <strong>does not guarantee</strong> the authenticity of profiles</li>
                            <li>Every <strong>journey agreement</strong> occurs directly between you and other travelers, <strong>without intermediation</strong> from the platform</li>
                            <li>The platform <strong>is not responsible</strong> for behavior, damages or issues arising from meetings or journeys organized through the service</li>
                        </ul>
                        <p>
                            <strong>We encourage you to use caution, common sense and to always meet other people in public places before departing.</strong>
                        </p>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="terms" required>
                            <strong>I accept</strong> the <a href="<?php echo home_url('/termini'); ?>" target="_blank">Terms and Conditions</a> and the <a href="<?php echo home_url('/privacy'); ?>" target="_blank">Privacy Policy</a> and <strong>declare that I have read and understood</strong> the notice above.
                        </label>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="disclaimer_understood" required>
                            <strong>I understand</strong> that the platform disclaims all responsibility for content published by users, behavior outside the platform and for the organization of journeys that occurs exclusively between travelers.
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary btn-large">Continue →</button>
                    </div>

                    <div class="form-footer">
                        Already have an account? <a href="<?php echo wp_login_url(); ?>">Login</a>
                    </div>
                </form>

                <!-- Step 2: Profile Information -->
                <form id="registration-step-2" class="registration-step">
                    <h2>Complete Your Profile</h2>

                    <div class="form-section">
                        <h3>Personal Information</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="birth_date">Date of Birth <span class="required">*</span></label>
                                <input type="date" id="birth_date" name="birth_date" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                <small>You must be at least 18 years old</small>
                            </div>

                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Prefer not to say</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City <span class="required">*</span></label>
                                <input type="text" id="city" name="city" required placeholder="e.g., Milan">
                            </div>

                            <div class="form-group">
                                <label for="country">Country <span class="required">*</span></label>
                                <input type="text" id="country" name="country" required value="Italy">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone (optional)</label>
                            <input type="tel" id="phone" name="phone" placeholder="+39 123 456 7890">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Tell Me About Yourself</h3>

                        <div class="form-group">
                            <label for="bio">Bio <span class="required">*</span></label>
                            <textarea id="bio" name="bio" rows="5" required placeholder="Tell us who you are, what you love about travel, your experiences..."></textarea>
                            <small id="bio-count">0/500 characters</small>
                        </div>

                        <div class="form-group">
                            <label for="languages">Languages Spoken <span class="required">*</span></label>
                            <input type="text" id="languages" name="languages" required placeholder="e.g., Italian, English, Spanish">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Travel Styles</h3>
                        <p>Select your favorite styles:</p>

                        <div class="checkbox-grid">
                            <label><input type="checkbox" name="travel_styles[]" value="Adventure"> Adventure</label>
                            <label><input type="checkbox" name="travel_styles[]" value="Beach"> Beach</label>
                            <label><input type="checkbox" name="travel_styles[]" value="Mountains"> Mountains</label>
                            <label><input type="checkbox" name="travel_styles[]" value="Art Cities"> Art Cities</label>
                            <label><input type="checkbox" name="travel_styles[]" value="Culture"> Culture</label>
                            <label><input type="checkbox" name="travel_styles[]" value="Relax"> Relax</label>
                            <label><input type="checkbox" name="travel_styles[]" value="Food & Wine"> Food & Wine</label>
                            <label><input type="checkbox" name="travel_styles[]" value="Sport"> Sport</label>
                            <label><input type="checkbox" name="travel_styles[]" value="Backpacking"> Backpacking</label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Interests</h3>
                        <p>What do you like to do while traveling?</p>

                        <div class="checkbox-grid">
                            <label><input type="checkbox" name="interests[]" value="Photography"> Photography</label>
                            <label><input type="checkbox" name="interests[]" value="Trekking"> Trekking</label>
                            <label><input type="checkbox" name="interests[]" value="Yoga"> Yoga</label>
                            <label><input type="checkbox" name="interests[]" value="Diving"> Diving</label>
                            <label><input type="checkbox" name="interests[]" value="History"> History</label>
                            <label><input type="checkbox" name="interests[]" value="Art"> Art</label>
                            <label><input type="checkbox" name="interests[]" value="Local Cuisine"> Local Cuisine</label>
                            <label><input type="checkbox" name="interests[]" value="Nightlife"> Nightlife</label>
                            <label><input type="checkbox" name="interests[]" value="Volunteering"> Volunteering</label>
                            <label><input type="checkbox" name="interests[]" value="Wildlife"> Wildlife</label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Travel Preferences</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="budget_range">Budget Range</label>
                                <select id="budget_range" name="budget_range">
                                    <option value="economico">Budget (< €500)</option>
                                    <option value="medio">Medium (€500-1500)</option>
                                    <option value="comfort">Comfort (€1500-3000)</option>
                                    <option value="lusso">Luxury (> €3000)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="travel_frequency">How often do you travel?</label>
                                <select id="travel_frequency" name="travel_frequency">
                                    <option value="raro">Rarely (1-2 times/year)</option>
                                    <option value="occasionale">Occasionally (3-4 times/year)</option>
                                    <option value="frequente">Frequently (5+ times/year)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="accommodation_preference">Accommodation Preference</label>
                                <select id="accommodation_preference" name="accommodation_preference">
                                    <option value="hostel">Hostels</option>
                                    <option value="hotel">Hotel</option>
                                    <option value="bnb">B&B / Airbnb</option>
                                    <option value="camping">Camping</option>
                                    <option value="misto">Mixed</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="travel_pace">Travel Pace</label>
                                <select id="travel_pace" name="travel_pace">
                                    <option value="rilassato">Relaxed</option>
                                    <option value="moderato">Moderate</option>
                                    <option value="intenso">Intense</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Social (Optional)</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="instagram">Instagram</label>
                                <input type="text" id="instagram" name="instagram" placeholder="@yourusername">
                            </div>

                            <div class="form-group">
                                <label for="facebook">Facebook</label>
                                <input type="text" id="facebook" name="facebook" placeholder="Profile URL">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Privacy</h3>
                        <p>Choose what to show in your public profile:</p>

                        <div class="checkbox-list">
                            <label><input type="checkbox" name="show_age" checked> Show my age</label>
                            <label><input type="checkbox" name="show_phone"> Show my phone</label>
                            <label><input type="checkbox" name="show_email"> Show my email</label>
                            <label><input type="checkbox" name="show_social" checked> Show my social media</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary btn-prev">← Back</button>
                        <button type="submit" class="btn-primary btn-large">Continue →</button>
                    </div>
                </form>

                <!-- Step 3: Profile Image -->
                <form id="registration-step-3" class="registration-step">
                    <h2>Add Your Profile Photo</h2>

                    <div class="profile-image-section">
                        <div class="image-preview">
                            <img id="profile-preview" src="" alt="Preview" style="display: none;">
                            <div class="placeholder-avatar">
                                <span class="icon">📷</span>
                                <p>Upload your photo</p>
                            </div>
                        </div>

                        <div class="image-upload-controls">
                            <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                            <button type="button" class="btn-secondary" id="upload-btn">Choose Photo</button>
                            <small>JPG or PNG, max 5MB</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary btn-prev">← Back</button>
                        <button type="button" class="btn-secondary" id="skip-photo">Skip for now</button>
                        <button type="button" class="btn-primary btn-large" id="continue-to-travel">Continue →</button>
                    </div>
                </form>

                <!-- Step 4: Create First Travel (Optional) -->
                <form id="registration-step-4" class="registration-step">
                    <h2>Would you like to post your first journey proposal and find travel companions?</h2>
                    <p class="step-intro">This step is completely <strong>optional</strong>. You can skip and add journeys later from your dashboard.</p>

                    <div class="optional-choice" style="text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <p style="font-size: 1.1rem; margin-bottom: 20px;">What would you like to do?</p>
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button type="button" class="btn-primary" id="show-travel-form">Yes, I want to create a journey</button>
                            <button type="button" class="btn-secondary" id="skip-travel-direct">No, complete registration</button>
                        </div>
                    </div>

                    <div id="travel-form-fields" style="display: none;">

                    <div class="form-group">
                        <label for="travel_title">Journey Title <span class="required">*</span></label>
                        <input type="text" id="travel_title" name="travel_title" placeholder="e.g., Weekend in Venice, Road Trip in Tuscany">
                    </div>

                    <div class="form-group">
                        <label for="travel_description">Description <span class="required">*</span></label>
                        <textarea id="travel_description" name="travel_description" rows="5" placeholder="Describe your journey: destinations, planned activities, what makes this experience special..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="travel_destination">Destination <span class="required">*</span></label>
                            <input type="text" id="travel_destination" name="travel_destination" placeholder="e.g., Venice, Tuscany">
                        </div>

                        <div class="form-group">
                            <label for="travel_country">Country <span class="required">*</span></label>
                            <input type="text" id="travel_country" name="travel_country" placeholder="e.g., Italy, France">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Date Type <span class="required">*</span></label>
                        <div class="radio-group" style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="travel_date_type" value="precise" checked>
                                <span>Precise dates</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="travel_date_type" value="month">
                                <span>Month only</span>
                            </label>
                        </div>
                    </div>

                    <div id="precise-dates-container-reg">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="travel_start_date">Start Date <span class="required">*</span></label>
                                <input type="date" id="travel_start_date" name="travel_start_date" min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="travel_end_date">End Date <span class="required">*</span></label>
                                <input type="date" id="travel_end_date" name="travel_end_date" min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>

                    <div id="month-container-reg" style="display: none;">
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
                                    echo '<option value="' . $current_year . '-' . $month_num . '">' . $months[$month_num] . ' ' . $current_year . '</option>';
                                }

                                // Mostra tutti i mesi del prossimo anno
                                $next_year = $current_year + 1;
                                foreach ($months as $num => $name) {
                                    echo '<option value="' . $next_year . '-' . $num . '">' . $name . ' ' . $next_year . '</option>';
                                }
                                ?>
                            </select>
                            <small>The journey will be available for the entire selected month</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="travel_budget">Budget per Person (€) <span class="required">*</span></label>
                            <input type="number" id="travel_budget" name="travel_budget" min="0" placeholder="500">
                        </div>

                        <div class="form-group">
                            <label for="travel_max_participants">Max Participants <span class="required">*</span></label>
                            <input type="number" id="travel_max_participants" name="travel_max_participants" min="2" max="50" value="5">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Journey Type</label>
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

                    <div class="disclaimer-box" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 8px; margin: 30px 0;">
                        <h4 style="margin-top: 0; color: #856404;">⚠️ Important Notice</h4>
                        <p style="margin-bottom: 15px;">By publishing this journey, you declare that you understand and accept that:</p>
                        <ul style="margin: 0 0 15px 0; padding-left: 20px;">
                            <li style="margin-bottom: 8px;">The platform facilitates meetings between travelers but <strong>does not organize</strong> journeys materially</li>
                            <li style="margin-bottom: 8px;">You are <strong>solely responsible</strong> for organization, safety and management of the journey</li>
                            <li style="margin-bottom: 8px;">The platform <strong>is not responsible</strong> for behavior, damages or issues</li>
                        </ul>
                        <label style="display: flex; align-items: start; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="accept_travel_disclaimer_reg" name="accept_travel_disclaimer" style="margin-top: 4px;">
                            <span>I accept and understand that I am solely responsible for this journey</span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary btn-prev-travel">← Back</button>
                        <button type="button" class="btn-secondary" id="skip-travel-from-form">Skip and Complete</button>
                        <button type="submit" class="btn-primary btn-large">Create Post and Complete ✓</button>
                    </div>

                    </div><!-- End travel-form-fields -->
                </form>
            </div>
        </div>
    </div>
</main>

<style>
.registration-page {
    padding: calc(var(--spacing-unit) * 6) 0;
    background: var(--bg-light);
}

.registration-wrapper {
    max-width: 800px;
    margin: 0 auto;
}

.registration-header {
    text-align: center;
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.registration-header h1 {
    margin-bottom: calc(var(--spacing-unit) * 2);
}

/* Progress Steps */
.registration-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: calc(var(--spacing-unit) * 1);
}

.step-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--bg-gray);
    color: var(--text-medium);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.25rem;
    transition: all var(--transition-base);
}

.step.active .step-number,
.step.completed .step-number {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}

.step-label {
    font-size: 0.9rem;
    color: var(--text-medium);
}

.step-connector {
    width: 80px;
    height: 2px;
    background: var(--bg-gray);
    margin: 0 calc(var(--spacing-unit) * 2);
}

/* Form Container */
.registration-form-container {
    background: white;
    padding: calc(var(--spacing-unit) * 4);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
}

.registration-step {
    display: none;
}

.registration-step.active {
    display: block;
}

.registration-step h2 {
    margin-bottom: calc(var(--spacing-unit) * 4);
    text-align: center;
}

/* Form Sections */
.form-section {
    margin-bottom: calc(var(--spacing-unit) * 4);
    padding-bottom: calc(var(--spacing-unit) * 4);
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h3 {
    margin-bottom: calc(var(--spacing-unit) * 2);
    color: var(--primary-color);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: calc(var(--spacing-unit) * 2);
}

.form-group {
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.form-group label {
    display: block;
    margin-bottom: calc(var(--spacing-unit) * 1);
    font-weight: 500;
    color: var(--text-dark);
}

.required {
    color: var(--error-color);
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="date"],
.form-group input[type="tel"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: calc(var(--spacing-unit) * 1.5);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    font-family: var(--font-primary);
    font-size: 1rem;
    transition: border-color var(--transition-fast);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}

.form-group small {
    display: block;
    margin-top: calc(var(--spacing-unit) * 0.5);
    color: var(--text-light);
    font-size: 0.85rem;
}

/* Checkbox Grid */
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: calc(var(--spacing-unit) * 1.5);
}

.checkbox-grid label,
.checkbox-list label {
    display: flex;
    align-items: center;
    gap: calc(var(--spacing-unit) * 1);
    cursor: pointer;
    padding: calc(var(--spacing-unit) * 1);
    border-radius: var(--border-radius-sm);
    transition: background-color var(--transition-fast);
}

.checkbox-grid label:hover,
.checkbox-list label:hover {
    background-color: var(--bg-light);
}

/* Profile Image Section */
.profile-image-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: calc(var(--spacing-unit) * 3);
    margin: calc(var(--spacing-unit) * 4) 0;
}

.image-preview {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid var(--border-color);
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.placeholder-avatar {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--bg-light);
    color: var(--text-light);
}

.placeholder-avatar .icon {
    font-size: 3rem;
    margin-bottom: calc(var(--spacing-unit) * 1);
}

.image-upload-controls {
    text-align: center;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: calc(var(--spacing-unit) * 2);
    justify-content: center;
    margin-top: calc(var(--spacing-unit) * 4);
    padding-top: calc(var(--spacing-unit) * 4);
    border-top: 1px solid var(--border-color);
}

.btn-large {
    padding: calc(var(--spacing-unit) * 2) calc(var(--spacing-unit) * 4);
    font-size: 1.1rem;
}

.form-footer {
    text-align: center;
    margin-top: calc(var(--spacing-unit) * 3);
    color: var(--text-medium);
}

/* Password Strength Meter */
.password-strength-meter {
    width: 100%;
    height: 4px;
    background: var(--bg-gray);
    border-radius: 2px;
    margin-top: calc(var(--spacing-unit) * 1);
    overflow: hidden;
}

.password-strength-bar {
    height: 100%;
    width: 0%;
    transition: all var(--transition-base);
    border-radius: 2px;
}

.password-strength-bar.weak {
    width: 33%;
    background: var(--error-color);
}

.password-strength-bar.medium {
    width: 66%;
    background: #f39c12;
}

.password-strength-bar.strong {
    width: 100%;
    background: var(--success-color);
}

#password-strength-text {
    display: block;
    margin-top: calc(var(--spacing-unit) * 0.5);
    font-size: 0.85rem;
}

#password-strength-text.weak {
    color: var(--error-color);
}

#password-strength-text.medium {
    color: #f39c12;
}

#password-strength-text.strong {
    color: var(--success-color);
}

/* Loading State */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Disclaimer Box */
.disclaimer-box {
    background: #fff3cd;
    border-left: 4px solid #ff9800;
    padding: calc(var(--spacing-unit) * 3);
    margin-bottom: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
}

.disclaimer-box h4 {
    margin: 0 0 calc(var(--spacing-unit) * 2) 0;
    color: #e65100;
    font-size: 1.1rem;
}

.disclaimer-box p {
    margin: 0 0 calc(var(--spacing-unit) * 1.5) 0;
    color: #7954;
    line-height: 1.6;
}

.disclaimer-box ul {
    margin: 0 0 calc(var(--spacing-unit) * 1.5) calc(var(--spacing-unit) * 3);
    padding: 0;
}

.disclaimer-box li {
    margin-bottom: calc(var(--spacing-unit) * 1);
    color: #795548;
    line-height: 1.5;
}

.disclaimer-box strong {
    color: #e65100;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .registration-steps {
        scale: 0.8;
    }

    .step-connector {
        width: 40px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentStep = 1;
    let userId = null;

    // Bio character count
    $('#bio').on('input', function() {
        const count = $(this).val().length;
        $('#bio-count').text(count + '/500 characters');

        if (count > 500) {
            $(this).val($(this).val().substring(0, 500));
        }
    });

    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        const feedback = [];

        if (password.length >= 8) strength++;
        else feedback.push('at least 8 characters');

        if (password.length >= 12) strength++;

        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        else feedback.push('uppercase and lowercase letters');

        if (/[0-9]/.test(password)) strength++;
        else feedback.push('numbers');

        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        else feedback.push('special symbols');

        return { strength, feedback };
    }

    $('#password').on('input', function() {
        const password = $(this).val();
        const result = checkPasswordStrength(password);
        const $bar = $('.password-strength-bar');
        const $text = $('#password-strength-text');

        // Remove all classes
        $bar.removeClass('weak medium strong');
        $text.removeClass('weak medium strong');

        if (password.length === 0) {
            $bar.css('width', '0%');
            $text.text('Password must contain at least 8 characters, uppercase letters, lowercase letters, numbers and symbols');
            return;
        }

        if (result.strength <= 2) {
            $bar.addClass('weak');
            $text.addClass('weak').text('Weak password. Missing: ' + result.feedback.join(', '));
        } else if (result.strength <= 3) {
            $bar.addClass('medium');
            $text.addClass('medium').text('Medium password. Missing: ' + result.feedback.join(', '));
        } else {
            $bar.addClass('strong');
            $text.addClass('strong').text('Strong password!');
        }
    });

    // Step 1: Account Creation
    $('#registration-step-1').on('submit', function(e) {
        e.preventDefault();

        const password = $('#password').val();
        const confirm = $('#password_confirm').val();

        if (password !== confirm) {
            alert('Passwords do not match');
            return;
        }

        // Check password strength
        const result = checkPasswordStrength(password);
        if (result.strength < 3) {
            if (!confirm('Password is weak. Do you want to continue anyway?')) {
                return;
            }
        }

        const formData = {
            action: 'cdv_register_step1',
            nonce: cdvAjax.nonce,
            username: $('#username').val(),
            email: $('#email').val(),
            password: password,
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            terms: $('input[name="terms"]').is(':checked') ? '1' : '0',
            disclaimer_understood: $('input[name="disclaimer_understood"]').is(':checked') ? '1' : '0',
        };

        $(this).addClass('loading');

        $.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    userId = response.data.user_id;

                    // Update nonce for logged-in user
                    if (response.data.new_nonce) {
                        cdvAjax.nonce = response.data.new_nonce;
                        console.log('Nonce updated for logged-in user');
                    }

                    nextStep();
                } else {
                    alert(response.data.message || 'Error during registration');
                }
            },
            error: function() {
                alert('Connection error');
            },
            complete: function() {
                $('#registration-step-1').removeClass('loading');
            }
        });
    });

    // Step 2: Profile Information
    $('#registration-step-2').on('submit', function(e) {
        e.preventDefault();

        // Check at least one travel style selected
        if ($('input[name="travel_styles[]"]:checked').length === 0) {
            alert('Select at least one travel style');
            return;
        }

        const formData = $(this).serializeArray();
        formData.push({ name: 'action', value: 'cdv_register_step2' });
        formData.push({ name: 'nonce', value: cdvAjax.nonce });

        console.log('Step 2 - Current nonce:', cdvAjax.nonce);
        console.log('Step 2 - Sending data:', formData);
        console.log('AJAX URL:', cdvAjax.ajaxurl);

        $(this).addClass('loading');

        $.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: $.param(formData),
            dataType: 'json',
            success: function(response) {
                console.log('Step 2 - Success response:', response);
                if (response.success) {
                    nextStep();
                } else {
                    alert(response.data.message || 'Error while saving');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Step 2 - Error details:', {
                    status: jqXHR.status,
                    statusText: jqXHR.statusText,
                    textStatus: textStatus,
                    errorThrown: errorThrown,
                    responseText: jqXHR.responseText
                });

                let errorMsg = 'Connection error';
                if (jqXHR.status === 500) {
                    errorMsg = 'Server error (500). Check PHP logs.';
                } else if (jqXHR.status === 403) {
                    errorMsg = 'Access denied (403). Nonce issue.';
                } else if (jqXHR.status === 404) {
                    errorMsg = 'Endpoint not found (404).';
                } else if (jqXHR.responseText) {
                    errorMsg = 'Error: ' + jqXHR.responseText.substring(0, 100);
                }

                alert(errorMsg);
            },
            complete: function() {
                $('#registration-step-2').removeClass('loading');
            }
        });
    });

    // Step 3: Profile Image
    $('#upload-btn').on('click', function() {
        $('#profile_image').click();
    });

    $('#profile_image').on('change', function(e) {
        const file = e.target.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#profile-preview').attr('src', e.target.result).show();
                $('.placeholder-avatar').hide();
            };
            reader.readAsDataURL(file);
        }
    });

    // Continue to travel step button
    $('#continue-to-travel').on('click', function() {
        const file = $('#profile_image')[0].files[0];

        if (file) {
            // Upload photo first, then go to step 4
            const formData = new FormData();
            formData.append('action', 'cdv_upload_profile_image');
            formData.append('nonce', cdvAjax.nonce);
            formData.append('profile_image', file);

            console.log('Step 3 - Uploading image:', file.name, 'Size:', file.size, 'Type:', file.type);
            console.log('Step 3 - Nonce:', cdvAjax.nonce);

            $('#registration-step-3').addClass('loading');

            $.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Step 3 - Upload response:', response);
                    if (response.success) {
                        console.log('Step 3 - Image uploaded successfully');
                        nextStep(); // Go to step 4
                    } else {
                        alert(response.data.message || 'Upload error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Step 3 - Upload error:', {
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        responseText: jqXHR.responseText
                    });

                    let errorMsg = 'Connection error';
                    if (jqXHR.status === 500) {
                        errorMsg = 'Server error (500). Check PHP logs.';
                    } else if (jqXHR.status === 403) {
                        errorMsg = 'Access denied (403). Nonce issue.';
                    } else if (jqXHR.status === 413) {
                        errorMsg = 'File too large (413). Reduce image size.';
                    } else if (jqXHR.responseText) {
                        errorMsg = 'Error: ' + jqXHR.responseText.substring(0, 100);
                    }

                    alert(errorMsg);
                },
                complete: function() {
                    $('#registration-step-3').removeClass('loading');
                }
            });
        } else {
            // Skip photo and go to step 4
            console.log('Step 3 - No image selected, skipping');
            nextStep();
        }
    });

    $('#skip-photo').on('click', function() {
        nextStep(); // Go to step 4
    });

    // Step 4: Show/hide travel form
    $('#show-travel-form').on('click', function() {
        console.log('Step 4 - User wants to create travel');
        $('.optional-choice').hide();
        $('#travel-form-fields').fadeIn();
    });

    $('#skip-travel-direct').on('click', function() {
        console.log('Step 4 - User skipped travel creation');
        window.location.href = '<?php echo home_url('/dashboard'); ?>';
    });

    $('#skip-travel-from-form').on('click', function() {
        console.log('Step 4 - User skipped from form');
        window.location.href = '<?php echo home_url('/dashboard'); ?>';
    });

    $('.btn-prev-travel').on('click', function() {
        console.log('Step 4 - Going back, hiding travel form');
        $('#travel-form-fields').hide();
        $('.optional-choice').fadeIn();
    });

    // Toggle between precise dates and month selection
    $('input[name="travel_date_type"]').on('change', function() {
        const dateType = $(this).val();

        if (dateType === 'precise') {
            $('#precise-dates-container-reg').show();
            $('#month-container-reg').hide();
            $('#travel_start_date').prop('required', true);
            $('#travel_end_date').prop('required', true);
            $('#travel_month').prop('required', false);
        } else {
            $('#precise-dates-container-reg').hide();
            $('#month-container-reg').show();
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

    // Step 4: Create Travel (Optional)
    $('#registration-step-4').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serializeArray();
        formData.push({ name: 'action', value: 'cdv_create_first_travel' });
        formData.push({ name: 'nonce', value: cdvAjax.nonce });

        console.log('Step 4 - Creating travel:', formData);
        console.log('Step 4 - Nonce:', cdvAjax.nonce);

        $(this).addClass('loading');

        $.ajax({
            url: cdvAjax.ajaxurl,
            type: 'POST',
            data: $.param(formData),
            dataType: 'json',
            success: function(response) {
                console.log('Step 4 - Response:', response);
                if (response.success) {
                    console.log('Step 4 - Travel created successfully');
                    window.location.href = '<?php echo home_url('/dashboard'); ?>';
                } else {
                    alert(response.data.message || 'Error creating journey');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Step 4 - Error details:', {
                    status: jqXHR.status,
                    statusText: jqXHR.statusText,
                    responseText: jqXHR.responseText
                });

                let errorMsg = 'Connection error';
                if (jqXHR.status === 500) {
                    errorMsg = 'Server error (500). Check PHP logs.';
                } else if (jqXHR.status === 403) {
                    errorMsg = 'Access denied (403). Nonce issue.';
                } else if (jqXHR.responseText) {
                    errorMsg = 'Error: ' + jqXHR.responseText.substring(0, 100);
                }

                alert(errorMsg);
            },
            complete: function() {
                $('#registration-step-4').removeClass('loading');
            }
        });
    });

    // Previous buttons
    $('.btn-prev').on('click', function() {
        prevStep();
    });

    function nextStep() {
        currentStep++;
        updateSteps();
    }

    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            updateSteps();
        }
    }

    function updateSteps() {
        // Update step indicators
        $('.step').removeClass('active completed');
        $('.step[data-step="' + currentStep + '"]').addClass('active');
        $('.step[data-step]').each(function() {
            const step = parseInt($(this).data('step'));
            if (step < currentStep) {
                $(this).addClass('completed');
            }
        });

        // Update forms
        $('.registration-step').removeClass('active');
        $('#registration-step-' + currentStep).addClass('active');

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
</script>

<?php
get_footer();
