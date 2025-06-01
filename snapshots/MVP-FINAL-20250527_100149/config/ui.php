<?php
/**
 * Configuration de l'interface utilisateur pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

return [
    // Paramètres généraux de l'interface
    'general' => [
        'theme' => 'light', // light, dark, system
        'date_format' => get_option('date_format', 'd/m/Y'),
        'time_format' => get_option('time_format', 'H:i'),
        'timezone' => get_option('timezone_string', 'Europe/Paris'),
        'enable_rtl' => is_rtl(),
        'enable_animations' => true,
    ],
    
    // Paramètres du calendrier
    'calendar' => [
        'default_view' => 'month', // month, week, day, agenda
        'first_day' => 1, // 0 = dimanche, 1 = lundi, etc.
        'business_hours' => [
            'monday' => ['09:00', '18:00'],
            'tuesday' => ['09:00', '18:00'],
            'wednesday' => ['09:00', '18:00'],
            'thursday' => ['09:00', '18:00'],
            'friday' => ['09:00', '18:00'],
            'saturday' => [],
            'sunday' => [],
        ],
        'slot_duration' => '00:30:00', // Durée par défaut d'un créneau
        'slot_min_time' => '00:15:00', // Durée minimale d'un créneau
        'slot_max_time' => '04:00:00', // Durée maximale d'un créneau
        'min_time' => '08:00:00', // Heure minimale affichée
        'max_time' => '20:00:00', // Heure maximale affichée
        'scroll_time' => '09:00:00', // Heure de défilement initiale
        'snap_duration' => '00:15:00', // Durée d'alignement des créneaux
        'display_event_time' => true, // Afficher l'heure des événements
        'display_event_end' => true, // Afficher l'heure de fin des événements
    ],
    
    // Paramètres du formulaire de réservation
    'booking_form' => [
        'layout' => 'vertical', // vertical, horizontal, multi-step
        'show_service_selection' => true,
        'show_provider_selection' => true,
        'show_date_selection' => true,
        'show_time_selection' => true,
        'show_customer_details' => true,
        'show_notes_field' => true,
        'require_terms' => true,
        'terms_page_id' => 0,
        'privacy_policy_page_id' => 0,
        'min_date' => 0, // Jours minimum avant la réservation (0 = aujourd'hui)
        'max_date' => 90, // Jours maximum avant la réservation
        'min_time_before_booking' => 2, // Heures minimum avant la réservation
        'max_per_day' => 0, // Nombre maximum de réservations par jour (0 = illimité)
        'date_format' => 'dd/mm/yy',
        'time_format' => 'H:i',
        'show_remaining_slots' => true,
        'auto_confirm' => false, // Confirmation automatique des rendez-vous
        'enable_guest_booking' => true, // Autoriser les réservations sans compte
        'login_required' => false, // Connexion obligatoire
        'redirect_after_booking' => 'booking_details', // booking_details, booking_list, custom_url
        'redirect_url' => '',
    ],
    
    // Paramètres de la page de liste des rendez-vous
    'appointments_page' => [
        'title' => __('Mes rendez-vous', 'calendrier-rdv'),
        'status' => ['confirmed', 'pending'],
        'per_page' => 10,
        'show_id' => true,
        'show_date' => true,
        'show_time' => true,
        'show_service' => true,
        'show_provider' => true,
        'show_status' => true,
        'show_actions' => true,
        'allow_cancellation' => true,
        'allow_reschedule' => true,
        'cancellation_deadline' => '2', // Heures avant le rendez-vous
        'no_appointments_message' => __('Vous n\'avez aucun rendez-vous à venir.', 'calendrier-rdv'),
    ],
    
    // Paramètres de la page de profil
    'profile_page' => [
        'title' => __('Mon profil', 'calendrier-rdv'),
        'show_avatar' => true,
        'show_name' => true,
        'show_email' => true,
        'show_phone' => true,
        'show_address' => true,
        'show_bio' => true,
        'allow_editing' => true,
        'allow_password_change' => true,
    ],
    
    // Paramètres des messages
    'messages' => [
        'success' => [
            'appointment_created' => __('Votre rendez-vous a été enregistré avec succès !', 'calendrier-rdv'),
            'appointment_updated' => __('Votre rendez-vous a été mis à jour avec succès.', 'calendrier-rdv'),
            'appointment_cancelled' => __('Votre rendez-vous a été annulé avec succès.', 'calendrier-rdv'),
            'profile_updated' => __('Votre profil a été mis à jour avec succès.', 'calendrier-rdv'),
        ],
        'error' => [
            'general' => __('Une erreur est survenue. Veuillez réessayer.', 'calendrier-rdv'),
            'invalid_form' => __('Veuillez remplir correctement tous les champs requis.', 'calendrier-rdv'),
            'invalid_date' => __('La date sélectionnée est invalide.', 'calendrier-rdv'),
            'invalid_time' => __('L\'horaire sélectionné est invalide.', 'calendrier-rdv'),
            'slot_not_available' => __('Ce créneau n\'est plus disponible. Veuillez en choisir un autre.', 'calendrier-rdv'),
            'cancellation_not_allowed' => __('L\'annulation n\'est plus possible pour ce rendez-vous.', 'calendrier-rdv'),
            'reschedule_not_allowed' => __('Le report n\'est plus possible pour ce rendez-vous.', 'calendrier-rdv'),
        ],
    ],
    
    // Paramètres des vues du calendrier
    'views' => [
        'month' => [
            'buttonText' => __('Mois', 'calendrier-rdv'),
            'titleFormat' => 'MMMM YYYY',
            'fixedWeekCount' => false,
        ],
        'week' => [
            'buttonText' => __('Semaine', 'calendrier-rdv'),
            'titleFormat' => 'D MMMM YYYY',
            'columnHeaderFormat' => 'dddd D',
        ],
        'day' => [
            'buttonText' => __('Jour', 'calendrier-rdv'),
            'titleFormat' => 'dddd D MMMM YYYY',
        ],
        'agenda' => [
            'buttonText' => __('Agenda', 'calendrier-rdv'),
            'titleFormat' => 'D MMMM YYYY',
            'columnHeaderFormat' => 'ddd D',
        ],
    ],
    
    // Paramètres des événements
    'events' => [
        'colors' => [
            'confirmed' => '#28a745',
            'pending' => '#ffc107',
            'cancelled' => '#dc3545',
            'completed' => '#6c757d',
        ],
        'text_color' => '#ffffff',
        'border_radius' => '3px',
        'border_width' => '1px',
        'background_opacity' => 0.8,
    ],
    
    // Paramètres de personnalisation CSS/JS
    'customization' => [
        'enable_custom_css' => false,
        'custom_css' => '',
        'enable_custom_js' => false,
        'custom_js' => '',
        'load_bootstrap' => true,
        'load_font_awesome' => true,
        'load_select2' => true,
        'load_flatpickr' => true,
    ],
];
