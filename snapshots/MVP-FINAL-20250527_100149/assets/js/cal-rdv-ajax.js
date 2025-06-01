/**
 * Gestion des appels AJAX pour le plugin Calendrier RDV
 * 
 * Ce fichier gère les appels AJAX sécurisés vers le serveur
 * en utilisant les nonces WordPress pour la sécurité.
 */

(function($) {
    'use strict';

    // Objet principal pour les appels AJAX
    const CalRdvAjax = {
        /**
         * Initialise les gestionnaires d'événements
         */
        init: function() {
            // Exemple d'initialisation d'événements
            // $(document).on('click', '.cal-rdv-check-availability', this.checkAvailability);
            // $(document).on('submit', '.cal-rdv-booking-form', this.submitBookingForm);
        },

        /**
         * Effectue un appel AJAX sécurisé
         * 
         * @param {string} action Action AJAX à appeler
         * @param {Object} data Données à envoyer
         * @param {Function} success Callback en cas de succès
         * @param {Function} error Callback en cas d'erreur
         */
        call: function(action, data, success, error) {
            // Récupération du nonce depuis les données localisées
            const ajaxData = window['ajax_cal-rdv-ajax'] || {};
            
            // Préparation des données de la requête
            const requestData = {
                action: action,
                _wpnonce: ajaxData.nonce,
                ...data
            };

            // Configuration de la requête AJAX
            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (typeof success === 'function') {
                            success(response.data);
                        }
                    } else {
                        CalRdvAjax.handleError(response, error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', status, error);
                    if (typeof error === 'function') {
                        error({
                            message: ajaxData.i18n?.error || 'Une erreur est survenue',
                            code: 'ajax_error',
                            xhr: xhr
                        });
                    }
                }
            });
        },

        /**
         * Gère les erreurs de manière centralisée
         * 
         * @param {Object} response Réponse d'erreur du serveur
         * @param {Function} customError Callback d'erreur personnalisé
         */
        handleError: function(response, customError) {
            console.error('Erreur:', response.message || 'Une erreur inconnue est survenue');
            
            // Affichage d'une alerte à l'utilisateur
            if (response.message) {
                alert(response.message);
            }
            
            // Appel du callback d'erreur personnalisé s'il est fourni
            if (typeof customError === 'function') {
                customError(response);
            }
        },

        // ====================================
        // MÉTHODES D'EXEMPLE POUR LES APPELS AJAX
        // ====================================


        /**
         * Vérifie la disponibilité d'un créneau
         * 
         * @param {Object} params Paramètres de la requête
         * @param {Function} success Callback en cas de succès
         * @param {Function} error Callback en cas d'erreur
         */
        checkAvailability: function(params, success, error) {
            CalRdvAjax.call('cal_rdv_check_availability', params, success, error);
        },

        /**
         * Récupère la liste des rendez-vous
         * 
         * @param {Object} params Paramètres de la requête
         * @param {Function} success Callback en cas de succès
         * @param {Function} error Callback en cas d'erreur
         */
        getAppointments: function(params, success, error) {
            CalRdvAjax.call('cal_rdv_get_appointments', params, success, error);
        },

        /**
         * Crée un nouveau rendez-vous
         * 
         * @param {Object} data Données du rendez-vous
         * @param {Function} success Callback en cas de succès
         * @param {Function} error Callback en cas d'erreur
         */
        createAppointment: function(data, success, error) {
            CalRdvAjax.call('cal_rdv_create_appointment', data, success, error);
        },

        /**
         * Soumet un formulaire de réservation
         * 
         * @param {jQuery} $form Élément formulaire jQuery
         * @param {Function} success Callback en cas de succès
         * @param {Function} error Callback en cas d'erreur
         */
        submitBookingForm: function($form, success, error) {
            // Récupération des données du formulaire
            const formData = $form.serializeArray();
            const data = {};
            
            // Conversion du tableau en objet
            formData.forEach(item => {
                data[item.name] = item.value;
            });
            
            // Appel AJAX pour créer le rendez-vous
            CalRdvAjax.createAppointment(
                data,
                function(response) {
                    // Redirection ou affichage d'un message de succès
                    if (typeof success === 'function') {
                        success(response);
                    } else {
                        alert('Rendez-vous enregistré avec succès !');
                        window.location.reload();
                    }
                },
                function(err) {
                    if (typeof error === 'function') {
                        error(err);
                    } else {
                        console.error('Erreur lors de la réservation:', err);
                    }
                }
            );
        }
    };

    // Initialisation au chargement du DOM
    $(document).ready(function() {
        CalRdvAjax.init();
    });

    // Exposition de l'objet pour une utilisation externe
    window.CalRdvAjax = CalRdvAjax;

})(jQuery);
