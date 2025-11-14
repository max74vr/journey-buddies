<?php
/**
 * Review Controller
 * Compagni di Viaggi
 */

require_once BASE_PATH . '/src/Models/Review.php';
require_once BASE_PATH . '/src/Models/TravelPost.php';
require_once BASE_PATH . '/src/Models/User.php';

class ReviewController {
    private $reviewModel;
    private $travelPostModel;
    private $userModel;

    public function __construct() {
        $this->reviewModel = new Review();
        $this->travelPostModel = new TravelPost();
        $this->userModel = new User();
    }

    /**
     * Show review form
     */
    public function showReviewForm($travelPostId, $reviewedUserId) {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();

        // Check if user can review
        if (!$this->reviewModel->canReview($travelPostId, $userId, $reviewedUserId)) {
            setFlashMessage('Non puoi lasciare questa recensione', 'error');
            redirect('/dashboard.php');
        }

        $travel = $this->travelPostModel->getById($travelPostId);
        $reviewedUser = $this->userModel->getById($reviewedUserId);

        if (!$travel || !$reviewedUser) {
            setFlashMessage('Dati non validi', 'error');
            redirect('/dashboard.php');
        }

        require_once BASE_PATH . '/src/Views/review/create.php';
    }

    /**
     * Handle create review
     */
    public function create() {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login.php');
        }

        $travelPostId = (int)$_POST['travel_post_id'];
        $reviewedUserId = (int)$_POST['reviewed_user_id'];
        $userId = getCurrentUserId();

        // Validate
        if (!$this->reviewModel->canReview($travelPostId, $userId, $reviewedUserId)) {
            setFlashMessage('Non puoi lasciare questa recensione', 'error');
            redirect('/dashboard.php');
        }

        $errors = [];

        $punctualityScore = (int)($_POST['punctuality_score'] ?? 0);
        $groupSpiritScore = (int)($_POST['group_spirit_score'] ?? 0);
        $respectScore = (int)($_POST['respect_score'] ?? 0);
        $adaptabilityScore = (int)($_POST['adaptability_score'] ?? 0);

        // Validate scores
        if ($punctualityScore < 1 || $punctualityScore > 5 ||
            $groupSpiritScore < 1 || $groupSpiritScore > 5 ||
            $respectScore < 1 || $respectScore > 5 ||
            $adaptabilityScore < 1 || $adaptabilityScore > 5) {
            $errors[] = 'Tutti i punteggi devono essere tra 1 e 5';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            redirect('/review.php?travel_id=' . $travelPostId . '&user_id=' . $reviewedUserId);
        }

        $reviewData = [
            'travel_post_id' => $travelPostId,
            'reviewer_id' => $userId,
            'reviewed_id' => $reviewedUserId,
            'punctuality_score' => $punctualityScore,
            'group_spirit_score' => $groupSpiritScore,
            'respect_score' => $respectScore,
            'adaptability_score' => $adaptabilityScore,
            'comment' => sanitize($_POST['comment'] ?? '')
        ];

        if ($this->reviewModel->create($reviewData)) {
            setFlashMessage('Recensione inviata con successo!', 'success');
            redirect('/profile.php?id=' . $reviewedUserId);
        } else {
            setFlashMessage('Errore durante l\'invio della recensione', 'error');
            redirect('/dashboard.php');
        }
    }

    /**
     * Show all reviews for a user
     */
    public function showUserReviews($userId) {
        $user = $this->userModel->getById($userId);

        if (!$user) {
            setFlashMessage('Utente non trovato', 'error');
            redirect('/index.php');
        }

        $reviews = $this->reviewModel->getUserReviews($userId);
        $reviewStats = $this->reviewModel->getUserReviewStats($userId);

        require_once BASE_PATH . '/src/Views/review/user-reviews.php';
    }

    /**
     * Show pending reviews for current user
     */
    public function showPendingReviews() {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();
        $pendingReviews = $this->reviewModel->getPendingReviews($userId);

        require_once BASE_PATH . '/src/Views/review/pending.php';
    }
}
