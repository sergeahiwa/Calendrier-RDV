<?php
// admin/export-csv.php
require_once __DIR__ . '/../includes/config.php';

// (Optionnel) Protection par session admin :
// session_start();
// if (!isset($_SESSION['admin'])) { exit('Accès interdit'); }

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=export-rdv.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Titre', 'Début', 'Fin', 'Prestataire']);

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$prestataire_id = $_GET['prestataire_id'] ?? null;

$query = "SELECT * FROM reservations WHERE 1=1";
$params = [];

if ($start) {
    $query .= " AND date_rdv >= :start";
    $params[':start'] = $start;
}
if ($end) {
    $query .= " AND date_rdv <= :end";
    $params[':end'] = $end;
}
if ($prestataire_id) {
    $query .= " AND prestataire = :prestataire_id";
    $params[':prestataire_id'] = $prestataire_id;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['nom'] ?? $row['title'] ?? '',
        $row['date_rdv'] ?? $row['start'],
        $row['heure_rdv'] ?? $row['end'],
        $row['prestataire']
    ]);
}
fclose($output);
exit;
