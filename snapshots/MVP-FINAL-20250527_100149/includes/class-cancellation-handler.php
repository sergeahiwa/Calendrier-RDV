<?php
/**
 * Gestion des annulations de rendez-vous en ligne
 */
class Calendrier_RDV_Cancellation_Handler {
    private $cancellation_window;
    private $allow_reschedule;
    
    public function __construct() {
        $options = get_option('calendrier_rdv_cancellation_settings', []);
        
        $this->cancellation_window = isset($options['cancellation_window']) ? 
            intval($options['cancellation_window']) : 24; // Heures avant l'heure du rendez-vous
            
        $this->allow_reschedule = isset($options['allow_reschedule']) ? 
            (bool) $options['allow_reschedule'] : true;
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Ajouter la réécriture d'URL pour la page d'annulation
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        
        // Intercepter la requête pour la page d'annulation
        add_action('template_redirect', [$this, 'handle_cancellation_request']);
        
        // Ajouter un shortcode pour le formulaire d'annulation
        add_shortcode('calendrier_rdv_cancellation_form', [$this, 'render_cancellation_form']);
        
        // Ajouter un shortcode pour le formulaire de réorganisation
        add_shortcode('calendrier_rdv_reschedule_form', [$this, 'render_reschedule_form']);
    }
    
    /**
     * Ajoute les règles de réécriture d'URL
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^annuler-rendez-vous/?$',
            'index.php?calendrier_rdv_action=cancel',
            'top'
        );
        
        add_rewrite_rule(
            '^reporter-rendez-vous/([^/]+)/?$',
            'index.php?calendrier_rdv_action=reschedule&booking_id=$matches[1]',
            'top'
        );
        
        // Régénérer les règles de réécriture si nécessaire
        if (get_option('calendrier_rdv_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('calendrier_rdv_flush_rewrite_rules');
        }
    }
    
    /**
     * Ajoute les variables de requête personnalisées
     */
    public function add_query_vars($vars) {
        $vars[] = 'calendrier_rdv_action';
        $vars[] = 'booking_id';
        $vars[] = 'token';
        return $vars;
    }
    
    /**
     * Gère la requête d'annulation
     */
    public function handle_cancellation_request() {
        global $wp_query;
        
        $action = get_query_var('calendrier_rdv_action');
        
        if ($action === 'cancel') {
            $this->process_cancellation_request();
        } elseif ($action === 'reschedule') {
            $this->process_reschedule_request();
        }
    }
    
    /**
     * Traite une demande d'annulation
     */
    private function process_cancellation_request() {
        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        // Vérifier les paramètres requis
        if (!$booking_id || !$token) {
            $this->show_error(__('Paramètres de requête invalides.', 'calendrier-rdv'));
            return;
        }
        
        // Vérifier le jeton de sécurité
        if (!$this->verify_booking_token($booking_id, $token)) {
            $this->show_error(__('Lien d\'annulation invalide ou expiré.', 'calendrier-rdv'));
            return;
        }
        
        // Vérifier si l'annulation est autorisée
        $booking = $this->get_booking_details($booking_id);
        if (!$this->is_cancellation_allowed($booking)) {
            $this->show_error(__('L\'annulation n\'est plus possible pour ce rendez-vous.', 'calendrier-rdv'));
            return;
        }
        
        // Traiter la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancellation'])) {
            $this->process_cancellation_confirmation($booking_id);
            return;
        }
        
