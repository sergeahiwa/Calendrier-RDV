<?php
/**
 * Fichier de configuration par défaut pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

return [
    // Paramètres généraux
    'general' => [
        'timezone' => 'Europe/Paris',
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'start_of_week' => 1, // 1 pour lundi, 0 pour dimanche
    ],
    
    // Paramètres des rendez-vous
    'appointments' => [
        'min_advance_booking' => 2, // En heures
        'max_advance_booking' => 90, // En jours
        'default_duration' => 30, // En minutes
        'min_duration' => 15, // En minutes
        'max_duration' => 240, // En minutes
        'time_slot_step' => 15, // En minutes
        'buffer_before' => 15, // En minutes
        'buffer_after' => 15, // En minutes
        'cancellation_policy' => 24, // En heures
    ],
    
    // Paramètres des horaires d'ouverture
    'business_hours' => [
        'monday' => [
            'open' => true,
            'start' => '09:00',
            'end' => '18:00',
        ],
        'tuesday' => [
            'open' => true,
            'start' => '09:00',
            'end' => '18:00',
        ],
        'wednesday' => [
            'open' => true,
            'start' => '09:00',
            'end' => '18:00',
        ],
        'thursday' => [
            'open' => true,
            'start' => '09:00',
            'end' => '18:00',
        ],
        'friday' => [
            'open' => true,
            'start' => '09:00',
            'end' => '18:00',
        ],
        'saturday' => [
            'open' => false,
            'start' => '09:00',
            'end' => '12:00',
        ],
        'sunday' => [
            'open' => false,
            'start' => '09:00',
            'end' => '12:00',
        ],
    ],
    
    // Paramètres des notifications
    'notifications' => [
        'admin_email' => get_bloginfo('admin_email'),
        'from_name' => get_bloginfo('name'),
        'from_email' => get_bloginfo('admin_email'),
        'reply_to' => get_bloginfo('admin_email'),
        'admin_notification' => true,
        'customer_notification' => true,
        'reminder_enabled' => true,
        'reminder_time' => '24', // En heures avant le rendez-vous
        'reminder_subject' => 'Rappel : Votre rendez-vous du {appointment_date}',
        'confirmation_subject' => 'Confirmation de votre rendez-vous du {appointment_date}',
        'cancellation_subject' => 'Annulation de votre rendez-vous du {appointment_date}',
    ],
    
    // Paramètres d'affichage
    'display' => [
        'show_service_description' => true,
        'show_provider_photo' => true,
        'show_provider_bio' => true,
        'show_location' => true,
        'show_map' => true,
        'show_ical_export' => true,
        'show_google_calendar' => true,
        'show_week_numbers' => true,
        'show_past_days' => false,
    ],
    
    // Paramètres de paiement
    'payments' => [
        'enabled' => false,
        'currency' => 'EUR',
        'currency_position' => 'right',
        'thousand_separator' => ' ',
        'decimal_separator' => ',',
        'number_of_decimals' => 2,
        'deposit' => 0, // Pourcentage
        'payment_methods' => [
            'bacs' => [
                'enabled' => true,
                'title' => 'Virement bancaire',
                'description' => 'Effectuez un virement directement sur notre compte bancaire.',
                'instructions' => 'Les détails du virement vous seront fournis après la confirmation de votre commande.',
            ],
            'cheque' => [
                'enabled' => true,
                'title' => 'Chèque',
                'description' => 'Envoyez-nous un chèque à l\'adresse de notre siège social.',
                'instructions' => 'Veuillez libeller votre chèque à l\'ordre de ' . get_bloginfo('name') . '.',
            ],
        ],
    ],
    
    // Paramètres d'intégration
    'integrations' => [
        'google_calendar' => [
            'enabled' => false,
            'client_id' => '',
            'client_secret' => '',
            'calendar_id' => '',
        ],
        'mailchimp' => [
            'enabled' => false,
            'api_key' => '',
            'list_id' => '',
        ],
    ],
];
