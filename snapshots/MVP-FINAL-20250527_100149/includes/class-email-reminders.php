<?php
/**
 * Gestion des rappels automatiques par email
 */
class Calendrier_RDV_Email_Reminders {
    private $reminder_times = [
        '24h' => 24 * HOUR_IN_SECONDS,    // 24 heures avant
        '1h'  => 1 * HOUR_IN_SECONDS,     // 1 heure avant
    ];
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Planifier le hook CRON pour les rappels
        add_action('calendrier_rdv_send_reminders', [$this, 'send_scheduled_reminders']);
        
        // Planifier l'envoi des rappels lors de la création d'un rendez-vous
        add_action('calendrier_rdv_booking_created', [$this, 'schedule_reminders']);
        
        // Nettoyage des rappels lors de l'annulation
        add_action('calendrier_rdv_booking_cancelled', [$this, 'unschedule_reminders']);
    }
    
    /**
     * Planifie les rappels pour un nouveau rendez-vous
     */
    public function schedule_reminders($booking_id) {
        $booking = $this->get_booking_details($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $appointment_time = strtotime($booking->date_rdv . ' ' . $booking->heure_debut);
        
        foreach ($this->reminder_times as $type => $time_before) {
            $reminder_time = $appointment_time - $time_before;
            
            // Ne pas planifier si le rappel serait dans le passé
            if ($reminder_time < current_time('timestamp')) {
                continue;
            }
            
            // Planifier le rappel
            wp_schedule_single_event(
                $reminder_time,
                'calendrier_rdv_send_reminder',
                [$booking_id, $type]
            );
            
            // Enregistrer les métadonnées du rappel
            $this->save_reminder_meta($booking_id, $type, $reminder_time);
        }
        
        return true;
    }
    
    /**
     * Envoie les rappels planifiés
     */
    public function send_scheduled_reminders() {
        global $wpdb;
        
        // Récupérer les rappels à envoyer
        $current_time = current_time('mysql');
        $reminders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rdv_reminders 
                WHERE reminder_time <= %s 
                AND status = 'scheduled'",
                $current_time
            )
        );
        
        foreach ($reminders as $reminder) {
            $this->send_reminder($reminder->booking_id, $reminder->reminder_type);
            
            // Marquer comme envoyé
            $wpdb->update(
                $wpdb->prefix . 'rdv_reminders',
                ['status' => 'sent', 'sent_at' => current_time('mysql')],
                ['id' => $reminder->id],
                ['%s', '%s'],
                ['%d']
            );
        }
    }
    
    /**
     * Envoie un rappel spécifique
     */
    private function send_reminder($booking_id, $type) {
        $booking = $this->get_booking_details($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $to = $booking->client_email;
        $subject = $this->get_reminder_subject($type, $booking);
        $message = $this->get_reminder_message($type, $booking);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        ];
        
        // Envoyer l'email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Journaliser l'envoi
        if ($sent) {
            $this->log_reminder($booking_id, $type, 'sent');
        } else {
            $this->log_reminder($booking_id, $type, 'failed');
        }
        
        return $sent;
    }
    
    /**
     * Annule les rappels planifiés pour un rendez-vous
     */
    public function unschedule_reminders($booking_id) {
        global $wpdb;
        
        // Récupérer les rappels planifiés
        $scheduled_reminders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rdv_reminders 
                WHERE booking_id = %d 
                AND status = 'scheduled'",
                $booking_id
            )
        );
        
        // Supprimer les événements CRON
        foreach ($scheduled_reminders as $reminder) {
            wp_clear_scheduled_hook(
                'calendrier_rdv_send_reminder',
                [$booking_id, $reminder->reminder_type]
            );
        }
        
        // Marquer comme annulé
        $wpdb->update(
            $wpdb->prefix . 'rdv_reminders',
            ['status' => 'cancelled', 'cancelled_at' => current_time('mysql')],
            ['booking_id' => $booking_id, 'status' => 'scheduled'],
            ['%s', '%s'],
            ['%d', '%s']
        );
        
        return true;
    }
    
    /**
     * Génère le sujet du rappel
     */
    private function get_reminder_subject($type, $booking) {
        $appointment_time = strtotime($booking->date_rdv . ' ' . $booking->heure_debut);
        $formatted_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $appointment_time);
        
        switch ($type) {
            case '24h':
                return sprintf(
                    // translators: %s: Formatted appointment date and time.
                    __('Rappel : Votre rendez-vous du %s', 'calendrier-rdv'), 
                    $formatted_date
                );
            case '1h':
                return sprintf(
                    // translators: %s: Formatted appointment date and time.
                    __('Rappel urgent : Votre rendez-vous approche (%s)', 'calendrier-rdv'), 
                    $formatted_date
                );
            default:
                return sprintf(
                    // translators: %s: Formatted appointment date and time.
                    __('Rappel de votre rendez-vous du %s', 'calendrier-rdv'), 
                    $formatted_date
                );
        }
    }
    
    /**
     * Génère le contenu du rappel
     */
    private function get_reminder_message($type, $booking) {
        $appointment_time = strtotime($booking->date_rdv . ' ' . $booking->heure_debut);
        $formatted_date = date_i18n(get_option('date_format'), $appointment_time);
        $formatted_time = date_i18n(get_option('time_format'), $appointment_time);
        
        ob_start();
        include CALENDRIER_RDV_PLUGIN_DIR . 'templates/emails/reminder-' . $type . '.php';
        return ob_get_clean();
    }
    
    /**
     * Enregistre les métadonnées d'un rappel
     */
    private function save_reminder_meta($booking_id, $type, $reminder_time) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'rdv_reminders',
            [
                'booking_id' => $booking_id,
                'reminder_type' => $type,
                'reminder_time' => date('Y-m-d H:i:s', $reminder_time),
                'status' => 'scheduled',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Journalise l'envoi d'un rappel
     */
    private function log_reminder($booking_id, $type, $status) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'rdv_reminder_logs',
            [
                'booking_id' => $booking_id,
                'reminder_type' => $type,
                'status' => $status,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Récupère les détails d'une réservation
     */
    private function get_booking_details($booking_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rdv_booking WHERE id = %d",
            $booking_id
        ));
    }
}

// Initialiser le gestionnaire de rappels
function calendrier_rdv_init_email_reminders() {
    new Calendrier_RDV_Email_Reminders();
}
add_action('plugins_loaded', 'calendrier_rdv_init_email_reminders');
