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

try {
    // Récupération des notifications non lues
    $notifications = getUnreadNotifications($pdo, $_SESSION['admin_id']);
    
    // Comptage des notifications non lues
    $count = count($notifications);
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'notifications' => $notifications
    ]);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