        // Afficher le formulaire de confirmation
        $this->render_cancellation_confirmation($booking);
        exit;
    }
    
    /**
     * Traite la confirmation d'annulation
     */
    private function process_cancellation_confirmation($booking_id) {
        check_admin_referer('calendrier_rdv_cancel_booking_' . $booking_id, 'cancellation_nonce');
        
        // Marquer le rendez-vous comme annulé
        $result = $this->cancel_booking($booking_id);
        
        if (is_wp_error($result)) {
            $this->show_error($result->get_error_message());
            return;
        }
        
        // Envoyer une confirmation d'annulation
        $this->send_cancellation_confirmation($booking_id);
        
        // Afficher la confirmation
        $this->show_success(
            __('Votre rendez-vous a été annulé avec succès.', 'calendrier-rdv'),
            $this->get_reschedule_button($booking_id)
        );
    }
    
    /**
     * Annule un rendez-vous
     */
    public function cancel_booking($booking_id, $notify = true) {
        global $wpdb;
        
        // Vérifier si le rendez-vous existe et n'est pas déjà annulé
        $booking = $this->get_booking_details($booking_id);
        if (!$booking) {
            return new WP_Error('booking_not_found', __('Rendez-vous introuvable.', 'calendrier-rdv'));
        }
        
        if ($booking->status === 'cancelled') {
            return new WP_Error('already_cancelled', __('Ce rendez-vous a déjà été annulé.', 'calendrier-rdv'));
        }
        
        // Mettre à jour le statut
        $result = $wpdb->update(
            $wpdb->prefix . 'rdv_booking',
            [
                'status' => 'cancelled',
                'cancelled_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $booking_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            return new WP_Error('database_error', __('Erreur lors de l\'annulation du rendez-vous.', 'calendrier-rdv'));
        }
        
        // Déclencher une action pour les extensions
        do_action('calendrier_rdv_booking_cancelled', $booking_id, $booking);
        
        // Envoyer une notification d'annulation si demandé
        if ($notify) {
            $this->send_cancellation_notification($booking_id);
        }
        
        return true;
    }
    
    /**
     * Vérifie si l'annulation est autorisée pour un rendez-vous
     */
    private function is_cancellation_allowed($booking) {
        // Vérifier si le rendez-vous est déjà annulé
        if ($booking->status === 'cancelled') {
            return false;
        }
        
        // Vérifier la fenêtre d'annulation
        $appointment_time = strtotime($booking->date_rdv . ' ' . $booking->heure_debut);
        $cancellation_deadline = $appointment_time - ($this->cancellation_window * HOUR_IN_SECONDS);
        
        if (current_time('timestamp') > $cancellation_deadline) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Affiche le formulaire de confirmation d'annulation
     */
    private function render_cancellation_confirmation($booking) {
        $appointment_time = strtotime($booking->date_rdv . ' ' . $booking->heure_debut);
        $formatted_date = date_i18n(get_option('date_format'), $appointment_time);
        $formatted_time = date_i18n(get_option('time_format'), $appointment_time);
        
        // Inclure le template
        include CALENDRIER_RDV_PLUGIN_DIR . 'templates/cancellation/confirmation.php';
    }
    
    /**
     * Affiche un message d'erreur
     */
    private function show_error($message) {
        $this->render_message('error', $message);
    }
    
    /**
     * Affiche un message de succès
     */
    private function show_success($message, $additional_content = '') {
        $this->render_message('success', $message, $additional_content);
    }
    
    /**
     * Affiche un message formaté
     */
    private function render_message($type, $message, $additional_content = '') {
        // Inclure le template de message
        include CALENDRIER_RDV_PLUGIN_DIR . 'templates/common/message.php';
    }
    
    /**
     * Vérifie un jeton de réservation
     */
    private function verify_booking_token($booking_id, $token) {
        $booking = $this->get_booking_details($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        // Générer le jeton attendu
        $expected_token = $this->generate_booking_token($booking_id, $booking->confirmation_token);
        
        return hash_equals($expected_token, $token);
    }
    
    /**
     * Génère un jeton de réservation sécurisé
     */
    public function generate_booking_token($booking_id, $confirmation_token) {
        $key = wp_salt('auth');
        return hash_hmac('sha256', $booking_id . '|' . $confirmation_token, $key);
    }
    
    /**
     * Envoie une notification d'annulation
     */
    private function send_cancellation_notification($booking_id) {
        $booking = $this->get_booking_details($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        // Envoyer un email d'annulation au client
        $to = $booking->client_email;
        $subject = sprintf(
            // translators: %s: Appointment date.
            __('Confirmation d\'annulation - Rendez-vous du %s', 'calendrier-rdv'), 
            date_i18n(get_option('date_format'), strtotime($booking->date_rdv))
        );
        
        $message = $this->get_email_template('cancellation_confirmation', [
            'booking' => $booking,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ]);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        wp_mail($to, $subject, $message, $headers);
        
        // Envoyer une notification à l'administrateur
        $admin_email = get_bloginfo('admin_email');
        $admin_subject = sprintf(
            // translators: %d: Booking ID.
            __('Annulation de rendez-vous #%d', 'calendrier-rdv'), 
            $booking_id
        );
        
        wp_mail($admin_email, $admin_subject, $message, $headers);
        
        return true;
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
    
    /**
     * Charge un modèle d'email
     */
    private function get_email_template($template_name, $args = []) {
        $template_path = CALENDRIER_RDV_PLUGIN_DIR . 'templates/emails/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            return '';
        }
        
        ob_start();
        extract($args);
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Génère le bouton de réorganisation
     */
    private function get_reschedule_button($booking_id) {
        if (!$this->allow_reschedule) {
            return '';
        }
        
        $booking = $this->get_booking_details($booking_id);
        $token = $this->generate_booking_token($booking_id, $booking->confirmation_token);
        $reschedule_url = home_url("/reporter-rendez-vous/{$booking_id}/?token={$token}");
        
        return sprintf(
            '<div class="calendrier-rdv-reschedule-cta">' .
            '<p>' . __('Souhaitez-vous reporter ce rendez-vous ?', 'calendrier-rdv') . '</p>' .
            '<a href="%s" class="button button-primary">' . __('Reporter le rendez-vous', 'calendrier-rdv') . '</a>' .
            '</div>',
            esc_url($reschedule_url)
        );
    }
}

// Initialiser le gestionnaire d'annulation
function calendrier_rdv_init_cancellation_handler() {
    new Calendrier_RDV_Cancellation_Handler();
}
add_action('plugins_loaded', 'calendrier_rdv_init_cancellation_handler');
