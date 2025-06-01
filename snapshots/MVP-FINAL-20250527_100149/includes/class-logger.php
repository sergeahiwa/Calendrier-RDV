<?php
/**
 * Gestionnaire de logs pour Calendrier RDV
 *
 * @package Calendrier_RDV
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant les logs d'activité
 */
class CalRdv_Logger {
    
    /**
     * Instance unique de la classe
     *
     * @var CalRdv_Logger
     */
    private static $instance = null;
    
    /**
     * Table de base de données pour les logs
     *
     * @var string
     */
    private $log_table;
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        global $wpdb;
        $this->log_table = $wpdb->prefix . 'cal_rdv_logs';
        $this->init_hooks();
    }
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return CalRdv_Logger
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
        // Enregistrer les actions importantes
        add_action('calendrier_rdv_appointment_created', [$this, 'log_appointment_created']);
        add_action('calendrier_rdv_appointment_updated', [$this, 'log_appointment_updated']);
        add_action('calendrier_rdv_appointment_cancelled', [$this, 'log_appointment_cancelled']);
        add_action('calendrier_rdv_booking_paid', [$this, 'log_payment_completed'], 10, 3);
        
        // Logs administratifs
        add_action('calendrier_rdv_settings_saved', [$this, 'log_settings_saved']);
        add_action('calendrier_rdv_service_created', [$this, 'log_service_created']);
        add_action('calendrier_rdv_service_updated', [$this, 'log_service_updated']);
        add_action('calendrier_rdv_provider_created', [$this, 'log_provider_created']);
        add_action('calendrier_rdv_provider_updated', [$this, 'log_provider_updated']);
    }
    
    /**
     * Ajoute une entrée de log
     * 
     * @param string $action Type d'action (création, modification, suppression, etc.)
     * @param string $object_type Type d'objet concerné (rendez-vous, service, etc.)
     * @param int $object_id ID de l'objet concerné
     * @param string $message Description de l'action
     * @param array $context Données contextuelles supplémentaires
     * @return int|false ID du log créé ou false en cas d'erreur
     */
    public function add_log($action, $object_type, $object_id, $message, $context = []) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        
        $result = $wpdb->insert(
            $this->log_table,
            [
                'user_id'     => $user_id,
                'action'      => $action,
                'object_type' => $object_type,
                'object_id'   => $object_id,
                'message'     => $message,
                'context'     => is_array($context) ? wp_json_encode($context) : '',
                'ip_address'  => $ip_address,
                'created_at'  => current_time('mysql'),
            ],
            [
                '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s'
            ]
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Récupère l'adresse IP du client
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Enregistre la création d'un rendez-vous
     * 
     * @param object $appointment Objet rendez-vous
     */
    public function log_appointment_created($appointment) {
        $message = sprintf(
            // translators: %s: Formatted appointment start date and time.
            __('Nouveau rendez-vous créé: %s', 'calendrier-rdv'),
            date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($appointment->start_date)
            )
        );
        
        $this->add_log(
            'create',
            'appointment',
            $appointment->id,
            $message,
            [
                'service_id' => $appointment->service_id,
                'provider_id' => $appointment->provider_id,
                'customer_id' => $appointment->customer_id,
                'start_date' => $appointment->start_date,
                'end_date' => $appointment->end_date,
                'status' => $appointment->status,
            ]
        );
    }
    
    /**
     * Enregistre la modification d'un rendez-vous
     * 
     * @param object $appointment Objet rendez-vous
     */
    public function log_appointment_updated($appointment) {
        $message = sprintf(
            // translators: %d: Appointment ID.
            __('Rendez-vous #%d mis à jour', 'calendrier-rdv'),
            $appointment->id
        );
        
        $this->add_log(
            'update',
            'appointment',
            $appointment->id,
            $message,
            [
                'service_id' => $appointment->service_id,
                'provider_id' => $appointment->provider_id,
                'start_date' => $appointment->start_date,
                'end_date' => $appointment->end_date,
                'status' => $appointment->status,
            ]
        );
    }
    
    /**
     * Enregistre l'annulation d'un rendez-vous
     * 
     * @param object $appointment Objet rendez-vous
     */
    public function log_appointment_cancelled($appointment) {
        $message = sprintf(
            // translators: %d: Appointment ID.
            __('Rendez-vous #%d annulé', 'calendrier-rdv'),
            $appointment->id
        );
        
        $this->add_log(
            'cancel',
            'appointment',
            $appointment->id,
            $message,
            [
                'service_id' => $appointment->service_id,
                'provider_id' => $appointment->provider_id,
                'start_date' => $appointment->start_date,
                'cancelled_by' => get_current_user_id(),
            ]
        );
    }
    
    /**
     * Enregistre un paiement complété
     * 
     * @param int $booking_id ID de la réservation
     * @param string $transaction_id ID de la transaction
     * @param float $amount Montant du paiement
     */
    public function log_payment_completed($booking_id, $transaction_id, $amount) {
        $message = sprintf(
            // translators: %1$d: Booking ID, %2$s: Payment amount, %3$s: Currency.
            __('Paiement complété pour la réservation #%1$d: %2$s %3$s', 'calendrier-rdv'),
            $booking_id,
            number_format($amount / 100, 2),
            get_option('calendrier_rdv_currency', 'EUR')
        );
        
        $this->add_log(
            'payment',
            'booking',
            $booking_id,
            $message,
            [
                'transaction_id' => $transaction_id,
                'amount' => $amount / 100,
                'currency' => get_option('calendrier_rdv_currency', 'EUR'),
            ]
        );
    }
    
    /**
     * Enregistre la sauvegarde des paramètres
     * 
     * @param array $settings Paramètres sauvegardés
     */
    public function log_settings_saved($settings) {
        $message = __('Paramètres du plugin mis à jour', 'calendrier-rdv');
        
        $this->add_log(
            'settings',
            'plugin',
            0,
            $message,
            [
                'settings' => array_keys($settings),
            ]
        );
    }
    
    /**
     * Enregistre la création d'un service
     * 
     * @param object $service Objet service
     */
    public function log_service_created($service) {
        $message = sprintf(
            __('Nouveau service créé: %s', 'calendrier-rdv'),
            $service->name
        );
        
        $this->add_log(
            'create',
            'service',
            $service->id,
            $message,
            [
                'name' => $service->name,
                'duration' => $service->duration,
                'price' => $service->price,
            ]
        );
    }
    
    /**
     * Enregistre la modification d'un service
     * 
     * @param object $service Objet service
     */
    public function log_service_updated($service) {
        $message = sprintf(
            __('Service #%d mis à jour: %s', 'calendrier-rdv'),
            $service->id,
            $service->name
        );
        
        $this->add_log(
            'update',
            'service',
            $service->id,
            $message,
            [
                'name' => $service->name,
                'duration' => $service->duration,
                'price' => $service->price,
            ]
        );
    }
    
    /**
     * Enregistre la création d'un prestataire
     * 
     * @param object $provider Objet prestataire
     */
    public function log_provider_created($provider) {
        $message = sprintf(
            __('Nouveau prestataire créé: %s', 'calendrier-rdv'),
            $provider->name
        );
        
        $this->add_log(
            'create',
            'provider',
            $provider->id,
            $message,
            [
                'name' => $provider->name,
                'email' => $provider->email,
            ]
        );
    }
    
    /**
     * Enregistre la modification d'un prestataire
     * 
     * @param object $provider Objet prestataire
     */
    public function log_provider_updated($provider) {
        $message = sprintf(
            __('Prestataire #%d mis à jour: %s', 'calendrier-rdv'),
            $provider->id,
            $provider->name
        );
        
        $this->add_log(
            'update',
            'provider',
            $provider->id,
            $message,
            [
                'name' => $provider->name,
                'email' => $provider->email,
            ]
        );
    }
    
    /**
     * Récupère les logs avec filtrage et pagination
     * 
     * @param array $args Arguments de filtrage et pagination
     * @return array Tableau des logs et total
     */
    public function get_logs($args = []) {
        global $wpdb;
        
        $defaults = [
            'action' => '',
            'object_type' => '',
            'object_id' => 0,
            'user_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'per_page' => 20,
            'page' => 1,
        ];
        
        $args = wp_parse_args($args, $defaults);
        $where = [];
        $query_args = [];
        
        // Filtres
        if (!empty($args['action'])) {
            $where[] = 'action = %s';
            $query_args[] = $args['action'];
        }
        
        if (!empty($args['object_type'])) {
            $where[] = 'object_type = %s';
            $query_args[] = $args['object_type'];
        }
        
        if (!empty($args['object_id'])) {
            $where[] = 'object_id = %d';
            $query_args[] = $args['object_id'];
        }
        
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $query_args[] = $args['user_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $query_args[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $query_args[] = $args['date_to'];
        }
        
        // Construction de la requête
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Total des résultats
        $count_query = "SELECT COUNT(*) FROM {$this->log_table} {$where_clause}";
        $total = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
        
        // Requête principale avec pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $query = "SELECT * FROM {$this->log_table} {$where_clause} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        
        $final_query_args = array_merge($query_args, [$args['per_page'], $offset]);
        $logs = $wpdb->get_results($wpdb->prepare($query, $final_query_args));
        
        // Transformer les résultats
        foreach ($logs as &$log) {
            if (!empty($log->context)) {
                $log->context = json_decode($log->context, true);
            } else {
                $log->context = [];
            }
            
            // Ajouter des informations supplémentaires
            if ($log->user_id) {
                $user = get_userdata($log->user_id);
                $log->user_name = $user ? $user->display_name : __('Utilisateur supprimé', 'calendrier-rdv');
            } else {
                $log->user_name = __('Système', 'calendrier-rdv');
            }
        }
        
        return [
            'logs' => $logs,
            'total' => $total,
            'total_pages' => ceil($total / $args['per_page']),
        ];
    }
    
    /**
     * Supprime les anciens logs
     * 
     * @param int $days Nombre de jours à conserver
     * @return int Nombre de logs supprimés
     */
    public function purge_old_logs($days = 90) {
        global $wpdb;
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $query = $wpdb->prepare(
            "DELETE FROM {$this->log_table} WHERE created_at < %s",
            $date_limit
        );
        
        return $wpdb->query($query);
    }
}

/**
 * Fonction utilitaire pour accéder au logger
 * 
 * @return CalRdv_Logger
 */
function calendrier_rdv_logger() {
    return CalRdv_Logger::get_instance();
}

// Initialiser le logger
add_action('plugins_loaded', ['CalRdv_Logger', 'get_instance']);
