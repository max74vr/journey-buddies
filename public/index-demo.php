<?php
/**
 * DEMO VERSION - With Mock Data
 * This version works without database for testing the frontend
 */

$pageTitle = 'Home';

// Mock data for demo
$recentTravels = [
    [
        'id' => 1,
        'title' => 'Trekking nelle Dolomiti',
        'description' => 'Alla scoperta dei sentieri più belli delle Dolomiti, tra rifugi alpini e panorami mozzafiato. Perfetto per amanti della montagna!',
        'destination' => 'Cortina d\'Ampezzo',
        'country' => 'Italia',
        'start_date' => '2025-07-15',
        'end_date' => '2025-07-22',
        'travel_type' => 'avventura',
        'budget_level' => 'medium',
        'max_participants' => 6,
        'current_participants' => 2,
        'cover_image' => null,
        'first_name' => 'Marco',
        'profile_photo' => null,
        'reputation_score' => 4.8
    ],
    [
        'id' => 2,
        'title' => 'Sardegna in barca a vela',
        'description' => 'Una settimana tra le acque cristalline della Sardegna, navigando da cala a cala. Esperienza velica non necessaria!',
        'destination' => 'Costa Smeralda',
        'country' => 'Italia',
        'start_date' => '2025-08-01',
        'end_date' => '2025-08-08',
        'travel_type' => 'mare',
        'budget_level' => 'high',
        'max_participants' => 8,
        'current_participants' => 5,
        'cover_image' => null,
        'first_name' => 'Sara',
        'profile_photo' => null,
        'reputation_score' => 4.9
    ],
    [
        'id' => 3,
        'title' => 'Weekend a Barcellona',
        'description' => 'Esploriamo la città di Gaudì! Arte, cultura, tapas e vita notturna. Viaggio low-cost con volo e hostel.',
        'destination' => 'Barcellona',
        'country' => 'Spagna',
        'start_date' => '2025-06-20',
        'end_date' => '2025-06-23',
        'travel_type' => 'città',
        'budget_level' => 'low',
        'max_participants' => 4,
        'current_participants' => 1,
        'cover_image' => null,
        'first_name' => 'Luca',
        'profile_photo' => null,
        'reputation_score' => 4.6
    ]
];

$featuredUsers = [
    [
        'id' => 1,
        'first_name' => 'Elena',
        'last_name' => 'Rossi',
        'city' => 'Milano',
        'profile_photo' => null,
        'is_verified' => true,
        'reputation_score' => 4.9,
        'total_trips' => 12
    ],
    [
        'id' => 2,
        'first_name' => 'Francesco',
        'last_name' => 'Bianchi',
        'city' => 'Roma',
        'profile_photo' => null,
        'is_verified' => true,
        'reputation_score' => 4.8,
        'total_trips' => 8
    ],
    [
        'id' => 3,
        'first_name' => 'Giulia',
        'last_name' => 'Verdi',
        'city' => 'Firenze',
        'profile_photo' => null,
        'is_verified' => false,
        'reputation_score' => 4.7,
        'total_trips' => 5
    ],
    [
        'id' => 4,
        'first_name' => 'Andrea',
        'last_name' => 'Neri',
        'city' => 'Bologna',
        'profile_photo' => null,
        'is_verified' => true,
        'reputation_score' => 4.6,
        'total_trips' => 7
    ]
];

// Define constants for demo
define('SITE_URL', '');
define('BASE_PATH', dirname(__DIR__));

// Helper functions for demo
function isLoggedIn() {
    return false;
}

function getTravelTypeIcon($type) {
    $icons = [
        'avventura' => '🏔️',
        'mare' => '🏖️',
        'città' => '🏙️',
        'smart-working' => '💻',
        'relax' => '🧘',
        'party' => '🎉',
        'cultura' => '🎭',
        'natura' => '🌲'
    ];
    return $icons[$type] ?? '✈️';
}

