// Gestion des interactions UI
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des composants
    const loadingStates = document.querySelectorAll('.loading-state');
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    
    // Initialisation des tooltips
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip, {
            placement: tooltip.dataset.tooltipPlacement || 'top',
            trigger: 'hover'
        });
    });
    
    // Initialisation des popovers
    popovers.forEach(popover => {
        new bootstrap.Popover(popover, {
            placement: popover.dataset.popoverPlacement || 'top',
            trigger: 'click'
        });
    });
    
    // Gestion des états de chargement
    function showLoading(element) {
        element.classList.add('loading');
        element.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        `;
    }
    
    function hideLoading(element) {
        element.classList.remove('loading');
        element.innerHTML = '';
    }
    
    // Gestion des animations
    function animateElement(element, animation) {
        element.style.animation = `${animation} 0.3s ease-out`;
        element.style.display = 'block';
        
        // Réinitialiser l'animation après une seconde
        setTimeout(() => {
            element.style.animation = '';
        }, 1000);
    }
    
    // Gestion des modals
    const modalButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
    modalButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-bs-target');
            const modal = bootstrap.Modal.getInstance(document.querySelector(modalId));
            
            if (modal) {
                modal.show();
            }
        });
    });
    
    // Gestion des tooltips personnalisés
    const customTooltips = document.querySelectorAll('.custom-tooltip');
    customTooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'custom-tooltip-element fade-in';
            tooltipElement.textContent = tooltipText;
            this.appendChild(tooltipElement);
            
            // Positionner le tooltip
            const rect = this.getBoundingClientRect();
            tooltipElement.style.position = 'absolute';
            tooltipElement.style.top = `${rect.top - tooltipElement.offsetHeight - 10}px`;
            tooltipElement.style.left = `${rect.left + (rect.width / 2) - (tooltipElement.offsetWidth / 2)}px`;
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipElement = this.querySelector('.custom-tooltip-element');
            if (tooltipElement) {
                tooltipElement.remove();
            }
        });
    });
    
    // Gestion des états de chargement des images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('load', function() {
            this.classList.add('fade-in');
        });
    });
    
    // Gestion des transitions de page
    window.addEventListener('beforeunload', function() {
        document.body.classList.add('page-transition');
    });
    
    window.addEventListener('load', function() {
        document.body.classList.remove('page-transition');
    });
    
    // Gestion des erreurs
    window.addEventListener('error', function(event) {
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger fade-in';
        errorAlert.innerHTML = `
            Une erreur est survenue : ${event.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(errorAlert);
    });
});
