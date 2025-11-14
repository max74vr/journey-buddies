<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Models/TravelPost.php';
require_once BASE_PATH . '/src/Models/User.php';

$pageTitle = 'Home';

$travelPostModel = new TravelPost();
$userModel = new User();

// Get recent travels
$recentTravels = $travelPostModel->getAll(['status' => 'planning', 'available_spots' => true], 1, 6);

// Get featured users
$featuredUsers = $userModel->getFeaturedUsers(6);

include BASE_PATH . '/src/Views/layouts/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Trova il tuo compagno di viaggio ideale</h1>
            <p class="hero-subtitle">Unisciti alla community di viaggiatori, condividi esperienze e parti per nuove avventure insieme</p>

            <div class="hero-search">
                <form action="<?= SITE_URL ?>/travels.php" method="GET">
                    <div class="search-group">
                        <input type="text" name="destination" placeholder="Dove vuoi andare?" class="search-input">
                        <input type="date" name="start_date" placeholder="Data inizio" class="search-input">
                        <select name="travel_type" class="search-input">
                            <option value="">Tipo di viaggio</option>
                            <option value="avventura">Avventura</option>
                            <option value="mare">Mare</option>
                            <option value="città">Città</option>
                            <option value="smart-working">Smart Working</option>
                            <option value="relax">Relax</option>
                            <option value="party">Party</option>
                            <option value="cultura">Cultura</option>
                            <option value="natura">Natura</option>
                        </select>
                        <button type="submit" class="btn-primary">Cerca</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Come Funziona</h2>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">👤</div>
                <h3>Crea il tuo profilo</h3>
                <p>Racconta chi sei, i tuoi interessi di viaggio e le lingue che parli</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Cerca e scopri</h3>
                <p>Esplora viaggi pianificati o trova compagni per la tua prossima avventura</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>Connettiti</h3>
                <p>Chatta con altri viaggiatori e organizza i dettagli del viaggio</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">✈️</div>
                <h3>Parti insieme</h3>
                <p>Vivi esperienze uniche e crea ricordi indimenticabili</p>
            </div>
        </div>
    </div>
</section>

<section class="section bg-light">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Viaggi Disponibili</h2>
            <a href="<?= SITE_URL ?>/travels.php" class="btn-link">Vedi tutti →</a>
        </div>

        <?php if (!empty($recentTravels)): ?>
        <div class="travel-grid">
            <?php foreach ($recentTravels as $travel): ?>
            <div class="travel-card">
                <?php if ($travel['cover_image']): ?>
                <div class="travel-card-image" style="background-image: url('<?= SITE_URL ?>/uploads/travels/<?= htmlspecialchars($travel['cover_image']) ?>')"></div>
                <?php else: ?>
                <div class="travel-card-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)"></div>
                <?php endif; ?>

                <div class="travel-card-content">
                    <div class="travel-type-badge"><?= getTravelTypeIcon($travel['travel_type']) ?> <?= htmlspecialchars($travel['travel_type']) ?></div>

                    <h3><?= htmlspecialchars($travel['title']) ?></h3>

                    <div class="travel-meta">
                        <span>📍 <?= htmlspecialchars($travel['destination']) ?>, <?= htmlspecialchars($travel['country']) ?></span>
                        <span>📅 <?= formatDate($travel['start_date']) ?></span>
                    </div>

                    <p class="travel-description"><?= htmlspecialchars(substr($travel['description'], 0, 120)) ?>...</p>

                    <div class="travel-footer">
                        <div class="travel-creator">
                            <?php if ($travel['profile_photo']): ?>
                            <img src="<?= SITE_URL ?>/uploads/profiles/<?= htmlspecialchars($travel['profile_photo']) ?>" alt="<?= htmlspecialchars($travel['first_name']) ?>">
                            <?php else: ?>
                            <div class="avatar-placeholder"><?= strtoupper(substr($travel['first_name'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($travel['first_name']) ?></span>
                        </div>

                        <div class="travel-spots">
                            <span><?= $travel['max_participants'] - $travel['current_participants'] ?> posti liberi</span>
                        </div>
                    </div>

                    <a href="<?= SITE_URL ?>/travel.php?id=<?= $travel['id'] ?>" class="btn-primary btn-block">Vedi dettagli</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center">Nessun viaggio disponibile al momento. <a href="<?= SITE_URL ?>/create-travel.php">Crea il primo!</a></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Viaggiatori in Evidenza</h2>
            <a href="<?= SITE_URL ?>/explore-travelers.php" class="btn-link">Scopri altri →</a>
        </div>

        <?php if (!empty($featuredUsers)): ?>
        <div class="users-grid">
            <?php foreach ($featuredUsers as $user): ?>
            <div class="user-card">
                <div class="user-card-header">
                    <?php if ($user['profile_photo']): ?>
                    <img src="<?= SITE_URL ?>/uploads/profiles/<?= htmlspecialchars($user['profile_photo']) ?>" alt="<?= htmlspecialchars($user['first_name']) ?>" class="user-avatar">
                    <?php else: ?>
                    <div class="user-avatar-placeholder"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>
                    <?php endif; ?>

                    <?php if ($user['is_verified']): ?>
                    <span class="verified-badge" title="Profilo verificato">✓</span>
                    <?php endif; ?>
                </div>

                <h3><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars(substr($user['last_name'], 0, 1)) ?>.</h3>
                <p class="user-location">📍 <?= htmlspecialchars($user['city'] ?? 'Italia') ?></p>

                <?php if ($user['reputation_score'] > 0): ?>
                <div class="user-rating">
                    ⭐ <?= number_format($user['reputation_score'], 1) ?> (<?= $user['total_trips'] ?> viaggi)
                </div>
                <?php endif; ?>

                <a href="<?= SITE_URL ?>/profile.php?id=<?= $user['id'] ?>" class="btn-secondary btn-block">Vedi profilo</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Pronto a partire per la tua prossima avventura?</h2>
            <p>Unisciti alla nostra community di viaggiatori e inizia a esplorare il mondo insieme</p>
            <?php if (!isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/register.php" class="btn-primary btn-lg">Registrati Ora</a>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/create-travel.php" class="btn-primary btn-lg">Crea il tuo viaggio</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/src/Views/layouts/footer.php'; ?>
