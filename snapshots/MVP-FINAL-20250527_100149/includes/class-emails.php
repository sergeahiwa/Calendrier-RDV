<?php
/**
 * Classe de gestion des emails pour le plugin Calendrier RDV
 *
 * @package CalendrierRdv
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe Emails pour gérer les notifications par email
 */
class CalRdv_Emails {
    
    /**
     * Instance unique de la classe
     *
     * @var CalRdv_Emails
     */
    private static $instance = null;
    
    /**
     * Constructeur
     */
    private function __construct() {
        // Initialisation
    }
    
    /**
     * Obtenir l'instance unique de la classe
     *
     * @return CalRdv_Emails
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Envoyer un email de confirmation de rendez-vous au client
     *
     * @param int $appointment_id ID du rendez-vous
     * @return bool Succès de l'envoi
     */
    public function send_confirmation_email($appointment_id) {
        global $wpdb;
        
        // Récupérer les informations du rendez-vous
        $appointment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, p.name as provider_name, s.name as service_name 
                 FROM {$wpdb->prefix}calendrier_rdv_appointments a
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_providers p ON a.provider_id = p.id
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_services s ON a.service_id = s.id
                 WHERE a.id = %d",
                $appointment_id
            )
        );
        
        if (!$appointment) {
            return false;
        }
        
        // Destinataire
        $to = $appointment->customer_email;
        
        // Sujet
        $subject = sprintf(
            // translators: %s: Formatted appointment date.
            __('Confirmation de votre rendez-vous du %s', 'calendrier-rdv'),
            calendrier_rdv_format_date($appointment->appointment_date)
        );
        
        // En-têtes
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );
        
        // Corps du message
        $message = $this->get_email_template('confirmation');
        
        // Remplacer les variables
        $message = str_replace('{customer_name}', $appointment->customer_name, $message);
        $message = str_replace('{appointment_date}', calendrier_rdv_format_date($appointment->appointment_date), $message);
        $message = str_replace('{appointment_time}', calendrier_rdv_format_date($appointment->appointment_date, 'time'), $message);
        $message = str_replace('{provider_name}', $appointment->provider_name, $message);
        $message = str_replace('{service_name}', $appointment->service_name, $message);
        $message = str_replace('{duration}', $appointment->duration, $message);
        $message = str_replace('{site_name}', get_bloginfo('name'), $message);
        $message = str_replace('{site_url}', get_bloginfo('url'), $message);
        
        // Envoyer l'email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Enregistrer la notification
        if ($sent) {
            $wpdb->insert(
                $wpdb->prefix . 'calendrier_rdv_notifications',
                array(
                    'appointment_id' => $appointment_id,
                    'type' => 'confirmation',
                    'recipient' => $to,
                    'sent_at' => current_time('mysql'),
                    'status' => 'sent'
                )
            );
        }
        
        return $sent;
    }
    
    /**
     * Envoyer un email de rappel de rendez-vous au client
     *
     * @param int $appointment_id ID du rendez-vous
     * @return bool Succès de l'envoi
     */
    public function send_reminder_email($appointment_id) {
        global $wpdb;
        
        // Récupérer les informations du rendez-vous
        $appointment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, p.name as provider_name, s.name as service_name 
                 FROM {$wpdb->prefix}calendrier_rdv_appointments a
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_providers p ON a.provider_id = p.id
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_services s ON a.service_id = s.id
                 WHERE a.id = %d",
                $appointment_id
            )
        );
        
        if (!$appointment) {
            return false;
        }
        
        // Destinataire
        $to = $appointment->customer_email;
        
        // Sujet
        $subject = sprintf(
            // translators: %s: Formatted appointment date.
            __('Rappel : votre rendez-vous du %s', 'calendrier-rdv'),
            calendrier_rdv_format_date($appointment->appointment_date)
        );
        
        // En-têtes
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );
        
        // Corps du message
        $message = $this->get_email_template('reminder');
        
        // Remplacer les variables
        $message = str_replace('{customer_name}', $appointment->customer_name, $message);
        $message = str_replace('{appointment_date}', calendrier_rdv_format_date($appointment->appointment_date), $message);
        $message = str_replace('{appointment_time}', calendrier_rdv_format_date($appointment->appointment_date, 'time'), $message);
        $message = str_replace('{provider_name}', $appointment->provider_name, $message);
        $message = str_replace('{service_name}', $appointment->service_name, $message);
        $message = str_replace('{duration}', $appointment->duration, $message);
        $message = str_replace('{site_name}', get_bloginfo('name'), $message);
        $message = str_replace('{site_url}', get_bloginfo('url'), $message);
        
        // Envoyer l'email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Enregistrer la notification
        if ($sent) {
            $wpdb->insert(
                $wpdb->prefix . 'calendrier_rdv_notifications',
                array(
                    'appointment_id' => $appointment_id,
                    'type' => 'reminder',
                    'recipient' => $to,
                    'sent_at' => current_time('mysql'),
                    'status' => 'sent'
                )
            );
        }
        
        return $sent;
    }
    
    /**
     * Envoyer un email d'annulation de rendez-vous au client
     *
     * @param int $appointment_id ID du rendez-vous
     * @return bool Succès de l'envoi
     */
    public function send_cancellation_email($appointment_id) {
        global $wpdb;
        
        // Récupérer les informations du rendez-vous
        $appointment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, p.name as provider_name, s.name as service_name 
                 FROM {$wpdb->prefix}calendrier_rdv_appointments a
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_providers p ON a.provider_id = p.id
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_services s ON a.service_id = s.id
                 WHERE a.id = %d",
                $appointment_id
            )
        );
        
        if (!$appointment) {
            return false;
        }
        
        // Destinataire
        $to = $appointment->customer_email;
        
        // Sujet
        $subject = sprintf(
            // translators: %s: Formatted appointment date.
            __('Annulation de votre rendez-vous du %s', 'calendrier-rdv'),
            calendrier_rdv_format_date($appointment->appointment_date)
        );
        
        // En-têtes
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );
        
        // Corps du message
        $message = $this->get_email_template('cancellation');
        
        // Remplacer les variables
        $message = str_replace('{customer_name}', $appointment->customer_name, $message);
        $message = str_replace('{appointment_date}', calendrier_rdv_format_date($appointment->appointment_date), $message);
        $message = str_replace('{appointment_time}', calendrier_rdv_format_date($appointment->appointment_date, 'time'), $message);
        $message = str_replace('{provider_name}', $appointment->provider_name, $message);
        $message = str_replace('{service_name}', $appointment->service_name, $message);
        $message = str_replace('{site_name}', get_bloginfo('name'), $message);
        $message = str_replace('{site_url}', get_bloginfo('url'), $message);
        
        // Envoyer l'email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Enregistrer la notification
        if ($sent) {
            $wpdb->insert(
                $wpdb->prefix . 'calendrier_rdv_notifications',
                array(
                    'appointment_id' => $appointment_id,
                    'type' => 'cancellation',
                    'recipient' => $to,
                    'sent_at' => current_time('mysql'),
                    'status' => 'sent'
                )
            );
        }
        
        return $sent;
    }
    
    /**
     * Envoyer un email de notification de rendez-vous au prestataire
     *
     * @param int $appointment_id ID du rendez-vous
     * @return bool Succès de l'envoi
     */
    public function send_provider_notification($appointment_id) {
        global $wpdb;
        
        // Récupérer les informations du rendez-vous
        $appointment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, p.name as provider_name, p.email as provider_email, s.name as service_name 
                 FROM {$wpdb->prefix}calendrier_rdv_appointments a
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_providers p ON a.provider_id = p.id
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_services s ON a.service_id = s.id
                 WHERE a.id = %d",
                $appointment_id
            )
        );
        
        if (!$appointment) {
            return false;
        }
        
        // Destinataire
        $to = $appointment->provider_email;
        
        // Sujet
        $subject = sprintf(
            // translators: %s: Formatted appointment date.
            __('Nouveau rendez-vous le %s', 'calendrier-rdv'), // Ajout d'un commentaire pour les traducteurs
            calendrier_rdv_format_date($appointment->appointment_date)
        );
        
        // En-têtes
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );
        
        // Corps du message
        $message = $this->get_email_template('provider_notification');
        
        // Remplacer les variables
        $message = str_replace('{provider_name}', $appointment->provider_name, $message);
        $message = str_replace('{customer_name}', $appointment->customer_name, $message);
        $message = str_replace('{customer_email}', $appointment->customer_email, $message);
        $message = str_replace('{customer_phone}', $appointment->customer_phone, $message);
        $message = str_replace('{appointment_date}', calendrier_rdv_format_date($appointment->appointment_date), $message);
        $message = str_replace('{appointment_time}', calendrier_rdv_format_date($appointment->appointment_date, 'time'), $message);
        $message = str_replace('{service_name}', $appointment->service_name, $message);
        $message = str_replace('{duration}', $appointment->duration, $message);
        $message = str_replace('{notes}', $appointment->notes, $message);
        $message = str_replace('{site_name}', get_bloginfo('name'), $message);
        $message = str_replace('{site_url}', get_bloginfo('url'), $message);
        
        // Envoyer l'email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Enregistrer la notification
        if ($sent) {
            $wpdb->insert(
                $wpdb->prefix . 'calendrier_rdv_notifications',
                array(
                    'appointment_id' => $appointment_id,
                    'type' => 'provider_notification',
                    'recipient' => $to,
                    'sent_at' => current_time('mysql'),
                    'status' => 'sent'
                )
            );
        }
        
        return $sent;
    }
    
    /**
     * Envoyer un email de notification de liste d'attente au client
     *
     * @param int $waitlist_id ID de l'entrée de liste d'attente
     * @param int $appointment_id ID du rendez-vous disponible (facultatif)
     * @return bool Succès de l'envoi
     */
    public function send_waitlist_notification($waitlist_id, $appointment_id = 0) {
        global $wpdb;
        
        // Récupérer les informations de la liste d'attente
        $waitlist = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT w.*, p.name as provider_name, s.name as service_name 
                 FROM {$wpdb->prefix}calendrier_rdv_waitlist w
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_providers p ON w.provider_id = p.id
                 LEFT JOIN {$wpdb->prefix}calendrier_rdv_services s ON w.service_id = s.id
                 WHERE w.id = %d",
                $waitlist_id
            )
        );
        
        if (!$waitlist) {
            return false;
        }
        
        // Destinataire
        $to = $waitlist->customer_email;
        
        // Sujet
        if ($appointment_id > 0) {
            $subject = __('Un créneau est disponible pour votre rendez-vous', 'calendrier-rdv');
        } else {
            $subject = __('Mise à jour de votre demande de liste d\'attente', 'calendrier-rdv');
        }
        
        // En-têtes
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );
        
        // Corps du message
        if ($appointment_id > 0) {
            $message = $this->get_email_template('waitlist_slot_available');
            
            // Récupérer les informations du rendez-vous disponible
            $appointment = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}calendrier_rdv_appointments WHERE id = %d",
                    $appointment_id
                )
            );
            
            if ($appointment) {
                $message = str_replace('{appointment_date}', calendrier_rdv_format_date($appointment->appointment_date), $message);
                $message = str_replace('{appointment_time}', calendrier_rdv_format_date($appointment->appointment_date, 'time'), $message);
                
                // URL de confirmation
                $confirmation_url = add_query_arg(
                    array(
                        'action' => 'confirm_waitlist',
                        'waitlist_id' => $waitlist_id,
                        'appointment_id' => $appointment_id,
                        'token' => wp_create_nonce('confirm_waitlist_' . $waitlist_id)
                    ),
                    home_url()
                );
                
                $message = str_replace('{confirmation_url}', $confirmation_url, $message);
            }
        } else {
            $message = $this->get_email_template('waitlist_confirmation');
        }
        
        // Remplacer les variables communes
        $message = str_replace('{customer_name}', $waitlist->customer_name, $message);
        $message = str_replace('{provider_name}', $waitlist->provider_name, $message);
        $message = str_replace('{service_name}', $waitlist->service_name, $message);
        $message = str_replace('{preferred_date}', date_i18n(get_option('date_format'), strtotime($waitlist->preferred_date)), $message);
        $message = str_replace('{site_name}', get_bloginfo('name'), $message);
        $message = str_replace('{site_url}', get_bloginfo('url'), $message);
        
        // Envoyer l'email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Enregistrer la notification
        if ($sent) {
            $wpdb->insert(
                $wpdb->prefix . 'calendrier_rdv_notifications',
                array(
                    'appointment_id' => $appointment_id > 0 ? $appointment_id : 0,
                    'type' => $appointment_id > 0 ? 'waitlist_slot_available' : 'waitlist_confirmation',
                    'recipient' => $to,
                    'sent_at' => current_time('mysql'),
                    'status' => 'sent'
                )
            );
        }
        
        return $sent;
    }
    
    /**
     * Récupérer un modèle d'email
     *
     * @param string $template Nom du modèle
     * @return string Contenu HTML du modèle
     */
    private function get_email_template($template) {
        $template_file = CAL_RDV_PLUGIN_DIR . 'templates/emails/' . $template . '.php';
        
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        }
        
        // Modèle par défaut si le fichier n'existe pas
        return $this->get_default_template($template);
    }
    
    /**
     * Récupérer un modèle d'email par défaut
     *
     * @param string $template Nom du modèle
     * @return string Contenu HTML du modèle par défaut
     */
    private function get_default_template($template) {
        $templates = array(
            'confirmation' => '
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                    <h2 style="color: #333;">Confirmation de rendez-vous</h2>
                    <p>Bonjour {customer_name},</p>
                    <p>Votre rendez-vous a été confirmé avec les détails suivants :</p>
                    <div style="background-color: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50;">
                        <p><strong>Date :</strong> {appointment_date}</p>
                        <p><strong>Heure :</strong> {appointment_time}</p>
                        <p><strong>Prestataire :</strong> {provider_name}</p>
                        <p><strong>Service :</strong> {service_name}</p>
                        <p><strong>Durée :</strong> {duration} minutes</p>
                    </div>
                    <p>Merci d\'avoir choisi {site_name}.</p>
                    <p>Si vous avez des questions ou si vous souhaitez modifier votre rendez-vous, veuillez nous contacter.</p>
                    <p>Cordialement,<br>{site_name}</p>
                </div>
            ',
            'reminder' => '
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                    <h2 style="color: #333;">Rappel de rendez-vous</h2>
                    <p>Bonjour {customer_name},</p>
                    <p>Nous vous rappelons votre rendez-vous prévu pour demain :</p>
                    <div style="background-color: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3;">
                        <p><strong>Date :</strong> {appointment_date}</p>
                        <p><strong>Heure :</strong> {appointment_time}</p>
                        <p><strong>Prestataire :</strong> {provider_name}</p>
                        <p><strong>Service :</strong> {service_name}</p>
                        <p><strong>Durée :</strong> {duration} minutes</p>
                    </div>
                    <p>Nous nous réjouissons de vous accueillir.</p>
                    <p>Si vous ne pouvez pas vous présenter à ce rendez-vous, veuillez nous en informer dès que possible.</p>
                    <p>Cordialement,<br>{site_name}</p>
                </div>
            ',
            'cancellation' => '
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                    <h2 style="color: #333;">Annulation de rendez-vous</h2>
                    <p>Bonjour {customer_name},</p>
                    <p>Nous vous informons que votre rendez-vous a été annulé :</p>
                    <div style="background-color: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #F44336;">
                        <p><strong>Date :</strong> {appointment_date}</p>
                        <p><strong>Heure :</strong> {appointment_time}</p>
                        <p><strong>Prestataire :</strong> {provider_name}</p>
                        <p><strong>Service :</strong> {service_name}</p>
                    </div>
                    <p>Si vous souhaitez prendre un nouveau rendez-vous, veuillez visiter notre site web.</p>
                    <p>Cordialement,<br>{site_name}</p>
                </div>
            ',
            'provider_notification' => '
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                    <h2 style="color: #333;">Nouveau rendez-vous</h2>
                    <p>Bonjour {provider_name},</p>
                    <p>Un nouveau rendez-vous a été pris avec vous :</p>
                    <div style="background-color: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #9C27B0;">
                        <p><strong>Client :</strong> {customer_name}</p>
                        <p><strong>Email :</strong> {customer_email}</p>
                        <p><strong>Téléphone :</strong> {customer_phone}</p>
                        <p><strong>Date :</strong> {appointment_date}</p>
                        <p><strong>Heure :</strong> {appointment_time}</p>
                        <p><strong>Service :</strong> {service_name}</p>
                        <p><strong>Durée :</strong> {duration} minutes</p>
                        <p><strong>Notes :</strong> {notes}</p>
                    </div>
                    <p>Veuillez vous connecter à votre compte pour gérer ce rendez-vous.</p>
                    <p>Cordialement,<br>{site_name}</p>
                </div>
            ',
            'waitlist_confirmation' => '
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                    <h2 style="color: #333;">Confirmation de liste d\'attente</h2>
                    <p>Bonjour {customer_name},</p>
                    <p>Nous avons bien reçu votre demande d\'inscription sur notre liste d\'attente :</p>
                    <div style="background-color: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #FF9800;">
                        <p><strong>Prestataire :</strong> {provider_name}</p>
                        <p><strong>Service :</strong> {service_name}</p>
                        <p><strong>Date souhaitée :</strong> {preferred_date}</p>
                    </div>
                    <p>Nous vous contacterons dès qu\'un créneau sera disponible.</p>
                    <p>Cordialement,<br>{site_name}</p>
                </div>
            ',
            'waitlist_slot_available' => '
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                    <h2 style="color: #333;">Créneau disponible</h2>
                    <p>Bonjour {customer_name},</p>
                    <p>Bonne nouvelle ! Un créneau est maintenant disponible pour votre rendez-vous :</p>
                    <div style="background-color: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50;">
                        <p><strong>Prestataire :</strong> {provider_name}</p>
                        <p><strong>Service :</strong> {service_name}</p>
                        <p><strong>Date :</strong> {appointment_date}</p>
                        <p><strong>Heure :</strong> {appointment_time}</p>
                    </div>
                    <p>Pour confirmer ce rendez-vous, veuillez cliquer sur le lien ci-dessous :</p>
                    <p style="text-align: center;">
                        <a href="{confirmation_url}" style="display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Confirmer le rendez-vous</a>
                    </p>
                    <p>Ce créneau sera réservé pour vous pendant 24 heures. Passé ce délai, il sera proposé à une autre personne sur la liste d\'attente.</p>
                    <p>Cordialement,<br>{site_name}</p>
                </div>
            '
        );
        
        return isset($templates[$template]) ? $templates[$template] : '';
    }
}
