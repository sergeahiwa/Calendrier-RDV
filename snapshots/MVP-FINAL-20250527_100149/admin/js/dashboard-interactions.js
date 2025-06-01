// Initialisation des tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialisation des popovers Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Gestion des modals de confirmation de suppression
    const deleteButtons = document.querySelectorAll('[data-bs-target^="#modalSupprimer"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-bs-target');
            const modal = bootstrap.Modal.getInstance(document.querySelector(modalId));
            
            // Mettre à jour le titre du modal avec le nom du client
            const rdvId = modalId.replace('#modalSupprimer', '');
            const rdvRow = document.querySelector(`tr[data-rdv-id="${rdvId}"]`);
            const clientName = rdvRow.querySelector('td:nth-child(2)').textContent;
            
            const modalTitle = modal._element.querySelector('.modal-title');
            modalTitle.textContent = `Supprimer le rendez-vous de ${clientName}`;
        });
    });

    // Gestion du changement de statut via AJAX
    const statusButtons = document.querySelectorAll('.change-status');
    statusButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const rdvId = this.dataset.rdvId;
            const newStatus = this.dataset.newStatus;
            
            try {
                const response = await fetch('ajax-update-rdv-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: rdvId,
                        status: newStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Mettre à jour l'interface
                    const statusCell = this.closest('tr').querySelector('.status-cell');
                    statusCell.innerHTML = `
                        <span class="badge bg-${result.statusClass}">${result.statusText}</span>
                    `;

                    // Mettre à jour les boutons de statut
                    const statusButtons = this.closest('tr').querySelectorAll('.change-status');
                    statusButtons.forEach(btn => {
                        btn.classList.remove('active');
                        if (btn.dataset.newStatus === newStatus) {
                            btn.classList.add('active');
                        }
                    });

                    // Mettre à jour les graphiques
                    updateCharts();
                } else {
                    alert('Erreur lors de la mise à jour du statut');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            }
        });
    });

    // Fonction pour mettre à jour les graphiques
    function updateCharts() {
        // Récupérer les nouvelles données
        fetch('ajax-get-stats.php')
            .then(response => response.json())
            .then(data => {
                // Mettre à jour les graphiques
                if (chartStatut) {
                    chartStatut.data.datasets[0].data = data.par_statut;
                    chartStatut.update();
                }
                
                if (chartMois) {
                    chartMois.data.datasets[0].data = data.par_mois;
                    chartMois.update();
                }
            })
            .catch(error => console.error('Erreur:', error));
    }
});
