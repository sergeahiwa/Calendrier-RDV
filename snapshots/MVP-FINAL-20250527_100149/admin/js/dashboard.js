// Initialisation des graphiques du tableau de bord
document.addEventListener('DOMContentLoaded', function() {
    // Données pour le graphique des statuts
    const statutCtx = document.getElementById('chartStatut');
    if (statutCtx) {
        // Récupérer les données passées depuis PHP
        const statutData = JSON.parse(document.getElementById('statutData').textContent);
        
        new Chart(statutCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statutData),
                datasets: [{
                    data: Object.values(statutData),
                    backgroundColor: [
                        '#4e73df', // Bleu
                        '#1cc88a', // Vert
                        '#f6c23e', // Jaune
                        '#e74a3b', // Rouge
                        '#858796'  // Gris
                    ],
                    hoverBackgroundColor: [
                        '#2e59d9',
                        '#17a673',
                        '#dda20a',
                        '#be2617',
                    ],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                },
                cutout: '70%',
            }
        });
    }

    // Données pour le graphique d'activité mensuelle
    const moisCtx = document.getElementById('chartMois');
    if (moisCtx) {
        // Récupérer les données passées depuis PHP
        const moisData = JSON.parse(document.getElementById('moisData').textContent);
        
        // Formater les dates pour l'affichage (MMM YYYY)
        const moisLabels = Object.keys(moisData).map(date => {
            const [year, month] = date.split('-')
            return new Date(year, month - 1).toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' })
        });

        new Chart(moisCtx, {
            type: 'line',
            data: {
                labels: moisLabels,
                datasets: [{
                    label: "Rendez-vous",
                    lineTension: 0.3,
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    data: Object.values(moisData)
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
