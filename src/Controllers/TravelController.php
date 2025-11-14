<?php
/**
 * Travel Controller
 * Compagni di Viaggi
 */

require_once BASE_PATH . '/src/Models/TravelPost.php';
require_once BASE_PATH . '/src/Models/User.php';

class TravelController {
    private $travelPostModel;
    private $userModel;

    public function __construct() {
        $this->travelPostModel = new TravelPost();
        $this->userModel = new User();
    }

    /**
     * Show travel bacheca (listing)
     */
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        $filters = [
            'destination' => $_GET['destination'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'travel_type' => $_GET['travel_type'] ?? '',
            'budget_level' => $_GET['budget_level'] ?? '',
            'available_spots' => isset($_GET['available_spots'])
        ];

        $travels = $this->travelPostModel->getAll($filters, $page);
        $totalCount = $this->travelPostModel->getCount($filters);
        $totalPages = ceil($totalCount / ITEMS_PER_PAGE);

        require_once BASE_PATH . '/src/Views/travel/index.php';
    }

    /**
     * Show single travel post
     */
    public function show($id) {
        $travel = $this->travelPostModel->getById($id);

        if (!$travel) {
            setFlashMessage('Viaggio non trovato', 'error');
            redirect('/travels.php');
        }

        $participants = $this->travelPostModel->getParticipants($id, 'accepted');
        $pendingParticipants = [];

        // Show pending participants only to creator
        if (isLoggedIn() && getCurrentUserId() == $travel['creator_id']) {
            $pendingParticipants = $this->travelPostModel->getParticipants($id, 'pending');
        }

        $userParticipation = null;
        if (isLoggedIn()) {
            $userParticipation = $this->travelPostModel->isParticipant($id, getCurrentUserId());
        }

        require_once BASE_PATH . '/src/Views/travel/show.php';
    }

    /**
     * Show create travel form
     */
    public function showCreateForm() {
        if (!isLoggedIn()) {
            setFlashMessage('Devi effettuare il login per creare un viaggio', 'error');
            redirect('/login.php');
        }
        require_once BASE_PATH . '/src/Views/travel/create.php';
    }

    /**
     * Handle create travel
     */
    public function create() {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login.php');
        }

        $errors = [];

        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $destination = sanitize($_POST['destination'] ?? '');
        $country = sanitize($_POST['country'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $travelType = $_POST['travel_type'] ?? '';

        // Validation
        if (empty($title) || strlen($title) < 5) {
            $errors[] = 'Il titolo deve essere almeno 5 caratteri';
        }

        if (empty($description) || strlen($description) < 20) {
            $errors[] = 'La descrizione deve essere almeno 20 caratteri';
        }

        if (empty($destination) || empty($country)) {
            $errors[] = 'Destinazione e paese sono obbligatori';
        }

        if (empty($startDate) || empty($endDate)) {
            $errors[] = 'Le date sono obbligatorie';
        } elseif (strtotime($startDate) < time()) {
            $errors[] = 'La data di inizio deve essere nel futuro';
        } elseif (strtotime($endDate) < strtotime($startDate)) {
            $errors[] = 'La data di fine deve essere dopo la data di inizio';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect('/create-travel.php');
        }

        // Handle cover image upload
        $coverImage = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['cover_image'], TRAVEL_PHOTOS_DIR);
            if ($uploadResult['success']) {
                $coverImage = $uploadResult['filename'];
            }
        }

        $travelData = [
            'creator_id' => getCurrentUserId(),
            'title' => $title,
            'description' => $description,
            'destination' => $destination,
            'country' => $country,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'travel_type' => $travelType,
            'budget_level' => $_POST['budget_level'] ?? 'medium',
            'estimated_cost' => !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null,
            'max_participants' => (int)($_POST['max_participants'] ?? 5),
            'accommodation_type' => sanitize($_POST['accommodation_type'] ?? ''),
            'is_flexible' => isset($_POST['is_flexible']),
            'cover_image' => $coverImage
        ];

        $travelId = $this->travelPostModel->create($travelData);

