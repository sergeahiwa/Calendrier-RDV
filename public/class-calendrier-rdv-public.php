<?php
/**
 * Gestion des fonctionnalités front-end du plugin Calendrier RDV
 */
class Calendrier_RDV_Public {
    private $plugin_name;
    private $version;
    private $timezone_handler;
    private $waitlist_handler;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Initialiser les gestionnaires
        $this->timezone_handler = new Calendrier_RDV_Timezone_Handler();
        $this->waitlist_handler = new Calendrier_RDV_Waitlist_Handler();
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Enregistrer les styles et scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Shortcodes
        add_shortcode('calendrier_rdv', [$this, 'render_booking_form']);
        add_shortcode('calendrier_rdv_waitlist_button', [$this, 'render_waitlist_button']);
        
        // AJAX handlers
        add_action('wp_ajax_get_available_slots', [$this, 'ajax_get_available_slots']);
        add_action('wp_ajax_nopriv_get_available_slots', [$this, 'ajax_get_available_slots']);
        
        add_action('wp_ajax_submit_booking', [$this, 'ajax_submit_booking']);
        add_action('wp_ajax_nopriv_submit_booking', [$this, 'ajax_submit_booking']);
        
        add_action('wp_ajax_cancel_booking', [$this, 'ajax_cancel_booking']);
        add_action('wp_ajax_nopriv_cancel_booking', [$this, 'ajax_cancel_booking']);
        
