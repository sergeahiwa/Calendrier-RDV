<?php
/**
 * Gestion des conflits de rendez-vous
 * 
 * @package CalendrierRdv\Includes
 */

if (!defined('ABSPATH')) {
    exit; // Sortie si accès direct
}

class Appointment_Conflict_Detector {
    /**
     * Instance de la classe
     * 
     * @var Appointment_Conflict_Detector
     */
    private static $instance = null;

    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Récupère l'instance unique de la classe
     * 
     * @return Appointment_Conflict_Detector
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
        // Vérification avant l'enregistrement d'un rendez-vous
        add_filter('calendrier_rdv_before_save_appointment', array($this, 'check_conflict_before_save'), 10, 2);
        
        // Vérification en temps réel via AJAX
        add_action('wp_ajax_check_appointment_conflict', array($this, 'ajax_check_conflict'));
        add_action('wp_ajax_nopriv_check_appointment_conflict', array($this, 'ajax_check_conflict'));
    }

    /**
     * Vérifie les conflits avant d'enregistrer un rendez-vous
     * 
     * @param array $appointment_data Données du rendez-vous
     * @param int   $appointment_id   ID du rendez-vous (0 si nouveau)
     * @return array|WP_Error Données du rendez-vous ou erreur
     */
    public function check_conflict_before_save($appointment_data, $appointment_id = 0) {
        $start_time = strtotime($appointment_data['start_datetime']);
        $end_time = strtotime($appointment_data['end_datetime']);
        $provider_id = $appointment_data['provider_id'];
        $service_id = $appointment_data['service_id'];

        // Vérifier les conflits
        $conflicts = $this->find_conflicts(
            $provider_id,
            $service_id,
            $start_time,
            $end_time,
            $appointment_id
        );

        if (!empty($conflicts)) {
            return new WP_Error(
                'appointment_conflict',
                $this->format_conflict_message($conflicts),
                array('status' => 400)
            );
        }

        return $appointment_data;
    }

    /**
     * Gère la requête AJAX de vérification de conflit
     */
    public function ajax_check_conflict() {
        check_ajax_referer('calendrier_rdv_nonce', 'nonce');

        $data = array_map('sanitize_text_field', $_POST);
        
        $start_time = strtotime($data['start_datetime']);
        $end_time = strtotime($data['end_datetime']);
        $provider_id = intval($data['provider_id']);
        $service_id = intval($data['service_id']);
        $appointment_id = !empty($data['appointment_id']) ? intval($data['appointment_id']) : 0;

        $conflicts = $this->find_conflicts(
            $provider_id,
            $service_id,
            $start_time,
            $end_time,
            $appointment_id
        );

        if (!empty($conflicts)) {
            wp_send_json_error(array(
                'message' => $this->format_conflict_message($conflicts),
                'conflicts' => $conflicts
            ));
        }

        wp_send_json_success(array(
            'message' => __('Créneau disponible', 'calendrier-rdv')
        ));
    }

    /**
     * Recherche les conflits de rendez-vous
     * 
     * @param int    $provider_id    ID du prestataire
     * @param int    $service_id     ID du service
     * @param int    $start_time     Timestamp de début
     * @param int    $end_time       Timestamp de fin
     * @param int    $exclude_id     ID du rendez-vous à exclure (pour la modification)
     * @return array Liste des conflits
     */
    private function find_conflicts($provider_id, $service_id, $start_time, $end_time, $exclude_id = 0) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'calendrier_rdv_appointments';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE provider_id = %d 
            AND service_id = %d 
            AND id != %d 
            AND status NOT IN ('cancelled', 'rejected')
            AND (
                (start_datetime < %s AND end_datetime > %s) OR  // Nouveau RDV commence pendant un autre
                (start_datetime < %s AND end_datetime > %s) OR  // Nouveau RDV se termine pendant un autre
                (start_datetime >= %s AND end_datetime <= %s)    // Nouveau RDV est contenu dans un autre
            )",
            $provider_id,
            $service_id,
            $exclude_id,
            date('Y-m-d H:i:s', $end_time),
            date('Y-m-d H:i:s', $start_time),
            date('Y-m-d H:i:s', $start_time),
            date('Y-m-d H:i:s', $end_time),
            date('Y-m-d H:i:s', $start_time),
            date('Y-m-d H:i:s', $end_time)
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Formate le message de conflit
     * 
     * @param array $conflicts Liste des conflits
     * @return string Message formaté
     */
    private function format_conflict_message($conflicts) {
        $message = __('Conflit de rendez-vous détecté :', 'calendrier-rdv') . '\n\n';
        
        foreach ($conflicts as $conflict) {
            $start = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conflict['start_datetime']));
            $end = date_i18n(get_option('time_format'), strtotime($conflict['end_datetime']));
            
            $message .= sprintf(
                __('• %1$s de %2$s à %3$s\n', 'calendrier-rdv'),
                date_i18n(get_option('date_format'), strtotime($conflict['start_datetime'])),
                $start,
                $end
            );
        }
        
        $message .= '\n' . __('Veuillez choisir un autre créneau.', 'calendrier-rdv');
        
        return $message;
    }

    /**
     * Vérifie si un créneau est disponible
     * 
     * @param int $provider_id ID du prestataire
     * @param int $service_id  ID du service
     * @param int $start_time  Timestamp de début
     * @param int $end_time    Timestamp de fin
     * @param int $exclude_id  ID du rendez-vous à exclure
     * @return bool True si disponible, false sinon
     */
    public function is_slot_available($provider_id, $service_id, $start_time, $end_time, $exclude_id = 0) {
        $conflicts = $this->find_conflicts(
            $provider_id,
            $service_id,
            $start_time,
            $end_time,
            $exclude_id
        );
        
        return empty($conflicts);
    }
}

// Initialisation de la classe
function calendrier_rdv_conflict_detector() {
    return Appointment_Conflict_Detector::get_instance();
}

// Démarrer le détecteur de conflits
add_action('plugins_loaded', 'calendrier_rdv_conflict_detector');
