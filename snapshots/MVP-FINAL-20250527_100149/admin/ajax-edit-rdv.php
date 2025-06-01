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

// Récupération des données POST
$data = json_decode(file_get_contents('php://input'), true);

// Validation des données
if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

try {
    // Préparation de la requête
    $stmt = $pdo->prepare("
        UPDATE reservations 
        SET 
            nom = COALESCE(?, nom),
            telephone = COALESCE(?, telephone),
            email = COALESCE(?, email),
            prestation = COALESCE(?, prestation),
            statut = COALESCE(?, statut),
            prestataire = COALESCE(?, prestataire),
            date_rdv = COALESCE(?, date_rdv),
            heure_rdv = COALESCE(?, heure_rdv)
        WHERE id = ?
    ");
    
    // Exécution de la requête
    $stmt->execute([
        $data['nom'] ?? null,
        $data['telephone'] ?? null,
        $data['email'] ?? null,
        $data['prestation'] ?? null,
        $data['statut'] ?? null,
        $data['prestataire'] ?? null,
        $data['date_rdv'] ?? null,
        $data['heure_rdv'] ?? null,
        $data['id']
    ]);
    
    // Création d'une notification
    createNotification($pdo, $_SESSION['admin_id'], 'rdv_updated', 
        "Le rendez-vous avec {$data['nom']} a été mis à jour");
    
    echo json_encode([
        'success' => true,
        'message' => 'Rendez-vous mis à jour avec succès'
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour du rendez-vous: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}
