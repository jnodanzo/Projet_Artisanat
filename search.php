<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/ArtisanProfile.php';

$database = new Database();
$db = $database->getConnection();
$artisanProfile = new ArtisanProfile($db);

// Récupérer les paramètres de recherche
$profession = isset($_GET['profession']) ? sanitize($_GET['profession']) : '';
$city = isset($_GET['city']) ? sanitize($_GET['city']) : '';
$min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : null;
$sort_by = isset($_GET['sort_by']) ? sanitize($_GET['sort_by']) : 'rating';

// Effectuer la recherche
$artisans = $artisanProfile->search($profession, $city, $min_rating);

// Liste des professions disponibles
$professions = [
    'Maçon' => 'Maçon',
    'Plombier' => 'Plombier', 
    'Électricien' => 'Électricien',
    'Menuisier' => 'Menuisier',
    'Peintre' => 'Peintre',
    'Carreleur' => 'Carreleur',
    'Serrurier' => 'Serrurier',
    'Autre' => 'Autre'
];

// Liste des villes
$cities = [
    'Abidjan' => 'Abidjan',
    'Bouaké' => 'Bouaké',
    'San-Pédro' => 'San-Pédro',
    'Korhogo' => 'Korhogo',
    'Yamoussoukro' => 'Yamoussoukro',
    'Daloa' => 'Daloa',
    'Gagnoa' => 'Gagnoa',
    'Man' => 'Man',
    'Divo' => 'Divo',
    'Autre' => 'Autre'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher un artisan - TchôkôArtisan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .search-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
        .price-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
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
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="search.php">Rechercher</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">À propos</a>
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
    <section class="search-hero">
        <div class="container">
            <div class="text-center">
                <h1 class="display-4 fw-bold mb-4">Trouvez votre artisan qualifié</h1>
                <p class="lead mb-4">Recherchez parmi des milliers d'artisans vérifiés en Côte d'Ivoire</p>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Filtres -->
                <div class="col-lg-3 mb-4">
                    <div class="filter-card p-4">
                        <h5 class="mb-3">
                            <i class="fas fa-filter"></i> Filtres
                        </h5>
                        
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label for="profession" class="form-label">Profession</label>
                                <select class="form-select" id="profession" name="profession">
                                    <option value="">Toutes les professions</option>
                                    <?php foreach($professions as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $profession == $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="city" class="form-label">Ville</label>
                                <select class="form-select" id="city" name="city">
                                    <option value="">Toutes les villes</option>
                                    <?php foreach($cities as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $city == $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="min_rating" class="form-label">Note minimum</label>
                                <select class="form-select" id="min_rating" name="min_rating">
                                    <option value="">Toutes les notes</option>
                                    <option value="4" <?php echo $min_rating == 4 ? 'selected' : ''; ?>>4 étoiles et plus</option>
                                    <option value="3" <?php echo $min_rating == 3 ? 'selected' : ''; ?>>3 étoiles et plus</option>
                                    <option value="2" <?php echo $min_rating == 2 ? 'selected' : ''; ?>>2 étoiles et plus</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="sort_by" class="form-label">Trier par</label>
                                <select class="form-select" id="sort_by" name="sort_by">
                                    <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>Meilleure note</option>
                                    <option value="reviews" <?php echo $sort_by == 'reviews' ? 'selected' : ''; ?>>Plus d'avis</option>
                                    <option value="price" <?php echo $sort_by == 'price' ? 'selected' : ''; ?>>Prix croissant</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                            
                            <a href="search.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i> Réinitialiser
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Résultats -->
                <div class="col-lg-9">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>
                            <i class="fas fa-users"></i> 
                            <?php echo count($artisans); ?> artisan(s) trouvé(s)
                        </h4>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="changeView('grid')">
                                <i class="fas fa-th"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="changeView('list')">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>

                    <?php if (empty($artisans)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>Aucun artisan trouvé</h5>
                            <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4" id="artisansGrid">
                            <?php foreach($artisans as $artisan): ?>
                            <div class="col-lg-6 col-md-6">
                                <div class="card artisan-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start mb-3">
                                            <img src="<?php echo $artisan['profile_photo'] ?: 'assets/images/default-avatar.jpg'; ?>" 
                                                 alt="<?php echo $artisan['first_name'] . ' ' . $artisan['last_name']; ?>" 
                                                 class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-1"><?php echo $artisan['first_name'] . ' ' . $artisan['last_name']; ?></h6>
                                                <small class="text-muted"><?php echo $artisan['profession']; ?></small>
                                                <div class="rating mt-1">
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star<?php echo $i <= $artisan['rating'] ? '' : '-o'; ?>"></i>
                                                    <?php endfor; ?>
                                                    <small class="ms-1">(<?php echo $artisan['total_reviews']; ?>)</small>
                                                </div>
                                            </div>
                                            <span class="badge price-badge">
                                                <?php echo number_format($artisan['hourly_rate'], 0, ',', ' '); ?> FCFA/h
                                            </span>
                                        </div>
                                        
                                        <p class="card-text"><?php echo substr($artisan['description'], 0, 100) . '...'; ?></p>
                                        
                                        <div class="row text-muted small mb-3">
                                            <div class="col-6">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo $artisan['city']; ?>
                                            </div>
                                            <div class="col-6">
                                                <i class="fas fa-clock"></i> <?php echo $artisan['experience_years']; ?> ans d'exp.
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <a href="artisan-profile.php?id=<?php echo $artisan['id']; ?>" class="btn btn-primary btn-sm flex-fill">
                                                <i class="fas fa-eye"></i> Voir profil
                                            </a>
                                            <a href="contact-artisan.php?id=<?php echo $artisan['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-phone"></i> Contacter
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Pagination des artisans" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Précédent</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Suivant</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
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
    <script>
        function changeView(view) {
            const grid = document.getElementById('artisansGrid');
            if (view === 'list') {
                grid.classList.remove('row');
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
                grid.classList.add('row');
            }
        }
    </script>
</body>
</html> 