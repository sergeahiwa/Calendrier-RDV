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
$page_title = "Statistiques et Visualisations";

// Récupérer les paramètres de filtrage
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$providerId = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : null;

// Fonction pour obtenir les statistiques générales
function getGeneralStats(PDO $pdo, $startDate, $endDate, $providerId = null) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_rdv,
                SUM(CASE WHEN statut = 'confirmé' THEN 1 ELSE 0 END) as confirmes,
                SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                SUM(CASE WHEN statut = 'annulé' THEN 1 ELSE 0 END) as annules,
                AVG(TIMESTAMPDIFF(MINUTE, date_rdv, heure_rdv)) as avg_duration
            FROM reservations
            WHERE date_rdv BETWEEN ? AND ?
            AND (? IS NULL OR prestataire = ?)
        ");
        
        $stmt->execute([
            $startDate,
            $endDate,
            $providerId, $providerId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques générales: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les statistiques par prestataire
function getStatsByProvider(PDO $pdo, $startDate, $endDate) {
    try {
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
            $startDate,
            $endDate
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques par prestataire: " . $e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les statistiques par jour
function getStatsByDay(PDO $pdo, $startDate, $endDate) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(date_rdv) as date,
                COUNT(*) as total_rdv,
                SUM(CASE WHEN statut = 'confirmé' THEN 1 ELSE 0 END) as confirmes,
                SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                SUM(CASE WHEN statut = 'annulé' THEN 1 ELSE 0 END) as annules
            FROM reservations
            WHERE date_rdv BETWEEN ? AND ?
            GROUP BY DATE(date_rdv)
            ORDER BY DATE(date_rdv)
        ");
        
        $stmt->execute([
            $startDate,
            $endDate
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques par jour: " . $e->getMessage());
        return [];
    }
}

// Récupérer les données
$generalStats = getGeneralStats($pdo, $startDate, $endDate, $providerId);
$statsByProvider = getStatsByProvider($pdo, $startDate, $endDate);
$statsByDay = getStatsByDay($pdo, $startDate, $endDate);

// Préparer les données pour les graphiques
$chartData = [
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

// Convertir les données en JSON
$chartDataJson = json_encode($chartData);
$providerChartDataJson = json_encode($providerChartData);
$durationChartDataJson = json_encode($durationChartData);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAN Digital - Statistiques</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="css/status-badges.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
        .chart-container {
            position: relative;
            height: 400px;
        }
        .stat-card {
            height: 100%;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 1.2rem;
            color: #6c757d;
        }
        .stat-change {
            font-size: 1.1rem;
            margin-top: 5px;
        }
        .positive-change {
            color: #28a745;
        }
        .negative-change {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-chart-bar me-2"></i>Statistiques et Visualisations</h1>
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
                                    <?php
                                    $stmt = $pdo->query("SELECT id, nom FROM prestataires ORDER BY nom");
                                    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($providers as $provider): ?>
                                        <option value="<?php echo $provider['id']; ?>" 
                                                <?php echo $provider['id'] == $providerId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($provider['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Appliquer les filtres
                                </button>
                                <a href="statistiques.php" class="btn btn-secondary ms-2">
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
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Total RDV</h5>
                        <div class="stat-value"><?php echo $generalStats['total_rdv'] ?? 0; ?></div>
                        <div class="stat-label">Rendez-vous total</div>
                        <div class="stat-change"><?php 
                            $change = ($generalStats['total_rdv'] ?? 0) - ($generalStats['annules'] ?? 0);
                            echo $change > 0 ? "+ {$change} confirmés" : "- {$change} annulés";
                            echo ' <i class="fas ' . ($change > 0 ? 'fa-arrow-up positive-change' : 'fa-arrow-down negative-change') . '"></i>';
                        ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Confirmés</h5>
                        <div class="stat-value"><?php echo $generalStats['confirmes'] ?? 0; ?></div>
                        <div class="stat-label">Rendez-vous confirmés</div>
                        <div class="stat-change"><?php 
                            $percentage = ($generalStats['confirmes'] ?? 0) / ($generalStats['total_rdv'] ?? 1) * 100;
                            echo number_format($percentage, 1) . '% du total';
                        ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">En attente</h5>
                        <div class="stat-value"><?php echo $generalStats['en_attente'] ?? 0; ?></div>
                        <div class="stat-label">Rendez-vous en attente</div>
                        <div class="stat-change"><?php 
                            $percentage = ($generalStats['en_attente'] ?? 0) / ($generalStats['total_rdv'] ?? 1) * 100;
                            echo number_format($percentage, 1) . '% du total';
                        ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Annulés</h5>
                        <div class="stat-value"><?php echo $generalStats['annules'] ?? 0; ?></div>
                        <div class="stat-label">Rendez-vous annulés</div>
                        <div class="stat-change"><?php 
                            $percentage = ($generalStats['annules'] ?? 0) / ($generalStats['total_rdv'] ?? 1) * 100;
                            echo number_format($percentage, 1) . '% du total';
                        ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Évolution des rendez-vous</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="rdvChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Statistiques par prestataire</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="providerChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Durée moyenne des rendez-vous</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="durationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des prestataires -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Détails par prestataire</h5>
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
                                        <th>Durée moyenne</th>
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
                                                <td><?php 
                                                    $duration = $provider['avg_duration'] ?? 0;
                                                    $hours = floor($duration / 60);
                                                    $minutes = $duration % 60;
                                                    echo sprintf('%dh %02dm', $hours, $minutes);
                                                ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/admin-footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
    <script src="js/dashboard-interactions.js"></script>
    <script src="js/filters.js"></script>
    <script src="js/notifications.js"></script>
    <script src="js/ui-interactions.js"></script>
    <script src="js/reports.js"></script>
    <script src="js/statistics.js"></script>
</body>
</html>
