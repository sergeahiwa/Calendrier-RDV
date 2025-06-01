/**
 * Gestion de l'interface d'administration des paramètres
 */
(function($) {
    'use strict';

    // Initialisation au chargement du DOM
    $(document).ready(function() {
        // Gestion des onglets
        initTabs();
        
        // Gestion des modales d'aide
        initHelpModals();
        
        // Initialisation des sélecteurs de couleur
        initColorPickers();
        
        // Gestion des champs conditionnels
        initConditionalFields();
        
        // Validation des formulaires
        initFormValidation();
    });
    
    /**
     * Initialise la navigation par onglets
     */
    function initTabs() {
        // Afficher l'onglet actif
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'general';
        
        // Masquer tous les contenus d'onglets
        $('.cal-rdv-tab-content').hide();
        
        // Afficher le contenu de l'onglet actif
        $(`#cal-rdv-tab-${activeTab}`).show();
        
        // Gérer le clic sur les onglets
        $('.cal-rdv-nav-tabs a').on('click', function(e) {
            e.preventDefault();
            
            // Mettre à jour l'URL sans recharger la page
            const tabId = $(this).attr('href').split('tab=')[1];
            window.history.pushState({}, '', updateQueryStringParameter(window.location.href, 'tab', tabId));
            
            // Mettre à jour l'onglet actif
            $('.cal-rdv-nav-tabs a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Afficher le contenu de l'onglet sélectionné
            $('.cal-rdv-tab-content').hide();
            $(`#cal-rdv-tab-${tabId}`).fadeIn();
        });
    }
    
    /**
     * Initialise les modales d'aide
     */
    function initHelpModals() {
        // Gérer l'ouverture des modales d'aide
        $('.cal-rdv-help-trigger').on('click', function(e) {
            e.preventDefault();
            
            const modal = $('#cal-rdv-help-modal');
            const title = $(this).data('title') || 'Aide';
            const content = $(this).data('content') || 'Aucune information d\'aide disponible.';
            
            // Mettre à jour le contenu de la modale
            $('#cal-rdv-help-modal-title').text(title);
            $('#cal-rdv-help-modal-content').html(content);
            
            // Afficher la modale
            modal.fadeIn();
        });
        
        // Gérer la fermeture des modales
        $('.cal-rdv-modal-close, .cal-rdv-modal').on('click', function(e) {
            if ($(e.target).hasClass('cal-rdv-modal') || $(e.target).hasClass('cal-rdv-modal-close')) {
                e.preventDefault();
                $('.cal-rdv-modal').fadeOut();
            }
        });
        
        // Empêcher la fermeture lors du clic sur le contenu
        $('.cal-rdv-modal-content').on('click', function(e) {
            e.stopPropagation();
        });
    }
    
    /**
     * Initialise les sélecteurs de couleur
     */
    function initColorPickers() {
        if (typeof wp !== 'undefined' && wp.hasOwnProperty('ColorPicker')) {
            $('.cal-rdv-color-picker').wpColorPicker();
        }
    }
    
    /**
     * Initialise les champs conditionnels
     */
    function initConditionalFields() {
        // Exemple : Afficher/masquer des champs en fonction d'une case à cocher
        $('.cal-rdv-conditional-trigger').on('change', function() {
            const target = $(this).data('target');
            const condition = $(this).is(':checked');
            
            if (condition) {
                $(target).fadeIn();
            } else {
                $(target).fadeOut();
            }
        }).trigger('change');
    }
    
    /**
     * Initialise la validation des formulaires
     */
    function initFormValidation() {
        $('.cal-rdv-settings-form').on('submit', function(e) {
            let isValid = true;
            const form = $(this);
            
            // Réinitialiser les erreurs
            $('.cal-rdv-field-error').removeClass('cal-rdv-field-error');
            $('.cal-rdv-error-message').remove();
            
            // Validation des champs requis
            form.find('[required]').each(function() {
                const field = $(this);
                
                if (!field.val().trim()) {
                    isValid = false;
                    field.addClass('cal-rdv-field-error');
                    
                    // Ajouter un message d'erreur
                    const errorMsg = $('<span class="cal-rdv-error-message">Ce champ est requis.</span>');
                    field.after(errorMsg);
                }
            });
            
            // Validation des emails
            form.find('input[type="email"]').each(function() {
                const email = $(this).val().trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (email && !emailRegex.test(email)) {
                    isValid = false;
                    $(this).addClass('cal-rdv-field-error');
                    
                    // Ajouter un message d'erreur
                    const errorMsg = $('<span class="cal-rdv-error-message">Veuillez entrer une adresse email valide.</span>');
                    $(this).after(errorMsg);
                }
            });
            
            // Si le formulaire n'est pas valide, empêcher la soumission
            if (!isValid) {
                e.preventDefault();
                
                // Faire défiler jusqu'au premier champ en erreur
                $('html, body').animate({
                    scrollTop: $('.cal-rdv-field-error').first().offset().top - 100
                }, 500);
                
                // Afficher un message d'erreur général
                $('.cal-rdv-settings-form').prepend(
                    '<div class="notice notice-error"><p>Veuillez corriger les erreurs dans le formulaire.</p></div>'
                );
            }
        });
    }
    
    /**
     * Met à jour un paramètre dans l'URL
     */
    function updateQueryStringParameter(uri, key, value) {
        const re = new RegExp(`([?&])${key}=.*?(&|$)`, 'i');
        const separator = uri.indexOf('?') !== -1 ? '&' : '?';
        
        if (uri.match(re)) {
            return uri.replace(re, `$1${key}=${value}$2`);
        } else {
            return `${uri + separator + key}=${value}`;
        }
    }
    
    /**
     * Affiche une notification
     */
    function showNotice(message, type = 'success') {
        const notice = $(`
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Fermer</span>
                </button>
            </div>
        `);
        
        // Ajouter la notification en haut du conteneur
        $('.cal-rdv-wrap').prepend(notice);
        
        // Fermer la notification au clic
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        });
        
        // Fermer automatiquement après 5 secondes
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Exposer des méthodes publiques si nécessaire
    window.calRdvSettings = {
        showNotice: showNotice
    };
    
})(jQuery);
