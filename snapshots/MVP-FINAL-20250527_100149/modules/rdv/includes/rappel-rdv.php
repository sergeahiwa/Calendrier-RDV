<?php
/**
 * Gestion des rappels de rendez-vous
 * 
 * @package CalendrierRDV
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}

/**
 * Classe de gestion des rappels de rendez-vous
 */
class RDV_Reminder_Handler {
    
    /**
     * Instance unique de la classe
     * 
     * @var RDV_Reminder_Handler
     */
    private static $instance = null;
    
    /**
     * Nom de la table des rendez-vous
     * 
     * @var string
     */
    private $table_rdv;
    
    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        global $wpdb;
        $this->table_rdv = $wpdb->prefix . 'rdv_events';
    }
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return RDV_Reminder_Handler
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Envoie les rappels pour les rendez-vous à venir
     * 
     * @return array Résultats de l'envoi des rappels
     */
    public function send_reminders() {
        global $wpdb;
        
        $results = [
            'total' => 0,
            'success' => 0,
            'errors' => 0,
            'appointments' => []
        ];
        
        // Heure actuelle
        $now = current_time('mysql');
        
        // Heure de début de la plage de rappel (dans 24h)
        $start_time = date('Y-m-d H:i:s', strtotime('+23 hours', strtotime($now)));
        
        // Heure de fin de la plage de rappel (dans 25h)
        $end_time = date('Y-m-d H:i:s', strtotime('+25 hours', strtotime($now)));
        
        // Récupérer les rendez-vous à rappeler
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_rdv} 
            WHERE start BETWEEN %s AND %s 
            AND statut = 'confirmé' 
            AND reminder_sent = 0",
            $start_time,
            $end_time
        );
        
        $appointments = $wpdb->get_results($query);
        $results['total'] = count($appointments);
        
        if (empty($appointments)) {
            return $results;
        }
        
        // Envoyer un rappel pour chaque rendez-vous
        foreach ($appointments as $appointment) {
            $result = $this->send_reminder($appointment);
            
            if ($result['success']) {
                $results['success']++;
                
                // Marquer le rappel comme envoyé dans la base de données
                $wpdb->update(
                    $this->table_rdv,
                    ['reminder_sent' => 1, 'updated_at' => current_time('mysql')],
                    ['id' => $appointment->id],
                    ['%d', '%s'],
                    ['%d']
                );
            } else {
                $results['errors']++;
            }
            
            $results['appointments'][] = [
                'id' => $appointment->id,
                'client' => $appointment->title,
                'email' => $appointment->email,
                'date' => $appointment->start,
                'success' => $result['success'],
                'message' => $result['message']
            ];
        }
        
        return $results;
    }
    
    /**
     * Envoie un rappel pour un rendez-vous spécifique
     * 
     * @param object $appointment Données du rendez-vous
     * @return array Résultat de l'envoi
     */
    private function send_reminder($appointment) {
        // Récupérer les paramètres du plugin
        $from_email = get_option('admin_email');
        $from_name = get_bloginfo('name');
        $subject = sprintf(
            __('Rappel : Votre rendez-vous du %s', 'calendrier-rdv'),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($appointment->start))
        );
        
        // Préparer les données pour le template d'email
        $template_data = [
            'appointment_id' => $appointment->id,
            'client_name' => $appointment->title,
            'client_email' => $appointment->email,
            'appointment_date' => date_i18n(get_option('date_format'), strtotime($appointment->start)),
            'appointment_time' => date_i18n(get_option('time_format'), strtotime($appointment->start)),
            'prestataire_name' => get_bloginfo('name'),
            'notes' => $appointment->notes,
            'site_url' => home_url(),
            'site_name' => get_bloginfo('name')
        ];
        
        // Charger le template d'email
        ob_start();
        include RDV_PLUGIN_DIR . 'templates/emails/rappel-client.php';
        $message = ob_get_clean();
        
        // En-têtes de l'email
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        ];
        
        // Envoyer l'email
        $sent = wp_mail(
            $appointment->email,
            $subject,
            $message,
            $headers
        );
        
        if ($sent) {
            return [
                'success' => true,
                'message' => __('Rappel envoyé avec succès', 'calendrier-rdv')
            ];
        } else {
            return [
                'success' => false,
                'message' => __('Échec de l\'envoi du rappel', 'calendrier-rdv')
            ];
        }
    }
}

// Initialisation du gestionnaire de rappels
function rdv_init_reminder_handler() {
    return RDV_Reminder_Handler::get_instance();
}

// Démarrer le gestionnaire de rappels
add_action('plugins_loaded', 'rdv_init_reminder_handler');

// Planification de l'envoi des rappels
if (!wp_next_scheduled('rdv_send_reminders')) {
    wp_schedule_event(time(), 'hourly', 'rdv_send_reminders');
}

// Action pour l'envoi des rappels
add_action('rdv_send_reminders', [rdv_init_reminder_handler(), 'send_reminders']);
