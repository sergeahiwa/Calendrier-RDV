document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('admin-calendar-container');
    var modal = document.getElementById('appointment-details-modal');
    var closeModalButtonInHeader = modal ? modal.querySelector('.modal-header .close') : null;
    var closeModalButtonInFooter = modal ? modal.querySelector('.modal-footer button[data-dismiss="modal"]') : null;

    function hideModal() {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    if (closeModalButtonInHeader) {
        closeModalButtonInHeader.addEventListener('click', hideModal);
    }
    if (closeModalButtonInFooter) {
        closeModalButtonInFooter.addEventListener('click', hideModal);
    }

    // Fermer la modale si on clique en dehors de son contenu principal
    window.addEventListener('click', function(event) {
        if (event.target == modal) { // Si le clic est sur le fond de la modale
            hideModal();
        }
    });

    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: calendrierRdvDashboard.calendar_locale || 'fr', // Utiliser la locale passée ou 'fr' par défaut
            initialView: 'dayGridMonth', // Vue initiale
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' // Options de vues
            },
            buttonText: {
                today:    'Aujourd\'hui',
                month:    'Mois',
                week:     'Semaine',
                day:      'Jour',
                list:     'Liste'
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                console.log('Fetching events from:', calendrierRdvDashboard.api_base_url + 'admin/appointments?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr);
                fetch(calendrierRdvDashboard.api_base_url + 'admin/appointments?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr, { 
                    method: 'GET', 
                    headers: { 
                        'X-WP-Nonce': calendrierRdvDashboard.nonce 
                    } 
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        successCallback(data.data);
                    } else {
                        console.error('Error fetching appointments:', data.message || 'Unknown error');
                        failureCallback(new Error(data.message || 'Erreur lors de la récupération des rendez-vous'));
                    }
                })
                .catch(error => {
                    console.error('Network error fetching appointments:', error);
                    failureCallback(error);
                });
            },
            eventDidMount: function(info) {
                // Optionnel: ajouter une tooltip ou des détails supplémentaires à l'événement
            },
            eventClick: function(info) {
                if (!modal) {
                    console.error('Modal element not found!');
                    return;
                }

                // Peupler les champs de la modale
                document.getElementById('modal-appointment-title').textContent = info.event.title || 'N/A';
                
                const dateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false };
                document.getElementById('modal-appointment-start').textContent = info.event.start ? info.event.start.toLocaleString('fr-FR', dateTimeFormatOptions) : 'N/A';
                document.getElementById('modal-appointment-end').textContent = info.event.end ? info.event.end.toLocaleString('fr-FR', dateTimeFormatOptions) : 'N/A';
                
                const props = info.event.extendedProps;
                document.getElementById('modal-appointment-customer').textContent = props.customerName || 'N/A';
                document.getElementById('modal-appointment-service').textContent = props.serviceName || 'N/A';
                document.getElementById('modal-appointment-provider').textContent = props.providerName || 'N/A';
                document.getElementById('modal-appointment-status').textContent = props.status || 'N/A';

                modal.style.display = 'block'; // Afficher la modale
            }
        });
        calendar.render();
    } else {
        console.error('Calendar container #admin-calendar-container not found.');
    }

    if (!modal) {
        console.error('Modal #appointment-details-modal not found. Event click details will not be displayed.');
    }
});
