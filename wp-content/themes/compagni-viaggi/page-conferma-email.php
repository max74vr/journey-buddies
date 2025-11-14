<?php
/**
 * Template Name: Email Confirmation
 * Pagina per confermare l'indirizzo email
 */

get_header();

// Check for verification result in query params
$error = isset($_GET['verification_error']) ? urldecode(sanitize_text_field($_GET['verification_error'])) : '';
$success = isset($_GET['verification_success']) && $_GET['verification_success'] === '1';
$user_id = false;

if ($success) {
    // Get current user or the last verified user
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
    }
}
?>

<main class="site-main email-verification">
    <div class="container">
        <div class="verification-box">
            <?php if ($success) : ?>
                <!-- Successo -->
                <div class="verification-success">
                    <div class="success-icon">✓</div>
                    <h1>Email Confirmed Successfully!</h1>
                    <p>Your email address has been successfully verified.</p>

                    <div class="next-steps">
                        <h3>🎉 Welcome to Journey Buddies!</h3>
                        <p><strong>Il tuo account è ora attivo e pronto all'uso.</strong></p>
                        <p>You can now start:</p>
                        <ul style="text-align: left; display: inline-block;">
                            <li>Search for travel buddies</li>
                            <li>Create your journey listings</li>
                            <li>Join others journeys</li>
                            <li>Share your experiences</li>
                        </ul>
                        <div class="action-buttons">
                            <?php if(is_user_logged_in()): ?>
                                <a href="<?php echo home_url('/dashboard/'); ?>" class="btn btn-primary">
                                    Go to Dashboard
                                </a>
                            <?php else: ?>
                                <a href="<?php echo wp_login_url(); ?>" class="btn btn-primary">
                                    Log In Now
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo get_post_type_archive_link('viaggio'); ?>" class="btn btn-secondary">
                                Discover Journeys
                            </a>
                        </div>
                    </div>
                </div>

            <?php elseif ($error) : ?>
                <!-- Errore -->
                <div class="verification-error">
                    <div class="error-icon">✕</div>
                    <h1>Verification Error</h1>
                    <p class="error-message"><?php echo esc_html($error); ?></p>

                    <div class="error-help">
                        <h3>What you can do:</h3>
                        <ul>
                            <li>Controlla di aver cliccato sul link corretto dall'email</li>
                            <li>Verify that the link has not expired (valid 24 hours)</li>
                            <li>Request a new verification email</li>
                        </ul>

                        <?php if (is_user_logged_in()) : ?>
                            <button class="btn btn-primary" id="resend-verification">
                                Send New Verification Email
                            </button>
                        <?php else : ?>
                            <a href="<?php echo wp_login_url(); ?>" class="btn btn-secondary">
                                Log In to Request New Email
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else : ?>
                <!-- Nessun token -->
                <div class="verification-waiting">
                    <div class="waiting-icon">📧</div>
                    <h1>Confirm Your Email Address</h1>
                    <p>To complete registration, click the link we sent you via email.</p>

                    <div class="help-section">
                        <h3>Non hai ricevuto l'email?</h3>
                        <ul>
                            <li>Check your spam or junk folder</li>
                            <li>Verifica che l'indirizzo email sia corretto</li>
                            <li>Attendi qualche minuto, l'email potrebbe essere in ritardo</li>
                        </ul>

                        <?php if (is_user_logged_in()) : ?>
                            <?php
                            $current_user = wp_get_current_user();
                            $is_verified = CDV_Email_Verification::is_email_verified($current_user->ID);
                            ?>

                            <?php if (!$is_verified) : ?>
                                <p><strong>Registered email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
                                <button class="btn btn-primary" id="resend-verification">
                                    Send New Verification Email
                                </button>
                            <?php else : ?>
                                <p class="success-text">✓ Your email has already been verified!</p>
                                <a href="<?php echo home_url('/dashboard/'); ?>" class="btn btn-primary">
                                    Go to Dashboard
                                </a>
                            <?php endif; ?>
                        <?php else : ?>
                            <a href="<?php echo wp_login_url(); ?>" class="btn btn-secondary">
                                Accedi
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.email-verification {
    padding: 4rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.verification-box {
    background: white;
    border-radius: 16px;
    padding: 3rem;
    max-width: 600px;
    margin: 0 auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    text-align: center;
}

.success-icon, .error-icon, .waiting-icon {
    font-size: 5rem;
    margin-bottom: 1rem;
}

.success-icon {
    color: #28a745;
    background: #d4edda;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
}

.error-icon {
    color: #dc3545;
    background: #f8d7da;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
}

.waiting-icon {
    margin: 0 auto 2rem;
}

.verification-box h1 {
    margin: 0 0 1rem 0;
    color: #333;
}

.verification-box > div > p {
    font-size: 1.125rem;
    color: #666;
    margin-bottom: 2rem;
}

.error-message {
    color: #dc3545;
    font-weight: bold;
    padding: 1rem;
    background: #f8d7da;
    border-radius: 8px;
}

.next-steps, .error-help, .help-section {
    margin-top: 2rem;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
    text-align: left;
}

.next-steps h3, .error-help h3, .help-section h3 {
    margin: 0 0 1rem 0;
    color: var(--primary-color);
}

.next-steps ul, .error-help ul, .help-section ul {
    margin: 1rem 0;
    padding-left: 2rem;
}

.next-steps li, .error-help li, .help-section li {
    margin: 0.5rem 0;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 1.5rem;
}

.success-text {
    color: #28a745;
    font-weight: bold;
    padding: 1rem;
    background: #d4edda;
    border-radius: 8px;
}

#resend-verification {
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .verification-box {
        padding: 2rem 1.5rem;
    }

    .success-icon, .error-icon {
        width: 80px;
        height: 80px;
        font-size: 3rem;
    }

    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resendBtn = document.getElementById('resend-verification');

    if (resendBtn) {
        resendBtn.addEventListener('click', function() {
            this.disabled = true;
            this.textContent = 'Sending...';

            jQuery.ajax({
                url: cdvAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cdv_resend_verification',
                    nonce: cdvAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Verification email sent! Check your inbox.');
                        resendBtn.textContent = 'Email Sent ✓';
                    } else {
                        alert('Errore: ' + (response.data || 'Impossibile inviare l\'email'));
                        resendBtn.disabled = false;
                        resendBtn.textContent = 'Send New Verification Email';
                    }
                },
                error: function() {
                    alert('Errore di connessione. Riprova più tardi.');
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Send New Verification Email';
                }
            });
        });
    }
});
</script>

<?php get_footer(); ?>
