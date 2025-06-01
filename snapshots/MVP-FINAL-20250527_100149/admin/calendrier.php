<?php
// ================================
// Fichier : admin/calendrier.php
// Rôle    : Affichage du calendrier des réservations
// Auteur  : SAN Digital Solutions
// ================================

// Inclusion du fichier d'authentification
require_once 'auth.php';

// Inclusion du fichier de configuration
require_once __DIR__ . '/../includes/config.php';

// Définir le titre de la page
$page_title = "Calendrier des rendez-vous";

// Récupération des prestataires pour le filtre
try {
    $stmt = $pdo->query("SELECT id, nom FROM prestataires ORDER BY nom");
    $prestataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des prestataires: " . $e->getMessage();
    error_log($error_message);
    $prestataires = [];
}
?>

<?php
require_once __DIR__ . '/../includes/flash.php';
display_flash();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAN Digital - <?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/admin-style.css">
    <!-- Ajout de FullCalendar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/fr.js"></script>
    <!-- Ajout des icônes Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Styles personnalisés pour le calendrier -->
    <style>
        .calendar-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        .fc-event {
            cursor: pointer;
        }
        
        .fc .fc-toolbar-title {
            font-size: 1.3rem;
            color: var(--primary);
        }
        
        .fc-event-time, .fc-event-title {
            padding: 0 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .fc-day-today {
            background-color: rgba(52, 111, 179, 0.1) !important;
        }
        
        /* Modal pour les détails du RDV */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            width: 80%;
            max-width: 600px;
            animation: modal-appear 0.3s ease;
        }
        
        @keyframes modal-appear {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
            transition: color 0.2s;
        }
        
        .close-modal:hover {
            color: var(--danger);
        }
        
        .event-details {
            margin-top: 15px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
            padding-bottom: 10px;
        }
        
        .detail-label {
            flex: 0 0 120px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .detail-value {
            flex: 1;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        
        .legend {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
        }
        
        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin-header.php'; ?>
        
        <main class="admin-content">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="calendar-container">
                <div class="filter-bar">
                    <label for="prestataire-filter">Filtrer par prestataire:</label>
                    <select id="prestataire-filter" class="form-control">
                        <option value="">Tous les prestataires</option>
                        <?php foreach ($prestataires as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button id="refresh-calendar" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                    
                    <a href="liste-rdv.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Vue liste
                    </a>
                </div>
                
                <div id="calendar"></div>
                
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #346fb3;"></div>
                        <span>En attente</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #8fad0c;"></div>
                        <span>Confirmé</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #cf4444;"></div>
                        <span>Annulé</span>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Modal pour les détails du rendez-vous -->
        <div id="rdv-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2 id="modal-title">Détails du rendez-vous</h2>
                
                <div class="event-details">
                    <div class="detail-row">
                        <div class="detail-label">Client:</div>
                        <div class="detail-value" id="event-client"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value" id="event-email"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Téléphone:</div>
                        <div class="detail-value" id="event-phone"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Prestation:</div>
                        <div class="detail-value" id="event-service"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Date:</div>
                        <div class="detail-value" id="event-date"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Prestataire:</div>
                        <div class="detail-value" id="event-prestataire"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Statut:</div>
                        <div class="detail-value" id="event-status"></div>
                    </div>
                    <div class="detail-row" id="comment-row">
                        <div class="detail-label">Commentaire:</div>
                        <div class="detail-value" id="event-comment"></div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button class="btn btn-primary" id="btn-edit">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    <button class="btn btn-success" id="btn-confirm">
                        <i class="fas fa-check-circle"></i> Confirmer
                    </button>
                    <button class="btn btn-danger" id="btn-cancel">
                        <i class="fas fa-times-circle"></i> Annuler
                    </button>
                </div>
            </div>
        </div>
        
        <?php include 'includes/admin-footer.php'; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation du calendrier
        var calendarEl = document.getElementById('calendar');
        var modal = document.getElementById('rdv-modal');
        var closeModal = document.querySelector('.close-modal');
        var selectedEvent = null;
        
        // Configuration du calendrier
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: "Aujourd'hui",
                month: 'Mois',
                week: 'Semaine',
                day: 'Jour',
                list: 'Liste'
            },
            height: 'auto',
            navLinks: true,
            selectable: true,
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5], // Lundi au vendredi
                startTime: '09:00',
                endTime: '18:00',
            },
            // Chargement des événements depuis l'API
            events: function(info, successCallback, failureCallback) {
                // Récupérer le filtre de prestataire
                const prestataireFilter = document.getElementById('prestataire-filter').value;
                
                // Construction de l'URL
                let url = '../fetch-events.php?start=' + info.startStr + '&end=' + info.endStr;
                if (prestataireFilter) {
                    url += '&prestataire=' + prestataireFilter;
                }
                
                // Appel AJAX pour récupérer les événements
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur réseau ou serveur');
                        }
                        return response.json();
                    })
                    .then(data => {
                        successCallback(data);
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des événements:', error);
                        failureCallback(error);
                    });
            },
            // Gestion des clics sur les événements
            eventClick: function(info) {
                // Stocker l'événement sélectionné
                selectedEvent = info.event;
                
                // Remplir les informations dans la modal
                document.getElementById('modal-title').textContent = 'Rendez-vous: ' + selectedEvent.title;
                document.getElementById('event-client').textContent = selectedEvent.title.split(' — ')[0];
                document.getElementById('event-service').textContent = selectedEvent.title.split(' — ')[1] || '';
                
                // Formater la date
                const eventDate = new Date(selectedEvent.start);
                const formattedDate = eventDate.toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('event-date').textContent = formattedDate;
                
                // Informations supplémentaires des extendedProps
                document.getElementById('event-prestataire').textContent = selectedEvent.extendedProps.prestataire || 'Non assigné';
                document.getElementById('event-status').textContent = selectedEvent.extendedProps.statut || 'En attente';
                
                // Email et téléphone (à récupérer via une requête AJAX)
                fetchEventDetails(selectedEvent.id);
                
                // Afficher ou masquer les boutons selon le statut
                const btnConfirm = document.getElementById('btn-confirm');
                const btnCancel = document.getElementById('btn-cancel');
                
                if (selectedEvent.extendedProps.statut === 'confirmé') {
                    btnConfirm.style.display = 'none';
                } else {
                    btnConfirm.style.display = 'inline-block';
                }
                
                if (selectedEvent.extendedProps.statut === 'annulé') {
                    btnCancel.style.display = 'none';
                } else {
                    btnCancel.style.display = 'inline-block';
                }
                
                // Commentaire
                const commentRow = document.getElementById('comment-row');
                if (selectedEvent.extendedProps.commentaire) {
                    document.getElementById('event-comment').textContent = selectedEvent.extendedProps.commentaire;
                    commentRow.style.display = 'flex';
                } else {
                    commentRow.style.display = 'none';
                }
                
                // Afficher la modal
                modal.style.display = 'block';
            }
        });
        
        // Initialiser le calendrier
        calendar.render();
        
        // Fonction pour récupérer les détails complets d'un rendez-vous
        function fetchEventDetails(eventId) {
            // Remplacer par un appel AJAX pour obtenir les détails complets
            // Pour l'instant, on simule avec des valeurs vides
            document.getElementById('event-email').textContent = "Chargement...";
            document.getElementById('event-phone').textContent = "Chargement...";
            
            // Appel AJAX (à implémenter)
            fetch('ajax-get-rdv-details.php?id=' + eventId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('event-email').textContent = data.email || 'Non disponible';
                    document.getElementById('event-phone').textContent = data.telephone || 'Non disponible';
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des détails:', error);
                    document.getElementById('event-email').textContent = 'Non disponible';
                    document.getElementById('event-phone').textContent = 'Non disponible';
                });
        }
        
        // Fermer la modal
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Fermer la modal quand on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Actions des boutons de la modal
        document.getElementById('btn-edit').addEventListener('click', function() {
            if (selectedEvent) {
                window.location.href = 'modifier-rdv.php?id=' + selectedEvent.id;
            }
        });
        
        document.getElementById('btn-confirm').addEventListener('click', function() {
            if (selectedEvent) {
                if (confirm('Voulez-vous confirmer ce rendez-vous ?')) {
                    updateEventStatus(selectedEvent.id, 'confirmer');
                }
            }
        });
        
        document.getElementById('btn-cancel').addEventListener('click', function() {
            if (selectedEvent) {
                if (confirm('Voulez-vous annuler ce rendez-vous ?')) {
                    updateEventStatus(selectedEvent.id, 'annuler');
                }
            }
        });
        
        // Fonction pour mettre à jour le statut d'un rendez-vous
        function updateEventStatus(eventId, action) {
            const formData = new FormData();
            formData.append('rdv_id', eventId);
            formData.append('action', action);
            
            fetch('ajax-update-rdv-status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Le statut a été mis à jour avec succès.');
                    modal.style.display = 'none';
                    calendar.refetchEvents();
                } else {
                    alert('Erreur: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur lors de la mise à jour du statut:', error);
                alert('Une erreur s\'est produite lors de la mise à jour du statut.');
            });
        }
        
        // Actualisation du calendrier quand on change le filtre
        document.getElementById('prestataire-filter').addEventListener('change', function() {
            calendar.refetchEvents();
        });
        
        // Bouton d'actualisation
        document.getElementById('refresh-calendar').addEventListener('click', function() {
            calendar.refetchEvents();
        });
    });
    </script>
</body>
</html>
