<?php
// =============================================
// Fichier : public/class-public.php
// Description : Gestion de la partie publique du site
// Auteur : SAN Digital Solutions
// =============================================

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class CalRdv_Public {
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Initialiser les hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        
        // Shortcodes
        add_shortcode('calendrier_rdv', array($this, 'render_booking_form'));
        
        // Gestion des actions AJAX
        add_action('wp_ajax_calendrier_rdv_get_available_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_nopriv_calendrier_rdv_get_available_slots', array($this, 'ajax_get_available_slots'));
        
        add_action('wp_ajax_calendrier_rdv_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_nopriv_calendrier_rdv_submit_booking', array($this, 'ajax_submit_booking'));
    }
    
    /**
     * Charge les assets (CSS/JS) pour le front-end
     */
    public function enqueue_public_assets() {
        // FullCalendar
        wp_enqueue_style('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css', array(), '5.11.3');
        wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js', array('jquery'), '5.11.3', true);
        wp_enqueue_script('fullcalendar-locale', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js', array('fullcalendar'), '5.11.3', true);
        
        // Flatpickr pour les sélecteurs de date/heure
        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9');
        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), '4.6.9', true);
        wp_enqueue_script('flatpickr-fr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js', array('flatpickr'), '4.6.9', true);
        
        // Select2 pour les listes déroulantes avancées
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0-rc.0', true);
        
        // Styles et scripts personnalisés
        wp_enqueue_style('calendrier-rdv-public', CAL_RDV_PLUGIN_URL . 'public/css/public.css', array(), CAL_RDV_VERSION);
        wp_enqueue_script('calendrier-rdv-public', CAL_RDV_PLUGIN_URL . 'public/js/public.js', array('jquery', 'fullcalendar', 'flatpickr', 'select2'), CAL_RDV_VERSION, true);
        
        // Localisation des scripts
        wp_localize_script('calendrier-rdv-public', 'calendrierRdv', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('calendrier_rdv_public_nonce'),
            'locale' => get_locale(),
            'time_format' => get_option('time_format'),
            'date_format' => get_option('date_format'),
            'start_of_week' => get_option('start_of_week'),
            'timezone' => wp_timezone_string(),
            'texts' => array(
                'loading' => __('Chargement...', 'calendrier-rdv'),
                'error' => __('Une erreur est survenue', 'calendrier-rdv'),
                'no_slots' => __('Aucun créneau disponible', 'calendrier-rdv'),
                'select_date' => __('Sélectionnez une date', 'calendrier-rdv'),
                'select_time' => __('Sélectionnez une heure', 'calendrier-rdv'),
                'required_field' => __('Ce champ est obligatoire', 'calendrier-rdv'),
                'invalid_email' => __('Adresse email invalide', 'calendrier-rdv'),
                'invalid_phone' => __('Numéro de téléphone invalide', 'calendrier-rdv')
            )
        ));
    }
    
    /**
     * Affiche le formulaire de prise de rendez-vous
     */
    public function render_booking_form($atts) {
        // Récupérer les attributs du shortcode
        $atts = shortcode_atts(array(
            'prestataire_id' => 0,
            'service_id' => 0,
            'show_title' => 'yes',
            'show_description' => 'yes'
        ), $atts, 'calendrier_rdv');
        
        // Récupérer les prestataires et services
        global $wpdb;
        $prestataires = array();
        $services = array();
        
        // Si un prestataire est spécifié, ne charger que ses services
        if (!empty($atts['prestataire_id'])) {
            $prestataire_id = intval($atts['prestataire_id']);
            $prestataires = $wpdb->get_results($wpdb->prepare(
                "SELECT id, nom, description, photo 
                FROM {$wpdb->prefix}calrdv_prestataires 
                WHERE id = %d AND actif = 1",
                $prestataire_id
            ));
            
            $services = $wpdb->get_results($wpdb->prepare(
                "SELECT s.id, s.nom, s.duree, s.prix, s.description 
                FROM {$wpdb->prefix}calrdv_services s
                INNER JOIN {$wpdb->prefix}calrdv_prestataires_services ps ON s.id = ps.service_id
                WHERE ps.prestataire_id = %d AND s.actif = 1
                ORDER BY s.nom",
                $prestataire_id
            ));
        } else {
            // Sinon, charger tous les prestataires et services actifs
            $prestataires = $wpdb->get_results(
                "SELECT id, nom, description, photo 
                FROM {$wpdb->prefix}calrdv_prestataires 
                WHERE actif = 1 
                ORDER BY nom"
            );
            
            $services = $wpdb->get_results(
                "SELECT id, nom, duree, prix, description 
                FROM {$wpdb->prefix}calrdv_services 
                WHERE actif = 1 
                ORDER BY nom"
            );
        }
        
        // Démarrer la mise en mémoire tampon de sortie
        ob_start();
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'public/views/booking-form.php';
        
        // Récupérer et nettoyer le contenu du tampon de sortie
        return ob_get_clean();
    }
    
    /**
     * Gère la requête AJAX pour récupérer les créneaux disponibles
     */
    public function ajax_get_available_slots() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'calendrier_rdv_public_nonce')) {
            wp_send_json_error(__('Nonce invalide', 'calendrier-rdv'), 403);
        }
        
        // Vérifier les paramètres requis
        if (empty($_POST['prestataire_id']) || empty($_POST['service_id']) || empty($_POST['date'])) {
            wp_send_json_error(__('Paramètres manquants', 'calendrier-rdv'), 400);
        }
        
        // Récupérer les paramètres
        $prestataire_id = intval($_POST['prestataire_id']);
        $service_id = intval($_POST['service_id']);
        $date = sanitize_text_field($_POST['date']);
        
        // Valider la date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(__('Format de date invalide', 'calendrier-rdv'), 400);
        }
        
        // Récupérer la durée du service
        global $wpdb;
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT duree FROM {$wpdb->prefix}calrdv_services WHERE id = %d AND actif = 1",
            $service_id
        ));
        
        if (!$service) {
            wp_send_json_error(__('Service introuvable', 'calendrier-rdv'), 404);
        }
        
        // Récupérer les créneaux disponibles
        $creneaux = calendrier_rdv_get_creneaux_disponibles($prestataire_id, $date, $service->duree);
        
        // Formater la réponse
        $response = array(
            'date' => $date,
            'creneaux' => $creneaux,
            'count' => count($creneaux)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Gère la soumission du formulaire de rendez-vous
     */
    public function ajax_submit_booking() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'calendrier_rdv_public_nonce')) {
            wp_send_json_error(__('Nonce invalide', 'calendrier-rdv'), 403);
        }
        
        // Vérifier les paramètres requis
        $required_fields = array(
            'prestataire_id' => 'ID du prestataire manquant',
            'service_id' => 'ID du service manquant',
            'date_rdv' => 'Date du rendez-vous manquante',
            'heure_debut' => 'Heure de début manquante',
            'client_nom' => 'Nom du client manquant',
            'client_email' => 'Email du client manquant',
            'client_telephone' => 'Téléphone du client manquant'
        );
        
        $missing_fields = array();
        $data = array();
        
        foreach ($required_fields as $field => $error_message) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            } else {
                $data[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        if (!empty($missing_fields)) {
            wp_send_json_error(__('Champs obligatoires manquants : ', 'calendrier-rdv') . implode(', ', $missing_fields), 400);
        }
        
        // Valider l'email
        if (!is_email($data['client_email'])) {
            wp_send_json_error(__('Adresse email invalide', 'calendrier-rdv'), 400);
        }
        
        // Valider le numéro de téléphone (format basique)
        if (!preg_match('/^[0-9\s\+\(\)\.\-]{10,20}$/', $data['client_telephone'])) {
            wp_send_json_error(__('Numéro de téléphone invalide', 'calendrier-rdv'), 400);
        }
        
        // Valider la date et l'heure
        $date_rdv = $data['date_rdv'];
        $heure_debut = $data['heure_debut'];
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_rdv) || 
            !preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $heure_debut)) {
            wp_send_json_error(__('Format de date ou d\'heure invalide', 'calendrier-rdv'), 400);
        }
        
        // Vérifier que la date n'est pas passée
        $current_datetime = current_time('mysql');
        $rdv_datetime = $date_rdv . ' ' . $heure_debut;
        
        if (strtotime($rdv_datetime) < strtotime($current_datetime)) {
            wp_send_json_error(__('Impossible de prendre un rendez-vous à une date passée', 'calendrier-rdv'), 400);
        }
        
        // Récupérer les détails du service pour la durée
        global $wpdb;
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT duree FROM {$wpdb->prefix}calrdv_services WHERE id = %d AND actif = 1",
            intval($data['service_id'])
        ));
        
        if (!$service) {
            wp_send_json_error(__('Service introuvable', 'calendrier-rdv'), 404);
        }
        
        // Calculer l'heure de fin
        $heure_fin = date('H:i:s', strtotime($heure_debut) + ($service->duree * 60));
        
        // Vérifier la disponibilité du créneau
        $prestataire_id = intval($data['prestataire_id']);
        
        if (!calendrier_rdv_is_creneau_disponible($prestataire_id, $date_rdv, $heure_debut, $heure_fin)) {
            wp_send_json_error(__('Ce créneau n\'est plus disponible', 'calendrier-rdv'), 409);
        }
        
        // Préparer les données pour l'insertion
        $rdv_data = array(
            'prestataire_id' => $prestataire_id,
            'service_id' => intval($data['service_id']),
            'client_nom' => $data['client_nom'],
            'client_email' => $data['client_email'],
            'client_telephone' => $data['client_telephone'],
            'date_rdv' => $date_rdv,
            'heure_debut' => $heure_debut,
            'heure_fin' => $heure_fin,
            'statut' => 'confirme',
            'date_creation' => current_time('mysql'),
            'ip_client' => $_SERVER['REMOTE_ADDR'] ?? ''
        );
        
        // Champ optionnel : notes
        if (!empty($_POST['notes'])) {
            $rdv_data['notes'] = sanitize_textarea_field($_POST['notes']);
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
            
            wp_send_json_error(__('Une erreur est survenue lors de l\'enregistrement du rendez-vous', 'calendrier-rdv'), 500);
        }
        
        $rdv_id = $wpdb->insert_id;
        
        // Enregistrer une entrée de log
        calendrier_rdv_log(
            'Nouveau rendez-vous créé',
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
            'success' => true,
            'message' => __('Votre rendez-vous a bien été enregistré. Vous allez recevoir un email de confirmation.', 'calendrier-rdv'),
            'rdv_id' => $rdv_id,
            'email_sent' => $email_sent
        );
        
        wp_send_json_success($response);
    }
}
