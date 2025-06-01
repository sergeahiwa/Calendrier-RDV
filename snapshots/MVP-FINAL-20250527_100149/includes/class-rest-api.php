<?php
// =============================================
// Fichier : includes/class-rest-api.php
// Description : Gestion des endpoints REST API
// Auteur : SAN Digital Solutions
// =============================================

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class CalRdv_REST_API {
    
    /**
     * Namespace de l'API
     */
    private $namespace = 'calendrier-rdv/v1';
    
    /**
     * Constructeur
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Enregistre les routes de l'API
     */
    public function register_routes() {
        // Endpoint pour récupérer les créneaux disponibles
        register_rest_route($this->namespace, '/creneaux', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_available_slots'),
            'permission_callback' => '__return_true',
            'args' => array(
                'prestataire_id' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric')
                ),
                'service_id' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric')
                ),
                'date' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_date')
                )
            )
        ));
        
        // Endpoint pour soumettre un rendez-vous
        register_rest_route($this->namespace, '/rendez-vous', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_booking'),
            'permission_callback' => '__return_true',
            'args' => array(
                'prestataire_id' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric')
                ),
                'service_id' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric')
                ),
                'date_rdv' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_date')
                ),
                'heure_debut' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_time')
                ),
                'client_nom' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return !empty($param);
                    }
                ),
                'client_email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                ),
                'client_telephone' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_phone')
                ),
                'notes' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        // Endpoint pour annuler un rendez-vous
        register_rest_route($this->namespace, '/rendez-vous/(?P<id>\d+)/annuler', array(
            'methods' => 'POST',
            'callback' => array($this, 'cancel_booking'),
            'permission_callback' => array($this, 'check_booking_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric')
                ),
                'raison' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }
    
    /**
     * Récupère les créneaux disponibles pour un prestataire et un service
     */
    public function get_available_slots($request) {
        $prestataire_id = $request->get_param('prestataire_id');
        $service_id = $request->get_param('service_id');
        $date = $request->get_param('date');
        
        // Récupérer la durée du service
        global $wpdb;
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT duree FROM {$wpdb->prefix}calrdv_services WHERE id = %d AND actif = 1",
            $service_id
        ));
        
        if (!$service) {
            return new WP_Error(
                'service_not_found',
                __('Service introuvable', 'calendrier-rdv'),
                array('status' => 404)
            );
        }
        
        // Récupérer les créneaux disponibles
        $creneaux = calendrier_rdv_get_creneaux_disponibles($prestataire_id, $date, $service->duree);
        
        // Formater la réponse
        return rest_ensure_response(array(
            'date' => $date,
            'creneaux' => $creneaux,
            'count' => count($creneaux)
        ));
    }
    
    /**
     * Crée un nouveau rendez-vous
     */
    public function create_booking($request) {
        $params = $request->get_params();
        
        // Récupérer la durée du service
        global $wpdb;
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT duree FROM {$wpdb->prefix}calrdv_services WHERE id = %d AND actif = 1",
            intval($params['service_id'])
        ));
        
        if (!$service) {
            return new WP_Error(
                'service_not_found',
                __('Service introuvable', 'calendrier-rdv'),
                array('status' => 404)
            );
        }
        
        // Calculer l'heure de fin
        $heure_fin = date('H:i:s', strtotime($params['heure_debut']) + ($service->duree * 60));
        
        // Vérifier la disponibilité du créneau
        $prestataire_id = intval($params['prestataire_id']);
        
        if (!calendrier_rdv_is_creneau_disponible(
            $prestataire_id, 
            $params['date_rdv'], 
            $params['heure_debut'], 
            $heure_fin
        )) {
            return new WP_Error(
                'slot_not_available',
                __('Ce créneau n\'est plus disponible', 'calendrier-rdv'),
                array('status' => 409)
            );
        }
        
        // Préparer les données pour l'insertion
        $rdv_data = array(
            'prestataire_id' => $prestataire_id,
            'service_id' => intval($params['service_id']),
            'client_nom' => sanitize_text_field($params['client_nom']),
            'client_email' => sanitize_email($params['client_email']),
            'client_telephone' => sanitize_text_field($params['client_telephone']),
            'date_rdv' => sanitize_text_field($params['date_rdv']),
            'heure_debut' => sanitize_text_field($params['heure_debut']),
            'heure_fin' => $heure_fin,
            'statut' => 'confirme',
            'date_creation' => current_time('mysql'),
            'ip_client' => $_SERVER['REMOTE_ADDR'] ?? ''
        );
        
        // Champ optionnel : notes
        if (!empty($params['notes'])) {
            $rdv_data['notes'] = sanitize_textarea_field($params['notes']);
        }
        
        // Insérer le rendez-vous dans la base de données
        $result = $wpdb->insert(
            $wpdb->prefix . 'calrdv_reservations',
            $rdv_data,
            array(
                '%d', // prestataire_id
                '%d', // service_id
                '%s', // client_nom
                '%s', // client_email
                '%s', // client_telephone
                '%s', // date_rdv
                '%s', // heure_debut
                '%s', // heure_fin
                '%s', // statut
                '%s', // date_creation
                '%s', // ip_client
                '%s'  // notes (optionnel)
            )
        );
        
        if ($result === false) {
            // Enregistrer l'erreur
            calendrier_rdv_log(
                'Erreur lors de l\'insertion du rendez-vous',
                'error',
                array(
                    'error' => $wpdb->last_error,
                    'data' => $rdv_data
                )
            );
            
            return new WP_Error(
                'database_error',
                __('Une erreur est survenue lors de l\'enregistrement du rendez-vous', 'calendrier-rdv'),
                array('status' => 500)
            );
        }
        
        $rdv_id = $wpdb->insert_id;
        
        // Enregistrer une entrée de log
        calendrier_rdv_log(
            'Nouveau rendez-vous créé via API REST',
            'info',
            array(
                'rdv_id' => $rdv_id,
                'prestataire_id' => $prestataire_id,
                'service_id' => $rdv_data['service_id'],
                'client_email' => $rdv_data['client_email']
            )
        );
        
        // Envoyer l'email de confirmation
        $email_sent = calendrier_rdv_send_confirmation_email($rdv_id);
        
        if (!$email_sent) {
            // Enregistrer l'erreur d'envoi d'email
            calendrier_rdv_log(
                'Échec de l\'envoi de l\'email de confirmation',
                'warning',
                array('rdv_id' => $rdv_id)
            );
        }
        
        // Préparer la réponse
        $response = array(
            'id' => $rdv_id,
            'message' => __('Votre rendez-vous a bien été enregistré', 'calendrier-rdv'),
            'email_sent' => $email_sent,
            'reference' => calendrier_rdv_generate_reference($rdv_id)
        );
        
        return rest_ensure_response($response);
    }
    
    /**
     * Annule un rendez-vous
     */
    public function cancel_booking($request) {
        $rdv_id = $request->get_param('id');
        $raison = $request->get_param('raison', '');
        
        global $wpdb;
        $table = $wpdb->prefix . 'calrdv_reservations';
        
        // Vérifier que le rendez-vous existe et n'est pas déjà annulé
        $rdv = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND statut != 'annule'",
            $rdv_id
        ));
        
        if (!$rdv) {
            return new WP_Error(
                'rdv_not_found',
                __('Rendez-vous introuvable ou déjà annulé', 'calendrier-rdv'),
                array('status' => 404)
            );
        }
        
        // Mettre à jour le statut du rendez-vous
        $result = $wpdb->update(
            $table,
            array(
                'statut' => 'annule',
                'date_modification' => current_time('mysql')
            ),
            array('id' => $rdv_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error(
                'database_error',
                __('Une erreur est survenue lors de l\'annulation du rendez-vous', 'calendrier-rdv'),
                array('status' => 500)
            );
        }
        
        // Enregistrer une entrée de log
        calendrier_rdv_log(
            'Rendez-vous annulé via API REST',
            'info',
            array(
                'rdv_id' => $rdv_id,
                'raison' => $raison,
                'user_id' => get_current_user_id()
            )
        );
        
        // Envoyer un email de confirmation d'annulation (à implémenter)
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Le rendez-vous a bien été annulé', 'calendrier-rdv')
        ));
    }
    
    /**
     * Vérifie les permissions pour l'annulation d'un rendez-vous
     */
    public function check_booking_permission($request) {
        // Récupérer l'ID du rendez-vous depuis l'URL
        $rdv_id = $request->get_param('id');
        
        // Si l'utilisateur est connecté et a les droits nécessaires
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Vérifier si l'utilisateur est le client qui a réservé
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $user_email = $user->user_email;
            
            global $wpdb;
            $table = $wpdb->prefix . 'calrdv_reservations';
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE id = %d AND client_email = %s",
                $rdv_id,
                $user_email
            ));
            
            if ($count > 0) {
                return true;
            }
        }
        
        // Vérifier le token d'accès (à implémenter si nécessaire)
        
        return new WP_Error(
            'rest_forbidden',
            __('Vous n\'avez pas les droits nécessaires pour effectuer cette action', 'calendrier-rdv'),
            array('status' => 403)
        );
    }
    
    /**
     * Valide une valeur numérique
     */
    public function validate_numeric($value) {
        return is_numeric($value) && $value > 0;
    }
    
    /**
     * Valide une date au format YYYY-MM-DD
     */
    public function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Valide une heure au format HH:MM ou HH:MM:SS
     */
    public function validate_time($time) {
        return (bool) preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time);
    }
    
    /**
     * Valide un numéro de téléphone
     */
    public function validate_phone($phone) {
        // Format basique : 10 chiffres, avec éventuellement des espaces, tirets, points, etc.
        return (bool) preg_match('/^[0-9\s\+\-\(\)\.]{10,20}$/', $phone);
    }
}
