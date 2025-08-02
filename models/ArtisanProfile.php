<?php
require_once 'config/database.php';

class ArtisanProfile {
    private $conn;
    private $table_name = "artisan_profiles";

    public $id;
    public $user_id;
    public $profession;
    public $description;
    public $experience_years;
    public $hourly_rate;
    public $daily_rate;
    public $profile_photo;
    public $cover_photo;
    public $address;
    public $city;
    public $latitude;
    public $longitude;
    public $is_available;
    public $rating;
    public $total_reviews;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un profil d'artisan
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    profession = :profession,
                    description = :description,
                    experience_years = :experience_years,
                    hourly_rate = :hourly_rate,
                    daily_rate = :daily_rate,
                    address = :address,
                    city = :city,
                    latitude = :latitude,
                    longitude = :longitude";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->profession = htmlspecialchars(strip_tags($this->profession));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));

        // Lier les paramètres
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":profession", $this->profession);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":experience_years", $this->experience_years);
        $stmt->bindParam(":hourly_rate", $this->hourly_rate);
        $stmt->bindParam(":daily_rate", $this->daily_rate);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Lire un profil par user_id
    public function readByUserId($user_id) {
        $query = "SELECT ap.*, u.first_name, u.last_name, u.email, u.phone 
                FROM " . $this->table_name . " ap
                JOIN users u ON ap.user_id = u.id
                WHERE ap.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lire un profil par ID
    public function read($id) {
        $query = "SELECT ap.*, u.first_name, u.last_name, u.email, u.phone 
                FROM " . $this->table_name . " ap
                JOIN users u ON ap.user_id = u.id
                WHERE ap.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mettre à jour un profil
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    profession = :profession,
                    description = :description,
                    experience_years = :experience_years,
                    hourly_rate = :hourly_rate,
                    daily_rate = :daily_rate,
                    address = :address,
                    city = :city,
                    latitude = :latitude,
                    longitude = :longitude,
                    is_available = :is_available
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->profession = htmlspecialchars(strip_tags($this->profession));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));

        // Lier les paramètres
        $stmt->bindParam(":profession", $this->profession);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":experience_years", $this->experience_years);
        $stmt->bindParam(":hourly_rate", $this->hourly_rate);
        $stmt->bindParam(":daily_rate", $this->daily_rate);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":is_available", $this->is_available);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Rechercher des artisans
    public function search($profession = null, $city = null, $min_rating = null) {
        $query = "SELECT ap.*, u.first_name, u.last_name, u.email, u.phone 
                FROM " . $this->table_name . " ap
                JOIN users u ON ap.user_id = u.id
                WHERE ap.is_available = 1";

        $params = [];
        $types = [];

        if($profession) {
            $query .= " AND ap.profession LIKE ?";
            $params[] = "%$profession%";
            $types[] = PDO::PARAM_STR;
        }

        if($city) {
            $query .= " AND ap.city LIKE ?";
            $params[] = "%$city%";
            $types[] = PDO::PARAM_STR;
        }

        if($min_rating) {
            $query .= " AND ap.rating >= ?";
            $params[] = $min_rating;
            $types[] = PDO::PARAM_STR;
        }

        $query .= " ORDER BY ap.rating DESC, ap.total_reviews DESC";

        $stmt = $this->conn->prepare($query);
        
        for($i = 0; $i < count($params); $i++) {
            $stmt->bindParam($i + 1, $params[$i], $types[$i]);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lister tous les artisans
    public function readAll($limit = 10, $offset = 0) {
        $query = "SELECT ap.*, u.first_name, u.last_name, u.email, u.phone 
                FROM " . $this->table_name . " ap
                JOIN users u ON ap.user_id = u.id
                ORDER BY ap.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mettre à jour la note moyenne
    public function updateRating($artisan_id) {
        $query = "UPDATE " . $this->table_name . " ap
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

    // Compter tous les artisans
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?> 