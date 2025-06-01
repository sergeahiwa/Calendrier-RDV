/**
 * Scripts d'administration pour Calendrier RDV
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Objet principal
    const CalRdvAdmin = {
        /**
         * Initialisation
         */
        init: function() {
            this.bindEvents();
            this.initDatePickers();
            this.initTimePickers();
            this.initSelect2();
            this.initTooltips();
        },

        /**
         * Gestion des événements
         */
        bindEvents: function() {
            // Confirmation de suppression
            $(document).on('click', '.cal-rdv-delete', this.confirmDelete);
            
            // Soumission des formulaires AJAX
            $(document).on('submit', '.cal-rdv-ajax-form', this.handleAjaxForm);
            
            // Filtres de date
            $('.cal-rdv-date-filter').on('change', this.filterByDate);
            
            // Onglets
            $('.cal-rdv-tabs a').on('click', this.switchTab);
        },

        /**
         * Initialise les sélecteurs de date
         */
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.cal-rdv-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    firstDay: 1,
                    dayNamesMin: ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
                    monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
                    monthNamesShort: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']
                });
            }
        },

        /**
         * Initialise les sélecteurs d'heure
         */
        initTimePickers: function() {
            if ($.fn.timepicker) {
                $('.cal-rdv-timepicker').timepicker({
                    timeFormat: 'HH:mm',
                    step: 15,
                    scrollDefault: 'now',
                    forceRoundTime: true
                });
            }
        },

        /**
         * Initialise les sélecteurs avancés Select2
         */
        initSelect2: function() {
            if ($.fn.select2) {
                $('.cal-rdv-select2').select2({
                    width: '100%',
                    dropdownParent: $('.cal-rdv-modal')
                });
            }
        },

        /**
         * Initialise les infobulles
         */
        initTooltips: function() {
            if ($.fn.tooltip) {
                $('[data-toggle="tooltip"]').tooltip({
                    container: 'body',
                    trigger: 'hover'
                });
            }
        },

        /**
         * Affiche une boîte de dialogue de confirmation avant suppression
         */
        confirmDelete: function(e) {
            e.preventDefault();
            
            const $this = $(this);
            const message = $this.data('confirm') || cal_rdv_admin.i18n.confirm_delete;
            
            if (confirm(message)) {
                const url = $this.attr('href');
                if (url) {
                    window.location.href = url;
                } else {
                    $this.closest('form').submit();
                }
            }
            
            return false;
        },

        /**
         * Gère la soumission des formulaires AJAX
         */
        handleAjaxForm: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('[type="submit"]');
            const $spinner = $('<span class="cal-rdv-loading"></span>');
            
            // Désactiver le bouton et afficher le spinner
            $submitBtn.prop('disabled', true).prepend($spinner);
            
            // Envoyer les données
            $.ajax({
                url: cal_rdv_admin.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    // Afficher le message de succès
                    CalRdvAdmin.showNotice(response.data.message, 'success');
                    
                    // Rediriger si nécessaire
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1500);
                    }
                    
                    // Mettre à jour l'interface si nécessaire
                    if (response.data.update_ui) {
                        CalRdvAdmin.updateUI(response.data);
                    }
                } else {
                    // Afficher l'erreur
                    CalRdvAdmin.showNotice(response.data.message, 'error');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                CalRdvAdmin.showNotice(cal_rdv_admin.i18n.error, 'error');
            })
            .always(function() {
                // Réactiver le bouton et masquer le spinner
                $spinner.remove();
                $submitBtn.prop('disabled', false);
            });
        },

        /**
         * Affiche une notification
         */
        showNotice: function(message, type = 'info') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Fermer cette notification.</span>
                    </button>
                </div>
            `);
            
            // Ajouter la notification en haut de la page
            $('.wrap > h1').after($notice);
            
            // Fermer la notification au clic
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Fermer automatiquement après 5 secondes
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Filtre les éléments par date
         */
        filterByDate: function() {
            const date = $(this).val();
            const url = new URL(window.location.href);
            
            if (date) {
                url.searchParams.set('date', date);
            } else {
                url.searchParams.delete('date');
            }
            
            window.location.href = url.toString();
        },

        /**
         * Bascule entre les onglets
         */
        switchTab: function(e) {
            e.preventDefault();
            
            const $this = $(this);
            const target = $this.attr('href');
            
            if (!target || target === '#') return;
            
            // Mettre à jour l'URL sans recharger la page
            window.history.pushState(null, '', target);
            
            // Mettre à jour l'onglet actif
            $('.cal-rdv-tabs a').removeClass('nav-tab-active');
            $this.addClass('nav-tab-active');
            
            // Afficher le contenu correspondant
            $('.cal-rdv-tab-content').removeClass('active');
            $(target).addClass('active');
            
            // Déclencher un événement personnalisé
            $(document).trigger('cal_rdv_tab_changed', [target]);
        },

        /**
         * Met à jour l'interface utilisateur
         */
        updateUI: function(data) {
            if (data.update_selector && data.update_content) {
                $(data.update_selector).html(data.update_content);
            }
        }
    };

    // Initialisation au chargement du DOM
    $(document).ready(function() {
        CalRdvAdmin.init();
    });

})(jQuery);
