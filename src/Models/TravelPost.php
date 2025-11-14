<?php
/**
 * TravelPost Model
 * Compagni di Viaggi
 */

class TravelPost {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new travel post
     */
    public function create($data) {
        $sql = "INSERT INTO travel_posts
                (creator_id, title, description, destination, country, start_date, end_date,
                 travel_type, budget_level, estimated_cost, max_participants, accommodation_type,
                 is_flexible, cover_image)
                VALUES
                (:creator_id, :title, :description, :destination, :country, :start_date, :end_date,
                 :travel_type, :budget_level, :estimated_cost, :max_participants, :accommodation_type,
                 :is_flexible, :cover_image)";

        $stmt = $this->db->prepare($sql);

        $params = [
            ':creator_id' => $data['creator_id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':destination' => $data['destination'],
            ':country' => $data['country'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':travel_type' => $data['travel_type'],
            ':budget_level' => $data['budget_level'] ?? 'medium',
            ':estimated_cost' => $data['estimated_cost'] ?? null,
            ':max_participants' => $data['max_participants'] ?? 5,
            ':accommodation_type' => $data['accommodation_type'] ?? null,
            ':is_flexible' => $data['is_flexible'] ?? true,
            ':cover_image' => $data['cover_image'] ?? null
        ];

        if ($stmt->execute($params)) {
            $travelPostId = $this->db->lastInsertId();

            // Automatically add creator as participant
            $this->addParticipant($travelPostId, $data['creator_id'], 'accepted', 'Organizzatore del viaggio');

            return $travelPostId;
        }

        return false;
    }

    /**
     * Get travel post by ID
     */
    public function getById($id) {
        $sql = "SELECT tp.*, u.username, u.first_name, u.last_name, u.profile_photo, u.reputation_score
                FROM travel_posts tp
                INNER JOIN users u ON tp.creator_id = u.id
                WHERE tp.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get all travel posts with filters
     */
    public function getAll($filters = [], $page = 1, $limit = ITEMS_PER_PAGE) {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT tp.*, u.username, u.first_name, u.last_name, u.profile_photo, u.reputation_score,
                (tp.max_participants - tp.current_participants) as available_spots
                FROM travel_posts tp
                INNER JOIN users u ON tp.creator_id = u.id
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['destination'])) {
            $sql .= " AND (tp.destination LIKE :destination OR tp.country LIKE :destination)";
            $params[':destination'] = "%{$filters['destination']}%";
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND tp.start_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND tp.end_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['travel_type'])) {
            $sql .= " AND tp.travel_type = :travel_type";
            $params[':travel_type'] = $filters['travel_type'];
        }

        if (!empty($filters['budget_level'])) {
            $sql .= " AND tp.budget_level = :budget_level";
            $params[':budget_level'] = $filters['budget_level'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND tp.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            // By default, show only planning and confirmed trips
            $sql .= " AND tp.status IN ('planning', 'confirmed')";
        }

        if (!empty($filters['available_spots'])) {
            $sql .= " AND tp.current_participants < tp.max_participants";
        }

        $sql .= " ORDER BY tp.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get total count for pagination
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM travel_posts tp WHERE 1=1";
        $params = [];

        // Apply same filters as getAll
        if (!empty($filters['destination'])) {
            $sql .= " AND (tp.destination LIKE :destination OR tp.country LIKE :destination)";
            $params[':destination'] = "%{$filters['destination']}%";
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND tp.start_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND tp.end_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['travel_type'])) {
            $sql .= " AND tp.travel_type = :travel_type";
            $params[':travel_type'] = $filters['travel_type'];
        }

        if (!empty($filters['budget_level'])) {
            $sql .= " AND tp.budget_level = :budget_level";
            $params[':budget_level'] = $filters['budget_level'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND tp.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            $sql .= " AND tp.status IN ('planning', 'confirmed')";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }

    /**
     * Update travel post
     */
    public function update($id, $data) {
        $allowedFields = ['title', 'description', 'destination', 'country', 'start_date', 'end_date',
                          'travel_type', 'budget_level', 'estimated_cost', 'max_participants',
                          'accommodation_type', 'is_flexible', 'cover_image', 'status'];

        $updates = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE travel_posts SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete travel post
     */
    public function delete($id) {
        $sql = "DELETE FROM travel_posts WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Add participant to travel post
     */
    public function addParticipant($travelPostId, $userId, $status = 'pending', $joinMessage = null) {
        $sql = "INSERT INTO travel_participants (travel_post_id, user_id, status, join_message)
                VALUES (:travel_post_id, :user_id, :status, :join_message)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':travel_post_id' => $travelPostId,
            ':user_id' => $userId,
            ':status' => $status,
            ':join_message' => $joinMessage
        ]);

        if ($result && $status === 'accepted') {
            $this->updateParticipantCount($travelPostId);
        }

        return $result;
    }

    /**
     * Update participant status
     */
    public function updateParticipantStatus($travelPostId, $userId, $status) {
        $sql = "UPDATE travel_participants
                SET status = :status, updated_at = NOW()
                WHERE travel_post_id = :travel_post_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':status' => $status,
            ':travel_post_id' => $travelPostId,
            ':user_id' => $userId
        ]);

        if ($result) {
            $this->updateParticipantCount($travelPostId);
        }

        return $result;
    }

    /**
     * Get participants of a travel post
     */
    public function getParticipants($travelPostId, $status = null) {
        $sql = "SELECT tp.*, u.username, u.first_name, u.last_name, u.profile_photo, u.reputation_score
                FROM travel_participants tp
                INNER JOIN users u ON tp.user_id = u.id
                WHERE tp.travel_post_id = :travel_post_id";

        $params = [':travel_post_id' => $travelPostId];

        if ($status) {
            $sql .= " AND tp.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY tp.joined_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Check if user is participant
     */
    public function isParticipant($travelPostId, $userId) {
        $sql = "SELECT status FROM travel_participants
                WHERE travel_post_id = :travel_post_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':travel_post_id' => $travelPostId,
            ':user_id' => $userId
        ]);

        return $stmt->fetch();
    }

    /**
     * Update participant count
     */
    private function updateParticipantCount($travelPostId) {
        $sql = "UPDATE travel_posts
                SET current_participants = (
                    SELECT COUNT(*) FROM travel_participants
                    WHERE travel_post_id = :travel_post_id AND status = 'accepted'
                )
                WHERE id = :travel_post_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':travel_post_id' => $travelPostId]);
    }

    /**
     * Get user's travel posts
     */
    public function getUserTravelPosts($userId, $status = null) {
        $sql = "SELECT tp.*,
                (tp.max_participants - tp.current_participants) as available_spots
                FROM travel_posts tp
                WHERE tp.creator_id = :user_id";

        $params = [':user_id' => $userId];

        if ($status) {
            $sql .= " AND tp.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY tp.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get user's joined travels
     */
    public function getUserJoinedTravels($userId) {
        $sql = "SELECT tp.*, u.username, u.first_name, u.last_name, u.profile_photo,
                tpart.status as participation_status,
                (tp.max_participants - tp.current_participants) as available_spots
                FROM travel_participants tpart
                INNER JOIN travel_posts tp ON tpart.travel_post_id = tp.id
                INNER JOIN users u ON tp.creator_id = u.id
                WHERE tpart.user_id = :user_id AND tpart.status IN ('pending', 'accepted')
                ORDER BY tp.start_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
