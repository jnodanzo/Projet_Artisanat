<?php
require_once 'config/database.php';

class ServiceRequest {
    private $conn;
    private $table_name = "service_requests";

    public $id;
    public $client_id;
    public $artisan_id;
    public $service_id;
    public $title;
    public $description;
    public $request_date;
    public $preferred_time;
    public $status;
    public $total_amount;
    public $client_address;
    public $client_phone;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer une nouvelle demande de service
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    client_id = :client_id,
                    artisan_id = :artisan_id,
                    service_id = :service_id,
                    title = :title,
                    description = :description,
                    request_date = :request_date,
                    preferred_time = :preferred_time,
                    client_address = :client_address,
                    client_phone = :client_phone";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->client_address = htmlspecialchars(strip_tags($this->client_address));
        $this->client_phone = htmlspecialchars(strip_tags($this->client_phone));

        // Lier les paramètres
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":artisan_id", $this->artisan_id);
        $stmt->bindParam(":service_id", $this->service_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":request_date", $this->request_date);
        $stmt->bindParam(":preferred_time", $this->preferred_time);
        $stmt->bindParam(":client_address", $this->client_address);
        $stmt->bindParam(":client_phone", $this->client_phone);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Lire une demande par ID
    public function read($id) {
        $query = "SELECT sr.*, 
                        c.first_name as client_first_name, c.last_name as client_last_name, c.email as client_email,
                        a.profession as artisan_profession,
                        u.first_name as artisan_first_name, u.last_name as artisan_last_name, u.email as artisan_email
                FROM " . $this->table_name . " sr
                JOIN users c ON sr.client_id = c.id
                JOIN artisan_profiles a ON sr.artisan_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE sr.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mettre à jour le statut d'une demande
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $id);
        return $stmt->execute();
    }

    // Lister les demandes d'un client
    public function getClientRequests($client_id, $limit = 10, $offset = 0) {
        $query = "SELECT sr.*, 
                        a.profession as artisan_profession,
                        u.first_name as artisan_first_name, u.last_name as artisan_last_name
                FROM " . $this->table_name . " sr
                JOIN artisan_profiles a ON sr.artisan_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE sr.client_id = ?
                ORDER BY sr.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $client_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lister les demandes d'un artisan
    public function getArtisanRequests($artisan_id, $limit = 10, $offset = 0) {
        $query = "SELECT sr.*, 
                        c.first_name as client_first_name, c.last_name as client_last_name, c.email as client_email
                FROM " . $this->table_name . " sr
                JOIN users c ON sr.client_id = c.id
                WHERE sr.artisan_id = ?
                ORDER BY sr.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $artisan_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter les demandes par statut pour un artisan
    public function countByStatus($artisan_id) {
        $query = "SELECT status, COUNT(*) as count 
                FROM " . $this->table_name . " 
                WHERE artisan_id = ? 
                GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $artisan_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Supprimer une demande
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    // Lister toutes les demandes (admin)
    public function readAll($limit = 10, $offset = 0) {
        $query = "SELECT sr.*, 
                        c.first_name as client_first_name, c.last_name as client_last_name,
                        a.profession as artisan_profession,
                        u.first_name as artisan_first_name, u.last_name as artisan_last_name
                FROM " . $this->table_name . " sr
                JOIN users c ON sr.client_id = c.id
                JOIN artisan_profiles a ON sr.artisan_id = a.id
                JOIN users u ON a.user_id = u.id
                ORDER BY sr.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter toutes les demandes
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Obtenir les statistiques des demandes
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_requests,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests
                FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 