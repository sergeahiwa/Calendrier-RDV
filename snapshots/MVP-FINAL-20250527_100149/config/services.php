<?php
/**
 * Configuration des services et prestataires pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

return [
    // Paramètres généraux des services
    'services' => [
        'default_duration' => 60, // Durée par défaut en minutes
        'min_duration' => 15, // Durée minimale en minutes
        'max_duration' => 240, // Durée maximale en minutes
        'padding_before' => 0, // Temps de battement avant le service (minutes)
        'padding_after' => 0, // Temps de battement après le service (minutes)
        'max_capacity' => 1, // Capacité maximale par créneau
        'require_confirmation' => false, // Nécessite une confirmation manuelle
        'allow_cancellation' => true, // Permettre l'annulation
        'cancellation_deadline' => 2, // Délai d'annulation en heures
        'allow_reschedule' => true, // Permettre le report
        'reschedule_deadline' => 2, // Délai de report en heures
        'buffer_before' => 0, // Temps tampon avant le service (minutes)
        'buffer_after' => 0, // Temps tampon après le service (minutes)
        'color' => '#3498db', // Couleur par défaut
        'text_color' => '#ffffff', // Couleur du texte
        'show_in_booking' => true, // Afficher dans le formulaire de réservation
        'require_note' => false, // Note obligatoire
        'note_placeholder' => __('Avez-vous des informations supplémentaires à nous communiquer ?', 'calendrier-rdv'),
    ],
    
    // Paramètres des prestataires
    'providers' => [
        'enable_multi_providers' => true, // Activer la gestion multi-prestataires
        'default_working_hours' => [
            'monday' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '14:00', 'end' => '18:00'],
            ],
            'tuesday' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '14:00', 'end' => '18:00'],
            ],
            'wednesday' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '14:00', 'end' => '18:00'],
            ],
            'thursday' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '14:00', 'end' => '18:00'],
            ],
            'friday' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '14:00', 'end' => '18:00'],
            ],
            'saturday' => [],
            'sunday' => [],
        ],
        'min_working_hours' => '06:00',
        'max_working_hours' => '22:00',
        'default_slot_duration' => 30, // Durée par défaut des créneaux en minutes
        'min_slot_duration' => 15, // Durée minimale des créneaux en minutes
        'max_slot_duration' => 240, // Durée maximale des créneaux en minutes
        'default_break_duration' => 60, // Durée par défaut des pauses en minutes
        'min_break_duration' => 15, // Durée minimale des pauses en minutes
        'max_break_duration' => 240, // Durée maximale des pauses en minutes
        'default_booking_window' => 0, // Fenêtre de réservation par défaut en jours (0 = illimitée)
        'min_booking_notice' => 2, // Délai de prévenance minimum en heures
        'max_booking_notice' => 90, // Délai de réservation maximum en jours
        'require_approval' => false, // Nécessite une approbation manuelle
        'allow_customer_cancellation' => true, // Permettre l'annulation par le client
        'cancellation_deadline' => 2, // Délai d'annulation en heures
        'allow_customer_reschedule' => true, // Permettre le report par le client
        'reschedule_deadline' => 2, // Délai de report en heures
        'show_email' => true, // Afficher l'email du prestataire
        'show_phone' => true, // Afficher le téléphone du prestataire
        'show_avatar' => true, // Afficher la photo du prestataire
        'show_bio' => true, // Afficher la biographie du prestataire
    ],
    
    // Paramètres des catégories de services
    'categories' => [
        'enable_categories' => true, // Activer les catégories de services
        'hierarchical' => true, // Catégories hiérarchiques
        'show_in_booking' => true, // Afficher les catégories dans le formulaire de réservation
        'show_count' => true, // Afficher le nombre de services par catégorie
        'enable_filtering' => true, // Activer le filtrage par catégorie
        'default_thumbnail' => CAL_RDV_PLUGIN_URL . 'assets/images/default-service.jpg', // Image par défaut
    ],
    
    // Paramètres des ressources (salles, équipements, etc.)
    'resources' => [
        'enable_resources' => true, // Activer la gestion des ressources
        'require_resources' => false, // Les ressources sont-elles obligatoires ?
        'multiple_resources' => true, // Peut-on sélectionner plusieurs ressources ?
        'max_resources' => 0, // Nombre maximum de ressources sélectionnables (0 = illimité)
        'show_capacity' => true, // Afficher la capacité des ressources
        'enable_categories' => true, // Activer les catégories de ressources
        'default_thumbnail' => CAL_RDV_PLUGIN_URL . 'assets/images/default-resource.jpg', // Image par défaut
    ],
    
    // Paramètres des extras (options supplémentaires)
    'extras' => [
        'enable_extras' => true, // Activer les extras
        'multiple_extras' => true, // Peut-on sélectionner plusieurs extras ?
        'max_extras' => 0, // Nombre maximum d'extras sélectionnables (0 = illimité)
        'show_price' => true, // Afficher les prix des extras
        'show_duration' => true, // Afficher la durée des extras
        'show_quantity' => true, // Afficher le sélecteur de quantité
        'max_quantity' => 10, // Quantité maximale sélectionnable
    ],
    
    // Paramètres des forfaits et abonnements
    'packages' => [
        'enable_packages' => true, // Activer les forfaits
        'expiration_type' => 'months', // Type d'expiration (days, weeks, months, years)
        'expiration_length' => 12, // Durée d'expiration
        'expiration_unit' => 'months', // Unité d'expiration (day, week, month, year)
        'enable_renewal' => true, // Activer le renouvellement
        'renewal_days_before' => 7, // Jours avant l'expiration pour le rappel de renouvellement
        'enable_grace_period' => true, // Activer la période de grâce
        'grace_period_days' => 7, // Durée de la période de grâce en jours
    ],
    
    // Paramètres des tarifs
    'pricing' => [
        'currency' => 'EUR',
        'currency_position' => 'right', // left, right, left_space, right_space
        'thousand_separator' => ' ',
        'decimal_separator' => ',',
        'number_of_decimals' => 2,
        'price_display_suffix' => '', // Ex: 'TTC' ou 'HT'
        'enable_taxes' => false,
        'tax_rate' => 20, // Taux de TVA par défaut
        'tax_inclusive' => true, // Les prix sont-ils TTC ?
        'enable_coupons' => true, // Activer les codes promo
        'enable_deposit' => true, // Activer les acomptes
        'deposit_type' => 'percentage', // percentage, fixed
        'deposit_amount' => 50, // Montant ou pourcentage de l'acompte
        'enable_tip' => true, // Activer les pourboires
        'tip_amounts' => [5, 10, 15, 20], // Montants suggérés
        'custom_tip' => true, // Permettre un montant personnalisé
    ],
    
    // Paramètres des disponibilités
    'availability' => [
        'enable_google_calendar' => false, // Activer la synchronisation avec Google Calendar
        'google_client_id' => '',
        'google_client_secret' => '',
        'google_calendar_id' => '',
        'enable_outlook_calendar' => false, // Activer la synchronisation avec Outlook Calendar
        'outlook_client_id' => '',
        'outlook_client_secret' => '',
        'outlook_calendar_id' => '',
        'enable_ical_feed' => true, // Activer le flux iCal
        'ical_feed_url' => home_url('/?cal_rdv_feed=ical'),
        'enable_webhooks' => true, // Activer les webhooks
        'webhook_url' => '',
        'enable_buffer_time' => true, // Activer les temps de battement
        'buffer_before' => 0, // Temps de battement avant un rendez-vous (minutes)
        'buffer_after' => 0, // Temps de battement après un rendez-vous (minutes)
        'enable_minimum_notice' => true, // Activer le délai de prévenance minimum
        'minimum_notice' => 2, // Délai de prévenance minimum en heures
        'enable_maximum_notice' => true, // Activer le délai de réservation maximum
        'maximum_notice' => 90, // Délai de réservation maximum en jours
    ],
];
