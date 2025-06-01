// Gestion du calendrier
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des variables
    let calendar = null;
    const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
    const editEventBtn = document.getElementById('editEvent');
    const deleteEventBtn = document.getElementById('deleteEvent');
    
    // Fonction pour formater la date
    function formatDate(date) {
        return new Date(date).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Fonction pour obtenir les rendez-vous filtrés
    async function getFilteredEvents() {
        const provider = document.getElementById('provider_filter').value;
        const status = document.getElementById('status_filter').value;
        
        try {
            const response = await fetch('ajax-get-filtered-rdv.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    provider_id: provider,
                    status: status
                })
            });
            
            const data = await response.json();
            return data.events;
        } catch (error) {
            console.error('Erreur lors de la récupération des rendez-vous:', error);
            return [];
        }
    }
    
    // Fonction pour initialiser le calendrier
    function initializeCalendar() {
        calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            locale: 'fr',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: async function(fetchInfo, successCallback, failureCallback) {
                try {
                    const events = await getFilteredEvents();
                    successCallback(events);
                } catch (error) {
                    failureCallback(error);
                }
            },
            eventClick: function(info) {
                showEventDetails(info.event);
            },
            eventDidMount: function(info) {
                const status = info.event.extendedProps.status;
                info.el.style.backgroundColor = getStatusColor(status);
            }
        });
        
        calendar.render();
    }
    
    // Fonction pour obtenir la couleur du statut
    function getStatusColor(status) {
        switch (status) {
            case 'confirmé':
                return '#28a745';
            case 'en_attente':
                return '#ffc107';
            case 'annulé':
                return '#dc3545';
            default:
                return '#6c757d';
        }
    }
    
    // Fonction pour afficher les détails d'un rendez-vous
    function showEventDetails(event) {
        const eventDetails = event.extendedProps;
        
        document.getElementById('event_nom').textContent = eventDetails.nom;
        document.getElementById('event_telephone').textContent = eventDetails.telephone;
        document.getElementById('event_email').textContent = eventDetails.email;
        document.getElementById('event_prestation').textContent = eventDetails.prestation;
        document.getElementById('event_prestataire').textContent = eventDetails.prestataire_nom;
        document.getElementById('event_datetime').textContent = formatDate(event.start);
        
        const statusBadge = document.getElementById('event_status');
        statusBadge.textContent = eventDetails.status;
        statusBadge.className = `badge bg-${getStatusClass(eventDetails.status)}`;
        
        editEventBtn.onclick = () => editEvent(event);
        deleteEventBtn.onclick = () => deleteEvent(event);
        
        eventModal.show();
    }
    
    // Fonction pour obtenir la classe du statut
    function getStatusClass(status) {
        switch (status) {
            case 'confirmé':
                return 'success';
            case 'en_attente':
                return 'warning';
            case 'annulé':
                return 'danger';
            default:
                return 'secondary';
        }
    }
    
    // Fonction pour modifier un rendez-vous
    async function editEvent(event) {
        try {
            const response = await fetch('ajax-edit-rdv.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: event.id,
                    // Ajouter ici les autres champs à modifier
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                eventModal.hide();
                fetch('set-flash.php?type=success&msg=Le+rendez-vous+a+%C3%A9t%C3%A9+modifi%C3%A9+avec+succ%C3%A8s.', {method:'GET'})
                  .then(() => window.location.href = 'calendrier.php');
            }
        } catch (error) {
            console.error('Erreur lors de la modification du rendez-vous:', error);
        }
    }
    
    // Fonction pour supprimer un rendez-vous
    async function deleteEvent(event) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?')) {
            return;
        }
        
        try {
            const response = await fetch('ajax-delete-rdv.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: event.id
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                eventModal.hide();
                fetch('set-flash.php?type=success&msg=Le+rendez-vous+a+%C3%A9t%C3%A9+supprim%C3%A9+avec+succ%C3%A8s.', {method:'GET'})
                  .then(() => window.location.href = 'calendrier.php');
            }
        } catch (error) {
            console.error('Erreur lors de la suppression du rendez-vous:', error);
        }
    }
    
    // Écouteurs d'événements
    document.getElementById('applyFilters').addEventListener('click', function() {
        calendar.refetchEvents();
    });
    
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('provider_filter').value = '';
        document.getElementById('status_filter').value = '';
        calendar.refetchEvents();
    });
    
    // Initialisation du calendrier
    initializeCalendar();
});
