<?php
/**
 * Chat Model
 * Compagni di Viaggi
 */

class Chat {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new chat group
     */
    public function createGroup($travelPostId, $groupName, $createdBy) {
        $sql = "INSERT INTO chat_groups (travel_post_id, group_name, created_by)
                VALUES (:travel_post_id, :group_name, :created_by)";

        $stmt = $this->db->prepare($sql);

        if ($stmt->execute([
            ':travel_post_id' => $travelPostId,
            ':group_name' => $groupName,
            ':created_by' => $createdBy
        ])) {
            $groupId = $this->db->lastInsertId();

            // Add creator as admin member
            $this->addMember($groupId, $createdBy, true);

            return $groupId;
        }

        return false;
    }

    /**
     * Add member to chat group
     */
    public function addMember($chatGroupId, $userId, $isAdmin = false) {
        $sql = "INSERT INTO chat_group_members (chat_group_id, user_id, is_admin)
                VALUES (:chat_group_id, :user_id, :is_admin)
                ON DUPLICATE KEY UPDATE is_admin = :is_admin";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':chat_group_id' => $chatGroupId,
            ':user_id' => $userId,
            ':is_admin' => $isAdmin
        ]);
    }

    /**
     * Remove member from chat group
     */
    public function removeMember($chatGroupId, $userId) {
        $sql = "DELETE FROM chat_group_members
                WHERE chat_group_id = :chat_group_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':chat_group_id' => $chatGroupId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage($chatGroupId, $senderId, $message) {
        // Anti-spam check
        if ($this->isSpamming($chatGroupId, $senderId)) {
            return false;
        }

        $sql = "INSERT INTO chat_messages (chat_group_id, sender_id, message)
                VALUES (:chat_group_id, :sender_id, :message)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':chat_group_id' => $chatGroupId,
            ':sender_id' => $senderId,
            ':message' => substr($message, 0, MAX_MESSAGE_LENGTH)
        ]);
    }

    /**
     * Get messages for a chat group
     */
    public function getMessages($chatGroupId, $limit = 50, $offset = 0) {
        $sql = "SELECT cm.*, u.username, u.first_name, u.last_name, u.profile_photo
                FROM chat_messages cm
                INNER JOIN users u ON cm.sender_id = u.id
                WHERE cm.chat_group_id = :chat_group_id
                ORDER BY cm.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':chat_group_id', $chatGroupId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $messages = $stmt->fetchAll();
        return array_reverse($messages); // Return in chronological order
    }

    /**
     * Get user's chat groups
     */
    public function getUserChatGroups($userId) {
        $sql = "SELECT cg.*, tp.title as travel_title, tp.destination,
                (SELECT COUNT(*) FROM chat_messages WHERE chat_group_id = cg.id) as message_count,
                (SELECT message FROM chat_messages WHERE chat_group_id = cg.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM chat_messages WHERE chat_group_id = cg.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM chat_groups cg
                INNER JOIN chat_group_members cgm ON cg.id = cgm.chat_group_id
                LEFT JOIN travel_posts tp ON cg.travel_post_id = tp.id
                WHERE cgm.user_id = :user_id AND cg.is_active = 1
                ORDER BY last_message_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get chat group by ID
     */
    public function getGroupById($chatGroupId) {
        $sql = "SELECT cg.*, tp.title as travel_title, tp.destination
                FROM chat_groups cg
                LEFT JOIN travel_posts tp ON cg.travel_post_id = tp.id
                WHERE cg.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $chatGroupId]);
        return $stmt->fetch();
    }

    /**
     * Get chat group members
     */
    public function getGroupMembers($chatGroupId) {
        $sql = "SELECT u.*, cgm.is_admin, cgm.joined_at
                FROM chat_group_members cgm
                INNER JOIN users u ON cgm.user_id = u.id
                WHERE cgm.chat_group_id = :chat_group_id
                ORDER BY cgm.is_admin DESC, cgm.joined_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':chat_group_id' => $chatGroupId]);
        return $stmt->fetchAll();
    }

    /**
     * Check if user is member of chat group
     */
    public function isMember($chatGroupId, $userId) {
        $sql = "SELECT id FROM chat_group_members
                WHERE chat_group_id = :chat_group_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':chat_group_id' => $chatGroupId,
            ':user_id' => $userId
        ]);

        return $stmt->fetch() !== false;
    }

    /**
     * Check if user is admin of chat group
     */
    public function isAdmin($chatGroupId, $userId) {
        $sql = "SELECT is_admin FROM chat_group_members
                WHERE chat_group_id = :chat_group_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':chat_group_id' => $chatGroupId,
            ':user_id' => $userId
        ]);

        $result = $stmt->fetch();
        return $result && $result['is_admin'];
    }

    /**
     * Get or create chat group for a travel post
     */
    public function getOrCreateTravelChatGroup($travelPostId, $creatorId) {
        // Check if group already exists
        $sql = "SELECT id FROM chat_groups WHERE travel_post_id = :travel_post_id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':travel_post_id' => $travelPostId]);
        $existing = $stmt->fetch();

        if ($existing) {
            return $existing['id'];
        }

        // Create new group
        $travelPostModel = new TravelPost();
        $travel = $travelPostModel->getById($travelPostId);

        if ($travel) {
            $groupName = "Chat: " . $travel['title'];
            return $this->createGroup($travelPostId, $groupName, $creatorId);
        }

        return false;
    }

    /**
     * Anti-spam check
     */
    private function isSpamming($chatGroupId, $userId) {
        $sql = "SELECT COUNT(*) as count FROM chat_messages
                WHERE chat_group_id = :chat_group_id
                AND sender_id = :sender_id
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':chat_group_id' => $chatGroupId,
            ':sender_id' => $userId
        ]);

        $result = $stmt->fetch();
        return $result['count'] >= SPAM_THRESHOLD;
    }

    /**
     * Mark messages as read
     */
    public function markAsRead($chatGroupId, $userId) {
        $sql = "UPDATE chat_messages cm
                SET is_read = 1
                WHERE cm.chat_group_id = :chat_group_id
                AND cm.sender_id != :user_id
                AND cm.is_read = 0";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':chat_group_id' => $chatGroupId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM chat_messages cm
                INNER JOIN chat_group_members cgm ON cm.chat_group_id = cgm.chat_group_id
                WHERE cgm.user_id = :user_id
                AND cm.sender_id != :user_id
                AND cm.is_read = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
