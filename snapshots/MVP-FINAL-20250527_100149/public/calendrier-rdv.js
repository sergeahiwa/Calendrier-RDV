jQuery(document).ready(function($) {
    // Variables globales
    let selectedDate = null;
    let selectedTime = null;
    let currentServiceId = null;
    
    // Initialisation du calendrier
    $('.calendar').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 0,
        beforeShowDay: function(date) {
            // Désactiver les dimanches
            return [date.getDay() !== 0, ''];
        },
        onSelect: function(dateText) {
            selectedDate = dateText;
            $('.selected-date').text(dateText);
            loadAvailableSlots();
        }
    });
    
    // Chargement des créneaux disponibles
    function loadAvailableSlots() {
        if (!selectedDate || !currentServiceId) return;
        
        // Afficher le chargement
        $('.time-slots').html('<div class="loading">Chargement des créneaux disponibles...</div>');
        
        // Récupérer les créneaux disponibles via l'API REST
        $.ajax({
            url: calendrierRdvVars.restUrl + 'calendrier-rdv/v1/creneaux',
            method: 'GET',
            data: {
                prestataire_id: $('#prestataire').val(),
                service_id: currentServiceId,
                date: selectedDate
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', calendrierRdvVars.nonce);
            },
            success: function(response) {
                if (response.success && response.data.creneaux.length > 0) {
                    displayTimeSlots(response.data.creneaux);
                } else {
                    $('.time-slots').html('<div class="no-slots">Aucun créneau disponible pour cette date.</div>');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Une erreur est survenue lors du chargement des créneaux.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage(errorMessage, 'error');
                $('.time-slots').html('<div class="error-message">' + errorMessage + '</div>');
            }
        });
    }
    
    // Affichage des créneaux horaires
    function displayTimeSlots(slots) {
        const $container = $('.time-slots');
        $container.empty();
        
        if (slots.length === 0) {
            $container.html('<div class="no-slots">Aucun créneau disponible pour cette date.</div>');
            return;
        }
        
        slots.forEach(function(time) {
            $container.append(`
                <div class="time-slot" data-time="${time}">
                    ${time}
                </div>
            `);
        });
        
        // Gestion du clic sur un créneau
        $('.time-slot').on('click', function() {
            $('.time-slot').removeClass('selected');
            $(this).addClass('selected');
            selectedTime = $(this).data('time');
            updateFormState();
        });
    }
    
    // Mise à jour de l'état du formulaire
    function updateFormState() {
        const prestataire = $('#prestataire').val();
        const service = $('#service').val();
        
        // Mettre à jour l'ID du service courant
        currentServiceId = service;
        
        // Réinitialiser la sélection si le service change
        if (service !== currentServiceId) {
            selectedTime = null;
            $('.time-slot').removeClass('selected');
        }
        
        // Activer/désactiver le bouton de soumission
        if (prestataire && service && selectedDate && selectedTime) {
            $('.btn-submit').prop('disabled', false);
        } else {
            $('.btn-submit').prop('disabled', true);
        }
    }
    
    // Gestion de la soumission du formulaire
    $('.booking-form').on('submit', function(e) {
        e.preventDefault();
        
        // Afficher le chargement
        $('.form-actions').addClass('loading');
        $('.form-message').removeClass('error success').empty();
        
        // Récupérer les données du formulaire
        const formData = {
            prestataire_id: $('#prestataire').val(),
            service_id: $('#service').val(),
            date_rdv: selectedDate,
            heure_debut: selectedTime,
            client_nom: $('#nom').val(),
            client_email: $('#email').val(),
            client_telephone: $('#telephone').val(),
            notes: $('#notes').val()
        };
        
        // Envoyer les données à l'API REST
        $.ajax({
            url: calendrierRdvVars.restUrl + 'calendrier-rdv/v1/rendez-vous',
            method: 'POST',
            data: formData,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', calendrierRdvVars.nonce);
            },
            success: function(response) {
                if (response.success) {
                    // Afficher le message de succès
                    showMessage('Votre rendez-vous a bien été enregistré ! Un email de confirmation vous a été envoyé.', 'success');
                    
                    // Réinitialiser le formulaire
                    $('.booking-form')[0].reset();
                    selectedDate = null;
                    selectedTime = null;
                    currentServiceId = null;
                    $('.selected-date').text('Non sélectionnée');
                    $('.time-slots').empty();
                    $('.time-slot').removeClass('selected');
                } else {
                    showMessage('Une erreur est survenue lors de l\'enregistrement du rendez-vous.', 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Une erreur est survenue lors de l\'enregistrement du rendez-vous.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage(errorMessage, 'error');
            },
            complete: function() {
                $('.form-actions').removeClass('loading');
            }
        });
    });
    
    // Fonction utilitaire pour afficher les messages
    function showMessage(message, type = 'error') {
        const $message = $('.form-message');
        $message.removeClass('error success').addClass(type).text(message).show();
        
        // Masquer le message après 5 secondes
        setTimeout(function() {
            $message.fadeOut();
        }, 5000);
    }
    
    // Mettre à jour l'état du formulaire lors des changements
    $('#prestataire, #service').on('change', updateFormState);
    
    // Initialiser l'état du formulaire
    updateFormState();
});
