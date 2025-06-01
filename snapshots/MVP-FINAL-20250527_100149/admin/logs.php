<?php
// ================================
// Fichier : admin/logs.php
// Rôle    : Visualisation des logs d'administration
// Auteur  : SAN Digital Solutions
// ================================


// Inclusion du fichier d'authentification
require_once 'auth.php';

// Vérifier les droits d'administration
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Définir le titre de la page
$page_title = "Visualisation des logs";

// Dossier des logs
$logDir = dirname(__DIR__) . '/logs/';
$logFiles = [];
$logContent = '';
$selectedFile = '';

// Récupérer la liste des fichiers de log
if (is_dir($logDir)) {
    $files = scandir($logDir, SCANDIR_SORT_DESCENDING);
    $logFiles = array_filter($files, function($file) {
        return strpos($file, 'app-') === 0 && substr($file, -4) === '.log';
    });
    
    // Lire le contenu du fichier sélectionné
    if (isset($_GET['log']) && in_array($_GET['log'], $logFiles)) {
        $selectedFile = $_GET['log'];
        $logContent = file_get_contents($logDir . $selectedFile);
        // Nettoyer le contenu pour l'affichage
        $logContent = htmlspecialchars($logContent);
    } elseif (!empty($logFiles)) {
        // Par défaut, afficher le fichier le plus récent
        $selectedFile = reset($logFiles);
        $logContent = file_get_contents($logDir . $selectedFile);
        $logContent = htmlspecialchars($logContent);
    }
}

// Inclure l'en-tête d'administration
include 'includes/admin-header.php';
?>

<div class="admin-container">
    <?php include 'includes/admin-sidebar.php'; ?>
    
    <main class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-clipboard-list me-2"></i>Visualisation des logs</h1>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Fichiers de logs disponibles</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="list-group">
                            <?php if (empty($logFiles)): ?>
                                <div class="alert alert-warning">Aucun fichier de log trouvé.</div>
                            <?php else: ?>
                                <?php foreach ($logFiles as $file): ?>
                                    <a href="?log=<?php echo urlencode($file); ?>" 
                                       class="list-group-item list-group-item-action <?php echo ($file === $selectedFile) ? 'active' : ''; ?>">
                                        <i class="fas fa-file-alt me-2"></i><?php echo $file; ?>
                                        <span class="float-end">
                                            <?php echo date("d/m/Y H:i", filemtime($logDir . $file)); ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Contenu du fichier : <?php echo htmlspecialchars($selectedFile); ?></h6>
                                    <?php if ($selectedFile): ?>
                                        <a href="?log=<?php echo urlencode($selectedFile); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-sync-alt"></i> Actualiser
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if ($selectedFile): ?>
                                    <pre class="m-0 p-3" style="max-height: 600px; overflow-y: auto; background-color: #f8f9fa;">
                                        <?php echo $logContent; ?>
                                    </pre>
                                <?php else: ?>
                                    <div class="p-3 text-muted">Sélectionnez un fichier de log à afficher</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    line-height: 1.4;
}

.list-group-item.active {
    z-index: 0;
}
</style>

<?php include 'includes/admin-footer.php'; ?>
