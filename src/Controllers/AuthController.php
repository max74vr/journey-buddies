<?php
/**
 * Authentication Controller
 * Compagni di Viaggi
 */

require_once BASE_PATH . '/src/Models/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Show registration form
     */
    public function showRegisterForm() {
        if (isLoggedIn()) {
            redirect('/dashboard.php');
        }
        require_once BASE_PATH . '/src/Views/auth/register.php';
    }

    /**
     * Handle registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/register.php');
        }

        $errors = [];

        // Validate input
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $username = sanitize($_POST['username'] ?? '');
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $dateOfBirth = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';

        // Validation
        if (empty($email) || !isValidEmail($email)) {
            $errors[] = 'Email non valida';
        } elseif ($this->userModel->emailExists($email)) {
            $errors[] = 'Email già registrata';
        }

        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'Username deve essere almeno 3 caratteri';
        } elseif ($this->userModel->usernameExists($username)) {
            $errors[] = 'Username già in uso';
        }

        if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password deve essere almeno ' . PASSWORD_MIN_LENGTH . ' caratteri';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Le password non corrispondono';
        }

        if (empty($firstName) || empty($lastName)) {
            $errors[] = 'Nome e cognome sono obbligatori';
        }

        if (empty($dateOfBirth)) {
            $errors[] = 'Data di nascita è obbligatoria';
        } else {
            $birthDate = new DateTime($dateOfBirth);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            if ($age < 18) {
                $errors[] = 'Devi avere almeno 18 anni';
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect('/register.php');
        }

        // Create user
        $userData = [
            'email' => $email,
            'password' => $password,
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $dateOfBirth,
            'gender' => $gender,
            'city' => sanitize($_POST['city'] ?? ''),
            'country' => sanitize($_POST['country'] ?? '')
        ];

        $userId = $this->userModel->create($userData);

        if ($userId) {
            // Award early adopter badge
            $this->userModel->awardBadge($userId, 'early_adopter', 'Early Adopter', '🌟');

            setFlashMessage('Registrazione completata! Benvenuto su Compagni di Viaggi!', 'success');

            // Auto login
            $user = $this->userModel->getById($userId);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            redirect('/complete-profile.php');
        } else {
            setFlashMessage('Errore durante la registrazione', 'error');
            redirect('/register.php');
        }
    }

    /**
     * Show login form
     */
    public function showLoginForm() {
        if (isLoggedIn()) {
            redirect('/dashboard.php');
        }
        require_once BASE_PATH . '/src/Views/auth/login.php';
    }

    /**
     * Handle login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login.php');
        }

        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            setFlashMessage('Email e password sono obbligatori', 'error');
            redirect('/login.php');
        }

        $user = $this->userModel->authenticate($email, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            // Regenerate session ID for security
            session_regenerate_id(true);

            setFlashMessage('Benvenuto, ' . $user['first_name'] . '!', 'success');
            redirect('/dashboard.php');
        } else {
            setFlashMessage('Email o password non corretti', 'error');
            redirect('/login.php');
        }
    }

    /**
     * Handle logout
     */
    public function logout() {
        session_unset();
        session_destroy();
        setFlashMessage('Logout effettuato con successo', 'success');
        redirect('/index.php');
    }

    /**
     * Show complete profile form (after registration)
     */
    public function showCompleteProfileForm() {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }
        require_once BASE_PATH . '/src/Views/auth/complete-profile.php';
    }

    /**
     * Handle complete profile
     */
    public function completeProfile() {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();

        // Add bio
        if (!empty($_POST['bio'])) {
            $this->userModel->update($userId, ['bio' => sanitize($_POST['bio'])]);
        }

        // Add travel preferences
        if (!empty($_POST['travel_styles'])) {
            foreach ($_POST['travel_styles'] as $style) {
                $preferenceData = [
                    'travel_style' => sanitize($style),
                    'accommodation_type' => sanitize($_POST['accommodation_type'] ?? ''),
                    'food_preference' => sanitize($_POST['food_preference'] ?? ''),
                    'budget_level' => $_POST['budget_level'] ?? 'medium',
                    'smoking' => isset($_POST['smoking']),
                    'pets' => isset($_POST['pets'])
                ];
                $this->userModel->addPreference($userId, $preferenceData);
            }
        }

        // Add languages
        if (!empty($_POST['languages'])) {
            foreach ($_POST['languages'] as $langData) {
                $langParts = explode(':', $langData);
                if (count($langParts) === 2) {
                    $this->userModel->addLanguage(
                        $userId,
                        $langParts[0],
                        $langParts[1],
                        $_POST['language_proficiency'][$langParts[0]] ?? 'intermediate'
                    );
                }
            }
        }

        setFlashMessage('Profilo completato! Ora puoi iniziare a esplorare i viaggi.', 'success');
        redirect('/dashboard.php');
    }
}
