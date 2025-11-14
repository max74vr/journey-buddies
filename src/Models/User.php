<?php
/**
 * User Model
 * Compagni di Viaggi
 */

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new user
     */
    public function create($data) {
        $sql = "INSERT INTO users (email, password_hash, username, first_name, last_name, date_of_birth, gender, city, country)
                VALUES (:email, :password_hash, :username, :first_name, :last_name, :date_of_birth, :gender, :city, :country)";

        $stmt = $this->db->prepare($sql);

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $params = [
            ':email' => $data['email'],
            ':password_hash' => $passwordHash,
            ':username' => $data['username'],
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':date_of_birth' => $data['date_of_birth'],
            ':gender' => $data['gender'],
            ':city' => $data['city'] ?? null,
            ':country' => $data['country'] ?? null
        ];

        if ($stmt->execute($params)) {
            return $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = :id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Get user by username
     */
    public function getByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $user = $this->getByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $this->updateLastLogin($user['id']);
            return $user;
        }

        return false;
    }

    /**
     * Update user profile
     */
    public function update($id, $data) {
        $allowedFields = ['first_name', 'last_name', 'bio', 'profile_photo', 'city', 'country', 'date_of_birth', 'gender'];
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

        $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($id) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    /**
     * Update reputation score
     */
    public function updateReputationScore($userId, $score) {
        $sql = "UPDATE users SET reputation_score = :score WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':score' => $score, ':id' => $userId]);
    }

    /**
     * Increment total trips
     */
    public function incrementTotalTrips($userId) {
        $sql = "UPDATE users SET total_trips = total_trips + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $userId]);
    }

    /**
     * Get user preferences
     */
    public function getPreferences($userId) {
        $sql = "SELECT * FROM user_preferences WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Add user preference
     */
    public function addPreference($userId, $data) {
        $sql = "INSERT INTO user_preferences (user_id, travel_style, accommodation_type, food_preference, budget_level, smoking, pets)
                VALUES (:user_id, :travel_style, :accommodation_type, :food_preference, :budget_level, :smoking, :pets)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':travel_style' => $data['travel_style'],
            ':accommodation_type' => $data['accommodation_type'] ?? null,
            ':food_preference' => $data['food_preference'] ?? null,
            ':budget_level' => $data['budget_level'] ?? 'medium',
            ':smoking' => $data['smoking'] ?? false,
            ':pets' => $data['pets'] ?? false
        ]);
    }

    /**
     * Get user languages
     */
    public function getLanguages($userId) {
        $sql = "SELECT * FROM user_languages WHERE user_id = :user_id ORDER BY proficiency DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Add user language
     */
    public function addLanguage($userId, $languageCode, $languageName, $proficiency = 'intermediate') {
        $sql = "INSERT INTO user_languages (user_id, language_code, language_name, proficiency)
                VALUES (:user_id, :language_code, :language_name, :proficiency)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':language_code' => $languageCode,
            ':language_name' => $languageName,
            ':proficiency' => $proficiency
        ]);
    }

    /**
     * Get user badges
     */
    public function getBadges($userId) {
        $sql = "SELECT * FROM user_badges WHERE user_id = :user_id ORDER BY earned_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Award badge to user
     */
    public function awardBadge($userId, $badgeType, $badgeName, $badgeIcon = null) {
        $sql = "INSERT INTO user_badges (user_id, badge_type, badge_name, badge_icon)
                VALUES (:user_id, :badge_type, :badge_name, :badge_icon)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':badge_type' => $badgeType,
            ':badge_name' => $badgeName,
            ':badge_icon' => $badgeIcon
        ]);
    }

    /**
     * Get featured users (highest reputation)
     */
    public function getFeaturedUsers($limit = 10) {
        $sql = "SELECT u.*, COUNT(tp.id) as trip_count
                FROM users u
                LEFT JOIN travel_posts tp ON u.id = tp.creator_id
                WHERE u.is_active = 1 AND u.reputation_score > 0
                GROUP BY u.id
                ORDER BY u.reputation_score DESC, trip_count DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Search users
     */
    public function search($query, $filters = []) {
        $sql = "SELECT DISTINCT u.* FROM users u
                LEFT JOIN user_preferences up ON u.id = up.user_id
                LEFT JOIN user_languages ul ON u.id = ul.user_id
                WHERE u.is_active = 1";

        $params = [];

        if (!empty($query)) {
            $sql .= " AND (u.username LIKE :query OR u.first_name LIKE :query OR u.last_name LIKE :query OR u.city LIKE :query)";
            $params[':query'] = "%$query%";
        }

        if (!empty($filters['travel_style'])) {
            $sql .= " AND up.travel_style = :travel_style";
            $params[':travel_style'] = $filters['travel_style'];
        }

        if (!empty($filters['language'])) {
            $sql .= " AND ul.language_code = :language";
            $params[':language'] = $filters['language'];
        }

        $sql .= " ORDER BY u.reputation_score DESC LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE username = :username";
        $params = [':username' => $username];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
}
