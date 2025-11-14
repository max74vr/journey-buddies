<?php
/**
 * Template Name: Impostazioni Privacy
 * Template for user privacy and GDPR settings
 */

// Require login
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();
$user = get_userdata($user_id);

// Get user consent data
$cookie_consent = get_user_meta($user_id, 'cdv_cookie_consent', true);
?>

<main class="site-main privacy-settings-page">
    <div class="page-header">
        <div class="container">
            <h1>🔒 Privacy and Data Settings</h1>
            <p>Manage your privacy preferences and personal data</p>
        </div>
    </div>

    <div class="container">
        <!-- Cookie Consent Section -->
        <div class="privacy-settings-section">
            <h2>🍪 Cookie Management</h2>

            <?php if ($cookie_consent) : ?>
                <div class="gdpr-notice">
                    <h4>Consent Status</h4>
                    <p>
                        <strong>Last modified:</strong> <?php echo esc_html(date('d/m/Y H:i', strtotime($cookie_consent['timestamp']))); ?><br>
                        <strong>Analytics Cookies:</strong> <?php echo $cookie_consent['analytics'] ? '✓ Accepted' : '✗ Rejected'; ?><br>
                        <strong>Marketing Cookies:</strong> <?php echo $cookie_consent['marketing'] ? '✓ Accepted' : '✗ Rejected'; ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="privacy-setting-item">
                <h3>Essential Cookies</h3>
                <p>Necessary for the site to function (authentication, cart, preferences). Always active.</p>
                <label style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                    <input type="checkbox" checked disabled style="width: 20px; height: 20px;">
                    <strong>Enabled (required)</strong>
                </label>
            </div>

            <div class="privacy-setting-item">
                <h3>Cookie Analitici</h3>
                <p>Help us understand how you use the site to improve your experience.</p>
                <label style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                    <input type="checkbox" class="gdpr-consent-checkbox" data-consent-type="analytics"
                           <?php echo (isset($cookie_consent['analytics']) && $cookie_consent['analytics']) ? 'checked' : ''; ?>
                           style="width: 20px; height: 20px;">
                    <strong>Enable Analytics Cookies</strong>
                </label>
            </div>

            <div class="privacy-setting-item">
                <h3>Marketing Cookies</h3>
                <p>Used to show personalized advertising content.</p>
                <label style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                    <input type="checkbox" class="gdpr-consent-checkbox" data-consent-type="marketing"
                           <?php echo (isset($cookie_consent['marketing']) && $cookie_consent['marketing']) ? 'checked' : ''; ?>
                           style="width: 20px; height: 20px;">
                    <strong>Enable Marketing Cookies</strong>
                </label>
            </div>
        </div>

        <!-- Data Export Section -->
        <div class="privacy-settings-section">
            <h2>📦 Export Your Data</h2>
            <div class="privacy-setting-item">
                <h3>Request a Copy of Your Data</h3>
                <p>
                    Under GDPR, you have the right to receive a copy of all personal data we hold about you.
                    This includes your profile, journeys, messages, reviews and more.
                </p>
                <p>
                    Data will be provided in JSON format, easily readable and portable.
                </p>
                <div class="privacy-actions">
                    <button id="cdv-export-data" class="btn btn-primary">
                        📥 Download Your Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Retention Section -->
        <div class="privacy-settings-section">
            <h2>⏱️ Data Retention</h2>
            <div class="privacy-setting-item">
                <h3>Retention Policy</h3>
                <p>
                    Your personal data is stored for the following periods:
                </p>
                <ul style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                    <li><strong>User profile:</strong> Fino alla cancellazione dell'account</li>
                    <li><strong>Published journeys:</strong> Until manual deletion</li>
                    <li><strong>Messages:</strong> 2 years from sending (then automatically deleted)</li>
                    <li><strong>Reviews:</strong> Permanent (anonymized upon account deletion)</li>
                    <li><strong>Security logs:</strong> 90 days</li>
                </ul>
            </div>
        </div>

        <!-- Data Processing Section -->
        <div class="privacy-settings-section">
            <h2>⚙️ How We Use Your Data</h2>
            <div class="privacy-setting-item">
                <h3>Processing Purposes</h3>
                <p>We process your personal data for the following purposes:</p>
                <ul style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                    <li>✓ Provide and manage the travel buddy search service</li>
                    <li>✓ Facilitate communication between users</li>
                    <li>✓ Ensure platform security</li>
                    <li>✓ Improve service quality</li>
                    <li>✓ Send notifications related to your journeys</li>
                    <li>✓ Comply with legal obligations</li>
                </ul>
                <p style="margin-top: 15px;">
                    <strong>Legal basis:</strong> Contract execution, legitimate interest, consent (for non-essential cookies)
                </p>
            </div>
        </div>

        <!-- Account Deletion Section -->
        <div class="privacy-settings-section" style="border: 2px solid #f56565;">
            <h2 style="color: #c53030;">🗑️ Account and Data Deletion</h2>
            <div class="privacy-setting-item">
                <h3>Right to Deletion (Right to be Forgotten)</h3>
                <p>
                    Under GDPR, you have the right to request complete deletion of your account and all associated data.
                </p>
                <p>
                    <strong>What happens when you request deletion:</strong>
                </p>
                <ul style="margin: 15px 0; padding-left: 25px; line-height: 1.8;">
                    <li>You will receive an email confirmation</li>
                    <li>Your account will be deactivated immediately</li>
                    <li>Your personal data will be deleted or anonymized within 30 days</li>
                    <li>Published journeys will be removed</li>
                    <li>Reviews will be anonymized (kept for community transparency)</li>
                </ul>

                <div class="gdpr-notice" style="background: #fff5f5; border-color: #f56565; margin: 20px 0;">
                    <h4 style="color: #c53030;">⚠️ Warning</h4>
                    <p>
                        La cancellazione dell'account è <strong>irreversible</strong>.
                        Once completed, it will not be possible to recover your data.
                    </p>
                </div>

                <div class="privacy-actions">
                    <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" class="btn btn-secondary" target="_blank">
                        📄 Read the Privacy Policy
                    </a>
                    <button id="cdv-request-deletion" class="btn btn-danger">
                        🗑️ Request Account Deletion
                    </button>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="privacy-settings-section">
            <h2>📧 Contact the Data Protection Officer</h2>
            <div class="privacy-setting-item">
                <p>
                    For any questions about privacy or your personal data, contact our Data Protection Officer:
                </p>
                <p style="margin-top: 15px;">
                    <strong>Email:</strong> <a href="mailto:privacy@compagnidiviaggi.com">privacy@compagnidiviaggi.com</a><br>
                    <strong>Response within:</strong> 48 business hours
                </p>
            </div>
        </div>
    </div>
