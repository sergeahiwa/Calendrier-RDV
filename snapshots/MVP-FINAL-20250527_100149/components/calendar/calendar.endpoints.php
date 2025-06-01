<?php
require_once('../../includes/config.php');
require_once('../../includes/database.php');
require_once('../../includes/session.php');

header('Content-Type: application/json');

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetch':
        // Récupérer les rendez-vous pour FullCalendar (lecture publique possible)
        try {
            $start = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : null;
            $end = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : null;
            $query = "SELECT id, titre as title, date_rdv as start, DATE_ADD(date_rdv, INTERVAL 1 HOUR) as end, statut 
                      FROM {$wpdb->prefix}reservations 
                      WHERE date_rdv BETWEEN %s AND %s";
            $events = $wpdb->get_results($wpdb->prepare($query, $start, $end), ARRAY_A);
            echo json_encode(['success' => true, 'data' => $events]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'data' => [], 'error' => 'Erreur lors de la récupération des événements']);
        }
        break;

    case 'edit':
        // Ajouter ou modifier un rendez-vous (nécessite d'être connecté ou autorisé)
        try {
            // Supporte POST classique (WordPress) ou JSON
            $data = $_POST;
            if (empty($data)) {
                $data = json_decode(file_get_contents('php://input'), true);
            }
            $title = isset($data['title']) ? sanitize_text_field($data['title']) : '';
            $start = isset($data['start']) ? sanitize_text_field($data['start']) : '';
            $end = isset($data['end']) ? sanitize_text_field($data['end']) : '';
            $statut = isset($data['statut']) ? sanitize_text_field($data['statut']) : '';
            $id = isset($data['id']) ? intval($data['id']) : 0;
            if ($id) {
                // Modification
                $wpdb->update(
                    $wpdb->prefix . 'reservations',
                    [
                        'titre' => $title,
                        'date_rdv' => $start,
                        'statut' => $statut
                    ],
                    ['id' => $id]
                );
            } else {
                // Ajout
                $wpdb->insert(
                    $wpdb->prefix . 'reservations',
                    [
                        'titre' => $title,
                        'date_rdv' => $start,
                        'statut' => $statut
                    ]
                );
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'ajout ou modification du rendez-vous']);
        }
        break;

    case 'delete':
        // Supprimer un rendez-vous (nécessite d'être connecté ou autorisé)
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if ($id) {
                $wpdb->delete($wpdb->prefix . 'reservations', ['id' => $id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression du rendez-vous']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
}

// Utilisation de la connexion PDO existante
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';
header('Content-Type: application/json');

// Vérification de la session
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Fonction pour nettoyer les entrées
function sanitize_input($data) {
    return sanitize_input($data);
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'fetch':
            $start = sanitize_input($_GET['start'] ?? date('Y-m-01'));
            $end = sanitize_input($_GET['end'] ?? date('Y-m-t'));
            
            $stmt = $pdo->prepare("SELECT 
                id, 
                titre as title, 
                date_rdv as start, 
                DATE_ADD(date_rdv, INTERVAL 1 HOUR) as end, 
                statut
            FROM reservations 
            WHERE date_rdv BETWEEN ? AND ?");
            
            $stmt->execute([$start, $end]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'events' => $events]);
            break;

        case 'edit':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE reservations SET 
                titre = ?,
                date_rdv = ?,
                statut = ?
                WHERE id = ?");
            
            $ok = $stmt->execute([
                sanitize_input($data['title']),
                sanitize_input($data['start']),
                sanitize_input($data['statut']),
                (int)$data['id']
            ]);
            
            echo json_encode(['success' => $ok]);
            break;

        case 'delete':
            $id = (int)$_POST['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
            $ok = $stmt->execute([$id]);
            echo json_encode(['success' => $ok]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action inconnue']);
    }
} catch (PDOException $e) {
    error_log("Erreur dans calendar.endpoints.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
