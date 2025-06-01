<?php
// Vérification de la session
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: connexion.php');
    exit;
}

// Inclusion des fichiers nécessaires
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Titre de la page
$page_title = "Test Calendrier";

// Configuration du calendrier
$calendarConfig = [
    'defaultView' => 'dayGridMonth',
    'eventColors' => [
        'confirmé' => '#28a745',
        'en_attente' => '#ffc107',
        'annulé' => '#dc3545'
    ],
    'ajaxEndpoints' => [
        'fetch' => '/components/calendar/calendar.endpoints.php?action=fetch',
        'edit'  => '/components/calendar/calendar.endpoints.php?action=edit',
        'delete'=> '/components/calendar/calendar.endpoints.php?action=delete'
    ],
    'labels' => [
        'addEvent' => 'Ajouter un rendez-vous',
        'editEvent' => 'Modifier',
        'deleteEvent' => 'Supprimer',
        'eventDetails' => 'Détails du rendez-vous'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    
    <!-- CSS du calendrier -->
    <link rel="stylesheet" href="/components/calendar/calendar.css">
    
    <!-- Styles personnalisés -->
    <style>
        .calendar-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .event-details {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/admin/includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
        </div>

        <div class="calendar-container">
            <div id="calendar-container" data-config='<?= json_encode($calendarConfig) ?>'></div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/admin/includes/admin-footer.php'; ?>

    <!-- Scripts nécessaires -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script src="/components/calendar/calendar.js"></script>
    
    <!-- Callbacks personnalisés -->
    <script>
    window.calendarCustomHandlers = {
        eventClick: function(info) {
            const event = info.event;
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            
            document.getElementById('eventTitle').textContent = event.title;
            document.getElementById('eventStart').textContent = event.startStr;
            document.getElementById('eventEnd').textContent = event.endStr;
            document.getElementById('eventStatus').textContent = event.extendedProps.statut;
            
            modal.show();
        }
    };
    </script>
    
    <!-- Modal pour les détails des événements -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails du rendez-vous</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="event-details">
                        <p><strong>Titre :</strong> <span id="eventTitle"></span></p>
                        <p><strong>Date début :</strong> <span id="eventStart"></span></p>
                        <p><strong>Date fin :</strong> <span id="eventEnd"></span></p>
                        <p><strong>Statut :</strong> <span id="eventStatus"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