        if ($travelId) {
            setFlashMessage('Viaggio creato con successo!', 'success');
            redirect('/travel.php?id=' . $travelId);
        } else {
            setFlashMessage('Errore durante la creazione del viaggio', 'error');
            redirect('/create-travel.php');
        }
    }

    /**
     * Handle join travel request
     */
    public function join($travelId) {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();
        $travel = $this->travelPostModel->getById($travelId);

        if (!$travel) {
            setFlashMessage('Viaggio non trovato', 'error');
            redirect('/travels.php');
        }

        // Check if already participant
        if ($this->travelPostModel->isParticipant($travelId, $userId)) {
            setFlashMessage('Hai già richiesto di partecipare a questo viaggio', 'info');
            redirect('/travel.php?id=' . $travelId);
        }

        // Check if spots available
        if ($travel['current_participants'] >= $travel['max_participants']) {
            setFlashMessage('Non ci sono più posti disponibili', 'error');
            redirect('/travel.php?id=' . $travelId);
        }

        $joinMessage = sanitize($_POST['join_message'] ?? '');

        if ($this->travelPostModel->addParticipant($travelId, $userId, 'pending', $joinMessage)) {
            setFlashMessage('Richiesta inviata! Il creatore del viaggio la valuterà.', 'success');
        } else {
            setFlashMessage('Errore durante l\'invio della richiesta', 'error');
        }

        redirect('/travel.php?id=' . $travelId);
    }

    /**
     * Handle accept/reject participant
     */
    public function manageParticipant($travelId, $userId, $action) {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $currentUserId = getCurrentUserId();
        $travel = $this->travelPostModel->getById($travelId);

        if (!$travel || $travel['creator_id'] != $currentUserId) {
            setFlashMessage('Non hai i permessi per questa azione', 'error');
            redirect('/travels.php');
        }

        if ($action === 'accept') {
            // Check if spots available
            if ($travel['current_participants'] >= $travel['max_participants']) {
                setFlashMessage('Non ci sono più posti disponibili', 'error');
                redirect('/travel.php?id=' . $travelId);
            }

            $this->travelPostModel->updateParticipantStatus($travelId, $userId, 'accepted');
            setFlashMessage('Partecipante accettato!', 'success');
        } elseif ($action === 'reject') {
            $this->travelPostModel->updateParticipantStatus($travelId, $userId, 'rejected');
            setFlashMessage('Partecipante rifiutato', 'info');
        }

        redirect('/travel.php?id=' . $travelId);
    }

    /**
     * Handle leave travel
     */
    public function leave($travelId) {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();
        $travel = $this->travelPostModel->getById($travelId);

        if (!$travel) {
            setFlashMessage('Viaggio non trovato', 'error');
            redirect('/travels.php');
        }

        // Can't leave if you're the creator
        if ($travel['creator_id'] == $userId) {
            setFlashMessage('Non puoi lasciare un viaggio che hai creato', 'error');
            redirect('/travel.php?id=' . $travelId);
        }

        $this->travelPostModel->updateParticipantStatus($travelId, $userId, 'left');
        setFlashMessage('Hai lasciato il viaggio', 'info');
        redirect('/travels.php');
    }

    /**
     * Show edit travel form
     */
    public function showEditForm($id) {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $travel = $this->travelPostModel->getById($id);

        if (!$travel || $travel['creator_id'] != getCurrentUserId()) {
            setFlashMessage('Non hai i permessi per modificare questo viaggio', 'error');
            redirect('/travels.php');
        }

        require_once BASE_PATH . '/src/Views/travel/edit.php';
    }

    /**
     * Handle update travel
     */
    public function update($id) {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login.php');
        }

        $travel = $this->travelPostModel->getById($id);

        if (!$travel || $travel['creator_id'] != getCurrentUserId()) {
            setFlashMessage('Non hai i permessi per modificare questo viaggio', 'error');
            redirect('/travels.php');
        }

        $updateData = [
            'title' => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'destination' => sanitize($_POST['destination'] ?? ''),
            'country' => sanitize($_POST['country'] ?? ''),
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'travel_type' => $_POST['travel_type'] ?? '',
            'budget_level' => $_POST['budget_level'] ?? 'medium',
            'estimated_cost' => !empty($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null,
            'max_participants' => (int)($_POST['max_participants'] ?? 5),
            'accommodation_type' => sanitize($_POST['accommodation_type'] ?? ''),
            'is_flexible' => isset($_POST['is_flexible']),
            'status' => $_POST['status'] ?? $travel['status']
        ];

        // Handle cover image upload
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['cover_image'], TRAVEL_PHOTOS_DIR);
            if ($uploadResult['success']) {
                $updateData['cover_image'] = $uploadResult['filename'];
            }
        }

        if ($this->travelPostModel->update($id, $updateData)) {
            setFlashMessage('Viaggio aggiornato con successo!', 'success');
        } else {
            setFlashMessage('Errore durante l\'aggiornamento', 'error');
        }

        redirect('/travel.php?id=' . $id);
    }

    /**
     * Handle delete travel
     */
    public function delete($id) {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $travel = $this->travelPostModel->getById($id);

        if (!$travel || $travel['creator_id'] != getCurrentUserId()) {
            setFlashMessage('Non hai i permessi per eliminare questo viaggio', 'error');
            redirect('/travels.php');
        }

        if ($this->travelPostModel->delete($id)) {
            setFlashMessage('Viaggio eliminato', 'info');
        } else {
            setFlashMessage('Errore durante l\'eliminazione', 'error');
        }

        redirect('/travels.php');
    }
}
