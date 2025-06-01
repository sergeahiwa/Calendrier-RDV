<?php
// ================================
// Fichier : admin/login.php
// Rôle    : Page de connexion à l'administration
// Auteur  : SAN Digital Solutions
// ================================

// Démarrage de la session
session_start();

// Si l'utilisateur est déjà connecté, redirection vers l'accueil admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Définir le titre de la page
$page_title = "Connexion à l'administration";

// Inclusion du fichier de configuration
require_once __DIR__ . '/../includes/config.php';

// Traitement du formulaire si soumis
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
    
    // Vérification des identifiants
    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Connexion réussie - initialisation de la session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['last_activity'] = time();
            
            // Redirection vers la page d'accueil de l'administration
            header('Location: index.php');
            exit;
        } else {
            $error_message = "Identifiants incorrects";
        }
    } catch (PDOException $e) {
        $error_message = "Erreur de connexion: " . $e->getMessage();
        error_log("Erreur authentification admin: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAN Digital - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>SAN Digital Solutions</h1>
            <h2>Administration des rendez-vous</h2>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="post" action="" class="login-form">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </div>
        </form>
        
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> SAN Digital Solutions - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
