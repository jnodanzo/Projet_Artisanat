<?php
require_once 'config/database.php';

class Message {
    private $conn;
    private $table_name = "messages";

    public $id;
    public $sender_id;
    public $receiver_id;
    public $service_request_id;
    public $message;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouveau message
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    sender_id = :sender_id,
                    receiver_id = :receiver_id,
                    service_request_id = :service_request_id,
                    message = :message";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->message = htmlspecialchars(strip_tags($this->message));

        // Lier les paramètres
        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":receiver_id", $this->receiver_id);
        $stmt->bindParam(":service_request_id", $this->service_request_id);
        $stmt->bindParam(":message", $this->message);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Lire un message par ID
    public function read($id) {
        $query = "SELECT m.*, 
                        s.first_name as sender_first_name, s.last_name as sender_last_name,
                        r.first_name as receiver_first_name, r.last_name as receiver_last_name
                FROM " . $this->table_name . " m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                WHERE m.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Marquer un message comme lu
    public function markAsRead($id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    // Obtenir la conversation entre deux utilisateurs
    public function getConversation($user1_id, $user2_id, $limit = 50, $offset = 0) {
        $query = "SELECT m.*, 
                        s.first_name as sender_first_name, s.last_name as sender_last_name,
                        r.first_name as receiver_first_name, r.last_name as receiver_last_name
                FROM " . $this->table_name . " m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user1_id);
        $stmt->bindParam(2, $user2_id);
        $stmt->bindParam(3, $user2_id);
        $stmt->bindParam(4, $user1_id);
        $stmt->bindParam(5, $limit, PDO::PARAM_INT);
        $stmt->bindParam(6, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtenir les conversations d'un utilisateur
    public function getUserConversations($user_id) {
        $query = "SELECT DISTINCT 
                        CASE 
                            WHEN m.sender_id = ? THEN m.receiver_id 
                            ELSE m.sender_id 
                        END as other_user_id,
                        u.first_name, u.last_name,
                        (SELECT message FROM " . $this->table_name . " 
                         WHERE ((sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?))
                         ORDER BY created_at DESC LIMIT 1) as last_message,
                        (SELECT created_at FROM " . $this->table_name . " 
                         WHERE ((sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?))
                         ORDER BY created_at DESC LIMIT 1) as last_message_time,
                        (SELECT COUNT(*) FROM " . $this->table_name . " 
                         WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
                FROM " . $this->table_name . " m
                JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
                WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
                ORDER BY last_message_time DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $user_id);
        $stmt->bindParam(3, $user_id);
        $stmt->bindParam(4, $user_id);
        $stmt->bindParam(5, $user_id);
        $stmt->bindParam(6, $user_id);
        $stmt->bindParam(7, $user_id);
        $stmt->bindParam(8, $user_id);
        $stmt->bindParam(9, $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter les messages non lus d'un utilisateur
    public function countUnreadMessages($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                WHERE receiver_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Marquer tous les messages d'une conversation comme lus
    public function markConversationAsRead($user1_id, $user2_id) {
        $query = "UPDATE " . $this->table_name . " 
                SET is_read = 1 
                WHERE receiver_id = ? AND sender_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user1_id);
        $stmt->bindParam(2, $user2_id);
        return $stmt->execute();
    }

    // Supprimer un message
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    // Lister tous les messages (admin)
    public function readAll($limit = 10, $offset = 0) {
        $query = "SELECT m.*, 
                        s.first_name as sender_first_name, s.last_name as sender_last_name,
                        r.first_name as receiver_first_name, r.last_name as receiver_last_name
                FROM " . $this->table_name . " m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter tous les messages
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Obtenir les statistiques des messages
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total_messages,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_messages,
                    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_messages
                FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Rechercher des messages par mot-clé
    public function searchMessages($keyword, $user_id = null) {
        $query = "SELECT m.*, 
                        s.first_name as sender_first_name, s.last_name as sender_last_name,
                        r.first_name as receiver_first_name, r.last_name as receiver_last_name
                FROM " . $this->table_name . " m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                WHERE m.message LIKE ?";
        
        if ($user_id) {
            $query .= " AND (m.sender_id = ? OR m.receiver_id = ?)";
        }
        
        $query .= " ORDER BY m.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%$keyword%";
        $stmt->bindParam(1, $keyword);
        if ($user_id) {
            $stmt->bindParam(2, $user_id);
            $stmt->bindParam(3, $user_id);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtenir les messages liés à une demande de service
    public function getServiceRequestMessages($service_request_id) {
        $query = "SELECT m.*, 
                        s.first_name as sender_first_name, s.last_name as sender_last_name,
                        r.first_name as receiver_first_name, r.last_name as receiver_last_name
                FROM " . $this->table_name . " m
                JOIN users s ON m.sender_id = s.id
                JOIN users r ON m.receiver_id = r.id
                WHERE m.service_request_id = ?
                ORDER BY m.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $service_request_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 