<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $identifier = sanitize($_POST['identifier']); // email ou téléphone
    $password = $_POST['password'];

    // Vérifier si c'est un email ou un téléphone
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $user_data = $user->readByEmail($identifier);
    } else {
        $user_data = $user->readByPhone($identifier);
    }

    if ($user_data && $user->verifyPassword($password, $user_data['password'])) {
        if ($user_data['is_active']) {
            // Créer la session
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['user_type'] = $user_data['user_type'];
            $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
            $_SESSION['user_email'] = $user_data['email'];

            // Rediriger selon le type d'utilisateur
            if ($user_data['user_type'] == 'admin') {
                redirect('admin/dashboard.php');
            } else {
                redirect('dashboard.php');
            }
        } else {
            $error = 'Votre compte a été désactivé. Contactez l\'administrateur.';
        }
    } else {
        $error = 'Identifiants incorrects.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TchôkôArtisan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="login-card p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-hammer fa-3x text-primary mb-3"></i>
                        <h2 class="fw-bold">TchôkôArtisan</h2>
                        <p class="text-muted">Connectez-vous à votre compte</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="identifier" class="form-label">
                                <i class="fas fa-envelope"></i> Email ou Téléphone
                            </label>
                            <input type="text" class="form-control" id="identifier" name="identifier" 
                                   value="<?php echo isset($_POST['identifier']) ? htmlspecialchars($_POST['identifier']) : ''; ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="forgot-password.php" class="text-decoration-none">
                            Mot de passe oublié ?
                        </a>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">Pas encore de compte ?</p>
                        <div class="d-grid gap-2 mt-2">
                            <a href="register.php?type=client" class="btn btn-outline-primary">
                                <i class="fas fa-user"></i> S'inscrire comme client
                            </a>
                            <a href="register.php?type=artisan" class="btn btn-outline-success">
                                <i class="fas fa-tools"></i> S'inscrire comme artisan
                            </a>
                        </div>
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
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html> 