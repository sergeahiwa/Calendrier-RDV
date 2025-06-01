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

// Préparation de la requête
$where = "WHERE date_rdv >= CURDATE()";
$params = [];

// Ajout des filtres
if (!empty($data['provider_id'])) {
    $where .= " AND prestataire = ?";
    $params[] = $data['provider_id'];
}

if (!empty($data['status'])) {
    $where .= " AND statut = ?";
    $params[] = $data['status'];
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            date_rdv,
            heure_rdv,
            nom,
            telephone,
            email,
            prestation,
            statut,
            prestataire,
            (SELECT nom FROM prestataires WHERE id = prestataire) as prestataire_nom
        FROM reservations
        $where
        ORDER BY date_rdv, heure_rdv
    ");
    
    $stmt->execute($params);
    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatage des rendez-vous pour FullCalendar
    $events = array_map(function($rdv) {
        return [
            'id' => $rdv['id'],
            'title' => $rdv['nom'] . ' - ' . $rdv['prestation'],
            'start' => $rdv['date_rdv'] . 'T' . $rdv['heure_rdv'],
            'extendedProps' => [
                'nom' => $rdv['nom'],
                'telephone' => $rdv['telephone'],
                'email' => $rdv['email'],
                'prestation' => $rdv['prestation'],
                'statut' => $rdv['statut'],
                'prestataire' => $rdv['prestataire'],
                'prestataire_nom' => $rdv['prestataire_nom']
            ]
        ];
    }, $rdvs);
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des rendez-vous: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}
