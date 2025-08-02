<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/ArtisanProfile.php';

$error = '';
$success = '';

// Récupérer le type d'inscription depuis l'URL
$user_type = isset($_GET['type']) ? $_GET['type'] : 'client';
if (!in_array($user_type, ['client', 'artisan'])) {
    $user_type = 'client';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    // Validation des données
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $user_type = sanitize($_POST['user_type']);

    // Validation
    if (empty($email) || empty($phone) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        // Vérifier si l'email existe déjà
        if ($user->readByEmail($email)) {
            $error = 'Cette adresse email est déjà utilisée.';
        } elseif ($user->readByPhone($phone)) {
            $error = 'Ce numéro de téléphone est déjà utilisé.';
        } else {
            // Créer l'utilisateur
            $user->email = $email;
            $user->phone = $phone;
            $user->password = $password;
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->user_type = $user_type;
            $user->is_active = true;
            $user->is_verified = false;

            $user_id = $user->create();

            if ($user_id) {
                // Si c'est un artisan, créer le profil
                if ($user_type == 'artisan') {
                    $artisanProfile = new ArtisanProfile($db);
                    $artisanProfile->user_id = $user_id;
                    $artisanProfile->profession = sanitize($_POST['profession']);
                    $artisanProfile->description = sanitize($_POST['description']);
                    $artisanProfile->experience_years = (int)$_POST['experience_years'];
                    $artisanProfile->hourly_rate = (float)$_POST['hourly_rate'];
                    $artisanProfile->daily_rate = (float)$_POST['daily_rate'];
                    $artisanProfile->city = sanitize($_POST['city']);
                    $artisanProfile->address = sanitize($_POST['address']);

                    $artisanProfile->create();
                }

                $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                
                // Envoyer un email de confirmation
                $subject = "Bienvenue sur TchôkôArtisan";
                $message = "Bonjour $first_name $last_name,\n\n";
                $message .= "Votre compte a été créé avec succès sur TchôkôArtisan.\n";
                $message .= "Vous pouvez maintenant vous connecter et commencer à utiliser nos services.\n\n";
                $message .= "Cordialement,\nL'équipe TchôkôArtisan";
                
                sendEmail($email, $subject, $message);
            } else {
                $error = 'Erreur lors de la création du compte.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - TchôkôArtisan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-md-8 col-lg-6">
                <div class="register-card p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-hammer fa-3x text-primary mb-3"></i>
                        <h2 class="fw-bold">TchôkôArtisan</h2>
                        <p class="text-muted">Créez votre compte</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <br><a href="login.php" class="alert-link">Cliquez ici pour vous connecter</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="registerForm">
                        <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">
                        
                        <!-- Informations personnelles -->
                        <div class="form-section active" id="personalInfo">
                            <h5 class="mb-3">
                                <i class="fas fa-user"></i> Informations personnelles
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                           required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> Téléphone *
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                       placeholder="+2250700000000" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i> Mot de passe *
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="6" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock"></i> Confirmer le mot de passe *
                                    </label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="6" required>
                                </div>
                            </div>

                            <?php if ($user_type == 'artisan'): ?>
                                <button type="button" class="btn btn-primary" onclick="nextSection()">
                                    Suivant <i class="fas fa-arrow-right"></i>
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Créer mon compte
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Informations artisan (si applicable) -->
                        <?php if ($user_type == 'artisan'): ?>
                        <div class="form-section" id="artisanInfo">
                            <h5 class="mb-3">
                                <i class="fas fa-tools"></i> Informations professionnelles
                            </h5>
                            
                            <div class="mb-3">
                                <label for="profession" class="form-label">Profession *</label>
                                <select class="form-select" id="profession" name="profession" required>
                                    <option value="">Sélectionnez votre profession</option>
                                    <option value="Maçon" <?php echo (isset($_POST['profession']) && $_POST['profession'] == 'Maçon') ? 'selected' : ''; ?>>Maçon</option>
                                    <option value="Plombier" <?php echo (isset($_POST['profession']) && $_POST['profession'] == 'Plombier') ? 'selected' : ''; ?>>Plombier</option>
                                    <option value="Électricien" <?php echo (isset($_POST['profession']) && $_POST['profession'] == 'Électricien') ? 'selected' : ''; ?>>Électricien</option>
                                    <option value="Menuisier" <?php echo (isset($_POST['profession']) && $_POST['profession'] == 'Menuisier') ? 'selected' : ''; ?>>Menuisier</option>
                                    <option value="Peintre" <?php echo (isset($_POST['profession']) && $_POST['profession'] == 'Peintre') ? 'selected' : ''; ?>>Peintre</option>
                                    <option value="Carreleur" <?php echo (isset($_POST['profession']) && $_POST['profession'] == 'Carreleur') ? 'selected' : ''; ?>>Carreleur</option>
                                    <option value="Serrurier" <?php echo (isset($_POST['profession']) && $_POST['profession'] == 'Serrurier') ? 'selected' : ''; ?>>Serrurier</option>
                                    <option value="Autre" <?php echo (isset($_POST['profession']) && $_POST['profession'] == 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description de vos services</label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                          placeholder="Décrivez vos compétences et services..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="experience_years" class="form-label">Années d'expérience</label>
                                    <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                           min="0" max="50" value="<?php echo isset($_POST['experience_years']) ? $_POST['experience_years'] : '0'; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">Ville *</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" 
                                           placeholder="Abidjan" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Adresse complète</label>
                                <textarea class="form-control" id="address" name="address" rows="2" 
                                          placeholder="Votre adresse complète..."><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="hourly_rate" class="form-label">Tarif horaire (FCFA)</label>
                                    <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" 
                                           min="0" step="100" value="<?php echo isset($_POST['hourly_rate']) ? $_POST['hourly_rate'] : '5000'; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="daily_rate" class="form-label">Tarif journalier (FCFA)</label>
                                    <input type="number" class="form-control" id="daily_rate" name="daily_rate" 
                                           min="0" step="1000" value="<?php echo isset($_POST['daily_rate']) ? $_POST['daily_rate'] : '15000'; ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary" onclick="prevSection()">
                                    <i class="fas fa-arrow-left"></i> Précédent
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Créer mon compte
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">Déjà un compte ?</p>
                        <a href="login.php" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </a>
                    </div>

                    <div class="text-center mt-3">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i> Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function nextSection() {
            document.getElementById('personalInfo').classList.remove('active');
            document.getElementById('artisanInfo').classList.add('active');
        }

        function prevSection() {
            document.getElementById('artisanInfo').classList.remove('active');
            document.getElementById('personalInfo').classList.add('active');
        }

        // Validation du mot de passe
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html> 