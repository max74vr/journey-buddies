<?php
$pageTitle = 'Login';
include BASE_PATH . '/src/Views/layouts/header.php';
?>

<div class="container" style="max-width: 500px; margin: 4rem auto;">
    <div class="auth-card" style="background: white; padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--shadow-lg);">
        <h1 class="text-center mb-4">Sign In</h1>

        <form method="POST" action="<?= SITE_URL ?>/login.php" data-validate>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn-primary btn-block">Sign In</button>
        </form>

        <p class="text-center mt-3">
            Don't have an account? <a href="<?= SITE_URL ?>/register.php">Sign Up</a>
        </p>
    </div>
</div>

<?php include BASE_PATH . '/src/Views/layouts/footer.php'; ?>
