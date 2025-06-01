// Gestion des statistiques
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des variables
    const rdvChartCanvas = document.getElementById('rdvChart');
    const providerChartCanvas = document.getElementById('providerChart');
    const durationChartCanvas = document.getElementById('durationChart');
    
    // Configuration des options des graphiques
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Évolution des rendez-vous'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                }
            }
        }
    };
    
    // Initialisation des graphiques avec des données vides
    const rdvChart = new Chart(rdvChartCanvas, {
        type: 'line',
        data: {
            labels: [],
            datasets: []
        },
        options: chartOptions
    });

    const providerChart = new Chart(providerChartCanvas, {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Statistiques par prestataire'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    const durationChart = new Chart(durationChartCanvas, {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Durée moyenne des rendez-vous'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' min';
                        }
                    }
                }
            }
        }
    });
    
    // Fonction pour mettre à jour les graphiques
    function updateCharts() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const providerId = document.getElementById('provider_id').value;
        
        // Récupérer les nouvelles données
        fetch('ajax-get-stats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                start_date: startDate,
                end_date: endDate,
                provider_id: providerId
            })
        })
        .then(response => response.json())
        .then(data => {
            // Mettre à jour les données des graphiques
            rdvChart.data = data.rdvChart;
            providerChart.data = data.providerChart;
            durationChart.data = data.durationChart;
            
            // Mettre à jour les graphiques
            rdvChart.update();
            providerChart.update();
            durationChart.update();
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour des graphiques:', error);
        });
    }
    
    // Écouteurs d'événements
    document.getElementById('start_date').addEventListener('change', updateCharts);
    document.getElementById('end_date').addEventListener('change', updateCharts);
    document.getElementById('provider_id').addEventListener('change', updateCharts);
});
