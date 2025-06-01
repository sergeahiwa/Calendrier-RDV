<?php
/**
 * Gestion de la liste d'attente pour les créneaux complets
 */
class Calendrier_RDV_Waitlist_Handler {
    private $notify_on_availability;
    
    public function __construct() {
        $options = get_option('calendrier_rdv_waitlist_settings', []);
        $this->notify_on_availability = isset($options['notify_on_availability']) ? (bool) $options['notify_on_availability'] : true;
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Ajouter un utilisateur à la liste d'attente
        add_action('wp_ajax_calendrier_rdv_join_waitlist', [$this, 'ajax_join_waitlist']);
        add_action('wp_ajax_nopriv_calendrier_rdv_join_waitlist', [$this, 'ajax_join_waitlist']);
        
        // Vérifier les créneaux disponibles pour les personnes en liste d'attente
        add_action('calendrier_rdv_booking_cancelled', [$this, 'check_waitlist_on_booking_cancelled']);
        
        // Planifier la vérification quotidienne des créneaux
        if (!wp_next_scheduled('calendrier_rdv_check_waitlist_availability')) {
            wp_schedule_event(time(), 'daily', 'calendrier_rdv_check_waitlist_availability');
        }
        
        add_action('calendrier_rdv_check_waitlist_availability', [$this, 'check_waitlist_availability']);
    }
    
    /**
     * Gère la requête AJAX pour rejoindre la liste d'attente
     */
    public function ajax_join_waitlist() {
        check_ajax_referer('calendrier_rdv_nonce', 'nonce');
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';
        $end_time = isset($_POST['end_time']) ? sanitize_text_field($_POST['end_time']) : '';
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        
        // Validation des données
        if (!$service_id || !$date || !$start_time || !$end_time || !$email) {
            wp_send_json_error(['message' => __('Veuillez remplir tous les champs obligatoires.', 'calendrier-rdv')]);
        }
        
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Adresse email invalide.', 'calendrier-rdv')]);
        }
        
        // Vérifier si l'utilisateur est déjà dans la liste d'attente pour ce créneau
        if ($this->is_user_on_waitlist($email, $service_id, $date, $start_time)) {
            wp_send_json_error(['message' => __('Vous êtes déjà sur la liste d\'attente pour ce créneau.', 'calendrier-rdv')]);
        }
        
        // Ajouter à la liste d'attente
        $waitlist_id = $this->add_to_waitlist([
            'service_id' => $service_id,
            'date' => $date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'position' => $this->get_next_waitlist_position($service_id, $date, $start_time),
            'status' => 'waiting',
            'created_at' => current_time('mysql'),
        ]);
        
        if (!$waitlist_id) {
            wp_send_json_error(['message' => __('Une erreur est survenue. Veuillez réessayer.', 'calendrier-rdv')]);
        }
        
        // Envoyer une confirmation
        $this->send_waitlist_confirmation($waitlist_id);
        
