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
require_once __DIR__ . '/../includes/notifications.php';

// Récupération des données POST
$data = json_decode(file_get_contents('php://input'), true);

// Validation des données
if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

try {
    // Marquage de la notification comme lue
    $success = markNotificationAsRead($pdo, $data['id']);
    
    echo json_encode([
        'success' => $success
    ]);
} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour de la notification: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
