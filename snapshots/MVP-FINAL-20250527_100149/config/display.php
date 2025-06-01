<?php
/**
 * Configuration de l'affichage pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

return [
    // Paramètres généraux d'affichage
    'general' => [
        'theme' => 'light', // light, dark, system
        'date_format' => get_option('date_format', 'd/m/Y'),
        'time_format' => get_option('time_format', 'H:i'),
        'start_of_week' => get_option('start_of_week', 1), // 0 = dimanche, 1 = lundi, etc.
        'timezone' => get_option('timezone_string', 'Europe/Paris'),
        'enable_rtl' => is_rtl(),
    ],
    
    // Paramètres du calendrier
    'calendar' => [
        'default_view' => 'month', // month, week, day, agenda
        'first_hour' => 8, // Première heure affichée (0-23)
        'last_hour' => 20, // Dernière heure affichée (0-23)
        'time_slot_duration' => '00:30:00', // Durée d'un créneau (format H:i:s)
        'min_time' => '00:00:00', // Heure minimale sélectionnable
        'max_time' => '24:00:00', // Heure maximale sélectionnable
        'slot_min_time' => '00:15:00', // Durée minimale d'un créneau
        'slot_max_time' => '04:00:00', // Durée maximale d'un créneau
        'show_weekends' => true,
        'business_hours' => [
            'monday' => ['09:00', '18:00'],
            'tuesday' => ['09:00', '18:00'],
            'wednesday' => ['09:00', '18:00'],
            'thursday' => ['09:00', '18:00'],
            'friday' => ['09:00', '18:00'],
            'saturday' => [],
            'sunday' => [],
        ],
        'day_max_events' => 4, // Nombre maximum d'événements affichés par jour (0 = illimité)
        'event_limit' => 3, // Nombre d'événements avant "+ X de plus"
        'event_limit_click' => 'popover', // popover, week, day, timeGridWeek, timeGridDay, false
        'display_event_time' => true,
        'display_event_end' => true,
        'event_time_format' => [ // Format d'heure des événements
            'hour' => 'numeric',
            'minute' => '2-digit',
            'meridiem' => 'short',
            'hour12' => false,
        ],
        'header_toolbar' => [
            'left' => 'title',
            'center' => '',
            'right' => 'today prev,next',
        ],
        'views' => [
            'timeGridDay' => [
                'type' => 'timeGrid',
                'duration' => ['days' => 1],
                'buttonText' => __('Jour', 'calendrier-rdv'),
                'slotDuration' => '00:30:00',
            ],
            'timeGridWeek' => [
                'type' => 'timeGrid',
                'duration' => ['weeks' => 1],
                'buttonText' => __('Semaine', 'calendrier-rdv'),
                'slotDuration' => '01:00:00',
            ],
            'dayGridMonth' => [
                'type' => 'dayGrid',
                'duration' => ['months' => 1],
                'buttonText' => __('Mois', 'calendrier-rdv'),
                'fixedWeekCount' => false,
            ],
            'listWeek' => [
                'type' => 'list',
                'duration' => ['weeks' => 1],
                'buttonText' => __('Liste', 'calendrier-rdv'),
                'listDayFormat' => 'dddd D MMMM YYYY',
            ],
        ],
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
        'first_day' => 1, // 0 = dimanche, 1 = lundi, etc.
        'show_remaining_slots' => true,
        'auto_confirm' => false, // Confirmation automatique des rendez-vous
        'enable_guest_booking' => true, // Autoriser les réservations sans compte
        'login_required' => false, // Connexion obligatoire
        'redirect_after_booking' => 'booking_details', // booking_details, booking_list, custom_url
        'redirect_url' => '',
        'success_message' => __('Votre rendez-vous a été enregistré avec succès !', 'calendrier-rdv'),
        'error_message' => __('Une erreur est survenue lors de la réservation. Veuillez réessayer.', 'calendrier-rdv'),
    ],
    
    // Paramètres des couleurs
    'colors' => [
        'primary' => '#3498db',
        'secondary' => '#2ecc71',
        'success' => '#27ae60',
        'info' => '#3498db',
        'warning' => '#f39c12',
        'danger' => '#e74c3c',
        'light' => '#f8f9fa',
        'dark' => '#343a40',
        'background' => '#ffffff',
        'text' => '#212529',
        'muted' => '#6c757d',
        'border' => '#dee2e6',
    ],
    
    // Paramètres des événements
    'events' => [
        'default_duration' => '01:00:00',
        'min_duration' => '00:15:00',
        'max_duration' => '24:00:00',
        'default_color' => '#3498db',
        'text_color' => '#ffffff',
        'border_color' => 'rgba(0,0,0,0.1)',
        'background_opacity' => 0.8,
        'border_radius' => '3px',
        'border_width' => '1px',
        'display_duration' => true,
        'display_location' => true,
        'display_description' => false,
        'display_provider' => true,
        'display_service' => true,
    ],
    
    // Paramètres des notifications
    'notifications' => [
        'enable_admin_notifications' => true,
        'admin_email' => get_bloginfo('admin_email'),
        'enable_customer_notifications' => true,
        'enable_provider_notifications' => true,
        'enable_reminders' => true,
        'reminder_time' => '24', // Heures avant le rendez-vous
        'reminder_type' => 'email', // email, sms, both
        'enable_follow_up' => true,
        'follow_up_time' => '24', // Heures après le rendez-vous
        'enable_cancellation' => true,
        'cancellation_deadline' => '2', // Heures avant le rendez-vous
        'enable_reschedule' => true,
        'reschedule_deadline' => '2', // Heures avant le rendez-vous
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
];
