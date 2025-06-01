(function($) {
    'use strict';

    // Variables globales
    var calendrierRdv = {
        ajaxUrl: calendrierRdvVars.ajaxurl,
        nonce: calendrierRdvVars.nonce,
        dateFormat: calendrierRdvVars.dateFormat,
        timeFormat: calendrierRdvVars.timeFormat,
        timezone: calendrierRdvVars.timezone,
        waitlistEnabled: calendrierRdvVars.waitlistEnabled === 'yes',
        i18n: calendrierRdvVars.i18n,
        currentStep: 1,
        selectedDate: null,
        selectedTime: null,
        selectedService: null,
        selectedPrestataire: null,
        availableSlots: [],
        waitlistData: null
    };

    // Document ready
    $(document).ready(function() {
        initializeDatepicker();
        setupEventListeners();
        updateProgressBar();
    });

    /**
     * Initialise le datepicker jQuery UI
     */
    function initializeDatepicker() {
        $('.calendrier-rdv-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            beforeShowDay: function(date) {
                // Désactiver les jours passés et les jours non disponibles
                var day = date.getDay();
                // Ici, vous pouvez ajouter une logique pour désactiver des jours spécifiques
                return [true];
            },
            onSelect: function(dateText, inst) {
                handleDateSelect(dateText);
            }
        });
    }

    /**
     * Configure les écouteurs d'événements
     */
    function setupEventListeners() {
        // Navigation entre les étapes
        $('.calendrier-rdv-next-step').on('click', handleNextStep);
        $('.calendrier-rdv-prev-step').on('click', handlePrevStep);
        
        // Changement de service ou de prestataire
        $('#calendrier-rdv-service, #calendrier-rdv-prestataire').on('change', handleServiceOrPrestataireChange);
        
        // Soumission du formulaire
        $('#calendrier-rdv-form').on('submit', handleFormSubmit);
        
        // Gestion de la liste d'attente
        $(document).on('click', '#calendrier-rdv-join-waitlist', handleJoinWaitlist);
        
        // Changement de fuseau horaire
        $('.calendrier-rdv-timezone-select').on('change', handleTimezoneChange);
    }

    /**
     * Gère la sélection d'une date
     */
    function handleDateSelect(selectedDate) {
        calendrierRdv.selectedDate = selectedDate;
        
        // Afficher le chargement
        $('#calendrier-rdv-time').prop('disabled', true).html('<option value="">' + calendrierRdv.i18n.loading + '</option>');
        $('#calendrier-rdv-slots-loading').show();
        $('#calendrier-rdv-no-slots').hide();
        $('#calendrier-rdv-waitlist-container').hide();
        
        // Récupérer les créneaux disponibles
        var serviceId = $('#calendrier-rdv-service').val();
        var prestataireId = $('#calendrier-rdv-prestataire').val();
        
        // Appel AJAX pour récupérer les créneaux disponibles
        $.ajax({
            url: calendrierRdv.ajaxUrl,
            type: 'POST',
            data: {
                action: 'calendrier_rdv_get_available_slots',
                nonce: calendrierRdv.nonce,
                date: selectedDate,
                service_id: serviceId,
                prestataire_id: prestataireId,
                timezone: calendrierRdv.timezone
            },
            success: function(response) {
                if (response.success) {
                    updateTimeSlots(response.data.slots);
                    calendrierRdv.waitlistData = response.data.waitlist;
                } else {
                    showError(response.data.message || calendrierRdv.i18n.error);
                }
            },
            error: function() {
                showError(calendrierRdv.i18n.error);
            },
            complete: function() {
                $('#calendrier-rdv-slots-loading').hide();
            }
        });
    }

    /**
     * Met à jour la liste des créneaux horaires disponibles
     */
    function updateTimeSlots(slots) {
        var $timeSelect = $('#calendrier-rdv-time');
        $timeSelect.empty();
        
        if (slots.length === 0) {
            $timeSelect.append('<option value="">' + calendrierRdv.i18n.noSlotsAvailable + '</option>');
            $('#calendrier-rdv-no-slots').show();
            return;
        }
        
        // Ajouter une option par défaut
        $timeSelect.append('<option value="">' + calendrierRdv.i18n.selectTime + '</option>');
        
        // Ajouter chaque créneau disponible
        $.each(slots, function(index, slot) {
            var optionText = formatTime(slot.start) + ' - ' + formatTime(slot.end);
            var $option = $('<option>', {
                value: slot.start,
                'data-end': slot.end,
                'data-available': slot.available,
                'data-waitlist': slot.waitlist || false,
                text: optionText
            });
            
            if (!slot.available) {
                $option.attr('disabled', true);
                optionText += ' (' + calendrierRdv.i18n.full + ')';
                $option.text(optionText);
            }
            
            $timeSelect.append($option);
        });
        
        $timeSelect.prop('disabled', false);
    }

    /**
     * Gère le changement de service ou de prestataire
     */
    function handleServiceOrPrestataireChange() {
        // Réinitialiser les champs liés
        $('#calendrier-rdv-date, #calendrier-rdv-time').val('').prop('disabled', true);
        $('#calendrier-rdv-no-slots, #calendrier-rdv-waitlist-container').hide();
        
        // Activer/désactiver le bouton suivant
        var serviceId = $('#calendrier-rdv-service').val();
        var prestataireId = $('#calendrier-rdv-prestataire').val();
        $('.calendrier-rdv-next-step').prop('disabled', !serviceId || (prestataireId === ''));
    }

    /**
     * Gère le changement d'étape suivant
     */
    function handleNextStep() {
        var $currentStep = $('.calendrier-rdv-step[data-step="' + calendrierRdv.currentStep + '"]');
        var $nextStep = $('.calendrier-rdv-step[data-step="' + (calendrierRdv.currentStep + 1) + '"]');
        
        // Validation des champs obligatoires
        if (!validateCurrentStep()) {
            return;
        }
        
        // Mettre à jour les données du formulaire
        updateFormData();
        
        // Passer à l'étape suivante
        $currentStep.hide();
        $nextStep.show();
        calendrierRdv.currentStep++;
        updateProgressBar();
        
        // Faire défiler vers le haut du formulaire
        $('html, body').animate({
            scrollTop: $nextStep.offset().top - 50
        }, 300);
    }

    /**
     * Gère le retour à l'étape précédente
     */
    function handlePrevStep() {
        var $currentStep = $('.calendrier-rdv-step[data-step="' + calendrierRdv.currentStep + '"]');
        var $prevStep = $('.calendrier-rdv-step[data-step="' + (calendrierRdv.currentStep - 1) + '"]');
        
        $currentStep.hide();
        $prevStep.show();
        calendrierRdv.currentStep--;
        updateProgressBar();
        
        // Faire défiler vers le haut du formulaire
        $('html, body').animate({
            scrollTop: $prevStep.offset().top - 50
        }, 300);
    }

    /**
     * Valide les champs de l'étape en cours
     */
    function validateCurrentStep() {
        var isValid = true;
        
        // Réinitialiser les erreurs
        $('.calendrier-rdv-field').removeClass('error');
        $('.calendrier-rdv-error').remove();
        
        // Validation de l'étape 1 : Service et prestataire
        if (calendrierRdv.currentStep === 1) {
            var $serviceField = $('#calendrier-rdv-service').closest('.calendrier-rdv-field');
            var serviceId = $('#calendrier-rdv-service').val();
            
            if (!serviceId) {
                showFieldError($serviceField, calendrierRdv.i18n.requiredField);
                isValid = false;
            }
        }
        
        // Validation de l'étape 2 : Date et heure
        if (calendrierRdv.currentStep === 2) {
            var $dateField = $('#calendrier-rdv-date').closest('.calendrier-rdv-field');
            var $timeField = $('#calendrier-rdv-time').closest('.calendrier-rdv-field');
            var date = $('#calendrier-rdv-date').val();
            var time = $('#calendrier-rdv-time').val();
            
            if (!date) {
                showFieldError($dateField, calendrierRdv.i18n.requiredField);
                isValid = false;
            }
            
            if (!time) {
                showFieldError($timeField, calendrierRdv.i18n.requiredField);
                isValid = false;
            } else {
                // Vérifier si le créneau est disponible ou en liste d'attente
                var $selectedOption = $('#calendrier-rdv-time option:selected');
                var isAvailable = $selectedOption.data('available');
                var isWaitlist = $selectedOption.data('waitlist');
                
                if (!isAvailable && !isWaitlist) {
                    showFieldError($timeField, calendrierRdv.i18n.slotNoLongerAvailable);
                    isValid = false;
                }
                
                // Afficher la liste d'attente si le créneau est complet
                if (!isAvailable && isWaitlist && calendrierRdv.waitlistEnabled) {
                    $('#calendrier-rdv-waitlist-container').show();
                } else {
                    $('#calendrier-rdv-waitlist-container').hide();
                }
            }
        }
        
        // Validation de l'étape 3 : Informations personnelles
        if (calendrierRdv.currentStep === 3) {
            var $nameField = $('#calendrier-rdv-name').closest('.calendrier-rdv-field');
            var $emailField = $('#calendrier-rdv-email').closest('.calendrier-rdv-field');
            var $phoneField = $('#calendrier-rdv-phone').closest('.calendrier-rdv-field');
            
            var name = $('#calendrier-rdv-name').val();
            var email = $('#calendrier-rdv-email').val();
            var phone = $('#calendrier-rdv-phone').val();
            
            if (!name) {
                showFieldError($nameField, calendrierRdv.i18n.requiredField);
                isValid = false;
            }
            
            if (!email) {
                showFieldError($emailField, calendrierRdv.i18n.requiredField);
                isValid = false;
            } else if (!isValidEmail(email)) {
                showFieldError($emailField, calendrierRdv.i18n.invalidEmail);
                isValid = false;
            }
            
            if (phone && !isValidPhone(phone)) {
                showFieldError($phoneField, calendrierRdv.i18n.invalidPhone);
                isValid = false;
            }
        }
        
        return isValid;
    }

    /**
     * Met à jour les données du formulaire
     */
    function updateFormData() {
        if (calendrierRdv.currentStep === 1) {
            calendrierRdv.selectedService = $('#calendrier-rdv-service').val();
            calendrierRdv.selectedPrestataire = $('#calendrier-rdv-prestataire').val();
        } else if (calendrierRdv.currentStep === 2) {
            calendrierRdv.selectedDate = $('#calendrier-rdv-date').val();
            calendrierRdv.selectedTime = $('#calendrier-rdv-time').val();
            
            // Mettre à jour le récapitulatif
            updateSummary();
        }
    }

    /**
     * Met à jour le récapitulatif de la réservation
     */
    function updateSummary() {
        var serviceName = $('#calendrier-rdv-service option:selected').text().split(' - ')[0];
        var prestataireName = $('#calendrier-rdv-prestataire option:selected').text();
        var date = moment(calendrierRdv.selectedDate).format('LL');
        var time = formatTime(calendrierRdv.selectedTime);
        
        $('#calendrier-rdv-summary-service').text(serviceName);
        $('#calendrier-rdv-summary-prestataire').text(prestataireName);
        $('#calendrier-rdv-summary-date').text(date + ' à ' + time);
    }

    /**
     * Gère la soumission du formulaire
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        // Valider le formulaire
        if (!validateCurrentStep()) {
            return;
        }
        
        // Afficher le chargement
        showLoading(true);
        
        // Récupérer les données du formulaire
        var formData = {
            action: 'calendrier_rdv_submit_booking',
            nonce: calendrierRdv.nonce,
            service_id: $('#calendrier-rdv-service').val(),
            prestataire_id: $('#calendrier-rdv-prestataire').val(),
            date: $('#calendrier-rdv-date').val(),
            time: $('#calendrier-rdv-time').val(),
            name: $('#calendrier-rdv-name').val(),
            email: $('#calendrier-rdv-email').val(),
            phone: $('#calendrier-rdv-phone').val(),
            notes: $('#calendrier-rdv-notes').val(),
            timezone: calendrierRdv.timezone
        };
        
        // Envoyer la requête AJAX
        $.ajax({
            url: calendrierRdv.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Afficher la confirmation
                    $('.calendrier-rdv-step').hide();
                    $('.calendrier-rdv-step[data-step="4"]').show();
                    calendrierRdv.currentStep = 4;
                    updateProgressBar();
                    
                    // Afficher les détails de la réservation
                    if (response.data.booking) {
                        var booking = response.data.booking;
                        $('#calendrier-rdv-booking-number').text(booking.booking_number || 'N/A');
                        $('#calendrier-rdv-booking-date').text(
                            moment(booking.date + ' ' + booking.time).format('LLL')
                        );
                        
                        // Afficher le lien de confirmation si nécessaire
                        if (booking.confirmation_url) {
                            $('#calendrier-rdv-confirmation-link').html(
                                '<a href="' + booking.confirmation_url + '" target="_blank">' + 
                                calendrierRdv.i18n.viewBookingDetails + '</a>'
                            ).show();
                        }
                    }
                    
                    // Faire défiler vers le haut
                    $('html, body').animate({
                        scrollTop: 0
                    }, 300);
                } else {
                    showError(response.data.message || calendrierRdv.i18n.error);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = calendrierRdv.i18n.error;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                showError(errorMessage);
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    /**
     * Gère l'inscription à la liste d'attente
     */
    function handleJoinWaitlist() {
        var $button = $(this);
        var originalText = $button.html();
        
        // Désactiver le bouton pendant la requête
        $button.prop('disabled', true).html('<span class="spinner is-active" style="margin: 0 5px 0 0;"></span>' + calendrierRdv.i18n.loading);
        
        // Préparer les données
        var formData = {
            action: 'calendrier_rdv_join_waitlist',
            nonce: calendrierRdv.nonce,
            service_id: $('#calendrier-rdv-service').val(),
            prestataire_id: $('#calendrier-rdv-prestataire').val(),
            date: $('#calendrier-rdv-date').val(),
            time: $('#calendrier-rdv-time').val(),
            name: $('#calendrier-rdv-name').val() || '',
            email: $('#calendrier-rdv-email').val() || '',
            timezone: calendrierRdv.timezone
        };
        
        // Envoyer la requête AJAX
        $.ajax({
            url: calendrierRdv.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Afficher le message de succès
                    $('#calendrier-rdv-waitlist-container').html(
                        '<div class="calendrier-rdv-notice notice notice-success">' +
                        '<p>' + (response.data.message || calendrierRdv.i18n.waitlistAddSuccess) + '</p>' +
                        '</div>'
                    );
                } else {
                    // Afficher l'erreur
                    showError(response.data.message || calendrierRdv.i18n.error);
                    $button.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showError(calendrierRdv.i18n.error);
                $button.html(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Gère le changement de fuseau horaire
     */
    function handleTimezoneChange() {
        var newTimezone = $(this).val();
        if (newTimezone && newTimezone !== calendrierRdv.timezone) {
            calendrierRdv.timezone = newTimezone;
            
            // Mettre à jour le cookie du fuseau horaire
            document.cookie = 'calendrier_rdv_timezone=' + encodeURIComponent(newTimezone) + 
                             ';path=/' + 
                             ';max-age=' + (60 * 60 * 24 * 365) + // 1 an
                             ';samesite=lax';
            
            // Recharger les créneaux si une date est sélectionnée
            if (calendrierRdv.selectedDate) {
                handleDateSelect(calendrierRdv.selectedDate);
            }
        }
    }

    /**
     * Met à jour la barre de progression
     */
    function updateProgressBar() {
        $('.calendrier-rdv-progress-step').each(function() {
            var step = parseInt($(this).data('step'));
            
            $(this).removeClass('active completed');
            
            if (step < calendrierRdv.currentStep) {
                $(this).addClass('completed');
            } else if (step === calendrierRdv.currentStep) {
                $(this).addClass('active');
            }
        });
    }

    /**
     * Affiche une erreur pour un champ spécifique
     */
    function showFieldError($field, message) {
        $field.addClass('error');
        $('<div class="calendrier-rdv-error">' + message + '</div>').insertAfter($field.find('input, select, textarea'));
    }

    /**
     * Affiche un message d'erreur générique
     */
    function showError(message) {
        // Supprimer les anciens messages d'erreur
        $('.calendrier-rdv-error-message').remove();
        
        // Afficher le nouveau message d'erreur
        $('.calendrier-rdv-form').prepend(
            '<div class="calendrier-rdv-error-message notice notice-error">' +
            '<p>' + message + '</p>' +
            '</div>'
        );
        
        // Faire défiler vers le message d'erreur
        $('html, body').animate({
            scrollTop: $('.calendrier-rdv-error-message').offset().top - 20
        }, 300);
    }

    /**
     * Affiche ou masque l'écran de chargement
     */
    function showLoading(show) {
        if (show) {
            $('#calendrier-rdv-loading').show();
        } else {
            $('#calendrier-rdv-loading').hide();
        }
    }

    /**
     * Formate une heure au format HH:MM
     */
    function formatTime(timeString) {
        if (!timeString) return '';
        
        var timeParts = timeString.split(':');
        var hours = parseInt(timeParts[0]);
        var minutes = timeParts[1];
        var ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // Convertir 0 en 12 pour minuit
        
        return hours + ':' + minutes + ' ' + ampm;
    }

    /**
     * Vérifie si une adresse email est valide
     */
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Vérifie si un numéro de téléphone est valide
     */
    function isValidPhone(phone) {
        // Format international simple : + jusqu'à 15 chiffres
        var re = /^\+?[0-9\s.-]{10,}$/;
        return re.test(phone);
    }

})(jQuery);