        wp_send_json_success([
            'message' => __('Vous avez été ajouté à la liste d\'attente avec succès. Nous vous contacterons si une place se libère.', 'calendrier-rdv'),
            'waitlist_id' => $waitlist_id,
        ]);
    }
    
    /**
     * Vérifie la liste d'attente lorsqu'une réservation est annulée
     */
    public function check_waitlist_on_booking_cancelled($booking_id) {
        $booking = $this->get_booking_details($booking_id);
        
        if (!$booking) {
            return;
        }
        
        // Vérifier s'il y a des personnes en liste d'attente pour ce créneau
        $waitlist_entries = $this->get_waitlist_entries(
            $booking->service_id, 
            $booking->date_rdv,
            $booking->heure_debut
        );
        
        if (empty($waitlist_entries)) {
            return;
        }
        
        // Prend la première personne de la liste
        $next_person = array_shift($waitlist_entries);
        
        // Créer automatiquement une réservation pour cette personne
        $booking_data = [
            'service_id' => $booking->service_id,
            'prestataire_id' => $booking->prestataire_id,
            'client_nom' => $next_person->name,
            'client_email' => $next_person->email,
            'client_telephone' => $next_person->phone,
            'date_rdv' => $booking->date_rdv,
            'heure_debut' => $booking->heure_debut,
            'duree' => $booking->duree,
            'notes' => __('Réservation automatique depuis la liste d\'attente', 'calendrier-rdv'),
            'source' => 'waitlist_auto',
        ];
        
        $new_booking_id = $this->create_booking($booking_data);
        
        if ($new_booking_id) {
            // Mettre à jour le statut de l'entrée de la liste d'attente
            $this->update_waitlist_entry($next_person->id, [
                'status' => 'booked',
                'booking_id' => $new_booking_id,
                'notified_at' => current_time('mysql'),
            ]);
            
            // Envoyer une notification
            $this->send_waitlist_availability_notification($next_person->id, $new_booking_id);
            
            // Mettre à jour la position des autres personnes en liste d'attente
            $this->update_waitlist_positions($waitlist_entries);
        }
    }
    
    /**
     * Vérifie périodiquement la disponibilité des créneaux en liste d'attente
     */
    public function check_waitlist_availability() {
        global $wpdb;
        
        // Récupérer les entrées en liste d'attente actives
        $waitlist_entries = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rdv_waitlist 
            WHERE status = 'waiting' 
            AND date >= CURDATE()
            ORDER BY service_id, date, start_time, position"
        );
        
        if (empty($waitlist_entries)) {
            return;
        }
        
        $current_service_id = null;
        $current_date = null;
        $available_slots = [];
        
        foreach ($waitlist_entries as $entry) {
            // Si le service ou la date a changé, récupérer les créneaux disponibles
            if ($entry->service_id != $current_service_id || $entry->date != $current_date) {
                $available_slots = $this->get_available_slots($entry->service_id, $entry->date);
                $current_service_id = $entry->service_id;
                $current_date = $entry->date;
            }
            
            // Vérifier si le créneau souhaité est maintenant disponible
            $slot_key = $entry->start_time . '-' . $entry->end_time;
            
            if (isset($available_slots[$slot_key])) {
                // Créer automatiquement une réservation
                $booking_data = [
                    'service_id' => $entry->service_id,
                    'client_nom' => $entry->name,
                    'client_email' => $entry->email,
                    'client_telephone' => $entry->phone,
                    'date_rdv' => $entry->date,
                    'heure_debut' => $entry->start_time,
                    'duree' => $this->calculate_duration($entry->start_time, $entry->end_time),
                    'notes' => __('Réservation automatique depuis la liste d\'attente', 'calendrier-rdv'),
                    'source' => 'waitlist_auto',
                ];
                
                $booking_id = $this->create_booking($booking_data);
                
                if ($booking_id) {
                    // Mettre à jour l'entrée de la liste d'attente
                    $this->update_waitlist_entry($entry->id, [
                        'status' => 'booked',
                        'booking_id' => $booking_id,
                        'notified_at' => current_time('mysql'),
                    ]);
                    
                    // Envoyer une notification
                    $this->send_waitlist_availability_notification($entry->id, $booking_id);
                }
            }
        }
    }
    
    /**
     * Vérifie si un utilisateur est déjà dans la liste d'attente pour un créneau donné
     */
    private function is_user_on_waitlist($email, $service_id, $date, $start_time) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rdv_waitlist 
            WHERE email = %s 
            AND service_id = %d 
            AND date = %s 
            AND start_time = %s 
            AND status = 'waiting'",
            $email,
            $service_id,
            $date,
            $start_time
        ));
        
        return $count > 0;
    }
    
    /**
     * Ajoute une entrée à la liste d'attente
     */
    private function add_to_waitlist($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'rdv_waitlist',
            $data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Récupère les entrées de la liste d'attente pour un créneau donné
     */
    private function get_waitlist_entries($service_id, $date, $start_time) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rdv_waitlist 
            WHERE service_id = %d 
            AND date = %s 
            AND start_time = %s 
            AND status = 'waiting' 
            ORDER BY position ASC",
            $service_id,
            $date,
            $start_time
        ));
    }
    
    /**
     * Met à jour une entrée de la liste d'attente
     */
    private function update_waitlist_entry($entry_id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'rdv_waitlist',
            $data,
            ['id' => $entry_id],
            null,
            ['%d']
        );
    }
    
    /**
     * Met à jour les positions des entrées de la liste d'attente
     */
    private function update_waitlist_positions($entries) {
        if (empty($entries)) {
            return;
        }
        
        $position = 1;
        
        foreach ($entries as $entry) {
            $this->update_waitlist_entry($entry->id, [
                'position' => $position,
                'updated_at' => current_time('mysql'),
            ]);
            $position++;
        }
    }
    
    /**
     * Récupère la prochaine position dans la liste d'attente
     */
    private function get_next_waitlist_position($service_id, $date, $start_time) {
        global $wpdb;
        
        $max_position = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(position) FROM {$wpdb->prefix}rdv_waitlist 
            WHERE service_id = %d 
            AND date = %s 
            AND start_time = %s",
            $service_id,
            $date,
            $start_time
        ));
        
        return (int) $max_position + 1;
    }
    
    /**
     * Récupère les créneaux disponibles pour un service et une date donnés
     */
    private function get_available_slots($service_id, $date) {
        // Cette méthode doit être implémentée pour récupérer les créneaux disponibles
        // en fonction de la logique métier de votre application
        return [];
    }
    
    /**
     * Calcule la durée en minutes entre deux heures
     */
    private function calculate_duration($start_time, $end_time) {
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);
        $interval = $start->diff($end);
        return ($interval->h * 60) + $interval->i;
    }
    
    /**
     * Crée une réservation
     */
    private function create_booking($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'rdv_booking',
            $data,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Envoie une confirmation d'inscription à la liste d'attente
     */
    private function send_waitlist_confirmation($waitlist_id) {
        $entry = $this->get_waitlist_entry($waitlist_id);
        
        if (!$entry) {
            return false;
        }
        
        $to = $entry->email;
        $subject = __('Confirmation d\'inscription en liste d\'attente', 'calendrier-rdv');
        
        $message = $this->get_email_template('waitlist_confirmation', [
            'entry' => $entry,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ]);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Envoie une notification de disponibilité à une personne en liste d'attente
     */
    private function send_waitlist_availability_notification($waitlist_id, $booking_id) {
        if (!$this->notify_on_availability) {
            return false;
        }
        
        $entry = $this->get_waitlist_entry($waitlist_id);
        $booking = $this->get_booking_details($booking_id);
        
        if (!$entry || !$booking) {
            return false;
        }
        
        $to = $entry->email;
        $subject = __('Un créneau s\'est libéré !', 'calendrier-rdv');
        
        $message = $this->get_email_template('waitlist_availability', [
            'entry' => $entry,
            'booking' => $booking,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ]);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Récupère une entrée de la liste d'attente
     */
    private function get_waitlist_entry($waitlist_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rdv_waitlist WHERE id = %d",
            $waitlist_id
        ));
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
}

// Initialiser le gestionnaire de liste d'attente
function calendrier_rdv_init_waitlist_handler() {
    new Calendrier_RDV_Waitlist_Handler();
}
add_action('plugins_loaded', 'calendrier_rdv_init_waitlist_handler');
