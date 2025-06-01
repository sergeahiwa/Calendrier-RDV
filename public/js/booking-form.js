jQuery(document).ready(function($) {
    // Variables globales
    let currentStep = 1;
    let selectedService = null;
    let selectedProvider = null;
    let selectedDate = null;
    let selectedTime = null;
    let availableSlots = [];
    
    // Initialisation du calendrier
    const calendar = $('#booking-calendar').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 0,
        beforeShowDay: function(date) {
            // Désactiver les dimanches
            const day = date.getDay();
            return [day !== 0];
        },
        onSelect: function(dateText) {
            selectedDate = dateText;
            updateSelectedDateDisplay();
            loadAvailableTimeSlots();
            updateNextButtonState();
        }
    });
    
    // Gestion des clics sur les cartes de service
    $('.service-card').on('click', function() {
        $('.service-card').removeClass('selected');
        $(this).addClass('selected');
        selectedService = $(this).data('service-id');
        $('.btn-next').prop('disabled', false);
    });
    
    // Gestion des clics sur les boutons Suivant/Précédent
    $('.btn-next').on('click', function() {
        if ($(this).is(':disabled')) return;
        
        // Validation avant de passer à l'étape suivante
        if (currentStep === 1 && !selectedService) return;
        if (currentStep === 2 && !selectedProvider) return;
        if (currentStep === 3 && (!selectedDate || !selectedTime)) return;
        
        // Mise à jour de l'interface
        $(`#step-${currentStep}`).hide();
        currentStep++;
        $(`#step-${currentStep}`).show();
        
        // Chargement des données si nécessaire
        if (currentStep === 2) {
            loadProvidersForService();
        } else if (currentStep === 4) {
            updateBookingSummary();
        }
        
        // Désactiver le bouton Suivant si nécessaire
        if (currentStep === 5) {
            $('.btn-next').hide();
            submitBooking();
        } else {
            $('.btn-next').show().prop('disabled', true);
        }
    });
    
    $('.btn-prev').on('click', function() {
        $(`#step-${currentStep}`).hide();
        currentStep--;
        $(`#step-${currentStep}`).show();
        $('.btn-next').show().prop('disabled', false);
    });
    
    // Fonction pour charger les prestataires pour un service
    function loadProvidersForService() {
        if (!selectedService) return;
        
        $('#providers-container').html('<p class="loading-text">Chargement des prestataires disponibles...</p>');
        
        // Simulation de chargement (à remplacer par un appel AJAX réel)
        setTimeout(() => {
            // Ici, vous feriez un appel AJAX pour récupérer les prestataires
            // Pour l'exemple, on utilise des données factices
            const mockProviders = [
                { id: 1, name: 'Dr. Dupont', specialty: 'Médecin généraliste', rating: 4.8 },
                { id: 2, name: 'Dr. Martin', specialty: 'Dentiste', rating: 4.9 }
            ];
            
            let html = '';
            mockProviders.forEach(provider => {
                html += `
                    <div class="provider-card" data-provider-id="${provider.id}">
                        <img src="${calendrierRdvVars.pluginUrl}assets/images/avatar-placeholder.png" alt="${provider.name}" class="provider-avatar">
                        <div class="provider-info">
                            <h4>${provider.name}</h4>
                            <p class="provider-specialty">${provider.specialty}</p>
                            <div class="provider-rating">
                                ${'★'.repeat(Math.floor(provider.rating))}${'☆'.repeat(5-Math.floor(provider.rating))} (${provider.rating.toFixed(1)})
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#providers-container').html(html || '<p class="info-message">Aucun prestataire disponible pour ce service.</p>');
            
            // Gestion de la sélection d'un prestataire
            $('.provider-card').on('click', function() {
                $('.provider-card').removeClass('selected');
                $(this).addClass('selected');
                selectedProvider = $(this).data('provider-id');
                $('.btn-next').prop('disabled', false);
            });
            
        }, 800);
    }
    
    // Fonction pour charger les créneaux disponibles
    function loadAvailableTimeSlots() {
        if (!selectedDate || !selectedProvider) return;
        
        $('#available-slots').html('<p class="loading-text">Chargement des créneaux disponibles...</p>');
        
        // Simulation de chargement (à remplacer par un appel AJAX réel)
        setTimeout(() => {
            // Ici, vous feriez un appel AJAX pour récupérer les créneaux disponibles
            // Pour l'exemple, on génère des créneaux factices
            const slots = [];
            const startHour = 9; // 9h
            const endHour = 18;  // 18h
            
            for (let hour = startHour; hour < endHour; hour++) {
                // Créneaux toutes les 30 minutes
                ['00', '30'].forEach(minutes => {
                    // Ne pas afficher les créneaux passés pour aujourd'hui
                    const now = new Date();
                    const slotDate = new Date(selectedDate);
                    const [year, month, day] = selectedDate.split('-');
                    const slotTime = new Date(year, month - 1, day, hour, minutes);
                    
                    if (now < slotTime) {
                        slots.push({
                            time: `${hour}:${minutes}`,
                            available: Math.random() > 0.3 // 70% de disponibilité pour l'exemple
                        });
                    }
                });
            }
            
            if (slots.length === 0) {
                $('#available-slots').html('<p class="info-message">Aucun créneau disponible pour cette date.</p>');
                return;
            }
            
            let html = '';
            slots.forEach(slot => {
                const timeClass = slot.available ? 'time-slot' : 'time-slot unavailable';
                html += `<div class="${timeClass}" data-time="${slot.time}">${slot.time}</div>`;
            });
            
            $('#available-slots').html(html);
            
            // Gestion de la sélection d'un créneau
            $('.time-slot:not(.unavailable)').on('click', function() {
                $('.time-slot').removeClass('selected');
                $(this).addClass('selected');
                selectedTime = $(this).data('time');
                updateNextButtonState();
            });
            
        }, 800);
    }
    
    // Mise à jour de l'affichage de la date sélectionnée
    function updateSelectedDateDisplay() {
        if (selectedDate) {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateObj = new Date(selectedDate);
            const formattedDate = dateObj.toLocaleDateString('fr-FR', options);
            $('.selected-date-display').html(`<p>Date sélectionnée : <strong>${formattedDate}</strong></p>`);
        }
    }
    
    // Mise à jour de l'état du bouton Suivant
    function updateNextButtonState() {
        if (currentStep === 3) {
            $('.btn-next').prop('disabled', !(selectedDate && selectedTime));
        }
    }
    
    // Mise à jour du récapitulatif
    function updateBookingSummary() {
        if (selectedService) {
            const serviceName = $(`.service-card[data-service-id="${selectedService}"] h3`).text();
            $('#selected-service').text(serviceName);
            $('#confirmation-service').text(serviceName);
        }
        
        if (selectedProvider) {
            // Dans une vraie implémentation, vous récupéreriez le nom du prestataire depuis la réponse AJAX
            const providerName = $('.provider-card.selected h4').text();
            $('#selected-provider').text(providerName);
            $('#confirmation-provider').text(providerName);
        }
        
        if (selectedDate) {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateObj = new Date(selectedDate);
            const formattedDate = dateObj.toLocaleDateString('fr-FR', options);
            $('#selected-date').text(formattedDate);
            $('#confirmation-date').text(formattedDate);
        }
        
        if (selectedTime) {
            $('#selected-time').text(selectedTime);
            $('#confirmation-time').text(selectedTime);
        }
        
        // Mettre à jour l'email de confirmation avec la valeur du champ email
        const userEmail = $('#email').val();
        if (userEmail) {
            $('#confirmation-email').text(userEmail);
        }
    }
    
    // Soumission du formulaire
    function submitBooking() {
        // Récupérer les données du formulaire
        const formData = {
            service_id: selectedService,
            provider_id: selectedProvider,
            date: selectedDate,
            time: selectedTime,
            customer_name: $('#nom').val(),
            customer_email: $('#email').val(),
            customer_phone: $('#telephone').val(),
            notes: $('#notes').val(),
            nonce: $('input[name="calendrier_rdv_nonce"]').val()
        };
        
        // Désactiver le bouton de soumission
        $('.btn-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traitement...');
        
        // Envoyer les données au serveur
        $.ajax({
            url: calendrierRdvVars.ajaxUrl,
            type: 'POST',
            data: {
                action: 'calendrier_rdv_submit_booking',
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    // Afficher le numéro de réservation
                    $('#booking-number').text(response.data.booking_id || 'N/A');
                    
                    // Afficher l'étape de confirmation
                    currentStep = 5;
                    $(`#step-4`).hide();
                    $(`#step-5`).show();
                    
                    // Faire défiler vers le haut
                    $('html, body').animate({
                        scrollTop: $('.calendrier-rdv-container').offset().top - 20
                    }, 500);
                } else {
                    // Afficher l'erreur
                    alert(response.data.message || 'Une erreur est survenue. Veuillez réessayer.');
                    $('.btn-submit').prop('disabled', false).text('Confirmer le rendez-vous');
                }
            },
            error: function() {
                alert('Une erreur est survenue lors de la communication avec le serveur. Veuillez réessayer.');
                $('.btn-submit').prop('disabled', false).text('Confirmer le rendez-vous');
            }
        });
    }
    
    // Gestion de l'impression
    $('#print-booking').on('click', function(e) {
        e.preventDefault();
        window.print();
    });
    
    // Gestion de la soumission du formulaire
    $('#calendrier-rdv-form').on('submit', function(e) {
        e.preventDefault();
        
        // Vérifier la validité du formulaire
        if (!$(this)[0].checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }
        
        // Soumettre le formulaire
        submitBooking();
    });
});
