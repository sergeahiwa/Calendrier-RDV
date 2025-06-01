<?php
// Vérification de la session
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des données POST
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$rdvId = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

if (!$rdvId || !in_array($status, ['en_attente', 'confirmé', 'annulé'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    // Mise à jour du statut
    $stmt = $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ?");
    $success = $stmt->execute([$status, $rdvId]);

    if ($success) {
        // Récupération des statistiques mises à jour
        $stats = getRdvStatistics($pdo);
        
        echo json_encode([
            'success' => true,
            'statusClass' => getStatusClass($status),
            'statusText' => ucfirst($status),
            'stats' => $stats
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour du statut: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} ================================
// Fichier : admin/ajax-update-rdv-status.php
// Rôle    : API AJAX pour mettre à jour le statut d'un rendez-vous
// Auteur  : SAN Digital Solutions
// ================================

// Vérification de l'authentification via la session
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Inclusion du fichier de configuration
require_once __DIR__ . '/../includes/config.php';

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Vérification des paramètres requis
if (!isset($_POST['rdv_id']) || !is_numeric($_POST['rdv_id']) || !isset($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants ou invalides']);
    exit;
}

$rdv_id = intval($_POST['rdv_id']);
$action = $_POST['action'];

// Validation de l'action
$actions_valides = ['confirmer', 'annuler'];
if (!in_array($action, $actions_valides)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Action non valide']);
    exit;
}

try {
    // Préparation de la mise à jour selon l'action
    if ($action === 'confirmer') {
        $stmt = $pdo->prepare("UPDATE reservations SET statut = 'confirmé' WHERE id = :id");
        $message_log = "Rendez-vous #$rdv_id confirmé";
    } else { // annuler
        $stmt = $pdo->prepare("UPDATE reservations SET statut = 'annulé' WHERE id = :id");
        $message_log = "Rendez-vous #$rdv_id annulé";
    }
    
    // Exécution de la requête
    $result = $stmt->execute([':id' => $rdv_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Récupération des informations du rendez-vous pour l'email
        $stmt = $pdo->prepare("
            SELECT 
                r.nom, r.email, r.date_rdv, r.heure_rdv, r.prestation,
                p.email AS email_prestataire
            FROM 
                reservations r
            LEFT JOIN 
                prestataires p ON r.prestataire = p.id
            WHERE 
                r.id = :id
        ");
        $stmt->execute([':id' => $rdv_id]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Enregistrement dans les logs d'activité (facultatif)
        $admin_username = $_SESSION['admin_username'] ?? 'Administrateur';
        $stmt_log = $pdo->prepare("
            INSERT INTO admin_logs (admin_username, action, message, ip_address) 
            VALUES (:admin_username, :action, :message, :ip)
        ");
        
        $stmt_log->execute([
            ':admin_username' => $admin_username,
            ':action' => $action,
            ':message' => $message_log,
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        // Tout s'est bien passé
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Statut mis à jour avec succès',
            'nouveau_statut' => $action === 'confirmer' ? 'confirmé' : 'annulé'
        ]);
        
        // TODO: Envoyer un email de notification (à implémenter)
        
    } else {
        // Aucune ligne mise à jour (ID inexistant ou déjà dans l'état demandé)
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Aucune modification effectuée'
        ]);
    }
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
    
    // Log de l'erreur
    error_log("Erreur dans ajax-update-rdv-status.php: " . $e->getMessage());
}
?>
