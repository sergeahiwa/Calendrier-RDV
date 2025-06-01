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
    // Récupérer les informations du rendez-vous avant suppression
    $stmt = $pdo->prepare("SELECT nom FROM reservations WHERE id = ?");
    $stmt->execute([$data['id']]);
    $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Supprimer le rendez-vous
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $stmt->execute([$data['id']]);
    
    // Créer une notification
    createNotification($pdo, $_SESSION['admin_id'], 'rdv_deleted', 
        "Le rendez-vous avec {$rdv['nom']} a été supprimé");
    
    echo json_encode([
        'success' => true,
        'message' => 'Rendez-vous supprimé avec succès'
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la suppression du rendez-vous: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}
