-- Création de la base de données TchôkôArtisan
CREATE DATABASE IF NOT EXISTS tchokoartisan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tchokoartisan;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    user_type ENUM('client', 'artisan', 'admin') DEFAULT 'client',
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des profils d'artisans
CREATE TABLE artisan_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    profession VARCHAR(100) NOT NULL,
    description TEXT,
    experience_years INT DEFAULT 0,
    hourly_rate DECIMAL(10,2),
    daily_rate DECIMAL(10,2),
    profile_photo VARCHAR(255),
    cover_photo VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    latitude DECIMAL(10,8),
    longitude DECIMAL(10,8),
    is_available BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des services proposés par les artisans
CREATE TABLE artisan_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artisan_id INT NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    price_type ENUM('hourly', 'daily', 'fixed') DEFAULT 'hourly',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artisan_id) REFERENCES artisan_profiles(id) ON DELETE CASCADE
);

-- Table des zones d'intervention
CREATE TABLE artisan_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artisan_id INT NOT NULL,
    zone_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artisan_id) REFERENCES artisan_profiles(id) ON DELETE CASCADE
);

-- Table des demandes de service
CREATE TABLE service_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    artisan_id INT NOT NULL,
    service_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    request_date DATE NOT NULL,
    preferred_time TIME,
    status ENUM('pending', 'accepted', 'rejected', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2),
    client_address TEXT,
    client_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artisan_id) REFERENCES artisan_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES artisan_services(id) ON DELETE SET NULL
);

-- Table des avis et commentaires
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    artisan_id INT NOT NULL,
    service_request_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (artisan_id) REFERENCES artisan_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE SET NULL
);

-- Table des messages
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    service_request_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE SET NULL
);

-- Table des disponibilités des artisans
CREATE TABLE artisan_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    artisan_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME,
    end_time TIME,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (artisan_id) REFERENCES artisan_profiles(id) ON DELETE CASCADE
);

-- Table des notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des paiements
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_request_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'card') DEFAULT 'cash',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE
);

-- Table des statistiques admin
CREATE TABLE admin_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    total_users INT DEFAULT 0,
    total_artisans INT DEFAULT 0,
    total_clients INT DEFAULT 0,
    total_requests INT DEFAULT 0,
    completed_requests INT DEFAULT 0,
    total_revenue DECIMAL(12,2) DEFAULT 0.00,
    date DATE UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion des données de test
INSERT INTO users (email, phone, password, first_name, last_name, user_type, is_verified) VALUES
('admin@tchokoartisan.ci', '+2250700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Tchôkô', 'admin', TRUE),
('client1@example.com', '+2250700000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kouassi', 'Jean', 'client', TRUE),
('artisan1@example.com', '+2250700000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Yao', 'Pierre', 'artisan', TRUE);

-- Insertion des profils d'artisans
INSERT INTO artisan_profiles (user_id, profession, description, experience_years, hourly_rate, city, rating) VALUES
(3, 'Maçon', 'Maçon expérimenté spécialisé dans la construction et rénovation', 8, 5000.00, 'Abidjan', 4.5);

-- Insertion des services
INSERT INTO artisan_services (artisan_id, service_name, description, price, price_type) VALUES
(1, 'Construction maison', 'Construction complète de maison', 15000.00, 'daily'),
(1, 'Rénovation salle de bain', 'Rénovation complète de salle de bain', 25000.00, 'fixed');

-- Insertion des zones d'intervention
INSERT INTO artisan_zones (artisan_id, zone_name) VALUES
(1, 'Cocody'),
(1, 'Marcory'),
(1, 'Riviera'); 