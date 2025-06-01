// Gestion des notifications
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des variables
    let notificationInterval = null;
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    // Fonction pour charger les notifications
    async function loadNotifications() {
        try {
            const response = await fetch('ajax-get-notifications.php');
            const data = await response.json();
            
            if (data.success) {
                updateNotificationBadge(data.count);
                updateNotificationDropdown(data.notifications);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
        }
    }

    // Fonction pour mettre à jour le badge des notifications
    function updateNotificationBadge(count) {
        if (count > 0) {
            notificationBadge.textContent = count;
            notificationBadge.style.display = 'block';
        } else {
            notificationBadge.style.display = 'none';
        }
    }

    // Fonction pour mettre à jour le dropdown des notifications
    function updateNotificationDropdown(notifications) {
        const dropdownMenu = notificationDropdown.querySelector('.dropdown-menu');
        dropdownMenu.innerHTML = '';

        if (notifications.length === 0) {
            dropdownMenu.innerHTML = `
                <div class="dropdown-item text-center py-3">
                    <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                    <p class="mb-0 text-muted">Aucune notification</p>
                </div>
            `;
            return;
        }

        notifications.forEach(notification => {
            const item = document.createElement('div');
            item.className = 'dropdown-item d-flex align-items-center';
            item.innerHTML = `
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">${notification.message}</span>
                        <small class="text-muted">${formatDateTime(notification.date_creation)}</small>
                    </div>
                    ${notification.rdv_nom ? `
                        <small class="text-muted">
                            Rendez-vous : ${notification.rdv_nom} - ${formatDateTime(notification.date_rdv, notification.heure_rdv)}
                        </small>
                    ` : ''}
                    ${notification.prestataire_nom ? `
                        <small class="text-muted">
                            Prestataire : ${notification.prestataire_nom}
                        </small>
                    ` : ''}
                </div>
                <button type="button" 
                        class="btn btn-link btn-sm ms-2"
                        onclick="markAsRead(${notification.id})">
                    <i class="fas fa-check"></i>
                </button>
            `;
            dropdownMenu.appendChild(item);
        });
    }

    // Fonction pour formater la date et l'heure
    function formatDateTime(date, time = null) {
        const dateTime = new Date(date);
        if (time) {
            dateTime.setHours(time.split(':')[0]);
            dateTime.setMinutes(time.split(':')[1]);
        }
        return dateTime.toLocaleString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Fonction pour marquer une notification comme lue
    async function markAsRead(notificationId) {
        try {
            const response = await fetch('ajax-mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: notificationId })
            });

            const data = await response.json();

            if (data.success) {
                loadNotifications();
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour de la notification:', error);
        }
    }

    // Fonction pour démarrer la vérification des notifications
    function startNotificationCheck() {
        clearInterval(notificationInterval);
        notificationInterval = setInterval(loadNotifications, 30000); // Toutes les 30 secondes
        loadNotifications(); // Charger immédiatement
    }

    // Écouteurs d'événements
    document.addEventListener('DOMContentLoaded', startNotificationCheck);
    
    // Marquer toutes les notifications comme lues
    document.getElementById('markAllRead').addEventListener('click', async function() {
        try {
            const response = await fetch('ajax-mark-all-notifications-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                loadNotifications();
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour des notifications:', error);
        }
    });
});
