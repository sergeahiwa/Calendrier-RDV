/**
 * Gestion des conflits de rendez-vous en temps réel
 */
(function($) {
    'use strict';

    class ConflictDetector {
        constructor() {
            this.selectors = {
                form: '.calendrier-rdv-form',
                datetimeFields: '.calendrier-rdv-datetime',
                providerField: 'select[name="provider_id"]',
                serviceField: 'select[name="service_id"]',
                submitButton: 'button[type="submit"]',
                conflictMessage: '.conflict-message'
            };

            this.elements = {};
            this.debounceTimer = null;
            this.debounceDelay = 500; // ms

            this.init();
        }


        init() {
            this.cacheElements();
            this.bindEvents();
        }

        cacheElements() {
            this.elements.form = $(this.selectors.form);
            this.elements.datetimeFields = $(this.selectors.datetimeFields);
            this.elements.providerField = $(this.selectors.providerField);
            this.elements.serviceField = $(this.selectors.serviceField);
            this.elements.submitButton = $(this.selectors.submitButton, this.elements.form);
            this.elements.conflictMessage = $(this.selectors.conflictMessage);
        }

        bindEvents() {
            // Vérification lors de la modification des champs
            this.elements.datetimeFields.on('change keyup', this.onDatetimeChange.bind(this));
            this.elements.providerField.add(this.elements.serviceField).on('change', this.onDatetimeChange.bind(this));
            
            // Vérification avant soumission du formulaire
            this.elements.form.on('submit', this.onFormSubmit.bind(this));
        }

        onDatetimeChange() {
            // Annuler la vérification précédente si elle est en cours
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            // Délai pour éviter des appels trop fréquents
            this.debounceTimer = setTimeout(() => {
                this.checkConflict();
            }, this.debounceDelay);
        }

        onFormSubmit(e) {
            // Si un conflit est détecté, empêcher la soumission
            if (this.elements.conflictMessage.hasClass('has-conflict')) {
                e.preventDefault();
                this.showConflictMessage(calendrier_rdv_vars.messages.conflict_detected);
                return false;
            }
            return true;
        }

        async checkConflict() {
            const startDatetime = this.elements.datetimeFields.first().val();
            const endDatetime = this.elements.datetimeFields.last().val();
            const providerId = this.elements.providerField.val();
            const serviceId = this.elements.serviceField.val();
            const appointmentId = this.elements.form.find('input[name="appointment_id"]').val() || 0;

            // Vérification des champs requis
            if (!startDatetime || !endDatetime || !providerId || !serviceId) {
                return;
            }

            try {
                const response = await this.checkConflictAjax({
                    start_datetime: startDatetime,
                    end_datetime: endDatetime,
                    provider_id: providerId,
                    service_id: serviceId,
                    appointment_id: appointmentId,
                    nonce: calendrier_rdv_vars.nonce
                });

                if (response.success) {
                    this.clearConflict();
                } else {
                    this.showConflict(response.data.message);
                }
            } catch (error) {
                console.error('Erreur lors de la vérification des conflits :', error);
            }
        }

        checkConflictAjax(data) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: calendrier_rdv_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'check_appointment_conflict',
                        ...data
                    },
                    dataType: 'json'
                })
                .done(response => resolve(response))
                .fail(error => reject(error));
            });
        }

        showConflict(message) {
            this.elements.conflictMessage
                .html(`<div class="notice notice-error"><p>${message}</p></div>`)
                .addClass('has-conflict')
                .show();

            this.elements.submitButton.prop('disabled', true);
        }

        clearConflict() {
            this.elements.conflictMessage
                .removeClass('has-conflict')
                .hide();

            this.elements.submitButton.prop('disabled', false);
        }

        showConflictMessage(message) {
            // Afficher un message d'erreur visible
            alert(message);
        }
    }

    // Initialisation lorsque le DOM est prêt
    $(document).ready(() => {
        if ($('.calendrier-rdv-form').length) {
            new ConflictDetector();
        }
    });

})(jQuery);
