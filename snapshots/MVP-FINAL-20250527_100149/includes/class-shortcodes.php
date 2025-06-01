<?php
/**
 * Gestion des shortcodes du plugin Calendrier RDV
 *
 * @package Calendrier_RDV
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gère les shortcodes du plugin
 */
class CalRdv_Shortcodes {
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Enregistrer les shortcodes
        add_shortcode('calendrier_rdv', array($this, 'render_booking_form'));
        add_shortcode('calendrier_rdv_liste', array($this, 'render_booking_list'));
        add_shortcode('calendrier_rdv_prestataires', array($this, 'render_prestataires_list'));
        add_shortcode('calendrier_rdv_waitlist', array($this, 'render_waitlist_status'));
        
        // Charger les assets pour les shortcodes
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Charge les scripts et styles nécessaires pour les shortcodes
     */
    public function enqueue_scripts() {
        global $post;
        
        // Vérifier si la page contient l'un de nos shortcodes
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'calendrier_rdv') ||
            has_shortcode($post->post_content, 'calendrier_rdv_waitlist')
        )) {
            // Charger les styles
            wp_enqueue_style(
                'calendrier-rdv-frontend',
                CAL_RDV_PLUGIN_URL . 'public/css/calendrier-rdv-public.css',
                array(),
                CAL_RDV_VERSION,
                'all'
            );
            
            // Charger les scripts
            wp_enqueue_script(
                'calendrier-rdv-frontend',
                CAL_RDV_PLUGIN_URL . 'public/js/calendrier-rdv-public.js',
                array('jquery', 'jquery-ui-datepicker'),
                CAL_RDV_VERSION,
                true
            );
            
            // Localiser le script avec des données PHP
            wp_localize_script('calendrier-rdv-frontend', 'calendrierRdvVars', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('calendrier_rdv_nonce'),
                'timezone' => wp_timezone_string(),
                'dateFormat' => get_option('date_format'),
                'timeFormat' => get_option('time_format'),
                'waitlistEnabled' => calendrier_rdv_waitlist_is_enabled() ? 'yes' : 'no',
                'i18n' => array(
                    'loading' => __('Chargement...', 'calendrier-rdv'),
                    'error' => __('Une erreur est survenue. Veuillez réessayer.', 'calendrier-rdv'),
                    'invalidEmail' => __('Veuillez entrer une adresse email valide.', 'calendrier-rdv'),
                    'requiredField' => __('Ce champ est obligatoire.', 'calendrier-rdv'),
                    'waitlistAddSuccess' => __('Vous avez été ajouté à la liste d\'attente avec succès.', 'calendrier-rdv'),
                    'waitlistRemoveSuccess' => __('Vous avez été retiré de la liste d\'attente.', 'calendrier-rdv'),
                    'slotAvailable' => __('Place disponible', 'calendrier-rdv'),
                    'slotFull' => __('Complet', 'calendrier-rdv'),
                    'waitingList' => __('Liste d\'attente', 'calendrier-rdv'),
                    'joinWaitlist' => __('Rejoindre la liste d\'attente', 'calendrier-rdv'),
                    'leaveWaitlist' => __('Quitter la liste d\'attente', 'calendrier-rdv'),
                )
            ));
            
            // Ajouter le style pour le datepicker
            wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', array(), '1.13.2');
        }
    }
    
    /**
     * Affiche le formulaire de prise de rendez-vous avec gestion des fuseaux horaires et liste d'attente
     *
     * @param array $atts Attributs du shortcode
     * @return string HTML du formulaire
     */
    public function render_booking_form($atts) {
        // Récupérer les attributs du shortcode avec valeurs par défaut
        $atts = shortcode_atts(array(
            'prestataire_id' => 0,
            'service_id' => 0,
            'show_title' => 'yes',
            'show_description' => 'yes',
            'show_timezone_selector' => 'yes',
            'default_timezone' => '',
            'show_waitlist' => 'yes',
            'max_days_ahead' => 90,
        ), $atts, 'calendrier_rdv');
        
        // Initialiser les variables
        $prestataires = array();
        $services = array();
        $selected_prestataire = !empty($_GET['prestataire_id']) ? intval($_GET['prestataire_id']) : 0;
        $selected_service = !empty($_GET['service_id']) ? intval($_GET['service_id']) : 0;
        $selected_date = !empty($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
        $timezone = !empty($_COOKIE['calendrier_rdv_timezone']) ? sanitize_text_field($_COOKIE['calendrier_rdv_timezone']) : '';
        
        // Utiliser le fuseau horaire par défaut si non défini
        if (empty($timezone)) {
            $timezone = !empty($atts['default_timezone']) ? $atts['default_timezone'] : wp_timezone_string();
        }
        
        // Récupérer les prestataires et services
        global $wpdb;
        
        // Si un prestataire est spécifié, ne charger que ses services
        if (!empty($atts['prestataire_id'])) {
            $prestataire_id = intval($atts['prestataire_id']);
            $prestataires = $wpdb->get_results($wpdb->prepare(
                "SELECT id, nom, description, photo, timezone 
                FROM {$wpdb->prefix}prestataires 
                WHERE id = %d AND actif = 1",
                $prestataire_id
            ));
            
            $services = $wpdb->get_results($wpdb->prepare(
                "SELECT s.id, s.nom, s.duree, s.prix, s.description, s.capacite_max 
                FROM {$wpdb->prefix}services s
                INNER JOIN {$wpdb->prefix}prestataires_services ps ON s.id = ps.service_id
                WHERE ps.prestataire_id = %d AND s.actif = 1
                ORDER BY s.nom",
                $prestataire_id
            ));
        } else {
            // Sinon, charger tous les prestataires et services actifs
            $prestataires = $wpdb->get_results(
                "SELECT id, nom, description, photo, timezone 
                FROM {$wpdb->prefix}prestataires 
                WHERE actif = 1 
                ORDER BY nom"
            );
            
            $services = $wpdb->get_results(
                "SELECT id, nom, duree, prix, description, capacite_max 
                FROM {$wpdb->prefix}services 
                WHERE actif = 1 
                ORDER BY nom"
            );
        }
        
        // Si un service est présélectionné, filtrer les prestataires
        if (!empty($selected_service)) {
            $filtered_prestataires = array();
            foreach ($prestataires as $prestataire) {
                $service_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) 
                    FROM {$wpdb->prefix}prestataires_services 
                    WHERE prestataire_id = %d AND service_id = %d",
                    $prestataire->id,
                    $selected_service
                ));
                
                if ($service_count > 0) {
                    $filtered_prestataires[] = $prestataire;
                }
            }
            $prestataires = $filtered_prestataires;
        }
        
        // Récupérer les créneaux disponibles si une date est sélectionnée
        $available_slots = array();
        $selected_prestataire_data = null;
        $selected_service_data = null;
        
        if ($selected_prestataire && $selected_service && $selected_date) {
            // Récupérer les informations du prestataire et du service
            $selected_prestataire_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}prestataires WHERE id = %d",
                $selected_prestataire
            ));
            
            $selected_service_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}services WHERE id = %d",
                $selected_service
            ));
            
            if ($selected_prestataire_data && $selected_service_data) {
                // Récupérer les créneaux disponibles
                $available_slots = $this->get_available_slots(
                    $selected_prestataire,
                    $selected_service,
                    $selected_date,
                    $timezone
                );
            }
        }
        
        // Démarrer la mise en mémoire tampon de sortie
        ob_start();
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'public/partials/booking-form.php';
        
        // Récupérer et nettoyer le contenu du tampon de sortie
        return ob_get_clean();
    }
    
    /**
     * Récupère les créneaux disponibles pour un prestataire, un service et une date donnés
     * 
     * @param int $prestataire_id ID du prestataire
     * @param int $service_id ID du service
     * @param string $date Date au format Y-m-d
     * @param string $timezone Fuseau horaire
     * @return array Tableau des créneaux disponibles
     */
    private function get_available_slots($prestataire_id, $service_id, $date, $timezone) {
        global $wpdb;
        
        // Récupérer les horaires d'ouverture du prestataire pour ce jour de la semaine
        $day_of_week = date('N', strtotime($date)); // 1 (lundi) à 7 (dimanche)
        
        $opening_hours = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}prestataires_horaires 
            WHERE prestataire_id = %d AND jour = %d",
            $prestataire_id,
            $day_of_week
        ));
        
        if (!$opening_hours || !$opening_hours->actif) {
            return array(); // Le prestataire ne travaille pas ce jour-là
        }
        
        // Récupérer les créneaux déjà réservés
        $booked_slots = $wpdb->get_col($wpdb->prepare(
            "SELECT heure_rdv 
            FROM {$wpdb->prefix}reservations 
            WHERE prestataire = %d 
            AND date_rdv = %s 
            AND statut IN ('confirme', 'en_attente')",
            $prestataire_id,
            $date
        ));
        
        // Convertir en tableau associatif pour une recherche plus rapide
        $booked_slots = array_flip($booked_slots);
        
        // Récupérer les informations du service
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}services WHERE id = %d",
            $service_id
        ));
        
        if (!$service) {
            return array();
        }
        
        // Convertir les horaires d'ouverture en timestamp
        $start_time = strtotime($date . ' ' . $opening_hours->heure_debut);
        $end_time = strtotime($date . ' ' . $opening_hours->heure_fin);
        $duration = $service->duree * 60; // Convertir en secondes
        $interval = 30 * 60; // Intervalle de 30 minutes entre les créneaux
        
        $available_slots = array();
        $current_time = $start_time;
        
        // Générer les créneaux disponibles
        while (($current_time + $duration) <= $end_time) {
            $slot_time = date('H:i:s', $current_time);
            $slot_end = date('H:i:s', $current_time + $duration);
            
            // Vérifier si le créneau est déjà réservé
            if (!isset($booked_slots[$slot_time])) {
                $available_slots[] = array(
                    'start' => $slot_time,
                    'end' => $slot_end,
                    'available' => true,
                    'waitlist' => false,
                );
            } else {
                // Vérifier s'il y a une liste d'attente pour ce créneau
                $waitlist_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) 
                    FROM {$wpdb->prefix}rdv_waitlist 
                    WHERE service_id = %d 
                    AND date = %s 
                    AND start_time = %s 
                    AND status = 'waiting'",
                    $service_id,
                    $date,
                    $slot_time
                ));
                
                $available_slots[] = array(
                    'start' => $slot_time,
                    'end' => $slot_end,
                    'available' => false,
                    'waitlist' => $waitlist_count > 0,
                    'waitlist_count' => $waitlist_count,
                );
            }
            
            $current_time += $interval;
        }
        
        return $available_slots;
    }
    
    /**
     * Affiche la liste des rendez-vous à venir
     */
    public function render_booking_list($atts) {
        // Vérifier les capacités de l'utilisateur
        if (!current_user_can('manage_options')) {
            return '<div class="calendrier-rdv-notice notice notice-warning">' . 
                   __('Vous devez être connecté en tant qu\'administrateur pour voir cette liste.', 'calendrier-rdv') . 
                   '</div>';
        }
        
        // Récupérer les attributs du shortcode
        $atts = shortcode_atts(array(
            'limit' => 10,
            'status' => 'all', // all, upcoming, past, confirmed, cancelled
            'show_pagination' => 'yes'
        ), $atts, 'calendrier_rdv_liste');
        
        // Récupérer les paramètres de pagination
        $paged = max(1, get_query_var('paged'));
        $limit = intval($atts['limit']);
        $offset = ($paged - 1) * $limit;
        
        // Construire la requête
        global $wpdb;
        $table_rdv = $wpdb->prefix . 'calrdv_reservations';
        $table_presta = $wpdb->prefix . 'calrdv_prestataires';
        $table_services = $wpdb->prefix . 'calrdv_services';
        
        $where = array('1=1');
        $params = array();
        
        // Filtrer par statut
        if ($atts['status'] !== 'all') {
            switch ($atts['status']) {
                case 'upcoming':
                    $where[] = 'r.date_rdv >= CURDATE()';
                    $where[] = 'r.statut = "confirme"';
                    break;
                case 'past':
                    $where[] = 'r.date_rdv < CURDATE()';
                    break;
                case 'confirmed':
                    $where[] = 'r.statut = "confirme"';
                    break;
                case 'cancelled':
                    $where[] = 'r.statut = "annule"';
                    break;
            }
        }
        
        // Compter le nombre total de rendez-vous
        $count_query = "
            SELECT COUNT(*) 
            FROM $table_rdv r
            WHERE " . implode(' AND ', $where);
        
        $total = $wpdb->get_var($wpdb->prepare($count_query, $params));
        $max_num_pages = ceil($total / $limit);
        
        // Récupérer les rendez-vous
        $query = "
            SELECT r.*, 
                   p.nom as prestataire_nom, 
                   s.nom as service_nom,
                   s.duree as service_duree
            FROM $table_rdv r
            LEFT JOIN $table_presta p ON r.prestataire_id = p.id
            LEFT JOIN $table_services s ON r.service_id = s.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY r.date_rdv DESC, r.heure_debut DESC
            LIMIT %d, %d";
        
        $params[] = $offset;
        $params[] = $limit;
        
        $rdvs = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Démarrer la mise en mémoire tampon de sortie
        ob_start();
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'public/views/booking-list.php';
        
        // Récupérer et nettoyer le contenu du tampon de sortie
        return ob_get_clean();
    }
    
    /**
     * Affiche la liste des prestataires avec leurs services et disponibilités
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML de la liste des prestataires
     */
    public function render_prestataires_list($atts) {
        // Récupérer les attributs du shortcode avec valeurs par défaut
        $atts = shortcode_atts(array(
            'show_services' => 'yes',
            'show_photos' => 'yes',
            'show_availability' => 'yes',
            'columns' => 3,
            'limit' => -1,
            'service_id' => 0,
            'category' => ''
        ), $atts, 'calendrier_rdv_prestataires');
        
        // Récupérer les prestataires
        global $wpdb;
        
        // Construire la requête de base
        $query = "
            SELECT DISTINCT p.*
            FROM {$wpdb->prefix}prestataires p
            WHERE p.actif = 1
        ";
        
        // Filtrer par service si spécifié
        if (!empty($atts['service_id'])) {
            $service_id = intval($atts['service_id']);
            $query .= $wpdb->prepare(
                " AND EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}prestataires_services ps 
                    WHERE ps.prestataire_id = p.id AND ps.service_id = %d
                )",
                $service_id
            );
        }
        
        // Filtrer par catégorie si spécifiée
        if (!empty($atts['category'])) {
            $category = sanitize_text_field($atts['category']);
            $query .= $wpdb->prepare(
                " AND EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}term_relationships tr
                    INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    INNER JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
                    WHERE tr.object_id = p.id 
                    AND tt.taxonomy = 'prestataire_category' 
                    AND t.slug = %s
                )",
                $category
            );
        }
        
        // Trier et limiter
        $query .= " ORDER BY p.nom";
        
        if ($atts['limit'] > 0) {
            $query .= $wpdb->prepare(" LIMIT %d", intval($atts['limit']));
        }
        
        $prestataires = $wpdb->get_results($query);
        
        // Pour chaque prestataire, récupérer les services et les prochaines disponibilités
        foreach ($prestataires as &$prestataire) {
            // Récupérer les services du prestataire
            $prestataire->services = $wpdb->get_results($wpdb->prepare(
                "SELECT s.* 
                FROM {$wpdb->prefix}services s
                INNER JOIN {$wpdb->prefix}prestataires_services ps ON s.id = ps.service_id
                WHERE ps.prestataire_id = %d AND s.actif = 1
                ORDER BY s.nom",
                $prestataire->id
            ));
            
            // Récupérer les prochaines disponibilités (si demandé)
            if ('yes' === $atts['show_availability']) {
                $prestataire->next_availability = $this->get_next_available_slots($prestataire->id);
            }
        }
        
        $prestataires = $wpdb->get_results($query);
        
        // Démarrer la mise en mémoire tampon de sortie
        ob_start();
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'public/views/prestataires-list.php';
        
        // Récupérer et nettoyer le contenu du tampon de sortie
        return ob_get_clean();
    }
    
    /**
     * Affiche le statut de la liste d'attente d'un utilisateur
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML du statut de la liste d'attente
     */
    public function render_waitlist_status($atts) {
        // Vérifier si l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return '<div class="calendrier-rdv-notice">' . 
                   __('Vous devez être connecté pour voir vos listes d\'attente.', 'calendrier-rdv') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . 
                   __('Se connecter', 'calendrier-rdv') . '</a></div>';
        }
        
        // Récupérer les attributs du shortcode
        $atts = shortcode_atts(array(
            'show_past_entries' => 'no',
            'limit' => 10,
        ), $atts, 'calendrier_rdv_waitlist');
        
        // Récupérer les entrées en liste d'attente de l'utilisateur
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT wl.*, s.nom as service_nom, p.nom as prestataire_nom
            FROM {$wpdb->prefix}rdv_waitlist wl
            LEFT JOIN {$wpdb->prefix}services s ON wl.service_id = s.id
            LEFT JOIN {$wpdb->prefix}prestataires p ON wl.prestataire_id = p.id
            WHERE (wl.user_id = %d OR wl.email = %s)",
            $user_id,
            $user->user_email
        );
        
        // Filtrer les entrées passées si nécessaire
        if ('no' === $atts['show_past_entries']) {
            $query .= " AND (wl.date >= CURDATE() OR wl.status = 'waiting' OR wl.status = 'notified')";
        }
        
        // Trier et limiter
        $query .= " ORDER BY wl.date ASC, wl.start_time ASC";
        
        if ($atts['limit'] > 0) {
            $query .= $wpdb->prepare(" LIMIT %d", intval($atts['limit']));
        }
        
        $waitlist_entries = $wpdb->get_results($query);
        
        // Démarrer la mise en mémoire tampon de sortie
        ob_start();
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'public/partials/waitlist-status.php';
        
        // Récupérer et nettoyer le contenu du tampon de sortie
        return ob_get_clean();
    }
    
    /**
     * Récupère les prochains créneaux disponibles pour un prestataire
     * 
     * @param int $prestataire_id ID du prestataire
     * @param int $limit Nombre de créneaux à retourner (par défaut: 3)
     * @return array Tableau des créneaux disponibles
     */
    private function get_next_available_slots($prestataire_id, $limit = 3) {
        global $wpdb;
        
        // Récupérer les services du prestataire
        $services = $wpdb->get_col($wpdb->prepare(
            "SELECT service_id FROM {$wpdb->prefix}prestataires_services WHERE prestataire_id = %d",
            $prestataire_id
        ));
        
        if (empty($services)) {
            return array();
        }
        
        // Récupérer les créneaux disponibles dans les 30 prochains jours
        $start_date = current_time('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+30 days'));
        $slots = array();
        
        // Pour chaque jour, vérifier les créneaux disponibles
        $current_date = $start_date;
        $found_slots = 0;
        
        while ($current_date <= $end_date && $found_slots < $limit) {
            $day_of_week = date('N', strtotime($current_date)); // 1 (lundi) à 7 (dimanche)
            
            // Récupérer les horaires du prestataire pour ce jour
            $opening_hours = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}prestataires_horaires 
                WHERE prestataire_id = %d AND jour = %d AND actif = 1",
                $prestataire_id,
                $day_of_week
            ));
            
            if ($opening_hours) {
                // Vérifier les créneaux disponibles pour chaque service
                foreach ($services as $service_id) {
                    $service = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}services WHERE id = %d",
                        $service_id
                    ));
                    
                    if ($service) {
                        $available_slots = $this->get_available_slots(
                            $prestataire_id,
                            $service_id,
                            $current_date,
                            wp_timezone_string()
                        );
                        
                        if (!empty($available_slots)) {
                            foreach ($available_slots as $slot) {
                                if ($slot['available']) {
                                    $slots[] = array(
                                        'date' => $current_date,
                                        'start_time' => $slot['start'],
                                        'service_id' => $service_id,
                                        'service_name' => $service->nom,
                                    );
                                    
                                    $found_slots++;
                                    
                                    if ($found_slots >= $limit) {
                                        break 3; // Sortir des 3 boucles
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
        
        return $slots;
    }
}
