<?php
// ================================
// Fichier : fetch-events.php
// Rôle   : Fournit les événements au calendrier FullCalendar
// Auteur : SAN Digital Solutions
// ================================

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

try {
    // 1. Récupération et validation des paramètres de requête
    $start = isset($_GET['start']) ? filter_var($_GET['start'], FILTER_SANITIZE_STRING) : null;
    $end = isset($_GET['end']) ? filter_var($_GET['end'], FILTER_SANITIZE_STRING) : null;
    $prestataire_id = isset($_GET['prestataire']) ? filter_var($_GET['prestataire'], FILTER_VALIDATE_INT) : null;
    
    // Vérification du format des dates
    if (($start && !preg_match('/^\d{4}-\d{2}-\d{2}/', $start)) || 
        ($end && !preg_match('/^\d{4}-\d{2}-\d{2}/', $end))) {
        throw new Exception('Format de date invalide');
    }
    
    // 2. Construction de la requête avec filtres optionnels
    $query = "
        SELECT 
            r.id,
            r.nom,
            r.date_rdv,
            r.heure_rdv,
            r.prestation,
            r.statut,
            r.commentaire,
            p.nom AS nom_prestataire,
            p.id AS prestataire_id
        FROM reservations r
        LEFT JOIN prestataires p ON r.prestataire = p.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Filtre par période si spécifié
    if ($start) {
        $query .= " AND r.date_rdv >= :start";
        $params[':start'] = $start;
    }
    
    if ($end) {
        $query .= " AND r.date_rdv <= :end";
        $params[':end'] = $end;
    }
    
    // Filtre par prestataire si spécifié
    if ($prestataire_id) {
        $query .= " AND r.prestataire = :prestataire_id";
        $params[':prestataire_id'] = $prestataire_id;
    }
    
    $query .= " ORDER BY r.date_rdv, r.heure_rdv";
    
    // 3. Exécution de la requête
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    // 4. Préparation des données pour FullCalendar
    $events = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Vérifier que date et heure sont présentes
        if (!empty($row['date_rdv']) && !empty($row['heure_rdv'])) {
            // Couleur selon le statut
            $color = '#346fb3'; // couleur par défaut (bleu SAN)
            
            if ($row['statut'] === 'confirmé') {
                $color = '#8fad0c'; // vert SAN
            } elseif ($row['statut'] === 'annulé') {
                $color = '#cf4444'; // rouge
            }
            
            $events[] = [
                'id'            => $row['id'],
                'title'         => htmlspecialchars($row['nom'].' — '.$row['prestation'], ENT_QUOTES, 'UTF-8'),
                'start'         => $row['date_rdv'].'T'.$row['heure_rdv'],
                'color'         => $color,
                'extendedProps' => [
                    'prestataire'    => htmlspecialchars($row['nom_prestataire'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'prestataire_id' => $row['prestataire_id'],
                    'statut'         => $row['statut'],
                    'commentaire'    => htmlspecialchars($row['commentaire'] ?? '', ENT_QUOTES, 'UTF-8')
                ]
            ];
        }
    }
    
    echo json_encode($events, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // En cas d'erreur, renvoyer un statut 500 et le message
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la récupération des événements : ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
    // Log de l'erreur pour le débogage
    error_log('Erreur fetch-events.php: ' . $e->getMessage());
}
