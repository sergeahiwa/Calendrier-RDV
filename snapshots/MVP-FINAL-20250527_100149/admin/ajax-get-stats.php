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
if (!isset($data['start_date']) || !isset($data['end_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dates manquantes']);
    exit;
}

try {
    // Récupérer les statistiques par jour
    $stmt = $pdo->prepare("
        SELECT 
            DATE(date_rdv) as date,
            COUNT(*) as total_rdv,
            SUM(CASE WHEN statut = 'confirmé' THEN 1 ELSE 0 END) as confirmes,
            SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN statut = 'annulé' THEN 1 ELSE 0 END) as annules
        FROM reservations
        WHERE date_rdv BETWEEN ? AND ?
        AND (? IS NULL OR prestataire = ?)
        GROUP BY DATE(date_rdv)
        ORDER BY DATE(date_rdv)
    ");
    
    $stmt->execute([
        $data['start_date'],
        $data['end_date'],
        $data['provider_id'], $data['provider_id']
    ]);
    
    $statsByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le graphique des rendez-vous
    $rdvChartData = [
        'labels' => array_column($statsByDay, 'date'),
        'datasets' => [
            [
                'label' => 'Total RDV',
                'data' => array_column($statsByDay, 'total_rdv'),
                'borderColor' => 'rgb(75, 192, 192)',
                'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                'fill' => true
            ],
            [
                'label' => 'Confirmés',
                'data' => array_column($statsByDay, 'confirmes'),
                'borderColor' => 'rgb(54, 162, 235)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'fill' => true
            ],
            [
                'label' => 'En attente',
                'data' => array_column($statsByDay, 'en_attente'),
                'borderColor' => 'rgb(255, 205, 86)',
                'backgroundColor' => 'rgba(255, 205, 86, 0.2)',
                'fill' => true
            ],
            [
                'label' => 'Annulés',
                'data' => array_column($statsByDay, 'annules'),
                'borderColor' => 'rgb(255, 99, 132)',
                'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                'fill' => true
            ]
        ]
    ];

    // Récupérer les statistiques par prestataire
    $stmt = $pdo->prepare("
        SELECT 
            p.nom as prestataire,
            COUNT(*) as total_rdv,
            SUM(CASE WHEN r.statut = 'confirmé' THEN 1 ELSE 0 END) as confirmes,
            SUM(CASE WHEN r.statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN r.statut = 'annulé' THEN 1 ELSE 0 END) as annules,
            AVG(TIMESTAMPDIFF(MINUTE, r.date_rdv, r.heure_rdv)) as avg_duration
        FROM reservations r
        LEFT JOIN prestataires p ON r.prestataire = p.id
        WHERE r.date_rdv BETWEEN ? AND ?
        GROUP BY p.id, p.nom
        ORDER BY p.nom
    ");
    
    $stmt->execute([
        $data['start_date'],
        $data['end_date']
    ]);
    
    $statsByProvider = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le graphique des prestataires
    $providerChartData = [
        'labels' => array_column($statsByProvider, 'prestataire'),
        'datasets' => [
            [
                'label' => 'Total RDV',
                'data' => array_column($statsByProvider, 'total_rdv'),
                'backgroundColor' => [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(153, 102, 255)',
                    'rgb(255, 159, 64)'
                ]
            ]
        ]
    ];

    // Préparer les données pour le graphique des durées
    $durationChartData = [
        'labels' => array_column($statsByProvider, 'prestataire'),
        'datasets' => [
            [
                'label' => 'Durée moyenne (minutes)',
                'data' => array_column($statsByProvider, 'avg_duration'),
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 205, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                'borderColor' => [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(153, 102, 255)',
                    'rgb(255, 159, 64)'
                ],
                'borderWidth' => 1
            ]
        ]
    ];

    echo json_encode([
        'success' => true,
        'rdvChart' => $rdvChartData,
        'providerChart' => $providerChartData,
        'durationChart' => $durationChartData
    ]);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}
