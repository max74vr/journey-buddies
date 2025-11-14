<?php
/**
 * Chat Controller
 * Compagni di Viaggi
 */

require_once BASE_PATH . '/src/Models/Chat.php';
require_once BASE_PATH . '/src/Models/TravelPost.php';

class ChatController {
    private $chatModel;
    private $travelPostModel;

    public function __construct() {
        $this->chatModel = new Chat();
        $this->travelPostModel = new TravelPost();
    }

    /**
     * Show all chat groups for user
     */
    public function index() {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();
        $chatGroups = $this->chatModel->getUserChatGroups($userId);

        require_once BASE_PATH . '/src/Views/chat/index.php';
    }

    /**
     * Show specific chat group
     */
    public function show($chatGroupId) {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();

        // Check if user is member
        if (!$this->chatModel->isMember($chatGroupId, $userId)) {
            setFlashMessage('Non hai accesso a questa chat', 'error');
            redirect('/chats.php');
        }

        $chatGroup = $this->chatModel->getGroupById($chatGroupId);
        $messages = $this->chatModel->getMessages($chatGroupId, 50);
        $members = $this->chatModel->getGroupMembers($chatGroupId);

        // Mark messages as read
        $this->chatModel->markAsRead($chatGroupId, $userId);

        require_once BASE_PATH . '/src/Views/chat/show.php';
    }

    /**
     * Handle send message
     */
    public function sendMessage() {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $chatGroupId = (int)($_POST['chat_group_id'] ?? 0);
        $message = $_POST['message'] ?? '';
        $userId = getCurrentUserId();

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Messaggio vuoto']);
            exit;
        }

        // Check if user is member
        if (!$this->chatModel->isMember($chatGroupId, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Non hai accesso a questa chat']);
            exit;
        }

        if ($this->chatModel->sendMessage($chatGroupId, $userId, $message)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Errore durante l\'invio del messaggio']);
        }
        exit;
    }

    /**
     * Get new messages (for AJAX polling)
     */
    public function getNewMessages() {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $chatGroupId = (int)($_GET['chat_group_id'] ?? 0);
        $lastMessageId = (int)($_GET['last_message_id'] ?? 0);
        $userId = getCurrentUserId();

        // Check if user is member
        if (!$this->chatModel->isMember($chatGroupId, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $messages = $this->chatModel->getMessages($chatGroupId, 50);

        // Filter messages after last known message
        $newMessages = array_filter($messages, function($msg) use ($lastMessageId) {
            return $msg['id'] > $lastMessageId;
        });

        echo json_encode([
            'success' => true,
            'messages' => array_values($newMessages)
        ]);
        exit;
    }

    /**
     * Create or get chat for a travel post
     */
    public function createTravelChat($travelPostId) {
        if (!isLoggedIn()) {
            redirect('/login.php');
        }

        $userId = getCurrentUserId();
        $travel = $this->travelPostModel->getById($travelPostId);

        if (!$travel) {
            setFlashMessage('Viaggio non trovato', 'error');
            redirect('/travels.php');
        }

        // Check if user is participant or creator
        $participation = $this->travelPostModel->isParticipant($travelPostId, $userId);
        if (!$participation || ($participation['status'] !== 'accepted' && $travel['creator_id'] != $userId)) {
            setFlashMessage('Devi essere un partecipante accettato per accedere alla chat', 'error');
            redirect('/travel.php?id=' . $travelPostId);
        }

        // Get or create chat group
        $chatGroupId = $this->chatModel->getOrCreateTravelChatGroup($travelPostId, $travel['creator_id']);

        // Add user as member if not already
        if (!$this->chatModel->isMember($chatGroupId, $userId)) {
            $this->chatModel->addMember($chatGroupId, $userId);
        }

        redirect('/chat.php?id=' . $chatGroupId);
    }

    /**
     * Add member to chat group (for admins)
     */
    public function addMember() {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login.php');
        }

        $chatGroupId = (int)($_POST['chat_group_id'] ?? 0);
        $userIdToAdd = (int)($_POST['user_id'] ?? 0);
        $currentUserId = getCurrentUserId();

        // Check if current user is admin
        if (!$this->chatModel->isAdmin($chatGroupId, $currentUserId)) {
            setFlashMessage('Non hai i permessi per aggiungere membri', 'error');
            redirect('/chat.php?id=' . $chatGroupId);
        }

        if ($this->chatModel->addMember($chatGroupId, $userIdToAdd)) {
            setFlashMessage('Membro aggiunto alla chat', 'success');
        } else {
            setFlashMessage('Errore durante l\'aggiunta del membro', 'error');
        }

        redirect('/chat.php?id=' . $chatGroupId);
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount() {
        if (!isLoggedIn()) {
            echo json_encode(['count' => 0]);
            exit;
        }

        $userId = getCurrentUserId();
        $count = $this->chatModel->getUnreadCount($userId);

        echo json_encode(['count' => $count]);
        exit;
    }
}
