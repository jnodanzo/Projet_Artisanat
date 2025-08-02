<?php
require_once 'config/database.php';

class Review {
    private $conn;
    private $table_name = "reviews";

    public $id;
    public $client_id;
    public $artisan_id;
    public $service_request_id;
    public $rating;
    public $comment;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouvel avis
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    client_id = :client_id,
                    artisan_id = :artisan_id,
                    service_request_id = :service_request_id,
                    rating = :rating,
                    comment = :comment";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->comment = htmlspecialchars(strip_tags($this->comment));

        // Lier les paramètres
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":artisan_id", $this->artisan_id);
        $stmt->bindParam(":service_request_id", $this->service_request_id);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comment", $this->comment);

        if($stmt->execute()) {
            // Mettre à jour la note moyenne de l'artisan
            $this->updateArtisanRating($this->artisan_id);
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Lire un avis par ID
    public function read($id) {
        $query = "SELECT r.*, 
                        c.first_name as client_first_name, c.last_name as client_last_name,
                        u.first_name as artisan_first_name, u.last_name as artisan_last_name
                FROM " . $this->table_name . " r
                JOIN users c ON r.client_id = c.id
                JOIN artisan_profiles a ON r.artisan_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE r.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lister les avis d'un artisan
    public function getArtisanReviews($artisan_id, $limit = 10, $offset = 0) {
        $query = "SELECT r.*, 
                        c.first_name as client_first_name, c.last_name as client_last_name
                FROM " . $this->table_name . " r
                JOIN users c ON r.client_id = c.id
                WHERE r.artisan_id = ?
                ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $artisan_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtenir les statistiques des avis d'un artisan
    public function getArtisanReviewStats($artisan_id) {
        $query = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM " . $this->table_name . " 
                WHERE artisan_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $artisan_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Vérifier si un client a déjà noté un artisan pour un service
    public function hasClientReviewed($client_id, $artisan_id, $service_request_id = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                WHERE client_id = ? AND artisan_id = ?";
        if ($service_request_id) {
            $query .= " AND service_request_id = ?";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $client_id);
        $stmt->bindParam(2, $artisan_id);
        if ($service_request_id) {
            $stmt->bindParam(3, $service_request_id);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }

    // Mettre à jour la note moyenne de l'artisan
    private function updateArtisanRating($artisan_id) {
        $query = "UPDATE artisan_profiles ap
                SET ap.rating = (
                    SELECT AVG(r.rating)
                    FROM reviews r
                    WHERE r.artisan_id = ap.id
                ),
                ap.total_reviews = (
                    SELECT COUNT(*)
                    FROM reviews r
                    WHERE r.artisan_id = ap.id
                )
                WHERE ap.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $artisan_id);
        return $stmt->execute();
    }

    // Supprimer un avis
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    // Lister tous les avis (admin)
    public function readAll($limit = 10, $offset = 0) {
        $query = "SELECT r.*, 
                        c.first_name as client_first_name, c.last_name as client_last_name,
                        u.first_name as artisan_first_name, u.last_name as artisan_last_name,
                        a.profession as artisan_profession
                FROM " . $this->table_name . " r
                JOIN users c ON r.client_id = c.id
                JOIN artisan_profiles a ON r.artisan_id = a.id
                JOIN users u ON a.user_id = u.id
                ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter tous les avis
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Obtenir les statistiques globales des avis
    public function getGlobalStats() {
        $query = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Rechercher des avis par mot-clé
    public function searchReviews($keyword, $artisan_id = null) {
        $query = "SELECT r.*, 
                        c.first_name as client_first_name, c.last_name as client_last_name,
                        u.first_name as artisan_first_name, u.last_name as artisan_last_name
                FROM " . $this->table_name . " r
                JOIN users c ON r.client_id = c.id
                JOIN artisan_profiles a ON r.artisan_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE r.comment LIKE ?";
        
        if ($artisan_id) {
            $query .= " AND r.artisan_id = ?";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%$keyword%";
        $stmt->bindParam(1, $keyword);
        if ($artisan_id) {
            $stmt->bindParam(2, $artisan_id);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 