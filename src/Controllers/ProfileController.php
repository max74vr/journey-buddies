<?php
/**
 * Profile Controller
 * Journey Buddies
 */

require_once BASE_PATH . '/src/Models/User.php';
require_once BASE_PATH . '/src/Models/TravelPost.php';
require_once BASE_PATH . '/src/Models/Review.php';

class ProfileController {
    private $userModel;
    private $travelPostModel;
    private $reviewModel;

    public function __construct() {
        $this->userModel = new User();
        $this->travelPostModel = new TravelPost();
        $this->reviewModel = new Review();
    }

    /**
     * Show user profile
     */
    public function show($userId = null) {
        if ($userId === null) {
            if (!isLoggedIn()) {
                redirect('/login.php');
            }
            $userId = getCurrentUserId();
        }

        $user = $this->userModel->getById($userId);

        if (!$user) {
            setFlashMessage('User not found', 'error');
            redirect('/index.php');
        }

        $preferences = $this->userModel->getPreferences($userId);
        $languages = $this->userModel->getLanguages($userId);
        $badges = $this->userModel->getBadges($userId);
        $reviews = $this->reviewModel->getUserReviews($userId, 5);
        $reviewStats = $this->reviewModel->getUserReviewStats($userId);
        $userTravels = $this->travelPostModel->getUserTravelPosts($userId);
        $joinedTravels = $this->travelPostModel->getUserJoinedTravels($userId);

        $isOwnProfile = isLoggedIn() && getCurrentUserId() == $userId;

        require_once BASE_PATH . '/src/Views/profile/show.php';
    }

    /**
     * Show edit profile form
     */
    public function showEditForm() {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();
        $user = $this->userModel->getById($userId);
        $preferences = $this->userModel->getPreferences($userId);
        $languages = $this->userModel->getLanguages($userId);

        require_once BASE_PATH . '/src/Views/profile/edit.php';
    }

    /**
     * Handle profile update
     */
    public function update() {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();

        $updateData = [
            'first_name' => sanitize($_POST['first_name'] ?? ''),
            'last_name' => sanitize($_POST['last_name'] ?? ''),
            'bio' => sanitize($_POST['bio'] ?? ''),
            'city' => sanitize($_POST['city'] ?? ''),
            'country' => sanitize($_POST['country'] ?? ''),
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'gender' => $_POST['gender'] ?? ''
        ];

        // Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['profile_photo'], PROFILE_PHOTOS_DIR);
            if ($uploadResult['success']) {
                $updateData['profile_photo'] = $uploadResult['filename'];
            }
        }

        if ($this->userModel->update($userId, $updateData)) {
            setFlashMessage('Profile updated successfully!', 'success');
        } else {
            setFlashMessage('Error updating profile', 'error');
        }

        redirect('/profile.php');
    }

    /**
     * Show dashboard
     */
    public function dashboard() {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();
        $user = $this->userModel->getById($userId);

        $myTravels = $this->travelPostModel->getUserTravelPosts($userId);
        $joinedTravels = $this->travelPostModel->getUserJoinedTravels($userId);
        $pendingReviews = $this->reviewModel->getPendingReviews($userId);

        // Get recommended travels based on user preferences
        $preferences = $this->userModel->getPreferences($userId);
        $recommendedTravels = [];

        if (!empty($preferences)) {
            $travelStyle = $preferences[0]['travel_style'] ?? null;
            if ($travelStyle) {
                $recommendedTravels = $this->travelPostModel->getAll([
                    'travel_type' => $travelStyle,
                    'available_spots' => true
                ], 1, 6);
            }
        }

        require_once BASE_PATH . '/src/Views/dashboard.php';
    }

    /**
     * Show all users (explore travelers)
     */
    public function exploreTravelers() {
        $query = $_GET['q'] ?? '';
        $filters = [
            'travel_style' => $_GET['travel_style'] ?? '',
            'language' => $_GET['language'] ?? ''
        ];

        if (!empty($query) || !empty($filters['travel_style']) || !empty($filters['language'])) {
            $users = $this->userModel->search($query, $filters);
        } else {
            $users = $this->userModel->getFeaturedUsers(50);
        }

        require_once BASE_PATH . '/src/Views/profile/explore.php';
    }
}
