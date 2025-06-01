<?php
/**
 * Gestionnaire principal du calendrier de rendez-vous
 * 
 * @package CalendrierRDV
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}

class RDV_Calendar {
    /**
     * Instance unique de la classe
     * 
     * @var RDV_Calendar
     */
    private static $instance = null;

    /**
     * Version de la base de données
     * 
     * @var string
     */
    private $db_version = '1.0.0';

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
        
        $this->init_hooks();
    }

    /**
     * Initialisation des hooks WordPress
     */
    private function init_hooks() {
        // Activation/désactivation du plugin
        register_activation_hook(RDV_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(RDV_PLUGIN_FILE, [$this, 'deactivate']);
        
        // Initialisation des actions AJAX
        add_action('wp_ajax_get_slots', [$this, 'ajax_get_slots']);
        add_action('wp_ajax_nopriv_get_slots', [$this, 'ajax_get_slots']);
        add_action('wp_ajax_book_slot', [$this, 'ajax_book_slot']);
        add_action('wp_ajax_nopriv_book_slot', [$this, 'ajax_book_slot']);
        
        // Initialisation des shortcodes
        add_shortcode('calendrier_rdv', [$this, 'render_calendar_shortcode']);
        
        // Chargement des assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Récupère l'instance unique de la classe
     * 
     * @return RDV_Calendar
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Activation du plugin
     */
    public static function activate() {
        $instance = self::get_instance();
        $instance->create_tables();
        $instance->schedule_cron_jobs();
    }

    /**
     * Désactivation du plugin
     */
    public static function deactivate() {
        $instance = self::get_instance();
        $instance->clear_cron_jobs();
    }

    /**
     * Création des tables de la base de données
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_rdv} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            start DATETIME NOT NULL,
            end DATETIME DEFAULT NULL,
            prestataire_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 1,
            statut VARCHAR(50) NOT NULL DEFAULT 'confirmé',
            email VARCHAR(100) DEFAULT NULL,
            telephone VARCHAR(50) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY start (start),
            KEY prestataire_id (prestataire_id),
            KEY statut (statut)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Mise à jour de la version de la base de données
        update_option('rdv_calendar_db_version', $this->db_version);
    }
    
    /**
     * Enregistrement des assets (scripts et styles)
     */
    public function enqueue_assets() {
        // Style principal
        wp_register_style(
            'rdv-calendar',
            RDV_PLUGIN_URL . 'assets/css/rdv-calendar.css',
            [],
            RDV_PLUGIN_VERSION
        );
        
        // Script principal
        wp_register_script(
            'rdv-calendar',
            RDV_PLUGIN_URL . 'assets/js/rdv-calendar.js',
            ['jquery', 'jquery-ui-datepicker'],
            RDV_PLUGIN_VERSION,
            true
        );
        
        // Localisation pour les URLs AJAX
        wp_localize_script(
            'rdv-calendar',
            'rdvCalendar',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rdv_nonce'),
                'i18n' => [
                    'loading' => __('Chargement...', 'calendrier-rdv'),
                    'invalid_date' => __('Date invalide', 'calendrier-rdv'),
                    'invalid_time' => __('Heure invalide', 'calendrier-rdv'),
                    'required_field' => __('Ce champ est requis', 'calendrier-rdv'),
                    'invalid_email' => __('Adresse email invalide', 'calendrier-rdv'),
                ]
            ]
        );
        
        // Chargement conditionnel
        if (is_singular()) {
            global $post;
            if (has_shortcode($post->post_content, 'calendrier_rdv')) {
                wp_enqueue_style('rdv-calendar');
                wp_enqueue_script('rdv-calendar');
                
                // Datepicker i18n
                wp_enqueue_script(
                    'jquery-ui-datepicker-fr',
                    RDV_PLUGIN_URL . 'assets/js/datepicker-fr.js',
                    ['jquery-ui-datepicker'],
                    '1.0.0',
                    true
                );
            }
        }
    }
    
    /**
     * Planification des tâches récurrentes
     */
    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('rdv_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'rdv_send_reminders');
        }
        
        // Ajout de l'action pour l'envoi des rappels
        add_action('rdv_send_reminders', [$this, 'send_reminders']);
    }

    /**
     * Nettoyage des tâches récurrentes
     */
    private function clear_cron_jobs() {
        wp_clear_scheduled_hook('rdv_send_reminders');
    }
    
    /**
     * Gestionnaire AJAX pour récupérer les créneaux disponibles
     */
    public function ajax_get_slots() {
        check_ajax_referer('rdv_nonce', 'nonce');
        
        $date = sanitize_text_field($_GET['date'] ?? '');
        $prestataire_id = absint($_GET['prestataire_id'] ?? 1);
        
        if (empty($date)) {
            wp_send_json_error(['message' => 'Date manquante.']);
        }
        
        $slots = $this->get_available_slots($date, $prestataire_id);
        wp_send_json_success($slots);
    }
    
    /**
     * Gestionnaire AJAX pour la réservation d'un créneau
     */
    public function ajax_book_slot() {
        check_ajax_referer('rdv_nonce', 'nonce');
        
        $data = [
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'start' => sanitize_text_field($_POST['date'] ?? '') . ' ' . sanitize_text_field($_POST['time'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'telephone' => sanitize_text_field($_POST['telephone'] ?? ''),
            'prestataire_id' => absint($_POST['prestataire_id'] ?? 1),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        ];
        
        // Validation des données
        $errors = $this->validate_booking_data($data);
        
        if (!empty($errors)) {
            wp_send_json_error(['message' => 'Erreur de validation', 'errors' => $errors]);
        }
        
        // Vérification de la disponibilité
        if (!$this->is_slot_available($data['start'], $data['prestataire_id'])) {
            wp_send_json_error(['message' => 'Ce créneau n\'est plus disponible.']);
        }
        
        // Enregistrement du rendez-vous
        $result = $this->save_appointment($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        // Envoi des notifications
        $this->send_notifications($result, $data);
        
        wp_send_json_success([
            'message' => 'Votre rendez-vous a été enregistré avec succès !',
            'appointment_id' => $result
        ]);
    }
    
    /**
     * Rendu du shortcode du calendrier
     */
    public function render_calendar_shortcode($atts) {
        // Enregistrement des scripts et styles
        wp_enqueue_style('rdv-calendar');
        wp_enqueue_script('rdv-calendar');
        
        // Récupération des prestataires
        $prestataires = $this->get_prestataires();
        
        // Rendu du template
        ob_start();
        include RDV_PLUGIN_DIR . 'templates/calendar-form.php';
        return ob_get_clean();
    }
    
    /**
     * Vérifie si un créneau est disponible
     */
    private function is_slot_available($datetime, $prestataire_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_rdv} 
            WHERE start = %s 
            AND prestataire_id = %d 
            AND statut != 'annulé'",
            $datetime,
            $prestataire_id
        ));
        
        return $count === '0';
    }
    
    /**
     * Enregistre un nouveau rendez-vous
     */
    private function save_appointment($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_rdv,
            [
                'title' => $data['title'],
                'start' => $data['start'],
                'end' => $data['start'], // Même heure de début et de fin pour un créneau
                'prestataire_id' => $data['prestataire_id'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'notes' => $data['notes'],
                'statut' => 'confirmé',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de l\'enregistrement du rendez-vous.');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Valide les données de réservation
     */
    private function validate_booking_data($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors['title'] = 'Le nom est requis.';
        }
        
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors['email'] = 'Une adresse email valide est requise.';
        }
        
        if (empty($data['telephone'])) {
            $errors['telephone'] = 'Un numéro de téléphone est requis.';
        }
        
        if (empty($data['start']) || !strtotime($data['start'])) {
            $errors['date'] = 'Date ou heure invalide.';
        }
        
        return $errors;
    }
    
    /**
     * Envoie les notifications par email
     */
    private function send_notifications($appointment_id, $data) {
        // Récupération des détails du prestataire
        $prestataire = $this->get_prestataire($data['prestataire_id']);
        
        // Données pour les templates d'email
        $email_data = [
            'appointment_id' => $appointment_id,
            'client_name' => $data['title'],
            'client_email' => $data['email'],
            'client_phone' => $data['telephone'],
            'appointment_date' => date_i18n('l j F Y', strtotime($data['start'])),
            'appointment_time' => date_i18n('H:i', strtotime($data['start'])),
            'prestataire_name' => $prestataire ? $prestataire->name : 'Notre équipe',
            'prestataire_email' => $prestataire ? $prestataire->email : get_option('admin_email'),
            'notes' => $data['notes']
        ];
        
        // Envoi au client
        $this->send_email(
            $email_data['client_email'],
            'Confirmation de votre rendez-vous',
            'emails/confirmation-client.php',
            $email_data
        );
        
        // Envoi au prestataire
        $this->send_email(
            $email_data['prestataire_email'],
            'Nouveau rendez-vous - ' . $email_data['client_name'],
            'emails/notification-prestataire.php',
            $email_data
        );
        
        // Notification admin si nécessaire
        if (get_option('rdv_notify_admin', '1') === '1') {
            $this->send_email(
                get_option('admin_email'),
                'Nouveau rendez-vous enregistré',
                'emails/notification-admin.php',
                $email_data
            );
        }
    }
    
    /**
     * Envoi d'un email avec template
     */
    private function send_email($to, $subject, $template, $data) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        // Récupération du contenu du template
        $template_path = RDV_PLUGIN_DIR . 'templates/' . $template;
        
        if (!file_exists($template_path)) {
            error_log("Template d'email introuvable : " . $template_path);
            return false;
        }
        
        ob_start();
        extract($data);
        include $template_path;
        $message = ob_get_clean();
        
        // Envoi de l'email
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Récupère les créneaux disponibles pour une date donnée
     */
    public function get_available_slots($date, $prestataire_id = 1) {
        // Heures d'ouverture (à configurer)
        $opening_hour = 9; // 9h
        $closing_hour = 19; // 19h
        $slot_duration = 30; // minutes
        
        // Création des créneaux
        $slots = [];
        $start = strtotime($date . ' ' . $opening_hour . ':00');
        $end = strtotime($date . ' ' . $closing_hour . ':00');
        
        for ($time = $start; $time < $end; $time += ($slot_duration * 60)) {
            $slots[] = date('H:i', $time);
        }
        
        // Récupération des créneaux déjà réservés
        global $wpdb;
        $reserved = $wpdb->get_col($wpdb->prepare(
            "SELECT TIME(start) 
            FROM {$this->table_rdv} 
            WHERE DATE(start) = %s 
            AND prestataire_id = %d 
            AND statut != 'annulé'
            ORDER BY start",
            $date,
            $prestataire_id
        ));
        
        // Filtrage des créneaux disponibles
        $available_slots = array_diff($slots, $reserved);
        
        return array_values($available_slots);
    }
    
    /**
     * Récupère les prestataires disponibles
     */
    private function get_prestataires() {
        // À implémenter : récupération des prestataires depuis la base de données
        // Pour l'instant, on retourne un tableau vide
        return [];
    }
    
    /**
     * Récupère les informations d'un prestataire
     */
    private function get_prestataire($prestataire_id) {
        // À implémenter : récupération d'un prestataire spécifique
        return null;
    }
    
    /**
     * Envoi des rappels de rendez-vous
     */
    public function send_reminders() {
        // À implémenter : logique d'envoi des rappels
    }
}
