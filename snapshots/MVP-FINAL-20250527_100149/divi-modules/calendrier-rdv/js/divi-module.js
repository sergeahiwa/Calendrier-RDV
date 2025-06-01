(function($) {
    'use strict';

    // === Gestion centralisée des messages utilisateur ===
    const messages = {
        fr: {
            loading: 'Chargement...',
            error: 'Une erreur est survenue',
            formLoadError: 'Erreur lors du chargement du formulaire',
            submitSending: 'Envoi en cours...',
            submitSuccess: 'Votre rendez-vous a bien été enregistré !',
            selectProvider: 'Veuillez sélectionner un prestataire',
            selectService: 'Veuillez sélectionner un service',
            requiredField: 'Ce champ est requis',
            invalidEmail: 'Adresse email invalide',
            unavailable: 'Aucun créneau disponible',
        },
        en: {
            loading: 'Loading...',
            error: 'An error occurred',
            formLoadError: 'Error loading the form',
            submitSending: 'Sending...',
            submitSuccess: 'Your appointment has been booked!',
            selectProvider: 'Please select a provider',
            selectService: 'Please select a service',
            requiredField: 'This field is required',
            invalidEmail: 'Invalid email address',
            unavailable: 'No slots available',
        }
    };
    // Détection automatique de la langue (par défaut : fr)
    let currentLang = document.documentElement.lang && document.documentElement.lang.startsWith('en') ? 'en' : 'fr';
    function t(key) {
        return (messages[currentLang] && messages[currentLang][key]) || messages['fr'][key] || key;
    }
    // Classe principale du module
    class CalendrierRdvModule {
        // Constantes
        static get DEFAULTS() {
            return {
                googleCalendarApiUrl: 'https://www.google.com/calendar/render',
                smsReminderTime: '1' // 1 heure avant par défaut
            };
        }
        constructor(element, options) {
            this.element = element;
            this.options = $.extend({
                // Options par défaut
                prestataireId: '',
                serviceId: '',
                showTitle: true,
                showDescription: true
            }, options);

            this.init();
        }

        init() {
            this.cacheElements();
            this.bindEvents();
            this.loadForm();
            
            // Initialiser les options du module
            this.initOptions();
        }
        
        /**
         * Initialise les options du module
         */
        initOptions() {
            // Options de personnalisation
            const moduleId = this.$container.attr('id');
            const moduleData = window[`calendrierRdvDiviModule_${moduleId.replace(/-/g, '_')}`] || {};
            
            // Mettre à jour les options avec les données du module
            this.options = {
                ...this.options,
                ...moduleData
            };
            
            // Initialiser les notifications SMS si activées
            if (this.options.enable_sms_notifications) {
                this.toggleSmsNotification(true);
            }
        }

        cacheElements() {
            this.$container = $(this.element);
            this.$formContainer = this.$container.find('.calendrier-rdv-container');
            this.template = this.$container.find('#calendrier-rdv-template').html();
        }

        bindEvents() {
            // Délégation d'événements pour les éléments chargés dynamiquement
            this.$container
                .on('change', '.calendrier-rdv-select', this.onSelectChange.bind(this))
                .on('click', '.time-slot', this.onTimeSlotClick.bind(this))
                .on('submit', '.calendrier-rdv-form', this.onFormSubmit.bind(this));
        }

        loadForm() {
            this.showLoading();

            // Affichage message de chargement
            this.showMessage('loading', 'info');
            // Récupérer le formulaire via l'API REST
            $.ajax({
                url: calendrierRdvDiviVars.restUrl + 'booking-form',
                method: 'GET',
                data: {
                    prestataire_id: this.options.prestataireId,
                    service_id: this.options.serviceId
                },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', calendrierRdvDiviVars.nonce);
                },
                success: (response) => {
                    if (response.success) {
                        this.renderForm(response.data.html);
                        this.initDatepicker();
                    } else {
                        this.showError(response.data.message || 'Erreur lors du chargement du formulaire');
                    }
                },
                error: (xhr) => {
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : t('error');
                    this.showMessage(errorMessage, 'error');
                }
            });
        }

        renderForm(html) {
            this.$formContainer.html(html);
            this.$form = this.$container.find('.calendrier-rdv-form');
            this.initSelect2();
            this.showMessage('', 'clear'); // Nettoyer les messages après chargement
        }

        initDatepicker() {
            this.$container.find('.calendrier-rdv-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                beforeShowDay: this.disableSundays,
                onSelect: this.onDateSelect.bind(this)
            });
        }

        initSelect2() {
            if (typeof $.fn.select2 === 'function') {
                this.$container.find('.calendrier-rdv-select').select2({
                    width: '100%',
                    dropdownParent: this.$container.closest('.et_pb_module')
                });
            }
        }

        onSelectChange(e) {
            const $select = $(e.currentTarget);
            const field = $select.data('field');
            const value = $select.val();

            // Mettre à jour les options en fonction de la sélection
            if (field === 'prestataire_id') {
                this.loadServices(value);
            } else if (field === 'service_id') {
                this.updateFormState();
            }
        }

        onDateSelect(dateText) {
            this.selectedDate = dateText;
            this.loadAvailableSlots();
        }

        onTimeSlotClick(e) {
            e.preventDefault();
            
            const $slot = $(e.currentTarget);
            if ($slot.hasClass('disabled')) {
                return;
            }

            this.$container.find('.time-slot').removeClass('selected');
            $slot.addClass('selected');
            this.selectedTime = $slot.data('time');
            this.updateFormState();
        }

        onFormSubmit(e) {
            e.preventDefault();
            
            const formData = this.getFormData();
            
            if (!this.validateForm(formData)) {
                this.showMessage('requiredField', 'error');
                return;
            }
            
            // Ajouter les paramètres de notification SMS si activés
            const smsSettings = this.getSmsNotificationSettings();
            if (smsSettings) {
                formData.sms_notification = smsSettings;
            }
            
            // Désactiver le bouton de soumission pendant l'envoi
            const $submitBtn = this.$form.find('.btn-submit');
            const originalBtnText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text(t('submitSending'));
            
            // Afficher l'indicateur de chargement
            this.showMessage('submitSending', 'info');
            
            // Envoyer la requête AJAX
            $.ajax({
                url: calendrierRdvDiviVars.restUrl + 'rendez-vous',
                method: 'POST',
                data: formData,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', calendrierRdvDiviVars.nonce);
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('submitSuccess', 'success');
                        
                        if (this.options.show_google_calendar) {
                            this.toggleGoogleCalendar(true, {
                                ...formData,
                                prestataire_nom: this.$form.find('[name="prestataire_id"] option:selected').text(),
                                service_nom: this.$form.find('[name="service_id"] option:selected').text()
                            });
                        }
                        
                        this.resetForm();
                    } else {
                        this.showError(response.data.message || 'Une erreur est survenue lors de l\'enregistrement du rendez-vous');
                    }
                },
                error: (xhr) => {
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : 'Une erreur est survenue lors de la communication avec le serveur';
                    this.showError(errorMessage);
                },
                complete: () => {
                    // Réactiver le bouton de soumission
                    $submitBtn.prop('disabled', false).text(originalBtnText);
                    this.hideLoading();
                }
            });
        }

        getFormData() {
            const formData = {};
            
            this.$form.find('[name]').each((index, element) => {
                const $element = $(element);
                formData[$element.attr('name')] = $element.val();
            });
            
            // Ajouter les données supplémentaires
            formData.date_rdv = this.selectedDate;
            formData.heure_debut = this.selectedTime;
            
            return formData;
        }

        validateForm(formData) {
            // Validation des champs obligatoires
            const requiredFields = [
                'prestataire_id',
                'service_id',
                'date_rdv',
                'heure_debut',
                'client_nom',
                'client_email',
                'client_telephone'
            ];

            const errors = [];

            requiredFields.forEach(field => {
                if (!formData[field]) {
                    errors.push(`Le champ ${field} est obligatoire`);
                }
            });

            // Validation de l'email
            if (formData.client_email && !this.isValidEmail(formData.client_email)) {
                errors.push('Veuillez entrer une adresse email valide');
            }

            // Afficher les erreurs
            if (errors.length > 0) {
                this.showError(errors.join('<br>'));
                return false;
            }

            return true;
        }

        submitForm(formData) {
            this.showLoading('Envoi en cours...');

            $.ajax({
                url: calendrierRdvDiviVars.restUrl + 'rendez-vous',
                method: 'POST',
                data: formData,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', calendrierRdvDiviVars.nonce);
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Votre rendez-vous a bien été enregistré ! Un email de confirmation vous a été envoyé.');
                        this.resetForm();
                    } else {
                        this.showError(response.data.message || 'Une erreur est survenue lors de l\'enregistrement du rendez-vous');
                    }
                },
                error: (xhr) => {
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : 'Une erreur est survenue lors de l\'enregistrement du rendez-vous';
                    this.showError(errorMessage);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        loadServices(prestataireId) {
            if (!prestataireId) {
                return;
            }

            this.showLoading('Chargement des services...');

            $.ajax({
                url: calendrierRdvDiviVars.restUrl + 'services',
                method: 'GET',
                data: {
                    prestataire_id: prestataireId
                },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', calendrierRdvDiviVars.nonce);
                },
                success: (response) => {
                    if (response.success) {
                        this.updateServicesDropdown(response.data.services);
                    } else {
                        this.showError(response.data.message || 'Erreur lors du chargement des services');
                    }
                },
                error: () => {
                    this.showError('Erreur lors du chargement des services');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        loadAvailableSlots() {
            const prestataireId = this.$form.find('[name="prestataire_id"]').val();
            const serviceId = this.$form.find('[name="service_id"]').val();

            if (!prestataireId || !serviceId || !this.selectedDate) {
                return;
            }

            this.showLoading('Chargement des créneaux disponibles...');

            $.ajax({
                url: calendrierRdvDiviVars.restUrl + 'creneaux',
                method: 'GET',
                data: {
                    prestataire_id: prestataireId,
                    service_id: serviceId,
                    date: this.selectedDate
                },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', calendrierRdvDiviVars.nonce);
                },
                success: (response) => {
                    if (response.success) {
                        this.renderTimeSlots(response.data.creneaux);
                    } else {
                        this.showError(response.data.message || 'Erreur lors du chargement des créneaux');
                    }
                },
                error: () => {
                    this.showError('Erreur lors du chargement des créneaux disponibles');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        updateServicesDropdown(services) {
            const $select = this.$form.find('[name="service_id"]');
            $select.empty().append('<option value="">Sélectionnez un service</option>');

            services.forEach(service => {
                $select.append(new Option(service.nom, service.id, false, false));
            });

            if (this.options.serviceId) {
                $select.val(this.options.serviceId).trigger('change');
            }

            this.updateFormState();
        }

        renderTimeSlots(timeSlots) {
            const $container = this.$form.find('.time-slots-container');
            
            if (!timeSlots || timeSlots.length === 0) {
                $container.html('<p class="no-slots">Aucun créneau disponible pour cette date</p>');
                return;
            }

            let html = '<div class="time-slots">';
            
            timeSlots.forEach(slot => {
                const isAvailable = slot.statut === 'disponible';
                const classNames = ['time-slot'];
                
                if (!isAvailable) {
                    classNames.push('disabled');
                }
                
                html += `
                    <div class="${classNames.join(' ')}" 
                         data-time="${slot.heure}" 
                         ${!isAvailable ? 'title="Créneau indisponible"' : ''}>
                        ${slot.heure}
                    </div>
                `;
            });
            
            html += '</div>';
            $container.html(html);
        }

        updateFormState() {
            const prestataireId = this.$form.find('[name="prestataire_id"]').val();
            const serviceId = this.$form.find('[name="service_id"]').val();
            const isFormValid = prestataireId && serviceId && this.selectedDate && this.selectedTime;
            
            this.$form.find('.btn-submit').prop('disabled', !isFormValid);
        }

        resetForm() {
            // Réinitialiser le formulaire
            this.$form[0].reset();
            this.selectedDate = null;
            this.selectedTime = null;
            
            // Réinitialiser les sélections
            this.$container.find('.time-slot').removeClass('selected');
            this.$container.find('.time-slots-container').empty();
            
            // Mettre à jour l'état du formulaire
            this.updateFormState();
        }

        showLoading(message) {
            this.showMessage(message || 'loading', 'info');
        }

        hideLoading() {
            this.$formContainer.find('.calendrier-rdv-loading').remove();
        }

        showError(message) {
            this.showMessage(message, 'error');
        }

        showSuccess(message) {
            this.showMessage(message, 'success');
        }

        showMessage(message, type = 'info') {
            const $message = $(`
                <div class="calendrier-rdv-message ${type}">
                    ${message}
                </div>
            `);
            
            this.$form.before($message);
            
            // Masquer le message après 5 secondes
            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, 5000);
        }

        // Méthodes utilitaires
        disableSundays(date) {
            return [date.getDay() !== 0, ''];
        }

        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(String(email).toLowerCase());
        }
        
        /**
         * Génère un lien Google Calendar pour l'ajout d'un événement
         */
        generateGoogleCalendarLink(bookingData) {
            if (!bookingData || !bookingData.date_rdv || !bookingData.heure_debut) {
                console.error('Données de réservation manquantes pour Google Calendar');
                return null;
            }
            
            // Formater la date et l'heure pour Google Calendar
            const startDate = new Date(`${bookingData.date_rdv}T${bookingData.heure_debut}`);
            const endDate = new Date(startDate.getTime() + (bookingData.duree || 30) * 60 * 1000);
            
            // Formater les dates au format requis par Google Calendar
            const formatDate = (date) => {
                return date.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
            };
            
            // Construire l'URL Google Calendar
            const params = new URLSearchParams({
                action: 'TEMPLATE',
                text: `Rendez-vous: ${bookingData.service_nom || 'Rendez-vous'}`,
                dates: `${formatDate(startDate)}/${formatDate(endDate)}`,
                details: `Prestataire: ${bookingData.prestataire_nom || ''}\n` +
                         `Service: ${bookingData.service_nom || ''}\n` +
                         `Client: ${bookingData.client_nom || ''}\n` +
                         `Téléphone: ${bookingData.client_telephone || ''}\n` +
                         `Email: ${bookingData.client_email || ''}\n` +
                         `Notes: ${bookingData.notes || ''}`,
                location: bookingData.lieu || '',
                ctz: Intl.DateTimeFormat().resolvedOptions().timeZone
            });
            
            return `${this.constructor.DEFAULTS.googleCalendarApiUrl}?${params.toString()}`;
        }
        
        /**
         * Affiche/masque l'option Google Calendar
         */
        toggleGoogleCalendar(show = true, bookingData = null) {
            const $googleCalendar = this.$container.find('.calendrier-rdv-google-calendar');
            
            if (show && bookingData) {
                const calendarLink = this.generateGoogleCalendarLink(bookingData);
                if (calendarLink) {
                    $googleCalendar.find('.google-calendar-btn').attr('href', calendarLink);
                    $googleCalendar.slideDown();
                }
            } else {
                $googleCalendar.slideUp();
            }
        }
        
        /**
         * Active/désactive les notifications SMS
         */
        toggleSmsNotification(enable = true) {
            const $smsCheckbox = this.$container.find('.sms-reminder-checkbox');
            $smsCheckbox.prop('checked', enable);
            
            // Si on active les notifications, on peut ajouter un sélecteur d'heure
            if (enable) {
                this.addSmsTimeSelector();
            } else {
                this.removeSmsTimeSelector();
            }
        }
        
        /**
         * Ajoute un sélecteur d'heure pour le rappel SMS
         */
        addSmsTimeSelector() {
            if (this.$container.find('.sms-reminder-time').length > 0) {
                return; // Déjà ajouté
            }
            
            const $smsContainer = this.$container.find('.calendrier-rdv-sms-notification');
            const $timeSelector = $(`
                <div class="sms-reminder-time" style="margin-top: 10px; margin-left: 25px; display: none;">
                    <label style="display: inline-block; margin-right: 10px;">
                        Rappeler
                    </label>
                    <select name="sms_reminder_time" class="calendrier-rdv-select" style="width: auto; display: inline-block;">
                        <option value="0.5">30 minutes avant</option>
                        <option value="1" selected>1 heure avant</option>
                        <option value="2">2 heures avant</option>
                        <option value="4">4 heures avant</option>
                        <option value="24">1 jour avant</option>
                        <option value="48">2 jours avant</option>
                    </select>
                </div>
            `);
            
            $smsContainer.append($timeSelector);
            
            // Afficher/masquer le sélecteur d'heure en fonction de la case à cocher
            $smsContainer.find('.sms-reminder-checkbox').on('change', (e) => {
                $timeSelector.toggle(e.target.checked);
            }).trigger('change');
        }
        
        /**
         * Supprime le sélecteur d'heure pour le rappel SMS
         */
        removeSmsTimeSelector() {
            this.$container.find('.sms-reminder-time').remove();
        }
        
        /**
         * Récupère les paramètres de notification SMS
         */
        getSmsNotificationSettings() {
            const $smsCheckbox = this.$container.find('.sms-reminder-checkbox');
            
            if (!$smsCheckbox.is(':checked')) {
                return null;
            }
            
            return {
                enabled: true,
                reminder_minutes: parseInt(this.$container.find('select[name="sms_reminder_time"]').val()) * 60
            };
        }
    }


    // Initialisation du module
    $.fn.calendrierRdvModule = function(options) {
        return this.each(function() {
            if (!$.data(this, 'calendrierRdvModule')) {
                $.data(this, 'calendrierRdvModule', new CalendrierRdvModule(this, options));
            }
        });
    };

    // Initialisation automatique des modules
    $(document).ready(function() {
        $('.calendrier-rdv-divi-module').each(function() {
            const $module = $(this);
            const options = {
                prestataireId: $module.data('prestataire-id') || '',
                serviceId: $module.data('service-id') || '',
                showTitle: $module.data('show-title') !== false,
                showDescription: $module.data('show-description') !== false
            };

            $module.calendrierRdvModule(options);
        });
    });

})(jQuery);