        // Filtres pour la personnalisation des données
        add_filter('calendrier_rdv_booking_data', [$this, 'filter_booking_data'], 10, 2);
        add_filter('calendrier_rdv_available_slots', [$this, 'filter_available_slots'], 10, 3);
    }
    
    /**
     * Enregistre les styles CSS
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/calendrier-rdv-public.css',
            [],
            $this->version,
            'all'
        );
        
        // Ajouter les styles pour le sélecteur de fuseau horaire
        if ($this->is_booking_page()) {
            wp_enqueue_style(
                'select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                [],
                '4.1.0-rc.0'
            );
        }
    }
    
    /**
     * Enregistre les scripts JavaScript
     */
    public function enqueue_scripts() {
        // Enregistrer les scripts nécessaires
        wp_register_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            '4.1.0-rc.0',
            true
        );
        
        wp_register_script(
            $this->plugin_name . '-moment',
            'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js',
            [],
            '2.29.1',
            true
        );
        
        wp_register_script(
            $this->plugin_name . '-moment-timezone',
            'https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.33/moment-timezone-with-data.min.js',
            [$this->plugin_name . '-moment'],
            '0.5.33',
            true
        );
        
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/calendrier-rdv-public.js',
            ['jquery', 'select2', $this->plugin_name . '-moment-timezone'],
            $this->version,
            true
        );
        
        // Localiser le script avec des données PHP
        wp_localize_script($this->plugin_name, 'calendrierRdvVars', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('calendrier_rdv_nonce'),
            'timezone' => $this->timezone_handler->get_current_timezone(),
            'i18n' => [
                'loading' => __('Chargement...', 'calendrier-rdv'),
                'no_slots_available' => __('Aucun créneau disponible', 'calendrier-rdv'),
                'select_time' => __('Sélectionnez un horaire', 'calendrier-rdv'),
                'booking_success' => __('Votre rendez-vous a été enregistré avec succès !', 'calendrier-rdv'),
                'booking_error' => __('Une erreur est survenue. Veuillez réessayer.', 'calendrier-rdv'),
                'cancellation_confirm' => __('Êtes-vous sûr de vouloir annuler ce rendez-vous ?', 'calendrier-rdv'),
                'join_waitlist' => __('Rejoindre la liste d\'attente', 'calendrier-rdv'),
                'leave_waitlist' => __('Quitter la liste d\'attente', 'calendrier-rdv'),
            ]
        ]);
    }
    
    /**
     * Affiche le formulaire de réservation
     */
    public function render_booking_form($atts) {
        // Vérifier si l'utilisateur est connecté si nécessaire
        if (apply_filters('calendrier_rdv_require_login', false) && !is_user_logged_in()) {
            return $this->render_login_notice();
        }
        
        // Récupérer les attributs du shortcode
        $atts = shortcode_atts([
            'service_id' => 0,
            'prestataire_id' => 0,
            'show_title' => 'yes',
            'show_description' => 'yes',
        ], $atts, 'calendrier_rdv');
        
        // Récupérer les services et prestataires
        $services = $this->get_services();
        $prestataires = $this->get_prestataires();
        
        // Si un seul service est disponible, le sélectionner par défaut
        if (count($services) === 1) {
            $atts['service_id'] = $services[0]->id;
        }
        
        // Récupérer les jours d'ouverture
        $opening_hours = $this->get_opening_hours();
        
        // Inclure le template du formulaire
        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/booking-form.php';
        return ob_get_clean();
    }
    
    /**
     * Affiche le bouton de liste d'attente
     */
    public function render_waitlist_button($atts) {
        if (!is_user_logged_in() && apply_filters('calendrier_rdv_require_login_for_waitlist', true)) {
            return '';
        }
        
        $atts = shortcode_atts([
            'service_id' => 0,
            'date' => '',
            'start_time' => '',
            'end_time' => '',
            'class' => 'button',
        ], $atts, 'calendrier_rdv_waitlist_button');
        
        // Vérifier si l'utilisateur est déjà dans la liste d'attente
        $is_on_waitlist = false;
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $is_on_waitlist = $this->is_user_on_waitlist(
                $user->user_email,
                $atts['service_id'],
                $atts['date'],
                $atts['start_time']
            );
        }
        
        // Rendre le bouton
        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/waitlist-button.php';
        return ob_get_clean();
    }
    
    /**
     * Gère la requête AJAX pour récupérer les créneaux disponibles
     */
    public function ajax_get_available_slots() {
        check_ajax_referer('calendrier_rdv_nonce', 'nonce');
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $prestataire_id = isset($_POST['prestataire_id']) ? intval($_POST['prestataire_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $timezone = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : '';
        
        if (!$service_id || !$date) {
            wp_send_json_error(['message' => __('Paramètres manquants', 'calendrier-rdv')]);
        }
        
        try {
            // Convertir la date dans le fuseau horaire du site si nécessaire
            if ($timezone) {
                $date_obj = new DateTime($date, new DateTimeZone($timezone));
                $date_obj->setTimezone($this->timezone_handler->get_timezone());
                $date = $date_obj->format('Y-m-d');
            }
            
            // Récupérer les créneaux disponibles
            $slots = $this->get_available_slots($service_id, $prestataire_id, $date);
            
            // Formater les créneaux pour l'affichage
            $formatted_slots = [];
            foreach ($slots as $slot) {
                $formatted_slots[] = [
                    'start' => $this->format_time_for_display($slot['start_time']),
                    'end' => $this->format_time_for_display($slot['end_time']),
                    'timestamp' => strtotime($slot['start_time']),
                    'available' => $slot['available'],
                    'waitlist' => $slot['waitlist_available'] ?? false,
                ];
            }
            
            wp_send_json_success([
                'slots' => $formatted_slots,
                'timezone' => $this->timezone_handler->get_timezone_string(),
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Gère la soumission du formulaire de réservation
     */
    public function ajax_submit_booking() {
        check_ajax_referer('calendrier_rdv_nonce', 'nonce');
        
        // Valider les données du formulaire
        $data = $this->validate_booking_data($_POST);
        
        if (is_wp_error($data)) {
            wp_send_json_error(['message' => $data->get_error_message()]);
        }
        
        try {
            // Créer la réservation
            $booking_id = $this->create_booking($data);
            
            if (!$booking_id) {
                throw new Exception(__('Erreur lors de la création de la réservation', 'calendrier-rdv'));
            }
            
            // Envoyer les notifications
            $this->send_booking_notifications($booking_id);
            
            // Préparer la réponse
            $response = [
                'booking_id' => $booking_id,
                'message' => __('Votre rendez-vous a été enregistré avec succès !', 'calendrier-rdv'),
                'redirect_url' => apply_filters('calendrier_rdv_booking_success_redirect', ''),
            ];
            
            // Ajouter des détails supplémentaires si nécessaire
            if (current_user_can('manage_options')) {
                $response['admin_edit_url'] = admin_url("admin.php?page=calendrier-rdv-bookings&action=edit&booking_id=$booking_id");
            }
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Gère l'annulation d'une réservation
     */
    public function ajax_cancel_booking() {
        check_ajax_referer('calendrier_rdv_nonce', 'nonce');
        
        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        
        if (!$booking_id || !$token) {
            wp_send_json_error(['message' => __('Paramètres manquants', 'calendrier-rdv')]);
        }
        
        try {
            // Vérifier le jeton de sécurité
            if (!$this->verify_booking_token($booking_id, $token)) {
                throw new Exception(__('Lien d\'annulation invalide ou expiré.', 'calendrier-rdv'));
            }
            
            // Annuler la réservation
            $cancelled = $this->cancel_booking($booking_id);
            
            if (is_wp_error($cancelled)) {
                throw new Exception($cancelled->get_error_message());
            }
            
            wp_send_json_success([
                'message' => __('Votre rendez-vous a été annulé avec succès.', 'calendrier-rdv'),
                'redirect_url' => apply_filters('calendrier_rdv_booking_cancelled_redirect', ''),
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Filtre les données de réservation avant enregistrement
     */
    public function filter_booking_data($data, $raw_data) {
        // Ajouter l'adresse IP de l'utilisateur
        $data['client_ip'] = $this->get_client_ip();
        
        // Ajouter l'agent utilisateur
        $data['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_AGENT'] : '';
        
        // Ajouter la référence de la page source
        $data['source_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // Générer un jeton de confirmation unique
        $data['confirmation_token'] = wp_generate_password(32, false);
        
        // Définir le statut par défaut
        $data['status'] = 'confirmed';
        
        // Définir la date de création
        $data['created_at'] = current_time('mysql');
        
        return $data;
    }
    
    /**
     * Filtre les créneaux disponibles avant affichage
     */
    public function filter_available_slots($slots, $service_id, $date) {
        if (empty($slots)) {
            return $slots;
        }
        
        // Vérifier si la liste d'attente est activée pour ce service
        $waitlist_enabled = get_post_meta($service_id, '_enable_waitlist', true);
        
        if (!$waitlist_enabled) {
            return $slots;
        }
        
        // Marquer les créneaux complets comme disponibles en liste d'attente
        foreach ($slots as &$slot) {
            if ($slot['available'] <= 0) {
                $slot['waitlist_available'] = $this->is_waitlist_available($service_id, $date, $slot['start_time']);
            }
        }
        
        return $slots;
    }
    
    /**
     * Vérifie si un utilisateur est sur la liste d'attente
     */
    private function is_user_on_waitlist($email, $service_id, $date, $start_time) {
        // Implémentation à compléter
        return false;
    }
    
    /**
     * Vérifie si la liste d'attente est disponible pour un créneau
     */
    private function is_waitlist_available($service_id, $date, $start_time) {
        // Vérifier si la liste d'attente est activée pour ce service
        $waitlist_enabled = get_post_meta($service_id, '_enable_waitlist', true);
        
        if (!$waitlist_enabled) {
            return false;
        }
        
        // Vérifier si le créneau est dans le futur
        $slot_time = strtotime("$date $start_time");
        if ($slot_time < current_time('timestamp')) {
            return false;
        }
        
        // Vérifier si la liste d'attente n'est pas déjà pleine
        $waitlist_count = $this->get_waitlist_count($service_id, $date, $start_time);
        $max_waitlist = apply_filters('calendrier_rdv_max_waitlist_per_slot', 10, $service_id);
        
        return $waitlist_count < $max_waitlist;
    }
    
    /**
     * Récupère le nombre de personnes en liste d'attente pour un créneau
     */
    private function get_waitlist_count($service_id, $date, $start_time) {
        global $wpdb;
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rdv_waitlist 
            WHERE service_id = %d 
            AND date = %s 
            AND start_time = %s 
            AND status = 'waiting'",
            $service_id,
            $date,
            $start_time
        ));
    }
    
    /**
     * Récupère l'adresse IP du client
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    /**
     * Affiche un message invitant à se connecter
     */
    private function render_login_notice() {
        $login_url = wp_login_url(get_permalink());
        $register_url = wp_registration_url();
        
        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/login-notice.php';
        return ob_get_clean();
    }
    
    /**
     * Vérifie si la page actuelle est une page de réservation
     */
    private function is_booking_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Vérifier si la page contient un shortcode de réservation
        return has_shortcode($post->post_content, 'calendrier_rdv');
    }
}

// Initialiser la classe publique
function calendrier_rdv_public_init() {
    global $calendrier_rdv_public;
    
    if (!isset($calendrier_rdv_public)) {
        $calendrier_rdv_public = new Calendrier_RDV_Public(
            'calendrier-rdv',
            CALENDRIER_RDV_VERSION
        );
    }
    
    return $calendrier_rdv_public;
}

// Démarrer le plugin
calendrier_rdv_public_init();
