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

// Définir le titre de la page
$page_title = "Rapports et Exportations";

// Récupérer les paramètres de filtrage
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$providerId = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Récupérer les données pour les rapports
try {
    // Statistiques générales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_rdv,
            SUM(CASE WHEN statut = 'confirmé' THEN 1 ELSE 0 END) as confirmes,
            SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN statut = 'annulé' THEN 1 ELSE 0 END) as annules
        FROM reservations
        WHERE date_rdv BETWEEN ? AND ?
        AND (? IS NULL OR prestataire = ?)
        AND (? IS NULL OR statut = ?)
    ");
    
    $stmt->execute([
        $startDate,
        $endDate,
        $providerId, $providerId,
        $status, $status
    ]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Statistiques par prestataire
    $stmt = $pdo->prepare("
        SELECT 
            p.nom as prestataire,
            COUNT(*) as total_rdv,
            SUM(CASE WHEN r.statut = 'confirmé' THEN 1 ELSE 0 END) as confirmes,
            SUM(CASE WHEN r.statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN r.statut = 'annulé' THEN 1 ELSE 0 END) as annules
        FROM reservations r
        LEFT JOIN prestataires p ON r.prestataire = p.id
        WHERE r.date_rdv BETWEEN ? AND ?
        AND (? IS NULL OR r.prestataire = ?)
        AND (? IS NULL OR r.statut = ?)
        GROUP BY p.id, p.nom
        ORDER BY p.nom
    ");
    
    $stmt->execute([
        $startDate,
        $endDate,
        $providerId, $providerId,
        $status, $status
    ]);
    $statsByProvider = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Liste des prestataires
    $stmt = $pdo->query("SELECT id, nom FROM prestataires ORDER BY nom");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
    error_log($error_message);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAN Digital - Rapports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="css/status-badges.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-chart-bar me-2"></i>Rapports et Exportations</h1>
            </div>
        </div>

        <!-- Filtres -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres</h5>
                    </div>
                    <div class="card-body">
                        <form class="row g-3" method="GET">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo htmlspecialchars($startDate); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Date de fin</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo htmlspecialchars($endDate); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="provider_id" class="form-label">Prestataire</label>
                                <select class="form-select" id="provider_id" name="provider_id">
                                    <option value="">Tous les prestataires</option>
                                    <?php foreach ($providers as $provider): ?>
                                        <option value="<?php echo $provider['id']; ?>" 
                                                <?php echo $provider['id'] == $providerId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($provider['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Statut</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="confirmé" <?php echo $status === 'confirmé' ? 'selected' : ''; ?>>Confirmé</option>
                                    <option value="en_attente" <?php echo $status === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="annulé" <?php echo $status === 'annulé' ? 'selected' : ''; ?>>Annulé</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Appliquer les filtres
                                </button>
                                <a href="rapports.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-sync-alt me-1"></i>Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques générales -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted mb-2">Total RDV</h5>
                        <h2 class="display-4 text-primary"><?php echo $stats['total_rdv'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted mb-2">Confirmés</h5>
                        <h2 class="display-4 text-success"><?php echo $stats['confirmes'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted mb-2">En attente</h5>
                        <h2 class="display-4 text-warning"><?php echo $stats['en_attente'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted mb-2">Annulés</h5>
                        <h2 class="display-4 text-danger"><?php echo $stats['annules'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques par prestataire -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Statistiques par prestataire</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Prestataire</th>
                                <th>Total RDV</th>
                                <th>Confirmés</th>
                                <th>En attente</th>
                                <th>Annulés</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($statsByProvider)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                        <p class="mb-0 text-muted">Aucune donnée disponible</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($statsByProvider as $provider): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($provider['prestataire']); ?></td>
                                        <td><?php echo $provider['total_rdv']; ?></td>
                                        <td><?php echo $provider['confirmes']; ?></td>
                                        <td><?php echo $provider['en_attente']; ?></td>
                                        <td><?php echo $provider['annules']; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="export.php?type=provider&id=<?php echo $provider['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file-excel"></i>
                                                </a>
                                                <a href="export.php?type=provider&id=<?php echo $provider['id']; ?>&format=pdf" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Boutons d'export -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i>Exporter les données</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="export.php?type=all&format=excel" class="btn btn-block btn-outline-primary">
                            <i class="fas fa-file-excel me-2"></i>Exporter en Excel
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="export.php?type=all&format=pdf" class="btn btn-block btn-outline-secondary">
                            <i class="fas fa-file-pdf me-2"></i>Exporter en PDF
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="export.php?type=all&format=csv" class="btn btn-block btn-outline-info">
                            <i class="fas fa-file-csv me-2"></i>Exporter en CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/admin-footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard-interactions.js"></script>
    <script src="js/filters.js"></script>
    <script src="js/notifications.js"></script>
    <script src="js/ui-interactions.js"></script>
</body>
</html>
