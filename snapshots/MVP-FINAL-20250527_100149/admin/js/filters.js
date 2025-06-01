// Gestion des filtres et de la recherche
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des filtres
    const dateFilter = document.getElementById('dateFilter');
    const statusFilter = document.getElementById('statusFilter');
    const providerFilter = document.getElementById('providerFilter');
    const searchInput = document.getElementById('searchInput');
    
    // Variables pour le stockage des filtres
    let currentFilters = {
        date: '',
        status: '',
        provider: '',
        search: ''
    };
    
    // Fonction pour appliquer les filtres
    async function applyFilters() {
        try {
            const response = await fetch('ajax-get-filtered-rdv.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    date: dateFilter.value,
                    status: statusFilter.value,
                    provider: providerFilter.value,
                    search: searchInput.value
                })
            });

            const data = await response.json();

            if (data.success) {
                updateRdvTable(data.rdv);
                updateStats(data.stats);
            } else {
                showError('Erreur lors de la récupération des données');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showError('Une erreur est survenue');
        }
    }

    // Fonction pour mettre à jour le tableau des rendez-vous
    function updateRdvTable(rdvList) {
        const tbody = document.querySelector('#rdvTable tbody');
        tbody.innerHTML = '';

        if (rdvList.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                        <p class="mb-0 text-muted">Aucun rendez-vous trouvé</p>
                    </td>
                </tr>
            `;
            return;
        }

        rdvList.forEach(rdv => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    ${formatDateTime(rdv.date_rdv, rdv.heure_rdv)}
                </td>
                <td>${rdv.nom}</td>
                <td>${rdv.prestation}</td>
                <td>${rdv.nom_prestataire ?? 'Non attribué'}</td>
                <td>
                    <span class="badge bg-${getStatusClass(rdv.statut)}">
                        ${ucfirst(rdv.statut)}
                    </span>
                </td>
                <td class="text-end">
                    <div class="btn-group">
                        <a href="voir-rdv.php?id=${rdv.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="modifier-rdv.php?id=${rdv.id}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalSupprimer${rdv.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Fonction pour mettre à jour les statistiques
    function updateStats(stats) {
        document.getElementById('rdvAujourdhui').textContent = stats.rdv_aujourdhui;
        document.getElementById('rdvAVenir').textContent = stats.rdv_a_venir;
        document.getElementById('totalRdv').textContent = stats.total_rdv;
        document.getElementById('totalPrestataires').textContent = stats.total_prestataires;
    }

    // Fonction pour formater la date et l'heure
    function formatDateTime(date, time) {
        const dateTime = new DateTime(date + ' ' + time);
        return dateTime.format('d/m/Y H:i');
    }

    // Fonction pour afficher les erreurs
    function showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.alerts-container').appendChild(alert);
    }

    // Écouteurs d'événements
    dateFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    providerFilter.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', debounce(applyFilters, 300));

    // Chargement initial des données
    applyFilters();

    // Fonction pour débouncer les appels
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
