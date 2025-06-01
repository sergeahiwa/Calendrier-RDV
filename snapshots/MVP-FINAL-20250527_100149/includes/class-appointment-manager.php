<?php
/**
 * Gestionnaire des rendez-vous
 *
 * @package Calendrier_RDV
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant les opérations liées aux rendez-vous
 */
class CalRdv_Appointment_Manager {
    
    /**
     * Instance unique de la classe
     *
     * @var CalRdv_Appointment_Manager
     */
    private static $instance = null;
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        // Initialisation des hooks
        $this->init_hooks();
    }
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return CalRdv_Appointment_Manager
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialise les hooks WordPress
     */
    private function init_hooks() {
        // Actions
        add_action('calendrier_rdv_send_reminders', [$this, 'send_reminders']);
        add_action('calendrier_rdv_check_no_shows', [$this, 'check_no_shows']);
        
        // Filtres
        add_filter('calendrier_rdv_available_slots', [$this, 'filter_available_slots'], 10, 3);
    }
    
    /**
     * Crée un nouveau rendez-vous
     * 
     * @param array $data Données du rendez-vous
     * @return int|WP_Error ID du rendez-vous ou erreur
     */
    public function create_appointment($data) {
        // Validation des données
        $validation = $this->validate_appointment_data($data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Création du rendez-vous
        $appointment = new CalRdv_Appointment($data);
        $result = $appointment->save();
        
        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de l\'enregistrement du rendez-vous', 'calendrier-rdv'));
        }
        
        // Déclencher l'action après la création
        do_action('calendrier_rdv_appointment_created', $appointment);
        
        return $appointment->id;
    }
    
    /**
     * Met à jour un rendez-vous existant
     * 
     * @param int $appointment_id ID du rendez-vous
     * @param array $data Nouvelles données
     * @return bool|WP_Error True si réussi, erreur sinon
     */
    public function update_appointment($appointment_id, $data) {
        $appointment = new CalRdv_Appointment($appointment_id);
        
        // Vérifier si le rendez-vous existe
        if (!$appointment->id) {
            return new WP_Error('not_found', __('Rendez-vous introuvable', 'calendrier-rdv'));
        }
        
        // Validation des données
        $validation = $this->validate_appointment_data($data, $appointment_id);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Mise à jour du rendez-vous
        $old_status = $appointment->status;
        $appointment->set_props($data);
        $result = $appointment->save();
        
        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la mise à jour du rendez-vous', 'calendrier-rdv'));
        }
        
        // Déclencher les actions appropriées
        if ($old_status !== $appointment->status) {
            do_action('calendrier_rdv_appointment_status_changed', $appointment->id, $old_status, $appointment->status);
        }
        
        do_action('calendrier_rdv_appointment_updated', $appointment);
        
        return true;
    }
    
    /**
     * Supprime un rendez-vous
     * 
     * @param int $appointment_id ID du rendez-vous
     * @return bool|WP_Error True si réussi, erreur sinon
     */
    public function delete_appointment($appointment_id) {
        $appointment = new CalRdv_Appointment($appointment_id);
        
        // Vérifier si le rendez-vous existe
        if (!$appointment->id) {
            return new WP_Error('not_found', __('Rendez-vous introuvable', 'calendrier-rdv'));
        }
        
        // Déclencher l'action avant suppression
        do_action('calendrier_rdv_appointment_before_delete', $appointment);
        
        // Supprimer le rendez-vous
        $result = $appointment->delete();
        
        if ($result === false) {
            return new WP_Error('db_error', __('Erreur lors de la suppression du rendez-vous', 'calendrier-rdv'));
        }
        
        // Déclencher l'action après suppression
        do_action('calendrier_rdv_appointment_deleted', $appointment_id);
        
        return true;
    }
    
    /**
     * Valide les données d'un rendez-vous
     * 
     * @param array $data Données à valider
     * @param int $appointment_id ID du rendez-vous (pour les mises à jour)
     * @return true|WP_Error True si valide, erreur sinon
     */
    private function validate_appointment_data($data, $appointment_id = 0) {
        $errors = new WP_Error();
        
        // Vérifier les champs requis
        $required_fields = [
            'client_id'    => __('ID du client', 'calendrier-rdv'),
            'provider_id'  => __('ID du prestataire', 'calendrier-rdv'),
            'service_id'   => __('ID du service', 'calendrier-rdv'),
            'start_datetime' => __('Date et heure de début', 'calendrier-rdv'),
            'end_datetime'   => __('Date et heure de fin', 'calendrier-rdv'),
        ];
        
        foreach ($required_fields as $field => $label) {
            if (empty($data[$field])) {
                $errors->add('missing_field', sprintf(__('Le champ %s est requis', 'calendrier-rdv'), $label));
            }
        }
        
        // Vérifier les formats de date
        try {
            $start = new DateTime($data['start_datetime']);
            $end = new DateTime($data['end_datetime']);
            
            // Vérifier que la date de fin est après la date de début
            if ($end <= $start) {
                $errors->add('invalid_datetime', __('La date de fin doit être postérieure à la date de début', 'calendrier-rdv'));
            }
            
            // Vérifier la disponibilité du créneau
            if (!CalRdv_Appointment::is_slot_available(
                $data['provider_id'],
                $start,
                $end,
                $appointment_id
            )) {
                $errors->add('slot_not_available', __('Ce créneau horaire n\'est plus disponible', 'calendrier-rdv'));
            }
            
        } catch (Exception $e) {
            $errors->add('invalid_datetime_format', __('Format de date ou d\'heure invalide', 'calendrier-rdv'));
        }
        
        // Vérifier que le client existe
        if (!empty($data['client_id']) && !get_user_by('id', $data['client_id'])) {
            $errors->add('invalid_client', __('Client invalide', 'calendrier-rdv'));
        }
        
        // Vérifier que le prestataire existe
        if (!empty($data['provider_id']) && !get_user_by('id', $data['provider_id'])) {
            $errors->add('invalid_provider', __('Prestataire invalide', 'calendrier-rdv'));
        }
        
        // Vérifier que le service existe
        if (!empty($data['service_id']) && !get_term($data['service_id'], 'service')) {
            $errors->add('invalid_service', __('Service invalide', 'calendrier-rdv'));
        }
        
        // Vérifier le statut
        $valid_statuses = ['scheduled', 'confirmed', 'cancelled', 'completed', 'no_show'];
        if (!empty($data['status']) && !in_array($data['status'], $valid_statuses)) {
            $errors->add('invalid_status', __('Statut invalide', 'calendrier-rdv'));
        }
        
        // Retourner la première erreur ou true si tout est valide
        if ($errors->has_errors()) {
            return $errors;
        }
        
        return true;
    }
    
    /**
     * Envoie les rappels de rendez-vous
     */
    public function send_reminders() {
        // Récupérer les rendez-vous à venir (dans les 24h) qui ont besoin d'un rappel
        $now = new DateTime();
        $reminder_time = clone $now;
        $reminder_time->modify('+24 hours');
        
        $appointments = CalRdv_Appointment::get_appointments([
            'start_date' => $now->format('Y-m-d H:i:s'),
            'end_date'   => $reminder_time->format('Y-m-d H:i:s'),
            'status'     => ['scheduled', 'confirmed'],
            'meta_query' => [
                [
                    'key'     => '_reminder_sent',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);
        
        foreach ($appointments as $appointment) {
            // Envoyer le rappel (implémentation à compléter)
            $sent = $this->send_reminder($appointment);
            
            if ($sent) {
                // Marquer comme rappel envoyé
                update_post_meta($appointment->id, '_reminder_sent', true);
                update_post_meta($appointment->id, '_reminder_sent_at', current_time('mysql'));
            }
        }
    }
    
    /**
     * Envoie un rappel pour un rendez-vous
     * 
     * @param CalRdv_Appointment $appointment Rendez-vous
     * @return bool True si envoyé avec succès
     */
    private function send_reminder($appointment) {
        // Récupérer les détails du client et du prestataire
        $client = get_userdata($appointment->client_id);
        $provider = get_userdata($appointment->provider_id);
        $service = get_term($appointment->service_id, 'service');
        
        if (!$client || !$provider || is_wp_error($service)) {
            return false;
        }
        
        // Préparer les données pour l'email
        $to = $client->user_email;
        $subject = sprintf(__('Rappel : Rendez-vous du %s', 'calendrier-rdv'), 
            $appointment->start_datetime->format(get_option('date_format') . ' ' . get_option('time_format'))
        );
        
        $message = sprintf(
            __("Bonjour %s,\n\nCeci est un rappel pour votre rendez-vous :\n\n" .
               "Service : %s\n" .
               "Date : %s\n" .
               "Heure : %s\n" .
               "Prestataire : %s\n\n" .
               "Cordialement,\nL'équipe\n", 'calendrier-rdv'),
            $client->display_name,
            $service->name,
            $appointment->start_datetime->format(get_option('date_format')),
            $appointment->start_datetime->format(get_option('time_format')),
            $provider->display_name
        );
        
        // Envoyer l'email
        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Vérifie les rendez-vous en retard (no-show)
     */
    public function check_no_shows() {
        $now = new DateTime();
        $cutoff_time = clone $now;
        $cutoff_time->modify('-15 minutes');
        
        // Récupérer les rendez-vous manqués
        $appointments = CalRdv_Appointment::get_appointments([
            'end_date'   => $cutoff_time->format('Y-m-d H:i:s'),
            'status'     => ['scheduled', 'confirmed'],
        ]);
        
        foreach ($appointments as $appointment) {
            // Marquer comme no-show
            $appointment->status = 'no_show';
            $appointment->save();
            
            // Déclencher l'action
            do_action('calendrier_rdv_appointment_no_show', $appointment);
        }
    }
    
    /**
     * Filtre les créneaux disponibles
     * 
     * @param array $slots Créneaux disponibles
     * @param int $provider_id ID du prestataire
     * @param string $date Date au format Y-m-d
     * @return array Créneaux disponibles filtrés
     */
    public function filter_available_slots($slots, $provider_id, $date) {
        // Implémentation de base - à personnaliser selon les besoins
        return $slots;
    }
}

/**
 * Fonction utilitaire pour accéder au gestionnaire de rendez-vous
 * 
 * @return CalRdv_Appointment_Manager
 */
function calendrier_rdv_appointments() {
    return CalRdv_Appointment_Manager::get_instance();
}

// Initialiser le gestionnaire de rendez-vous
add_action('plugins_loaded', ['CalRdv_Appointment_Manager', 'get_instance']);
