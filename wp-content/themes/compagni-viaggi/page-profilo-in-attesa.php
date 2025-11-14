<?php
/**
 * Template Name: Pending Profile
 *
 * Page shown to users awaiting approval
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$user_id = get_current_user_id();
$approved = CDV_User_Roles::is_user_approved($user_id);

// Redirect if already approved
if ($approved) {
    wp_redirect(home_url('/viaggi'));
    exit;
}

$profile_completion = CDV_User_Roles::get_profile_completion($user_id);

get_header();
?>

<main class="site-main pending-profile-page">
    <div class="container">
        <div class="pending-wrapper">
            <div class="pending-icon">
                <span class="hourglass">⏳</span>
            </div>

            <h1>Your profile is pending approval</h1>

            <p class="subtitle">Thank you for registering on Journey Buddies!</p>

            <div class="status-box">
                <div class="status-header">
                    <strong>Registration Status:</strong>
                    <span class="status-badge pending">Pending</span>
                </div>

                <div class="status-content">
                    <p>Our team is verifying your profile to ensure community safety.</p>
                    <p>You will receive an email as soon as your account is approved, usually within 24 hours.</p>
                </div>

                <!-- Profile Completion -->
                <div class="profile-completion-box">
                    <div class="completion-header">
                        <strong>Profile Completion</strong>
                        <span><?php echo $profile_completion; ?>%</span>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $profile_completion; ?>%;"></div>
                    </div>

                    <?php if ($profile_completion < 100) : ?>
                        <p class="help-text">
                            <small>A complete profile is more likely to be approved quickly!</small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="icon">✓</div>
                    <h3>Account Created</h3>
                    <p>Your account has been successfully created</p>
                </div>

                <div class="info-card">
                    <div class="icon">👤</div>
                    <h3>Profile Filled</h3>
                    <p>You have completed <?php echo $profile_completion; ?>% of your profile</p>
                </div>

                <div class="info-card">
                    <div class="icon">📧</div>
                    <h3>Confirmation Email</h3>
                    <p>You will receive an email upon approval</p>
                </div>
            </div>

            <div class="what-next">
                <h2>What happens now?</h2>

                <div class="steps-list">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>We will verify your profile</h4>
                            <p>Our team will check that all information is complete and complies with our guidelines.</p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>You will receive a confirmation</h4>
                            <p>We will send you an email to <strong><?php echo wp_get_current_user()->user_email; ?></strong> as soon as your account is approved.</p>
                        </div>
                    </div>

                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>You can start traveling!</h4>
                            <p>After approval you can search for journeys, create listings, join groups and much more!</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions-box">
                <?php if ($profile_completion < 100) : ?>
                    <a href="<?php echo get_edit_user_link(); ?>" class="btn-primary">Complete Profile</a>
                <?php endif; ?>

                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn-secondary">Log Out</a>
            </div>

            <div class="help-box">
                <p><strong>Questions?</strong> Contact us at <a href="mailto:<?php echo get_option('admin_email'); ?>"><?php echo get_option('admin_email'); ?></a></p>
            </div>
        </div>
    </div>
</main>

<style>
.pending-profile-page {
    padding: calc(var(--spacing-unit) * 8) 0;
    background: var(--bg-light);
}

.pending-wrapper {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.pending-icon {
    margin-bottom: calc(var(--spacing-unit) * 4);
}

.hourglass {
    font-size: 5rem;
    display: block;
    animation: rotate 2s infinite ease-in-out;
}

@keyframes rotate {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(180deg); }
}

.pending-wrapper h1 {
    color: var(--text-dark);
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.subtitle {
    font-size: 1.2rem;
    color: var(--text-medium);
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.status-box {
    background: white;
    padding: calc(var(--spacing-unit) * 4);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    margin-bottom: calc(var(--spacing-unit) * 6);
    text-align: left;
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: calc(var(--spacing-unit) * 3);
    padding-bottom: calc(var(--spacing-unit) * 2);
    border-bottom: 2px solid var(--border-color);
}

.status-badge {
    padding: calc(var(--spacing-unit) * 1) calc(var(--spacing-unit) * 2);
    border-radius: 999px;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-content p {
    margin-bottom: calc(var(--spacing-unit) * 2);
    color: var(--text-medium);
}

.profile-completion-box {
    margin-top: calc(var(--spacing-unit) * 4);
    padding-top: calc(var(--spacing-unit) * 4);
    border-top: 1px solid var(--border-color);
}

.completion-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.progress-bar {
    width: 100%;
    height: 30px;
    background: var(--bg-gray);
    border-radius: var(--border-radius-sm);
    overflow: hidden;
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    transition: width var(--transition-base);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: calc(var(--spacing-unit) * 3);
    margin-bottom: calc(var(--spacing-unit) * 6);
}

.info-card {
    background: white;
    padding: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.info-card .icon {
    font-size: 2.5rem;
    margin-bottom: calc(var(--spacing-unit) * 2);
}

.info-card h3 {
    font-size: 1.1rem;
    margin-bottom: calc(var(--spacing-unit) * 1);
}

.info-card p {
    color: var(--text-medium);
    font-size: 0.9rem;
}

.what-next {
    background: white;
    padding: calc(var(--spacing-unit) * 4);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    margin-bottom: calc(var(--spacing-unit) * 6);
    text-align: left;
}

.what-next h2 {
    text-align: center;
    margin-bottom: calc(var(--spacing-unit) * 4);
}

.steps-list {
    display: flex;
    flex-direction: column;
    gap: calc(var(--spacing-unit) * 3);
}

.step-item {
    display: flex;
    gap: calc(var(--spacing-unit) * 3);
}

.step-number {
    width: 50px;
    height: 50px;
    flex-shrink: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.5rem;
}

.step-content h4 {
    margin-bottom: calc(var(--spacing-unit) * 1);
}

.step-content p {
    color: var(--text-medium);
}

.actions-box {
    display: flex;
    gap: calc(var(--spacing-unit) * 2);
    justify-content: center;
    margin-bottom: calc(var(--spacing-unit) * 4);
}

.help-box {
    background: var(--bg-light);
    padding: calc(var(--spacing-unit) * 3);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--info-color);
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .actions-box {
        flex-direction: column;
    }

    .actions-box .btn-primary,
    .actions-box .btn-secondary {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php
get_footer();
