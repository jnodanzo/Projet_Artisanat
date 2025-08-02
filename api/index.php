<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/ArtisanProfile.php';
require_once '../models/ServiceRequest.php';
require_once '../models/Review.php';
require_once '../models/Message.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer la méthode HTTP et l'URL
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$endpoint = end($path_parts);

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Fonction pour envoyer une réponse JSON
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

// Fonction pour envoyer une erreur
function sendError($message, $status_code = 400) {
    sendResponse(['error' => $message], $status_code);
}

// Vérifier l'authentification (pour les endpoints protégés)
function requireAuth() {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$token) {
        sendError('Token d\'authentification requis', 401);
    }
    
    // Ici vous pouvez implémenter la vérification JWT
    // Pour l'instant, on utilise les sessions
    if (!isLoggedIn()) {
        sendError('Non authentifié', 401);
    }
}

// Router API
try {
    switch ($endpoint) {
        case 'artisans':
            if ($method === 'GET') {
                $artisanProfile = new ArtisanProfile($db);
                
                // Paramètres de recherche
                $profession = $_GET['profession'] ?? null;
                $city = $_GET['city'] ?? null;
                $min_rating = $_GET['min_rating'] ?? null;
                $limit = $_GET['limit'] ?? 10;
                $offset = $_GET['offset'] ?? 0;
                
                $artisans = $artisanProfile->search($profession, $city, $min_rating);
                sendResponse(['artisans' => $artisans, 'total' => count($artisans)]);
            }
            break;
            
        case 'artisan':
            if ($method === 'GET') {
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    sendError('ID de l\'artisan requis');
                }
                
                $artisanProfile = new ArtisanProfile($db);
                $artisan = $artisanProfile->read($id);
                
                if (!$artisan) {
                    sendError('Artisan non trouvé', 404);
                }
                
                sendResponse($artisan);
            }
            break;
            
        case 'register':
            if ($method === 'POST') {
                $user = new User($db);
                
                // Validation des données
                if (empty($input['email']) || empty($input['password']) || empty($input['first_name']) || empty($input['last_name'])) {
                    sendError('Tous les champs sont obligatoires');
                }
                
                // Vérifier si l'email existe déjà
                if ($user->readByEmail($input['email'])) {
                    sendError('Cette adresse email est déjà utilisée');
                }
                
                // Créer l'utilisateur
                $user->email = $input['email'];
                $user->phone = $input['phone'] ?? '';
                $user->password = $input['password'];
                $user->first_name = $input['first_name'];
                $user->last_name = $input['last_name'];
                $user->user_type = $input['user_type'] ?? 'client';
                
                $user_id = $user->create();
                
                if ($user_id) {
                    sendResponse(['message' => 'Compte créé avec succès', 'user_id' => $user_id], 201);
                } else {
                    sendError('Erreur lors de la création du compte', 500);
                }
            }
            break;
            
        case 'login':
            if ($method === 'POST') {
                $user = new User($db);
                
                $identifier = $input['identifier'] ?? '';
                $password = $input['password'] ?? '';
                
                if (empty($identifier) || empty($password)) {
                    sendError('Email/téléphone et mot de passe requis');
                }
                
                // Vérifier si c'est un email ou un téléphone
                if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                    $user_data = $user->readByEmail($identifier);
                } else {
                    $user_data = $user->readByPhone($identifier);
                }
                
                if ($user_data && $user->verifyPassword($password, $user_data['password'])) {
                    // Créer la session
                    $_SESSION['user_id'] = $user_data['id'];
                    $_SESSION['user_type'] = $user_data['user_type'];
                    $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
                    
                    sendResponse([
                        'message' => 'Connexion réussie',
                        'user' => [
                            'id' => $user_data['id'],
                            'email' => $user_data['email'],
                            'first_name' => $user_data['first_name'],
                            'last_name' => $user_data['last_name'],
                            'user_type' => $user_data['user_type']
                        ]
                    ]);
                } else {
                    sendError('Identifiants incorrects', 401);
                }
            }
            break;
            
        case 'service-requests':
            requireAuth();
            
            if ($method === 'GET') {
                $serviceRequest = new ServiceRequest($db);
                
                if (isArtisan()) {
                    $artisanProfile = new ArtisanProfile($db);
                    $artisan_data = $artisanProfile->readByUserId($_SESSION['user_id']);
                    $requests = $serviceRequest->getArtisanRequests($artisan_data['id']);
                } else {
                    $requests = $serviceRequest->getClientRequests($_SESSION['user_id']);
                }
                
                sendResponse(['requests' => $requests]);
            } elseif ($method === 'POST') {
                $serviceRequest = new ServiceRequest($db);
                
                $serviceRequest->client_id = $_SESSION['user_id'];
                $serviceRequest->artisan_id = $input['artisan_id'];
                $serviceRequest->title = $input['title'];
                $serviceRequest->description = $input['description'];
                $serviceRequest->request_date = $input['request_date'];
                $serviceRequest->preferred_time = $input['preferred_time'] ?? null;
                $serviceRequest->client_address = $input['client_address'];
                $serviceRequest->client_phone = $input['client_phone'];
                
                $request_id = $serviceRequest->create();
                
                if ($request_id) {
                    sendResponse(['message' => 'Demande créée avec succès', 'request_id' => $request_id], 201);
                } else {
                    sendError('Erreur lors de la création de la demande', 500);
                }
            }
            break;
            
        case 'reviews':
            if ($method === 'GET') {
                $review = new Review($db);
                $artisan_id = $_GET['artisan_id'] ?? null;
                
                if ($artisan_id) {
                    $reviews = $review->getArtisanReviews($artisan_id);
                    $stats = $review->getArtisanReviewStats($artisan_id);
                    sendResponse(['reviews' => $reviews, 'stats' => $stats]);
                } else {
                    sendError('ID de l\'artisan requis');
                }
            } elseif ($method === 'POST') {
                requireAuth();
                
                $review = new Review($db);
                
                $review->client_id = $_SESSION['user_id'];
                $review->artisan_id = $input['artisan_id'];
                $review->rating = $input['rating'];
                $review->comment = $input['comment'] ?? '';
                $review->service_request_id = $input['service_request_id'] ?? null;
                
                $review_id = $review->create();
                
                if ($review_id) {
                    sendResponse(['message' => 'Avis ajouté avec succès', 'review_id' => $review_id], 201);
                } else {
                    sendError('Erreur lors de l\'ajout de l\'avis', 500);
                }
            }
            break;
            
        case 'messages':
            requireAuth();
            
            if ($method === 'GET') {
                $message = new Message($db);
                $conversation_id = $_GET['conversation_id'] ?? null;
                
                if ($conversation_id) {
                    $messages = $message->getConversation($_SESSION['user_id'], $conversation_id);
                    sendResponse(['messages' => $messages]);
                } else {
                    $conversations = $message->getUserConversations($_SESSION['user_id']);
                    sendResponse(['conversations' => $conversations]);
                }
            } elseif ($method === 'POST') {
                $message = new Message($db);
                
                $message->sender_id = $_SESSION['user_id'];
                $message->receiver_id = $input['receiver_id'];
                $message->message = $input['message'];
                $message->service_request_id = $input['service_request_id'] ?? null;
                
                $message_id = $message->create();
                
                if ($message_id) {
                    sendResponse(['message' => 'Message envoyé', 'message_id' => $message_id], 201);
                } else {
                    sendError('Erreur lors de l\'envoi du message', 500);
                }
            }
            break;
            
        case 'profile':
            requireAuth();
            
            if ($method === 'GET') {
                $user = new User($db);
                $user_data = $user->read($_SESSION['user_id']);
                
                if (isArtisan()) {
                    $artisanProfile = new ArtisanProfile($db);
                    $artisan_data = $artisanProfile->readByUserId($_SESSION['user_id']);
                    $user_data['artisan_profile'] = $artisan_data;
                }
                
                sendResponse($user_data);
            } elseif ($method === 'PUT') {
                $user = new User($db);
                
                $user->id = $_SESSION['user_id'];
                $user->first_name = $input['first_name'];
                $user->last_name = $input['last_name'];
                $user->email = $input['email'];
                $user->phone = $input['phone'];
                
                if ($user->update()) {
                    sendResponse(['message' => 'Profil mis à jour avec succès']);
                } else {
                    sendError('Erreur lors de la mise à jour du profil', 500);
                }
            }
            break;
            
        default:
            sendError('Endpoint non trouvé', 404);
            break;
    }
} catch (Exception $e) {
    sendError('Erreur serveur: ' . $e->getMessage(), 500);
}
?> 