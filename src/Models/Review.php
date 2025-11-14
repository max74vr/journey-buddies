<?php
/**
 * Review Model
 * Compagni di Viaggi
 */

class Review {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new review
     */
    public function create($data) {
        $sql = "INSERT INTO reviews
                (travel_post_id, reviewer_id, reviewed_id, punctuality_score, group_spirit_score,
                 respect_score, adaptability_score, comment)
                VALUES
                (:travel_post_id, :reviewer_id, :reviewed_id, :punctuality_score, :group_spirit_score,
                 :respect_score, :adaptability_score, :comment)";

        $stmt = $this->db->prepare($sql);

        $params = [
            ':travel_post_id' => $data['travel_post_id'],
            ':reviewer_id' => $data['reviewer_id'],
            ':reviewed_id' => $data['reviewed_id'],
            ':punctuality_score' => $data['punctuality_score'],
            ':group_spirit_score' => $data['group_spirit_score'],
            ':respect_score' => $data['respect_score'],
            ':adaptability_score' => $data['adaptability_score'],
            ':comment' => $data['comment'] ?? null
        ];

        if ($stmt->execute($params)) {
            // Update reviewed user's reputation score
            $this->updateUserReputation($data['reviewed_id']);
            return $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Get reviews for a user
     */
    public function getUserReviews($userId, $limit = null) {
        $sql = "SELECT r.*, u.username, u.first_name, u.last_name, u.profile_photo,
                tp.title as travel_title, tp.destination
                FROM reviews r
                INNER JOIN users u ON r.reviewer_id = u.id
                INNER JOIN travel_posts tp ON r.travel_post_id = tp.id
                WHERE r.reviewed_id = :user_id
                ORDER BY r.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get review statistics for a user
     */
    public function getUserReviewStats($userId) {
        $sql = "SELECT
                COUNT(*) as total_reviews,
                AVG(punctuality_score) as avg_punctuality,
                AVG(group_spirit_score) as avg_group_spirit,
                AVG(respect_score) as avg_respect,
                AVG(adaptability_score) as avg_adaptability,
                AVG((punctuality_score + group_spirit_score + respect_score + adaptability_score) / 4) as overall_avg
                FROM reviews
                WHERE reviewed_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Check if user can review another user for a specific travel
     */
    public function canReview($travelPostId, $reviewerId, $reviewedId) {
        // Check if both users participated in the travel
        $sql = "SELECT COUNT(*) as count FROM travel_participants
                WHERE travel_post_id = :travel_post_id
                AND user_id IN (:reviewer_id, :reviewed_id)
                AND status = 'accepted'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':travel_post_id' => $travelPostId,
            ':reviewer_id' => $reviewerId,
            ':reviewed_id' => $reviewedId
        ]);

        $result = $stmt->fetch();

        if ($result['count'] < 2) {
            return false; // Both users must have participated
        }

        // Check if review already exists
        if ($this->reviewExists($travelPostId, $reviewerId, $reviewedId)) {
            return false;
        }

        // Check if travel is completed
        $travelSql = "SELECT status FROM travel_posts WHERE id = :id";
        $travelStmt = $this->db->prepare($travelSql);
        $travelStmt->execute([':id' => $travelPostId]);
        $travel = $travelStmt->fetch();

        return $travel && $travel['status'] === 'completed';
    }

    /**
     * Check if review exists
     */
    public function reviewExists($travelPostId, $reviewerId, $reviewedId) {
        $sql = "SELECT id FROM reviews
                WHERE travel_post_id = :travel_post_id
                AND reviewer_id = :reviewer_id
                AND reviewed_id = :reviewed_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':travel_post_id' => $travelPostId,
            ':reviewer_id' => $reviewerId,
            ':reviewed_id' => $reviewedId
        ]);

        return $stmt->fetch() !== false;
    }

    /**
     * Update user's reputation score based on reviews
     */
    private function updateUserReputation($userId) {
        $stats = $this->getUserReviewStats($userId);

        if ($stats && $stats['total_reviews'] > 0) {
            $reputationScore = min(5.0, $stats['overall_avg']);

            $sql = "UPDATE users SET reputation_score = :score WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':score' => round($reputationScore, 2),
                ':id' => $userId
            ]);
        }
    }

    /**
     * Get pending reviews for a user (travels completed but not yet reviewed)
     */
    public function getPendingReviews($userId) {
        $sql = "SELECT DISTINCT tp.id, tp.title, tp.destination, u.id as user_id,
                u.username, u.first_name, u.last_name, u.profile_photo
                FROM travel_posts tp
                INNER JOIN travel_participants tpart1 ON tp.id = tpart1.travel_post_id
                INNER JOIN travel_participants tpart2 ON tp.id = tpart2.travel_post_id
                INNER JOIN users u ON tpart2.user_id = u.id
                WHERE tp.status = 'completed'
                AND tpart1.user_id = :current_user_id
                AND tpart1.status = 'accepted'
                AND tpart2.user_id != :current_user_id
                AND tpart2.status = 'accepted'
                AND NOT EXISTS (
                    SELECT 1 FROM reviews r
                    WHERE r.travel_post_id = tp.id
                    AND r.reviewer_id = :current_user_id
                    AND r.reviewed_id = tpart2.user_id
                )
                ORDER BY tp.end_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':current_user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
