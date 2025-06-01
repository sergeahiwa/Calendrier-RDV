// Fonctionnalités spécifiques à Divi
jQuery(document).ready(function($) {
    // Initialisation du module Divi
    if (typeof ET_Builder_Module !== 'undefined') {
        // Configuration des options de module
        et_builder_ready(function() {
            // Personnalisation des options de module
            $('.et_pb_module.et_pb_calendrier_rdv').each(function() {
                const module = $(this);
                
                // Configuration des options de style
                const styleOptions = {
                    'service_columns': module.data('service-columns') || 3,
                    'service_display': module.data('service-display') || 'grid',
                    'provider_display': module.data('provider-display') || 'dropdown',
                    'booking_mode': module.data('booking-mode') || 'calendar'
                };
                
                // Application des styles personnalisés
                applyCustomStyles(module, styleOptions);
            });
        });
    }

    // Fonction pour appliquer les styles personnalisés
    function applyCustomStyles(module, options) {
        const content = module.find('.et_pb_calendrier_rdv_content');
        
        // Configuration du nombre de colonnes pour les services
        content.css('--service-columns', options.service_columns);
        
        // Configuration de l'affichage des services
        if (options.service_display === 'list') {
            content.find('.et-pb-calendrier-rdv-services').css('grid-template-columns', '1fr');
        }
        
        // Configuration de l'affichage des prestataires
        if (options.provider_display === 'grid') {
            content.find('.et-pb-calendrier-rdv-providers').addClass('et-pb-calendrier-rdv-providers-grid');
        } else if (options.provider_display === 'list') {
            content.find('.et-pb-calendrier-rdv-providers').addClass('et-pb-calendrier-rdv-providers-list');
        }
        
        // Configuration du mode de réservation
        if (options.booking_mode === 'list') {
            content.find('.et-pb-calendrier-rdv-calendar').addClass('et-pb-calendrier-rdv-calendar-list');
        }
    }

    // Gestion des événements Divi Builder
    $(document).on('et_builder_ready', function() {
        // Initialisation des modules existants
        $('.et_pb_module.et_pb_calendrier_rdv').each(function() {
            const module = $(this);
            
            // Configuration des événements
            setupModuleEvents(module);
            
            // Application des styles
            applyCustomStyles(module, {
                'service_columns': module.data('service-columns') || 3,
                'service_display': module.data('service-display') || 'grid',
                'provider_display': module.data('provider-display') || 'dropdown',
                'booking_mode': module.data('booking-mode') || 'calendar'
            });
        });
    });

    // Fonction pour configurer les événements du module
    function setupModuleEvents(module) {
        const content = module.find('.et_pb_calendrier_rdv_content');
        
        // Événements pour les services
        content.on('click', '.et-pb-calendrier-rdv-service-item', function() {
            const serviceId = $(this).data('service-id');
            updateSelectedService(serviceId);
        });
        
        // Événements pour les prestataires
        content.on('change', '.et-pb-calendrier-rdv-provider-select', function() {
            const providerId = $(this).val();
            updateSelectedProvider(providerId);
        });
        
        // Événements pour le formulaire
        content.on('submit', '.et-pb-calendrier-rdv-form', function(e) {
            e.preventDefault();
            handleSubmitForm($(this));
        });
    }

    // Fonction pour mettre à jour le service sélectionné
    function updateSelectedService(serviceId) {
        // Logique pour mettre à jour le service sélectionné
    }

    // Fonction pour mettre à jour le prestataire sélectionné
    function updateSelectedProvider(providerId) {
        // Logique pour mettre à jour le prestataire sélectionné
    }

    // Fonction pour soumettre le formulaire
    function handleSubmitForm(form) {
        const formData = form.serialize();
        
        $.ajax({
            url: calendrierRdvConfig.ajax_url,
            type: 'POST',
            data: {
                action: 'calendrier_rdv_create_appointment',
                nonce: calendrierRdvConfig.nonce,
                data: formData
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);
                } else {
                    showErrorMessage(response.data.message);
                }
            },
            error: function() {
                showErrorMessage(calendrierRdvConfig.i18n.error);
            }
        });
    }

    // Fonction pour afficher un message de succès
    function showSuccessMessage(message) {
        const content = $('.et_pb_calendrier_rdv_content');
        content.find('.et-pb-calendrier-rdv-message').remove();
        
        const successMessage = $('<div class="et-pb-calendrier-rdv-message et-pb-calendrier-rdv-message-success">' + message + '</div>');
        content.prepend(successMessage);
        
        setTimeout(function() {
            successMessage.fadeOut(500, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Fonction pour afficher un message d'erreur
    function showErrorMessage(message) {
        const content = $('.et_pb_calendrier_rdv_content');
        content.find('.et-pb-calendrier-rdv-message').remove();
        
        const errorMessage = $('<div class="et-pb-calendrier-rdv-message et-pb-calendrier-rdv-message-error">' + message + '</div>');
        content.prepend(errorMessage);
        
        setTimeout(function() {
            errorMessage.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
});
