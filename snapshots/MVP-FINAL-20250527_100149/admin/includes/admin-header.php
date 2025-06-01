<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAN Digital Solutions - Administration</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin-style.css">
    <link rel="stylesheet" href="../css/status-badges.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <!-- Scripts nécessaires pour le tableau de bord -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard-interactions.js" defer></script>
    <script src="js/filters.js" defer></script>
    <script src="js/notifications.js" defer></script>
    <script src="js/ui-interactions.js" defer></script>
    <script src="js/reports.js" defer></script>
</head>
<body>
<header class="admin-header navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <h1 class="h4 mb-0">
                <i class="fas fa-calendar-alt me-2"></i>
                SAN Digital Solutions
            </h1>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="liste-rdv.php">
                        <i class="fas fa-calendar-check me-1"></i> Rendez-vous
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="liste-prestataires.php">
                        <i class="fas fa-users me-1"></i> Prestataires
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calendrier.php">
                        <i class="far fa-calendar-alt me-1"></i> Calendrier
                    </a>
                </li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="logs.php">
                        <i class="fas fa-clipboard-list me-1"></i> Logs
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</header>

<main class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
