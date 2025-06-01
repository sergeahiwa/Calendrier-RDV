<?php
// ================================
// Fichier : admin/liste-rdv.php
// Rôle    : Gestion des rendez-vous
// Auteur  : SAN Digital Solutions
// ================================

// Inclusion du fichier d'authentification
require_once 'auth.php';

// Inclusion du fichier de configuration
require_once __DIR__ . '/../includes/config.php';

// Définir le titre de la page
$page_title = "Gestion des rendez-vous";

// Paramètres de pagination
$par_page = 10; // Nombre de rendez-vous par page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $par_page;

// Paramètres de filtrage
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$prestataire = isset($_GET['prestataire']) ? intval($_GET['prestataire']) : 0;
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';

// Construction de la requête SQL avec filtres
try {
    // Partie WHERE de la requête
    $where_conditions = ["1=1"]; // Toujours vrai pour simplifier les AND ultérieurs
    $params = [];
    
    // Filtrage par statut
    if (!empty($statut)) {
        $where_conditions[] = "r.statut = :statut";
        $params[':statut'] = $statut;
    }
    
    // Filtrage par date de début
    if (!empty($date_debut)) {
        $where_conditions[] = "r.date_rdv >= :date_debut";
        $params[':date_debut'] = $date_debut;
    }
    
    // Filtrage par date de fin
    if (!empty($date_fin)) {
        $where_conditions[] = "r.date_rdv <= :date_fin";
        $params[':date_fin'] = $date_fin;
    }
    
    // Filtrage par prestataire
    if (!empty($prestataire)) {
        $where_conditions[] = "r.prestataire = :prestataire";
        $params[':prestataire'] = $prestataire;
    }
    
    // Recherche par nom ou email
    if (!empty($recherche)) {
        $where_conditions[] = "(r.nom LIKE :recherche OR r.email LIKE :recherche OR r.prestation LIKE :recherche)";
        $params[':recherche'] = "%{$recherche}%";
    }
    
    // Construction de la clause WHERE complète
    $where_clause = implode(" AND ", $where_conditions);
    
    // Requête pour compter le nombre total de rendez-vous (pour pagination)
    $query_count = "
        SELECT COUNT(*) 
        FROM reservations r
        LEFT JOIN prestataires p ON r.prestataire = p.id
        WHERE {$where_clause}
    ";
    
    $stmt_count = $pdo->prepare($query_count);
    $stmt_count->execute($params);
    $total_rdv = $stmt_count->fetchColumn();
    
    // Calcul du nombre total de pages
    $total_pages = ceil($total_rdv / $par_page);
    
    // Requête pour récupérer les rendez-vous paginés
    $query = "
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
            {$where_clause}
        ORDER BY 
            r.date_rdv DESC, r.heure_rdv DESC
        LIMIT {$offset}, {$par_page}
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rendez_vous = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupération des prestataires pour le filtre
    $stmt_prestataires = $pdo->query("SELECT id, nom FROM prestataires ORDER BY nom");
    $prestataires = $stmt_prestataires->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des rendez-vous: " . $e->getMessage();
    error_log($error_message);
}

