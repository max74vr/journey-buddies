<?php
/**
 * Helper Functions
 * Compagni di Viaggi
 */

/**
 * Sanitize user input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a specific page
 */
function redirect($path) {
    header("Location: " . SITE_URL . $path);
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Calculate days until a date
 */
function daysUntil($date) {
    $now = new DateTime();
    $target = new DateTime($date);
    $interval = $now->diff($target);
    return $interval->days;
}

/**
 * Upload file with validation
 */
function uploadFile($file, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Errore nel caricamento del file'];
    }

    // Validate file type
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo di file non consentito'];
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5242880) {
        return ['success' => false, 'error' => 'File troppo grande (max 5MB)'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Errore nel salvataggio del file'];
}

/**
 * Calculate average review score
 */
function calculateAverageReviewScore($reviews) {
    if (empty($reviews)) {
        return 0;
    }

    $totalScore = 0;
    $totalReviews = count($reviews);

    foreach ($reviews as $review) {
        $avgScore = ($review['punctuality_score'] +
                     $review['group_spirit_score'] +
                     $review['respect_score'] +
                     $review['adaptability_score']) / 4;
        $totalScore += $avgScore;
    }

    return round($totalScore / $totalReviews, 2);
}

/**
 * Generate pagination HTML
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    $html = '<div class="pagination">';

    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="page-link">&laquo; Precedente</a>';
    }

    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $currentPage) ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }

    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="page-link">Successivo &raquo;</a>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Get travel type icon
 */
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

/**
 * Get budget level label
 */
function getBudgetLabel($level) {
    $labels = [
        'low' => 'Economico',
        'medium' => 'Medio',
        'high' => 'Lusso'
    ];
    return $labels[$level] ?? 'Non specificato';
}

/**
 * Protect against CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
