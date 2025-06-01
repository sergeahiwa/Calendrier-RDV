<?php
// ================================
// Fichier : admin/admin-ajouter-prestataire.php
// Rôle    : Formulaire d'ajout de prestataire sécurisé
// Auteur  : SAN Digital Solutions
// ================================

// Inclusion du fichier d'authentification
require_once 'auth.php';

// Inclusion du fichier de configuration
require_once __DIR__ . '/../includes/config.php';

// Définir le titre de la page
$page_title = "Ajouter un prestataire";

// Variables pour le formulaire
$nom = '';
$email = '';
$services = '';
$message = '';
$error = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $services = filter_input(INPUT_POST, 'services', FILTER_SANITIZE_STRING);
    
    // Validation des données
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom du prestataire est requis";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide";
    }
    
    // Si aucune erreur, traitement
    if (empty($errors)) {
        try {
            // Vérification si le prestataire existe déjà
            $check = $pdo->prepare("SELECT COUNT(*) FROM prestataires WHERE email = :email");
            $check->execute([':email' => $email]);
            
            if ($check->fetchColumn() > 0) {
                $message = "Un prestataire avec cette adresse email existe déjà";
                $error = true;
            } else {
                // Insertion du nouveau prestataire
                $stmt = $pdo->prepare("
                    INSERT INTO prestataires (nom, email, services) 
                    VALUES (:nom, :email, :services)
                ");
                
                $result = $stmt->execute([
                    ':nom' => $nom,
                    ':email' => $email,
                    ':services' => $services
                ]);
                
                if ($result) {
                    $message = "Le prestataire a été ajouté avec succès";
                    // Réinitialisation des champs
                    $nom = $email = $services = '';
                } else {
                    $message = "Erreur lors de l'ajout du prestataire";
                    $error = true;
                }
            }
        } catch (PDOException $e) {
            $message = "Erreur de base de données: " . $e->getMessage();
            $error = true;
            error_log("Erreur ajout prestataire: " . $e->getMessage());
        }
    } else {
        // Affichage des erreurs
        $message = "Erreurs dans le formulaire:<br>" . implode("<br>", $errors);
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAN Digital - <?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin-header.php'; ?>
        
        <main class="admin-content">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $error ? 'alert-danger' : 'alert-success'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="admin-form">
                <div class="form-group">
                    <label for="nom">Nom du prestataire</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required>
                    <small>Nom complet qui apparaîtra dans le formulaire de réservation</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <small>L'email sera utilisé pour notifier le prestataire des rendez-vous</small>
                </div>
                
                <div class="form-group">
                    <label for="services">Services proposés</label>
                    <textarea id="services" name="services" rows="4"><?php echo htmlspecialchars($services); ?></textarea>
                    <small>Liste des services séparés par des virgules (optionnel)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Ajouter le prestataire</button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </main>
        
        <?php include 'includes/admin-footer.php'; ?>
    </div>
</body>
</html>
