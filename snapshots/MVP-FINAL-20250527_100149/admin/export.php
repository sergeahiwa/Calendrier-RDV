<?php
// Vérification de la session
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: connexion.php');
    exit;
}

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Récupérer les paramètres
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'excel';
$providerId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Définir les dates par défaut
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Fonction pour générer le nom du fichier
function generateFileName($type, $format, $providerId = null) {
    $date = date('Y-m-d');
    $suffix = $providerId ? "_prestataire_{$providerId}" : '';
    return "rapport_{$type}_{$date}{$suffix}.{$format}";
}

// Fonction pour exporter en Excel
function exportToExcel($data, $fileName) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');
    
    // Écrire l'en-tête
    fputcsv($output, array_keys($data[0]), ';');
    
    // Écrire les données
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}

// Fonction pour exporter en CSV
function exportToCSV($data, $fileName) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');
    
    // Écrire l'en-tête
    fputcsv($output, array_keys($data[0]));
    
    // Écrire les données
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Fonction pour exporter en PDF
function exportToPDF($data, $fileName) {
    // Vérifier si TCPDF est installé
    if (!class_exists('TCPDF')) {
        require_once __DIR__ . '/vendor/tcpdf/tcpdf.php';
    }

    // Créer un nouveau PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configurer le document
    $pdf->SetCreator('SAN Digital Solutions');
    $pdf->SetAuthor('SAN Digital Solutions');
    $pdf->SetTitle($fileName);
    $pdf->SetSubject('Rapport de rendez-vous');
    $pdf->SetKeywords('rapport, rendez-vous, prestataire');

    // Ajouter une page
    $pdf->AddPage();

    // Police par défaut
    $pdf->SetFont('helvetica', '', 10);

    // Créer le tableau
    $header = array_keys($data[0]);
    $pdf->Cell(0, 10, 'Rapport de rendez-vous', 0, 1, 'C');
    $pdf->Ln(10);

    // En-têtes
    foreach ($header as $col) {
        $pdf->Cell(40, 10, $col, 1);
    }
    $pdf->Ln();

    // Données
    foreach ($data as $row) {
        foreach ($row as $value) {
            $pdf->Cell(40, 10, $value, 1);
        }
        $pdf->Ln();
    }

    // Sortir le PDF
    $pdf->Output($fileName, 'D');
    exit;
}

// Récupérer les données en fonction du type
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.date_rdv,
            r.heure_rdv,
            r.nom,
            r.telephone,
            r.email,
            r.prestation,
            r.statut,
            p.nom as prestataire_nom
        FROM reservations r
        LEFT JOIN prestataires p ON r.prestataire = p.id
        WHERE r.date_rdv BETWEEN ? AND ?
        AND (? IS NULL OR r.prestataire = ?)
        ORDER BY r.date_rdv, r.heure_rdv
    ");
    
    $stmt->execute([
        $startDate,
        $endDate,
        $providerId, $providerId
    ]);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le nom du fichier
    $fileName = generateFileName($type, $format, $providerId);
    
    // Exporter selon le format demandé
    switch ($format) {
        case 'excel':
            exportToExcel($data, $fileName);
            break;
        case 'csv':
            exportToCSV($data, $fileName);
            break;
        case 'pdf':
            exportToPDF($data, $fileName);
            break;
        default:
            throw new Exception('Format d\'export non supporté');
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de l'export: " . $e->getMessage());
    header('Content-Type: text/plain');
    echo "Une erreur est survenue lors de l'export des données.";
    exit;
}
