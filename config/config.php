<?php
/**
 * General Configuration
 * Compagni di Viaggi
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'Compagni di Viaggi');

// Auto-detect SITE_URL if not set in environment
if (getenv('SITE_URL')) {
    define('SITE_URL', getenv('SITE_URL'));
} else {
    // Auto-detect from server variables
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $protocol . '://' . $host);
}

define('BASE_PATH', dirname(__DIR__));

// Upload directories
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('PROFILE_PHOTOS_DIR', UPLOAD_DIR . 'profiles/');
define('TRAVEL_PHOTOS_DIR', UPLOAD_DIR . 'travels/');
define('VERIFICATION_DOCS_DIR', UPLOAD_DIR . 'verifications/');

// Create upload directories if they don't exist
$dirs = [UPLOAD_DIR, PROFILE_PHOTOS_DIR, TRAVEL_PHOTOS_DIR, VERIFICATION_DOCS_DIR];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Pagination
define('ITEMS_PER_PAGE', 12);

// Review settings
define('MIN_REVIEW_SCORE', 1);
define('MAX_REVIEW_SCORE', 5);

// Chat settings
define('MAX_MESSAGE_LENGTH', 2000);
define('SPAM_THRESHOLD', 10); // messages per minute

// Timezone
date_default_timezone_set('Europe/Rome');

// Error reporting (production mode by default)
// Set ENVIRONMENT=development in .env for development mode
$environment = getenv('ENVIRONMENT') ?: 'production';
if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/php-errors.log');
}

// Include database configuration
require_once BASE_PATH . '/config/database.php';

// Helper functions
require_once BASE_PATH . '/includes/helpers.php';
