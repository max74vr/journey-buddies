<?php
$pageTitle = 'Registration';
include BASE_PATH . '/src/Views/layouts/header.php';

$errors = $_SESSION['errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);
?>

<div class="container" style="max-width: 600px; margin: 4rem auto;">
    <div class="auth-card" style="background: white; padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--shadow-lg);">
        <h1 class="text-center mb-4">Create your account</h1>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error mb-3" style="background: #fed7d7; color: #742a2a; padding: 1rem; border-radius: var(--border-radius);">
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= SITE_URL ?>/register.php" data-validate>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($oldInput['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($oldInput['first_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($oldInput['last_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password * (minimum 8 characters)</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password *</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth *</label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($oldInput['date_of_birth'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="">Prefer not to say</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($oldInput['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" class="form-control" value="<?= htmlspecialchars($oldInput['country'] ?? '') ?>">
            </div>

            <button type="submit" class="btn-primary btn-block">Sign Up</button>
        </form>

        <p class="text-center mt-3">
            Already have an account? <a href="<?= SITE_URL ?>/login.php">Sign In</a>
        </p>
    </div>
</div>

<?php include BASE_PATH . '/src/Views/layouts/footer.php'; ?>
