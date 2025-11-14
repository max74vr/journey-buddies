<?php
/**
 * Template Name: Login
 * Frontend login for users
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

get_header();
?>

<main class="main-content">
    <div class="container" style="max-width: 500px; padding: calc(var(--spacing-unit) * 8) calc(var(--spacing-unit) * 3);">
        <div class="login-container">
            <div class="login-header" style="text-align: center; margin-bottom: calc(var(--spacing-unit) * 5);">
                <h1 style="margin-bottom: calc(var(--spacing-unit) * 2);">Welcome Back!</h1>
                <p style="color: var(--text-medium); font-size: 1.1rem;">
                    Login to your account to manage your journeys
                </p>
            </div>

            <div class="login-form-wrapper" style="background: white; padding: calc(var(--spacing-unit) * 4); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                <form id="login-form" class="cdv-form">
                    <?php wp_nonce_field('cdv_login_nonce', 'login_nonce'); ?>

                    <div id="login-message" style="display: none; margin-bottom: calc(var(--spacing-unit) * 3);"></div>

                    <div class="form-group">
                        <label for="login_username">Username or Email</label>
                        <input
                            type="text"
                            id="login_username"
                            name="username"
                            class="form-control"
                            placeholder="Your username or email"
                            required
                            autocomplete="username"
                        >
                    </div>

                    <div class="form-group">
                        <label for="login_password">Password</label>
                        <input
                            type="password"
                            id="login_password"
                            name="password"
                            class="form-control"
                            placeholder="Your password"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: calc(var(--spacing-unit) * 1);">
                        <input
                            type="checkbox"
                            id="remember_me"
                            name="remember"
                            value="1"
                            style="width: auto; margin: 0;"
                        >
                        <label for="remember_me" style="margin: 0; font-weight: normal; cursor: pointer;">
                            Remember me
                        </label>
                    </div>

                    <button type="submit" id="login-btn" class="btn-primary" style="width: 100%; margin-top: calc(var(--spacing-unit) * 3);">
                        Login
                    </button>

                    <div class="login-links" style="margin-top: calc(var(--spacing-unit) * 3); text-align: center; display: flex; flex-direction: column; gap: calc(var(--spacing-unit) * 2);">
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" style="color: var(--primary-color); text-decoration: none;">
                            Forgot password?
                        </a>
                        <div style="color: var(--text-medium);">
                            Don't have an account?
                            <a href="<?php echo esc_url(home_url('/registrazione')); ?>" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">
                                Sign Up
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Social Login (optional future feature) -->
            <!-- <div class="social-login" style="margin-top: calc(var(--spacing-unit) * 4); text-align: center;">
                <p style="color: var(--text-medium); margin-bottom: calc(var(--spacing-unit) * 2);">
                    Oppure accedi con
                </p>
                <div style="display: flex; gap: calc(var(--spacing-unit) * 2); justify-content: center;">
                    <button class="btn-social btn-google">Google</button>
                    <button class="btn-social btn-facebook">Facebook</button>
                </div>
            </div> -->
        </div>
    </div>
</main>

<style>
.cdv-form .form-group {
    margin-bottom: calc(var(--spacing-unit) * 3);
}

.cdv-form label {
    display: block;
    margin-bottom: calc(var(--spacing-unit) * 1);
    font-weight: 600;
    color: var(--text-dark);
}

.cdv-form .form-control {
    width: 100%;
    padding: calc(var(--spacing-unit) * 1.5);
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.cdv-form .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.login-message {
    padding: calc(var(--spacing-unit) * 2);
    border-radius: 8px;
    font-size: 0.95rem;
}

.login-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.login-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .login-form-wrapper {
        padding: calc(var(--spacing-unit) * 3) !important;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#login-form').on('submit', function(e) {
        e.preventDefault();

        const username = $('#login_username').val();
        const password = $('#login_password').val();
        const remember = $('#remember_me').is(':checked');
        const messageDiv = $('#login-message');
        const submitBtn = $('#login-btn');

        // Validation
        if (!username || !password) {
            messageDiv.html('<div class="login-message error">Inserisci username e password</div>').show();
            return;
        }

        // Disable button
        submitBtn.prop('disabled', true).text('Accesso in corso...');
        messageDiv.hide();

        // AJAX login
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cdv_frontend_login',
                username: username,
                password: password,
                remember: remember,
                nonce: $('#login_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    messageDiv.html('<div class="login-message success">' + response.data.message + '</div>').show();

                    // Redirect
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    messageDiv.html('<div class="login-message error">' + response.data.message + '</div>').show();
                    submitBtn.prop('disabled', false).text('Accedi');
                }
            },
            error: function() {
                messageDiv.html('<div class="login-message error">Errore di connessione. Riprova.</div>').show();
                submitBtn.prop('disabled', false).text('Accedi');
            }
        });
    });
});
</script>

<?php get_footer(); ?>
