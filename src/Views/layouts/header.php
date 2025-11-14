<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Journey Buddies' ?> - Find your travel companion</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <a href="<?= SITE_URL ?>/index.php">
                        <span class="logo-icon">✈️</span>
                        <span class="logo-text">Journey Buddies</span>
                    </a>
                </div>

                <ul class="nav-menu">
                    <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= SITE_URL ?>/travels.php">Discover Journeys</a></li>
                    <li><a href="<?= SITE_URL ?>/explore-travelers.php">Find Travelers</a></li>

                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= SITE_URL ?>/dashboard.php">Dashboard</a></li>
                        <li><a href="<?= SITE_URL ?>/chats.php">
                            Messages
                            <span class="unread-badge" id="unread-count" style="display:none;"></span>
                        </a></li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle">
                                <?= htmlspecialchars($_SESSION['username']) ?> ▼
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?= SITE_URL ?>/profile.php">My Profile</a></li>
                                <li><a href="<?= SITE_URL ?>/edit-profile.php">Edit Profile</a></li>
                                <li><a href="<?= SITE_URL ?>/create-travel.php">Create Journey</a></li>
                                <li><a href="<?= SITE_URL ?>/pending-reviews.php">Pending Reviews</a></li>
                                <li><hr></li>
                                <li><a href="<?= SITE_URL ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="<?= SITE_URL ?>/login.php" class="btn-secondary">Login</a></li>
                        <li><a href="<?= SITE_URL ?>/register.php" class="btn-primary">Sign Up</a></li>
                    <?php endif; ?>
                </ul>

                <div class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </nav>
        </div>
    </header>

    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
    <div class="flash-message flash-<?= $flash['type'] ?>">
        <div class="container">
            <p><?= htmlspecialchars($flash['message']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <main class="main-content">