</main>

<style>
.privacy-settings-page {
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

.privacy-settings-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.privacy-settings-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2d3748;
    font-size: 1.5rem;
}

.privacy-setting-item {
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 15px;
}

.privacy-setting-item:last-child {
    margin-bottom: 0;
}

.privacy-setting-item h3 {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
    color: #2d3748;
}

.privacy-setting-item p {
    margin: 0 0 10px 0;
    color: #4a5568;
    font-size: 0.95rem;
    line-height: 1.6;
}

.privacy-setting-item p:last-child {
    margin-bottom: 0;
}

.privacy-setting-item ul {
    color: #4a5568;
    font-size: 0.95rem;
}

.privacy-actions {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.gdpr-notice {
    background: #eef2ff;
    border-left: 4px solid #667eea;
    padding: 15px 20px;
    border-radius: 6px;
    margin: 20px 0;
}

.gdpr-notice h4 {
    margin: 0 0 10px 0;
    color: #434190;
    font-size: 1rem;
}

.gdpr-notice p {
    margin: 0;
    color: #4a5568;
    font-size: 0.9rem;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .privacy-settings-section {
        padding: 20px;
    }

    .privacy-actions {
        flex-direction: column;
    }

    .privacy-actions .btn {
        width: 100%;
    }
}
</style>

<?php
get_footer();
