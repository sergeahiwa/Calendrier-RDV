/**
 * Scripts d'administration pour Calendrier RDV
 */

(function($) {
    'use strict';

    // Prêt du document
    $(document).ready(function() {
        // Initialisation des sélecteurs de date et d'heure
        initDateTimePickers();
        
        // Gestion des onglets
        initTabs();
        
        // Gestion des modales
        initModals();
        
        // Gestion des confirmations de suppression
        initConfirmations();
        
        // Autres initialisations
        initOtherFeatures();
    });

    /**
     * Initialise les sélecteurs de date et d'heure
     */
    function initDateTimePickers() {
        // Sélecteur de date
        $('.cal-rdv-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            beforeShowDay: function(date) {
                // Désactiver les jours non travaillés (samedi et dimanche par défaut)
                const day = date.getDay();
                return [(day > 0 && day < 6), ''];
            },
            onSelect: function(selectedDate) {
                // Mettre à jour les champs liés si nécessaire
                const $endDate = $(this).closest('.cal-rdv-datetime-fields').find('.cal-rdv-end-date');
                if ($endDate.length && !$endDate.val()) {
                    $endDate.datepicker('setDate', selectedDate);
                }
            }
        });
        
        // Sélecteur d'heure
        $('.cal-rdv-timepicker').timepicker({
            timeFormat: 'HH:mm',
            step: 30,
            minTime: '08:00',
            maxTime: '20:00',
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });
    }
    
    /**
     * Initialise les onglets
     */
    function initTabs() {
        $('.cal-rdv-tabs').on('click', '.cal-rdv-tab', function(e) {
            e.preventDefault();
            
            const $tab = $(this);
            const $tabs = $tab.closest('.cal-rdv-tabs');
            const target = $tab.data('target');
            
            if (!$tab.hasClass('active')) {
                // Désactiver tous les onglets
                $tabs.find('.cal-rdv-tab').removeClass('active');
                $tabs.find('.cal-rdv-tab-content').removeClass('active');
                
                // Activer l'onglet sélectionné
                $tab.addClass('active');
                $(target).addClass('active');
                
                // Déclencher un événement personnalisé
                $(document).trigger('cal_rdv_tab_changed', [target]);
            }
        });
    }
    
    /**
     * Initialise les modales
     */
    function initModals() {
        // Ouvrir une modale
        $('[data-modal]').on('click', function(e) {
            e.preventDefault();
            const modalId = $(this).data('modal');
            $(`#${modalId}`).addClass('active');
        });
        
        // Fermer une modale
        $('.cal-rdv-modal-close, .cal-rdv-modal-overlay').on('click', function() {
            $(this).closest('.cal-rdv-modal').removeClass('active');
        });
        
        // Empêcher la fermeture lors du clic sur le contenu
        $('.cal-rdv-modal-content').on('click', function(e) {
            e.stopPropagation();
        });
    }
    
    /**
     * Initialise les confirmations de suppression
     */
    function initConfirmations() {
        $('.cal-rdv-confirm-delete').on('click', function(e) {
            if (!confirm(calRdvAdmin.texts.confirmDelete)) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    }
    
    /**
     * Autres fonctionnalités
     */
    function initOtherFeatures() {
        // Mise à jour en temps réel des champs liés
        $('.cal-rdv-duration, .cal-rdv-start-time').on('change', function() {
            updateEndTime();
        });
        
        // Initialisation des tooltips
        if ($.fn.tooltip) {
            $('[data-tooltip]').tooltip({
                content: function() {
                    return $(this).data('tooltip');
                },
                tooltipClass: 'cal-rdv-tooltip',
                position: {
                    my: 'center bottom-10',
                    at: 'center top',
                    using: function(position, feedback) {
                        $(this).css(position);
                        $('<div>')
                            .addClass('arrow')
                            .addClass(feedback.vertical)
                            .addClass(feedback.horizontal)
                            .appendTo(this);
                    }
                }
            });
        }
        
        // Gestion des champs conditionnels
        $('.cal-rdv-conditional-control').on('change', function() {
            const target = $(this).data('target');
            const value = $(this).val();
            const $targetElement = $(target);
            
            if ($(this).is(':checked') || ($(this).val() === value)) {
                $targetElement.show().find('input, select, textarea').prop('disabled', false);
            } else {
                $targetElement.hide().find('input, select, textarea').prop('disabled', true);
            }
        }).trigger('change');
    }
    
    /**
     * Met à jour l'heure de fin en fonction de la durée
     */
    function updateEndTime() {
        const $startTime = $('.cal-rdv-start-time');
        const $duration = $('.cal-rdv-duration');
        const $endTime = $('.cal-rdv-end-time');
        
        if ($startTime.length && $duration.length && $endTime.length) {
            const startTime = $startTime.val();
            const duration = parseInt($duration.val(), 10);
            
            if (startTime && !isNaN(duration)) {
                const [hours, minutes] = startTime.split(':').map(Number);
                const startDate = new Date();
                startDate.setHours(hours, minutes, 0, 0);
                
                const endDate = new Date(startDate.getTime() + duration * 60000);
                const endTimeString = endDate.toTimeString().substr(0, 5);
                
                $endTime.val(endTimeString);
            }
        }
    }
    
    // Exposer des méthodes publiques si nécessaire
    window.CalRdvAdmin = window.CalRdvAdmin || {};
    window.CalRdvAdmin.initDateTimePickers = initDateTimePickers;
    
})(jQuery);
