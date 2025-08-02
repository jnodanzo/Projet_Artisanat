<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/ArtisanProfile.php';

// Vérifier l'authentification
requireAuth();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$artisanProfile = new ArtisanProfile($db);

// Récupérer les informations de l'utilisateur
$user_data = $user->read($_SESSION['user_id']);

// Récupérer le profil artisan si applicable
$artisan_data = null;
if (isArtisan()) {
    $artisan_data = $artisanProfile->readByUserId($_SESSION['user_id']);
}

// Statistiques pour les artisans
$stats = [];
if (isArtisan() && $artisan_data) {
    // Compter les demandes de service
    $query = "SELECT COUNT(*) as total_requests FROM service_requests WHERE artisan_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $artisan_data['id']);
    $stmt->execute();
    $stats['total_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_requests'];

    // Compter les demandes en attente
    $query = "SELECT COUNT(*) as pending_requests FROM service_requests WHERE artisan_id = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $artisan_data['id']);
    $stmt->execute();
    $stats['pending_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_requests'];

    // Revenus totaux
    $query = "SELECT SUM(total_amount) as total_revenue FROM service_requests WHERE artisan_id = ? AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $artisan_data['id']);
    $stmt->execute();
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?: 0;
}

// Statistiques pour les clients
if (isClient()) {
    // Compter les demandes envoyées
    $query = "SELECT COUNT(*) as total_requests FROM service_requests WHERE client_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    $stats['total_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_requests'];

    // Compter les demandes en cours
    $query = "SELECT COUNT(*) as active_requests FROM service_requests WHERE client_id = ? AND status IN ('accepted', 'in_progress')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    $stats['active_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_requests'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - TchôkôArtisan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-hammer fa-2x text-white mb-2"></i>
                        <h5 class="text-white">TchôkôArtisan</h5>
                    </div>
                    
                    <div class="text-center mb-4">
                        <img src="<?php echo $artisan_data['profile_photo'] ?? 'assets/images/default-avatar.jpg'; ?>" 
                             alt="Avatar" class="rounded-circle mb-2" style="width: 60px; height: 60px; object-fit: cover;">
                        <h6 class="text-white mb-1"><?php echo $_SESSION['user_name']; ?></h6>
                        <small class="text-white-50">
                            <?php 
                            if (isAdmin()) echo 'Administrateur';
                            elseif (isArtisan()) echo 'Artisan';
                            else echo 'Client';
                            ?>
                        </small>
                    </div>

                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                        
                        <?php if (isArtisan()): ?>
                            <a class="nav-link" href="artisan-profile.php">
                                <i class="fas fa-user"></i> Mon profil
                            </a>
                            <a class="nav-link" href="service-requests.php">
                                <i class="fas fa-clipboard-list"></i> Demandes de service
                            </a>
                            <a class="nav-link" href="calendar.php">
                                <i class="fas fa-calendar"></i> Planning
                            </a>
                            <a class="nav-link" href="reviews.php">
                                <i class="fas fa-star"></i> Avis clients
                            </a>
                        <?php elseif (isClient()): ?>
                            <a class="nav-link" href="my-requests.php">
                                <i class="fas fa-clipboard-list"></i> Mes demandes
                            </a>
                            <a class="nav-link" href="favorites.php">
                                <i class="fas fa-heart"></i> Favoris
                            </a>
                            <a class="nav-link" href="messages.php">
                                <i class="fas fa-envelope"></i> Messages
                            </a>
                        <?php endif; ?>
                        
                        <a class="nav-link" href="messages.php">
                            <i class="fas fa-comments"></i> Messages
                        </a>
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Paramètres
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Tableau de bord</h2>
                            <p class="text-muted mb-0">Bienvenue, <?php echo $_SESSION['user_name']; ?> !</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="search.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher un artisan
                            </a>
                            <?php if (isClient()): ?>
                                <a href="new-request.php" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Nouvelle demande
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="row mb-4">
                        <?php if (isArtisan() && $artisan_data): ?>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card p-3 text-center">
                                    <i class="fas fa-clipboard-list fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-1"><?php echo $stats['total_requests']; ?></h4>
                                    <p class="text-muted mb-0">Demandes totales</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card p-3 text-center">
                                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1"><?php echo $stats['pending_requests']; ?></h4>
                                    <p class="text-muted mb-0">En attente</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card p-3 text-center">
                                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1"><?php echo number_format($artisan_data['rating'], 1); ?></h4>
                                    <p class="text-muted mb-0">Note moyenne</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stats-card p-3 text-center">
                                    <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
                                    <h4 class="mb-1"><?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?></h4>
                                    <p class="text-muted mb-0">Revenus (FCFA)</p>
                                </div>
                            </div>
                        <?php elseif (isClient()): ?>
                            <div class="col-md-4 mb-3">
                                <div class="stats-card p-3 text-center">
                                    <i class="fas fa-clipboard-list fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-1"><?php echo $stats['total_requests']; ?></h4>
                                    <p class="text-muted mb-0">Demandes envoyées</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="stats-card p-3 text-center">
                                    <i class="fas fa-tools fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1"><?php echo $stats['active_requests']; ?></h4>
                                    <p class="text-muted mb-0">Services en cours</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="stats-card p-3 text-center">
                                    <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                                    <h4 class="mb-1">0</h4>
                                    <p class="text-muted mb-0">Artisans favoris</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions rapides -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bolt"></i> Actions rapides
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php if (isArtisan()): ?>
                                            <div class="col-md-3 mb-3">
                                                <a href="service-requests.php" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-clipboard-list"></i><br>
                                                    Voir les demandes
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="calendar.php" class="btn btn-outline-success w-100">
                                                    <i class="fas fa-calendar"></i><br>
                                                    Gérer le planning
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="artisan-profile.php" class="btn btn-outline-info w-100">
                                                    <i class="fas fa-user-edit"></i><br>
                                                    Modifier le profil
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="messages.php" class="btn btn-outline-warning w-100">
                                                    <i class="fas fa-envelope"></i><br>
                                                    Messages
                                                </a>
                                            </div>
                                        <?php elseif (isClient()): ?>
                                            <div class="col-md-3 mb-3">
                                                <a href="new-request.php" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-plus"></i><br>
                                                    Nouvelle demande
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="search.php" class="btn btn-outline-success w-100">
                                                    <i class="fas fa-search"></i><br>
                                                    Rechercher un artisan
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="my-requests.php" class="btn btn-outline-info w-100">
                                                    <i class="fas fa-clipboard-list"></i><br>
                                                    Mes demandes
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="messages.php" class="btn btn-outline-warning w-100">
                                                    <i class="fas fa-envelope"></i><br>
                                                    Messages
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activité récente -->
                    <div class="row">
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history"></i> Activité récente
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Nouvelle demande reçue</h6>
                                                <small class="text-muted">Il y a 2 heures</small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill">Nouveau</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Message de Jean Kouassi</h6>
                                                <small class="text-muted">Il y a 1 jour</small>
                                            </div>
                                            <span class="badge bg-warning rounded-pill">Non lu</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Service terminé - Rénovation salle de bain</h6>
                                                <small class="text-muted">Il y a 3 jours</small>
                                            </div>
                                            <span class="badge bg-success rounded-pill">Terminé</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bell"></i> Notifications
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item">
                                            <small class="text-muted">Il y a 1 heure</small>
                                            <p class="mb-1">Nouvelle demande de service</p>
                                        </div>
                                        <div class="list-group-item">
                                            <small class="text-muted">Il y a 3 heures</small>
                                            <p class="mb-1">Message reçu de Yao Pierre</p>
                                        </div>
                                        <div class="list-group-item">
                                            <small class="text-muted">Hier</small>
                                            <p class="mb-1">Avis reçu - 5 étoiles</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 