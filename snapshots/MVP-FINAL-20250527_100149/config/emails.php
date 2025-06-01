<?php
/**
 * Configuration des modèles d'emails pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

return [
    // En-tête et pied de page des emails
    'header' => [
        'template' => 'emails/header',
        'logo' => CAL_RDV_ASSETS_URL . 'images/logo-email.png',
        'logo_alt' => get_bloginfo('name'),
        'logo_width' => 200,
        'logo_height' => 80,
        'header_bg_color' => '#f5f5f5',
        'header_text_color' => '#333333',
    ],
    
    'footer' => [
        'template' => 'emails/footer',
        'footer_text' => sprintf(
            __('&copy; %1$s %2$s. Tous droits réservés.', 'calendrier-rdv'),
            date('Y'),
            get_bloginfo('name')
        ),
        'footer_links' => [
            [
                'url' => home_url('/mentions-legales/'),
                'text' => __('Mentions légales', 'calendrier-rdv'),
            ],
            [
                'url' => home_url('/confidentialite/'),
                'text' => __('Confidentialité', 'calendrier-rdv'),
            ],
            [
                'url' => home_url('/contact/'),
                'text' => __('Contact', 'calendrier-rdv'),
            ],
        ],
        'footer_bg_color' => '#f5f5f5',
        'footer_text_color' => '#777777',
    ],
    
    // Modèles d'emails
    'templates' => [
        // Confirmation de rendez-vous
        'appointment_confirmation' => [
            'subject' => __('Confirmation de votre rendez-vous du {appointment_date}', 'calendrier-rdv'),
            'heading' => __('Confirmation de rendez-vous', 'calendrier-rdv'),
            'template' => 'emails/appointment-confirmation',
            'recipient' => 'customer',
            'attachments' => [],
            'placeholders' => [
                '{appointment_id}',
                '{appointment_date}',
                '{appointment_time}',
                '{appointment_duration}',
                '{service_name}',
                '{provider_name}',
                '{customer_name}',
                '{customer_email}',
                '{customer_phone}',
                '{location}',
                '{site_name}',
                '{site_url}',
                '{admin_email}',
            ],
        ],
        
        // Rappel de rendez-vous
        'appointment_reminder' => [
            'subject' => __('Rappel : Votre rendez-vous du {appointment_date}', 'calendrier-rdv'),
            'heading' => __('Rappel de rendez-vous', 'calendrier-rdv'),
            'template' => 'emails/appointment-reminder',
            'recipient' => 'customer',
            'attachments' => [],
            'placeholders' => [
                '{appointment_id}',
                '{appointment_date}',
                '{appointment_time}',
                '{appointment_duration}',
                '{service_name}',
                '{provider_name}',
                '{customer_name}',
                '{location}',
                '{cancel_url}',
                '{site_name}',
                '{site_url}',
            ],
        ],
        
        // Annulation de rendez-vous
        'appointment_cancellation' => [
            'subject' => __('Annulation de votre rendez-vous du {appointment_date}', 'calendrier-rdv'),
            'heading' => __('Rendez-vous annulé', 'calendrier-rdv'),
            'template' => 'emails/appointment-cancellation',
            'recipient' => 'both',
            'attachments' => [],
            'placeholders' => [
                '{appointment_id}',
                '{appointment_date}',
                '{appointment_time}',
                '{service_name}',
                '{provider_name}',
                '{customer_name}',
                '{cancellation_reason}',
                '{site_name}',
                '{site_url}',
            ],
        ],
        
        // Notification admin - Nouveau rendez-vous
        'admin_new_appointment' => [
            'subject' => __('[{site_name}] Nouveau rendez-vous : {service_name}', 'calendrier-rdv'),
            'heading' => __('Nouveau rendez-vous', 'calendrier-rdv'),
            'template' => 'emails/admin-new-appointment',
            'recipient' => 'admin',
            'attachments' => [],
            'placeholders' => [
                '{appointment_id}',
                '{appointment_date}',
                '{appointment_time}',
                '{service_name}',
                '{provider_name}',
                '{customer_name}',
                '{customer_email}',
                '{customer_phone}',
                '{customer_notes}',
                '{location}',
                '{site_name}',
                '{site_url}',
            ],
        ],
        
        // Notification admin - Rendez-vous annulé
        'admin_appointment_cancelled' => [
            'subject' => __('[{site_name}] Rendez-vous annulé : {service_name}', 'calendrier-rdv'),
            'heading' => __('Rendez-vous annulé', 'calendrier-rdv'),
            'template' => 'emails/admin-appointment-cancelled',
            'recipient' => 'admin',
            'attachments' => [],
            'placeholders' => [
                '{appointment_id}',
                '{appointment_date}',
                '{appointment_time}',
                '{service_name}',
                '{provider_name}',
                '{customer_name}',
                '{cancellation_reason}',
                '{site_name}',
                '{site_url}',
            ],
        ],
        
        // Notification prestataire - Nouveau rendez-vous
        'provider_new_appointment' => [
            'subject' => __('[{site_name}] Nouveau rendez-vous : {service_name}', 'calendrier-rdv'),
            'heading' => __('Nouveau rendez-vous', 'calendrier-rdv'),
            'template' => 'emails/provider-new-appointment',
            'recipient' => 'provider',
            'attachments' => [],
            'placeholders' => [
                '{appointment_id}',
                '{appointment_date}',
                '{appointment_time}',
                '{appointment_duration}',
                '{service_name}',
                '{provider_name}',
                '{customer_name}',
                '{customer_email}',
                '{customer_phone}',
                '{customer_notes}',
                '{location}',
                '{site_name}',
                '{site_url}',
            ],
        ],
        
        // Notification prestataire - Rappel de rendez-vous
        'provider_appointment_reminder' => [
            'subject' => __('[Rappel] Rendez-vous demain : {service_name}', 'calendrier-rdv'),
            'heading' => __('Rappel de rendez-vous', 'calendrier-rdv'),
            'template' => 'emails/provider-appointment-reminder',
            'recipient' => 'provider',
            'attachments' => [],
            'placeholders' => [
                '{appointment_id}',
                '{appointment_date}',
                '{appointment_time}',
                '{appointment_duration}',
                '{service_name}',
                '{provider_name}',
                '{customer_name}',
                '{customer_phone}',
                '{customer_notes}',
                '{location}',
                '{site_name}',
                '{site_url}',
            ],
        ],
    ],
    
    // Paramètres d'envoi des emails
    'sending' => [
        'from_name' => get_bloginfo('name'),
        'from_email' => get_bloginfo('admin_email'),
        'content_type' => 'text/html',
        'charset' => get_bloginfo('charset'),
        'send_as_html' => true,
        'send_as_plain_text' => false,
        'disable_emails' => false,
    ],
    
    // Paramètres de rappel
    'reminders' => [
        'enabled' => true,
        'time' => '24', // Heures avant le rendez-vous
        'subject' => __('Rappel : Votre rendez-vous du {appointment_date}', 'calendrier-rdv'),
        'send_to_admin' => false,
        'send_to_provider' => true,
    ],
    
    // Paramètres de notification
    'notifications' => [
        'admin_new_appointment' => true,
        'admin_cancelled_appointment' => true,
        'provider_new_appointment' => true,
        'provider_appointment_reminder' => true,
        'customer_confirmation' => true,
        'customer_reminder' => true,
        'customer_cancellation' => true,
    ],
];
