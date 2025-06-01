/**
 * Script principal du calendrier de rendez-vous
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    let selectedDate = '';
    let selectedTime = '';
    let selectedPrestataire = 1;
    
    // Initialisation du datepicker
    $('.rdv-datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 0,
        beforeShowDay: disableBookedDays,
        onSelect: function(dateText, inst) {
            selectedDate = dateText;
            $('.rdv-time-slots').show();
            $('.rdv-time-slots-container').html('<p>Chargement des créneaux...</p>');
            
            // Récupération des créneaux disponibles
            getAvailableSlots(selectedDate, selectedPrestataire);
        }
    });
    
    // Gestion du changement de prestataire
    $('#rdv-prestataire').on('change', function() {
        selectedPrestataire = $(this).val();
        if (selectedDate) {
            getAvailableSlots(selectedDate, selectedPrestataire);
        }
    });
    
    // Soumission du formulaire
    $('#rdv-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return false;
        }
        
        const formData = {
            action: 'book_slot',
            nonce: rdvCalendar.nonce,
            date: selectedDate,
            time: selectedTime,
            title: $('#rdv-name').val(),
            email: $('#rdv-email').val(),
            telephone: $('#rdv-phone').val(),
            notes: $('#rdv-notes').val(),
            prestataire_id: selectedPrestataire
        };
        
        // Désactiver le bouton de soumission
        const $submitBtn = $('.rdv-btn-submit');
        $submitBtn.prop('disabled', true).html(rdvCalendar.i18n.saving + '...');
        
        // Envoi de la requête AJAX
        $.post(rdvCalendar.ajax_url, formData, function(response) {
            if (response.success) {
                // Afficher le message de confirmation
                $('.rdv-booking-details').hide();
                $('.rdv-booking-confirmation').show();
                $('.rdv-reference').text('RDV-' + response.data.appointment_id);
                
                // Faire défiler jusqu'au message de confirmation
                $('html, body').animate({
                    scrollTop: $('.rdv-booking-confirmation').offset().top - 100
                }, 500);
            } else {
                // Afficher l'erreur
                showMessage('error', response.data.message || 'Une erreur est survenue.');
            }
        }).fail(function(xhr, status, error) {
            showMessage('error', 'Erreur de connexion. Veuillez réessayer.');
            console.error('Erreur AJAX:', status, error);
        }).always(function() {
            // Réactiver le bouton
            $submitBtn.prop('disabled', false).html(rdvCalendar.i18n.confirm_booking);
        });
    });
    
    // Bouton d'annulation
    $('.rdv-btn-cancel').on('click', function() {
        selectedTime = '';
        $('.rdv-time-slot').removeClass('selected');
        $('.rdv-booking-details').hide();
    });
    
    // Nouvelle réservation
    $('.rdv-btn-new-booking').on('click', function() {
        location.reload();
    });
    
    /**
     * Récupère les créneaux disponibles pour une date donnée
     */
    function getAvailableSlots(date, prestataireId) {
        $.ajax({
            url: rdvCalendar.ajax_url,
            type: 'GET',
            data: {
                action: 'get_slots',
                nonce: rdvCalendar.nonce,
                date: date,
                prestataire_id: prestataireId
            },
            beforeSend: function() {
                $('.rdv-time-slots-container').html('<p>Chargement des créneaux...</p>');
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    renderTimeSlots(response.data);
                } else {
                    $('.rdv-time-slots-container').html('<p>Aucun créneau disponible pour cette date.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur lors de la récupération des créneaux:', status, error);
                showMessage('error', 'Impossible de charger les créneaux. Veuillez réessayer.');
            }
        });
    }
    
    /**
     * Affiche les créneaux horaires disponibles
     */
    function renderTimeSlots(slots) {
        let html = '';
        
        if (slots.length === 0) {
            html = '<p>Aucun créneau disponible pour cette date.</p>';
        } else {
            slots.forEach(function(slot) {
                html += `
                    <div class="rdv-time-slot" data-time="${slot}">
                        ${slot}
                    </div>`;
            });
            
            // Gestion du clic sur un créneau
            $('.rdv-time-slot').on('click', function() {
                selectedTime = $(this).data('time');
                $('.rdv-time-slot').removeClass('selected');
                $(this).addClass('selected');
                
                // Afficher le formulaire de réservation
                $('.rdv-booking-details').show().get(0).scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            });
        }
        
        $('.rdv-time-slots-container').html(html);
    }
    
    /**
     * Désactive les jours complets dans le datepicker
     */
    function disableBookedDays(date) {
        // À implémenter : logique pour désactiver les jours complets
        return [true, ''];
    }
    
    /**
     * Valide le formulaire
     */
    function validateForm() {
        let isValid = true;
        const $form = $('#rdv-booking-form');
        
        // Réinitialiser les erreurs
        $('.rdv-form-control').removeClass('error');
        $('.error-message').remove();
        
        // Validation du nom
        if ($('#rdv-name').val().trim() === '') {
            showFieldError('#rdv-name', rdvCalendar.i18n.required_field);
            isValid = false;
        }
        
        // Validation de l'email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test($('#rdv-email').val())) {
            showFieldError('#rdv-email', rdvCalendar.i18n.invalid_email);
            isValid = false;
        }
        
        // Validation du téléphone (au moins 10 chiffres)
        const phoneRegex = /[0-9]{10,}/;
        if (!phoneRegex.test($('#rdv-phone').val().replace(/\s+/g, ''))) {
            showFieldError('#rdv-phone', 'Veuillez entrer un numéro de téléphone valide');
            isValid = false;
        }
        
        // Validation de la date et de l'heure
        if (!selectedDate) {
            showMessage('error', rdvCalendar.i18n.invalid_date);
            isValid = false;
        }
        
        if (!selectedTime) {
            showMessage('error', rdvCalendar.i18n.invalid_time);
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Affiche un message d'erreur pour un champ
     */
    function showFieldError(selector, message) {
        const $field = $(selector);
        $field.addClass('error');
        $('<span class="error-message">' + message + '</span>')
            .insertAfter($field)
            .css('color', '#dc3232')
            .css('display', 'block')
            .css('margin-top', '5px');
    }
    
    /**
     * Affiche un message à l'utilisateur
     */
    function showMessage(type, message) {
        const $messageDiv = $('<div class="rdv-alert rdv-alert-' + type + '">' + message + '</div>');
        $('.rdv-calendar-messages').html($messageDiv);
        
        // Faire défiler jusqu'au message
        $('html, body').animate({
            scrollTop: $messageDiv.offset().top - 100
        }, 500);
        
        // Masquer le message après 10 secondes
        setTimeout(function() {
            $messageDiv.fadeOut(500, function() {
                $(this).remove();
            });
        }, 10000);
    }
});
