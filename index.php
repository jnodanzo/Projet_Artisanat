<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/ArtisanProfile.php';

$database = new Database();
$db = $database->getConnection();
$artisanProfile = new ArtisanProfile($db);

// Récupérer les artisans populaires
$popular_artisans = $artisanProfile->readAll(6, 0);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TchôkôArtisan - Trouvez votre artisan qualifié</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .artisan-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .artisan-card:hover {
            transform: translateY(-5px);
        }
        .rating {
            color: #ffc107;
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hammer"></i> TchôkôArtisan
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#artisans">Artisans</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">À propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Mon Compte</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary ms-2" href="register.php">S'inscrire</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Trouvez votre artisan qualifié en Côte d'Ivoire
                    </h1>
                    <p class="lead mb-4">
                        Connectez-vous avec des artisans professionnels pour tous vos projets de construction, rénovation et réparation.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="search.php" class="btn btn-light btn-lg">
                            <i class="fas fa-search"></i> Rechercher un artisan
                        </a>
                        <a href="register.php?type=artisan" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus"></i> Devenir artisan
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/hero-image.jpg" alt="Artisans au travail" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Nos Services</h2>
                <p class="lead text-muted">Découvrez nos catégories d'artisans qualifiés</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-hammer fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Maçonnerie</h5>
                            <p class="card-text">Construction, rénovation, réparation de murs, dalles, fondations</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-tint fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Plomberie</h5>
                            <p class="card-text">Installation, réparation, maintenance des systèmes d'eau et d'assainissement</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-bolt fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Électricité</h5>
                            <p class="card-text">Installation électrique, dépannage, maintenance des équipements</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-cut fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Menuiserie</h5>
                            <p class="card-text">Fabrication, installation, réparation de meubles et structures en bois</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-paint-brush fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Peinture</h5>
                            <p class="card-text">Peinture intérieure et extérieure, finitions, décoration</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-tools fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Autres Services</h5>
                            <p class="card-text">Carrelage, vitrerie, serrurerie, climatisation et plus encore</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Artisans Section -->
    <section id="artisans" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Artisans Populaires</h2>
                <p class="lead text-muted">Découvrez nos artisans les mieux notés</p>
            </div>
            <div class="row g-4">
                <?php foreach($popular_artisans as $artisan): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card artisan-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $artisan['profile_photo'] ?: 'assets/images/default-avatar.jpg'; ?>" 
                                     alt="<?php echo $artisan['first_name'] . ' ' . $artisan['last_name']; ?>" 
                                     class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                <div>
                                    <h6 class="card-title mb-0"><?php echo $artisan['first_name'] . ' ' . $artisan['last_name']; ?></h6>
                                    <small class="text-muted"><?php echo $artisan['profession']; ?></small>
                                </div>
                            </div>
                            <p class="card-text"><?php echo substr($artisan['description'], 0, 100) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $artisan['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                    <small class="ms-1">(<?php echo $artisan['total_reviews']; ?>)</small>
                                </div>
                                <a href="artisan-profile.php?id=<?php echo $artisan['id']; ?>" class="btn btn-primary btn-sm">
                                    Voir profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="search.php" class="btn btn-primary btn-lg">
                    Voir tous les artisans
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">À propos de TchôkôArtisan</h2>
                    <p class="lead mb-4">
                        TchôkôArtisan est la première plateforme de mise en relation entre clients et artisans qualifiés en Côte d'Ivoire.
                    </p>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-0">Artisans vérifiés</h6>
                                    <small class="text-muted">Profils validés</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-primary fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-0">Sécurisé</h6>
                                    <small class="text-muted">Paiements sécurisés</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-star text-warning fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-0">Notation</h6>
                                    <small class="text-muted">Avis clients</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-headset text-info fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-0">Support 24/7</h6>
                                    <small class="text-muted">Assistance client</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/about-image.jpg" alt="Équipe TchôkôArtisan" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Contactez-nous</h2>
                <p class="lead text-muted">Nous sommes là pour vous aider</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="row g-4">
                        <div class="col-md-4 text-center">
                            <i class="fas fa-phone fa-3x text-primary mb-3"></i>
                            <h5>Téléphone</h5>
                            <p>+225 27 22 49 74 84</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                            <h5>Email</h5>
                            <p>contact@tchokoartisan.ci</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                            <h5>Adresse</h5>
                            <p>Abidjan, Côte d'Ivoire</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-hammer"></i> TchôkôArtisan</h5>
                    <p class="mb-0">La plateforme de référence pour trouver des artisans qualifiés en Côte d'Ivoire.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2024 TchôkôArtisan. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 