// Gestion des rapports
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des variables
    const dateRange = document.querySelector('.date-range');
    const providerSelect = document.getElementById('provider_id');
    const statusSelect = document.getElementById('status');
    const exportButtons = document.querySelectorAll('.export-btn');
    
    // Fonction pour mettre à jour les dates
    function updateDateRange() {
        const today = new Date();
        const thisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        document.getElementById('start_date').value = thisMonth.toISOString().split('T')[0];
        document.getElementById('end_date').value = nextMonth.toISOString().split('T')[0];
    }
    
    // Fonction pour valider les dates
    function validateDates() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('La date de début ne peut pas être postérieure à la date de fin');
            return false;
        }
        return true;
    }
    
    // Fonction pour gérer les exports
    function handleExport(event) {
        event.preventDefault();
        
        if (!validateDates()) {
            return;
        }
        
        const format = this.dataset.format;
        const type = this.dataset.type;
        const providerId = providerSelect.value;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        window.location.href = `export.php?type=${type}&format=${format}&id=${providerId}&start_date=${startDate}&end_date=${endDate}`;
    }
    
    // Écouteurs d'événements
    document.getElementById('start_date').addEventListener('change', validateDates);
    document.getElementById('end_date').addEventListener('change', validateDates);
    
    exportButtons.forEach(button => {
        button.addEventListener('click', handleExport);
    });
    
    // Initialisation
    updateDateRange();
});
