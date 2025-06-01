<?php
// ================================
// Fichier : admin/ajax-get-rdv-details.php
// Rôle    : API AJAX pour récupérer les détails d'un rendez-vous
// Auteur  : SAN Digital Solutions
// ================================

// Vérification de l'authentification via la session
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Inclusion du fichier de configuration
require_once __DIR__ . '/../includes/config.php';

// Vérification du paramètre ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de rendez-vous non valide']);
    exit;
}

$rdv_id = intval($_GET['id']);

try {
    // Récupération des détails complets du rendez-vous
    $stmt = $pdo->prepare("
        SELECT 
            r.id, 
            r.nom, 
            r.email, 
            r.telephone, 
            r.prestation, 
            r.date_rdv,
            r.heure_rdv, 
            r.statut, 
            r.commentaire,
            r.date_creation,
            p.nom AS nom_prestataire
        FROM 
            reservations r
        LEFT JOIN 
            prestataires p ON r.prestataire = p.id
        WHERE 
            r.id = :id
    ");
    
    $stmt->execute([':id' => $rdv_id]);
    $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rdv) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Rendez-vous non trouvé']);
        exit;
    }
    
    // Formater la date
    $rdv['date_formattee'] = date('d/m/Y', strtotime($rdv['date_rdv']));
    $rdv['heure_formattee'] = date('H:i', strtotime($rdv['heure_rdv']));
    
    // Retourner les données au format JSON
    header('Content-Type: application/json');
    echo json_encode($rdv);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
    
    // Log de l'erreur
    error_log("Erreur dans ajax-get-rdv-details.php: " . $e->getMessage());
}
?>