function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function getFlashMessage() {
    return null;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Compagni di Viaggi' ?> - Trova il tuo compagno di viaggio</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <a href="/index-demo.php">
                        <span class="logo-icon">✈️</span>
                        <span class="logo-text">Compagni di Viaggi</span>
                    </a>
                </div>

                <ul class="nav-menu">
                    <li><a href="/index-demo.php">Home</a></li>
                    <li><a href="#" onclick="alert('Demo: Database non disponibile')">Scopri Viaggi</a></li>
                    <li><a href="#" onclick="alert('Demo: Database non disponibile')">Trova Viaggiatori</a></li>
                    <li><a href="#" onclick="alert('Demo: Database non disponibile')" class="btn-secondary">Login</a></li>
                    <li><a href="#" onclick="alert('Demo: Database non disponibile')" class="btn-primary">Registrati</a></li>
                </ul>

                <div class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </nav>
        </div>
    </header>

    <div style="background: #fef3cd; border-left: 4px solid #f0ad4e; padding: 1rem; text-align: center;">
        <strong>⚠️ MODALITÀ DEMO</strong> - Questa è una versione di test con dati fittizi (MySQL non disponibile)
    </div>

    <main class="main-content">

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Trova il tuo compagno di viaggio ideale</h1>
            <p class="hero-subtitle">Unisciti alla community di viaggiatori, condividi esperienze e parti per nuove avventure insieme</p>

            <div class="hero-search">
                <form onsubmit="alert('Demo: Ricerca non disponibile senza database'); return false;">
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
            <a href="#" onclick="alert('Demo: Database non disponibile'); return false;" class="btn-link">Vedi tutti →</a>
        </div>

        <div class="travel-grid">
            <?php foreach ($recentTravels as $travel): ?>
            <div class="travel-card">
                <div class="travel-card-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)"></div>

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
                            <div class="avatar-placeholder"><?= strtoupper(substr($travel['first_name'], 0, 1)) ?></div>
                            <span><?= htmlspecialchars($travel['first_name']) ?></span>
                        </div>

                        <div class="travel-spots">
                            <span><?= $travel['max_participants'] - $travel['current_participants'] ?> posti liberi</span>
                        </div>
                    </div>

                    <a href="#" onclick="alert('Demo: Dettagli viaggio non disponibili senza database'); return false;" class="btn-primary btn-block">Vedi dettagli</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Viaggiatori in Evidenza</h2>
            <a href="#" onclick="alert('Demo: Database non disponibile'); return false;" class="btn-link">Scopri altri →</a>
        </div>

        <div class="users-grid">
            <?php foreach ($featuredUsers as $user): ?>
            <div class="user-card">
                <div class="user-card-header">
                    <div class="user-avatar-placeholder"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>

                    <?php if ($user['is_verified']): ?>
                    <span class="verified-badge" title="Profilo verificato">✓</span>
                    <?php endif; ?>
                </div>

                <h3><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars(substr($user['last_name'], 0, 1)) ?>.</h3>
                <p class="user-location">📍 <?= htmlspecialchars($user['city']) ?></p>

                <?php if ($user['reputation_score'] > 0): ?>
                <div class="user-rating">
                    ⭐ <?= number_format($user['reputation_score'], 1) ?> (<?= $user['total_trips'] ?> viaggi)
                </div>
                <?php endif; ?>

                <a href="#" onclick="alert('Demo: Profilo non disponibile senza database'); return false;" class="btn-secondary btn-block">Vedi profilo</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Pronto a partire per la tua prossima avventura?</h2>
            <p>Unisciti alla nostra community di viaggiatori e inizia a esplorare il mondo insieme</p>
            <a href="#" onclick="alert('Demo: Registrazione richiede database MySQL configurato'); return false;" class="btn-primary btn-lg">Registrati Ora</a>
        </div>
    </div>
</section>

    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Compagni di Viaggi</h3>
                    <p>La community per trovare compagni di viaggio e vivere avventure indimenticabili insieme.</p>
                </div>

                <div class="footer-section">
                    <h4>Esplora</h4>
                    <ul>
                        <li><a href="#">Scopri Viaggi</a></li>
                        <li><a href="#">Trova Viaggiatori</a></li>
                        <li><a href="#">Crea un Viaggio</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Supporto</h4>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Come Funziona</a></li>
                        <li><a href="#">Sicurezza</a></li>
                        <li><a href="#">Contatti</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Legale</h4>
                    <ul>
                        <li><a href="#">Termini di Servizio</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Compagni di Viaggi. Tutti i diritti riservati.</p>
            </div>
        </div>
    </footer>

    <script src="/js/main.js"></script>
</body>
</html>