// Traitement des actions (modification de statut)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['rdv_id'])) {
    $rdv_id = intval($_POST['rdv_id']);
    $action = $_POST['action'];
    
    try {
        if ($action === 'confirmer') {
            $stmt = $pdo->prepare("UPDATE reservations SET statut = 'confirmé' WHERE id = :id");
            $stmt->execute([':id' => $rdv_id]);
            $success_message = "Le rendez-vous a été confirmé avec succès";
        } elseif ($action === 'annuler') {
            $stmt = $pdo->prepare("UPDATE reservations SET statut = 'annulé' WHERE id = :id");
            $stmt->execute([':id' => $rdv_id]);
            $success_message = "Le rendez-vous a été annulé avec succès";
        } elseif ($action === 'supprimer') {
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = :id");
            $stmt->execute([':id' => $rdv_id]);
            $success_message = "Le rendez-vous a été supprimé avec succès";
        }
        
        // Redirection pour éviter les soumissions multiples
        header("Location: liste-rdv.php?action_success=1");
        exit;
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la modification du rendez-vous: " . $e->getMessage();
        error_log($error_message);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAN Digital - <?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/admin-style.css">
    <!-- Ajout des icônes Font Awesome pour les boutons d'action -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin-header.php'; ?>
        
        <main class="admin-content">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            
            <?php if (isset($_GET['action_success'])): ?>
                <div class="alert alert-success">
                    L'action a été effectuée avec succès.
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- Filtres -->
            <div class="filter-panel">
                <h2><i class="fas fa-filter"></i> Filtres</h2>
                <form method="get" action="" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="statut">Statut</label>
                            <select id="statut" name="statut">
                                <option value="">Tous</option>
                                <option value="en_attente" <?php echo $statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="confirmé" <?php echo $statut === 'confirmé' ? 'selected' : ''; ?>>Confirmé</option>
                                <option value="annulé" <?php echo $statut === 'annulé' ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_debut">Date de début</label>
                            <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_fin">Date de fin</label>
                            <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="prestataire">Prestataire</label>
                            <select id="prestataire" name="prestataire">
                                <option value="">Tous</option>
                                <?php foreach ($prestataires as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $prestataire == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-group search-group">
                            <label for="recherche">Recherche</label>
                            <input type="text" id="recherche" name="recherche" placeholder="Nom, email, prestation..." value="<?php echo htmlspecialchars($recherche); ?>">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                            <a href="liste-rdv.php" class="btn btn-secondary">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tableau des rendez-vous -->
            <div class="table-container">
                <?php if (empty($rendez_vous)): ?>
                    <p class="no-data">Aucun rendez-vous ne correspond à ces critères</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Contact</th>
                                <th>Prestation</th>
                                <th>Date & Heure</th>
                                <th>Prestataire</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rendez_vous as $rdv): ?>
                                <tr>
                                    <td><?php echo $rdv['id']; ?></td>
                                    <td><?php echo htmlspecialchars($rdv['nom']); ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($rdv['email']); ?>" title="Envoyer un email">
                                            <?php echo htmlspecialchars($rdv['email']); ?>
                                        </a>
                                        <?php if (!empty($rdv['telephone'])): ?>
                                            <br><small><?php echo htmlspecialchars($rdv['telephone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($rdv['prestation']); ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?>
                                        <br>
                                        <small><?php echo date('H:i', strtotime($rdv['heure_rdv'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($rdv['nom_prestataire'] ?? 'Non assigné'); ?></td>
                                    <td>
                                        <span class="statut-badge statut-<?php echo $rdv['statut']; ?>">
                                            <?php echo htmlspecialchars($rdv['statut']); ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <div class="dropdown">
                                            <button class="btn-icon dropdown-toggle">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-content">
                                                <a href="voir-rdv.php?id=<?php echo $rdv['id']; ?>">
                                                    <i class="fas fa-eye"></i> Voir détails
                                                </a>
                                                
                                                <?php if ($rdv['statut'] !== 'confirmé'): ?>
                                                    <form method="post" action="" onsubmit="return confirm('Confirmer ce rendez-vous ?')">
                                                        <input type="hidden" name="rdv_id" value="<?php echo $rdv['id']; ?>">
                                                        <input type="hidden" name="action" value="confirmer">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-check-circle"></i> Confirmer
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($rdv['statut'] !== 'annulé'): ?>
                                                    <form method="post" action="" onsubmit="return confirm('Annuler ce rendez-vous ?')">
                                                        <input type="hidden" name="rdv_id" value="<?php echo $rdv['id']; ?>">
                                                        <input type="hidden" name="action" value="annuler">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-times-circle"></i> Annuler
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce rendez-vous ? Cette action est irréversible.')">
                                                    <input type="hidden" name="rdv_id" value="<?php echo $rdv['id']; ?>">
                                                    <input type="hidden" name="action" value="supprimer">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash-alt"></i> Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&statut=<?php echo urlencode($statut); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&prestataire=<?php echo $prestataire; ?>&recherche=<?php echo urlencode($recherche); ?>" class="page-link">
                                    &laquo; Précédent
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&statut=<?php echo urlencode($statut); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&prestataire=<?php echo $prestataire; ?>&recherche=<?php echo urlencode($recherche); ?>" class="page-link <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&statut=<?php echo urlencode($statut); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&prestataire=<?php echo $prestataire; ?>&recherche=<?php echo urlencode($recherche); ?>" class="page-link">
                                    Suivant &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <a id="export-link" href="export-csv.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-secondary">
                    <i class="fas fa-file-export"></i> Exporter (CSV)
                </a>
                <a href="calendrier.php" class="btn btn-info">
                    <i class="fas fa-calendar-alt"></i> Voir le calendrier
                </a>
            </div>
        </main>
        
        <?php include 'includes/admin-footer.php'; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var exportBtn = document.getElementById('export-link');
        if(exportBtn) {
            exportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.location = this.href;
                setTimeout(function() {
                    window.location.href = 'liste-rdv.php';
                }, 1000);
            });
        }
    });
    </script>
    
    <script>
    // Script pour le menu déroulant des actions
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const dropdown = this.closest('.dropdown');
                
                // Fermer tous les autres dropdowns
                document.querySelectorAll('.dropdown').forEach(d => {
                    if (d !== dropdown) d.classList.remove('active');
                });
                
                // Basculer l'état du dropdown actuel
                dropdown.classList.toggle('active');
            });
        });
        
        // Fermer les dropdowns quand on clique ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown').forEach(d => {
                    d.classList.remove('active');
                });
            }
        });
    });
    </script>
</body>
</html>
