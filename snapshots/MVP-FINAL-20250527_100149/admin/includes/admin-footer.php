        </div> <!-- Fin de la colonne principale -->
    </div> <!-- Fin de la rangée -->
</main> <!-- Fin du contenu principal -->

<footer class="footer mt-auto py-3 bg-light">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">&copy; <?php echo date('Y'); ?> SAN Digital Solutions - Tous droits réservés</span>
            <span class="text-muted">Version 1.1.0</span>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="js/dashboard.js"></script>

<!-- Scripts pour les graphiques -->
<script>
// Données pour les graphiques
const statutData = <?php echo json_encode($stats['par_statut'] ?? []); ?>;
const moisData = <?php echo json_encode($stats['par_mois'] ?? []); ?>;

// Script pour le menu déroulant mobile
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialisation des popovers Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
</script>
