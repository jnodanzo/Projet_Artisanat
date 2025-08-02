<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $email;
    public $phone;
    public $password;
    public $first_name;
    public $last_name;
    public $user_type;
    public $is_active;
    public $is_verified;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouvel utilisateur
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    email = :email,
                    phone = :phone,
                    password = :password,
                    first_name = :first_name,
                    last_name = :last_name,
                    user_type = :user_type";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->user_type = htmlspecialchars(strip_tags($this->user_type));

        // Hasher le mot de passe
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Lier les paramètres
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":user_type", $this->user_type);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Lire un utilisateur par email
    public function readByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lire un utilisateur par téléphone
    public function readByPhone($phone) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE phone = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $phone);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lire un utilisateur par ID
    public function read($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mettre à jour un utilisateur
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    email = :email,
                    phone = :phone,
                    first_name = :first_name,
                    last_name = :last_name,
                    is_active = :is_active,
                    is_verified = :is_verified
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));

        // Lier les paramètres
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":is_verified", $this->is_verified);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Supprimer un utilisateur
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    // Lister tous les utilisateurs
    public function readAll($limit = 10, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Compter tous les utilisateurs
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Vérifier le mot de passe
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    // Changer le mot de passe
    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . " SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bindParam(1, $hashed_password);
        $stmt->bindParam(2, $this->id);
        return $stmt->execute();
    }
}
?> 