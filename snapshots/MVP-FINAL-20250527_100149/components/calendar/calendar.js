// Composant Calendrier Réutilisable
// Nécessite FullCalendar et Bootstrap déjà inclus

function initCalendar() {
    const container = document.getElementById('calendar-container');
    if (!container) return;
    const config = JSON.parse(container.dataset.config);

    var calendarEl = container;
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: config.defaultView || 'dayGridMonth',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(config.ajaxEndpoints.fetch +
                `&start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                .then(res => res.json())
                .then(events => successCallback(events))
                .catch(failureCallback);
        },
        selectable: true,
        select: function(info) {
            var title = prompt('Titre du rendez-vous :');
            if (title) {
                fetch('/components/calendar/calendar.endpoints.php?action=edit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        title: title,
                        start: info.startStr,
                        end: info.endStr,
                        statut: 'confirmé'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        calendar.refetchEvents();
                    } else {
                        alert('Erreur lors de l\'ajout');
                    }
                });
            }
            calendar.unselect();
        },
        eventClick: function(info) {
            if (window.calendarCustomHandlers && typeof window.calendarCustomHandlers.eventClick === 'function') {
                window.calendarCustomHandlers.eventClick(info);
            } else if (confirm('Supprimer ce rendez-vous ?')) {
                fetch('/components/calendar/calendar.endpoints.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(info.event.id)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        calendar.refetchEvents();
                    } else {
                        alert('Erreur lors de la suppression');
                    }
                });
            } else {
                alert(config.labels.eventDetails + '\n' + info.event.title);
            }
        },
        eventClassNames: function(arg) {
            return ['fc-event-' + (arg.event.extendedProps.statut || 'autre')];
        }
    });
    calendar.render();
}

// Initialisation automatique du calendrier
document.addEventListener('DOMContentLoaded', initCalendar);
