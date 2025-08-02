<?php
// Configuration générale de l'application
session_start();

// Configuration de base
define('BASE_URL', 'http://localhost/tchokoartisan');
define('APP_NAME', 'TchôkôArtisan');
define('APP_VERSION', '1.0.0');

// Configuration des emails
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Configuration SMS (Twilio)
define('TWILIO_ACCOUNT_SID', 'your-twilio-account-sid');
define('TWILIO_AUTH_TOKEN', 'your-twilio-auth-token');
define('TWILIO_PHONE_NUMBER', '+1234567890');

// Configuration des uploads
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Configuration JWT
define('JWT_SECRET', 'your-jwt-secret-key');
define('JWT_EXPIRE', 3600); // 1 heure

// Fonctions utilitaires
function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isArtisan() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'artisan';
}

function isClient() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'client';
}

function requireAuth() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        redirect('dashboard.php');
    }
}

function requireArtisan() {
    requireAuth();
    if (!isArtisan()) {
        redirect('dashboard.php');
    }
}

function requireClient() {
    requireAuth();
    if (!isClient()) {
        redirect('dashboard.php');
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function sendEmail($to, $subject, $message) {
    // Implémentation de l'envoi d'email
    $headers = "From: " . SMTP_USERNAME . "\r\n";
    $headers .= "Reply-To: " . SMTP_USERNAME . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function sendSMS($to, $message) {
    // Implémentation de l'envoi SMS avec Twilio
    // Code à implémenter avec l'API Twilio
    return true;
}
?> 