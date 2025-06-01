<?php
// =============================================
// Fichier : admin/class-admin.php
// Description : Gestion de l'interface d'administration
// Auteur : SAN Digital Solutions
// =============================================

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class CalRdv_Admin {
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Initialiser les hooks d'administration
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Ajouter des liens dans la page des plugins
        add_filter('plugin_action_links_' . CAL_RDV_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
    }
    
    /**
     * Ajoute les entrées de menu dans l'administration
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            __('Calendrier RDV', 'calendrier-rdv'),
            __('Calendrier RDV', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv',
            array($this, 'display_calendar_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Sous-menus
        add_submenu_page(
            'calendrier-rdv',
            __('Calendrier', 'calendrier-rdv'),
            __('Calendrier', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv',
            array($this, 'display_calendar_page')
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Liste des RDV', 'calendrier-rdv'),
            __('Liste des RDV', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv-liste',
            array($this, 'display_list_page')
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Prestataires', 'calendrier-rdv'),
            __('Prestataires', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv-prestataires',
            array($this, 'display_prestataires_page')
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Paramètres', 'calendrier-rdv'),
            __('Paramètres', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv-parametres',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Charge les assets (CSS/JS) de l'administration
     */
    public function enqueue_admin_assets($hook) {
        // Ne charger que sur les pages du plugin
        if (strpos($hook, 'calendrier-rdv') === false) {
            return;
        }
        
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
        wp_enqueue_style('calendrier-rdv-admin', CAL_RDV_PLUGIN_URL . 'admin/css/admin.css', array(), CAL_RDV_VERSION);
        wp_enqueue_script('calendrier-rdv-admin', CAL_RDV_PLUGIN_URL . 'admin/js/admin.js', array('jquery', 'fullcalendar', 'flatpickr', 'select2'), CAL_RDV_VERSION, true);
        
        // Localisation des scripts
        wp_localize_script('calendrier-rdv-admin', 'calendrierRdv', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('calendrier_rdv_nonce'),
            'locale' => get_locale(),
            'time_format' => get_option('time_format'),
            'date_format' => get_option('date_format'),
            'start_of_week' => get_option('start_of_week'),
            'timezone' => wp_timezone_string(),
            'texts' => array(
                'confirm_delete' => __('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?', 'calendrier-rdv'),
                'error' => __('Une erreur est survenue', 'calendrier-rdv'),
                'loading' => __('Chargement...', 'calendrier-rdv'),
                'saving' => __('Enregistrement...', 'calendrier-rdv'),
                'success' => __('Modifications enregistrées', 'calendrier-rdv'),
            )
        ));
    }
    
    /**
     * Enregistre les paramètres du plugin
     */
    public function register_settings() {
        // Section générale
        add_settings_section(
            'calendrier_rdv_general',
            __('Paramètres généraux', 'calendrier-rdv'),
            array($this, 'render_general_section'),
            'calendrier-rdv-settings'
        );
        
        // Champ : Durée par défaut des RDV
        register_setting('calendrier_rdv_settings', 'calendrier_rdv_duree_defaut');
        add_settings_field(
            'calendrier_rdv_duree_defaut',
            __('Durée par défaut des RDV (minutes)', 'calendrier-rdv'),
            array($this, 'render_number_field'),
            'calendrier-rdv-settings',
            'calendrier_rdv_general',
            array(
                'name' => 'calendrier_rdv_duree_defaut',
                'value' => get_option('calendrier_rdv_duree_defaut', 30),
                'min' => 5,
                'max' => 240,
                'step' => 5,
                'description' => __('Durée par défaut d\'un rendez-vous en minutes', 'calendrier-rdv')
            )
        );
        
        // Champ : Délai de rappel
        register_setting('calendrier_rdv_settings', 'calendrier_rdv_delai_rappel');
        add_settings_field(
            'calendrier_rdv_delai_rappel',
            __('Délai de rappel (heures)', 'calendrier-rdv'),
            array($this, 'render_number_field'),
            'calendrier-rdv-settings',
            'calendrier_rdv_general',
            array(
                'name' => 'calendrier_rdv_delai_rappel',
                'value' => get_option('calendrier_rdv_delai_rappel', 24),
                'min' => 1,
                'max' => 168,
                'step' => 1,
                'description' => __('Délai avant le RDV pour l\'envoi du rappel (en heures)', 'calendrier-rdv')
            )
        );
        
        // Section notifications
        add_settings_section(
            'calendrier_rdv_notifications',
            __('Paramètres des notifications', 'calendrier-rdv'),
            array($this, 'render_notifications_section'),
            'calendrier-rdv-settings'
        );
        
        // Champ : Email de notification
        register_setting('calendrier_rdv_settings', 'calendrier_rdv_email_notification');
        add_settings_field(
            'calendrier_rdv_email_notification',
            __('Email de notification', 'calendrier-rdv'),
            array($this, 'render_email_field'),
            'calendrier-rdv-settings',
            'calendrier_rdv_notifications',
            array(
                'name' => 'calendrier_rdv_email_notification',
                'value' => get_option('calendrier_rdv_email_notification', get_bloginfo('admin_email')),
                'description' => __('Email qui recevra les notifications de nouveaux rendez-vous', 'calendrier-rdv')
            )
        );
    }
    
    /**
     * Affiche la section des paramètres généraux
     */
    public function render_general_section() {
        echo '<p>' . __('Configurez les paramètres généraux du plugin.', 'calendrier-rdv') . '</p>';
    }
    
    /**
     * Affiche la section des paramètres de notification
     */
    public function render_notifications_section() {
        echo '<p>' . __('Configurez les paramètres des notifications par email.', 'calendrier-rdv') . '</p>';
    }
    
    /**
     * Affiche un champ de type nombre
     */
    public function render_number_field($args) {
        $value = isset($args['value']) ? $args['value'] : '';
        $min = isset($args['min']) ? ' min="' . esc_attr($args['min']) . '"' : '';
        $max = isset($args['max']) ? ' max="' . esc_attr($args['max']) . '"' : '';
        $step = isset($args['step']) ? ' step="' . esc_attr($args['step']) . '"' : '';
        $description = isset($args['description']) ? $args['description'] : '';
        
        echo sprintf(
            '<input type="number" id="%1$s" name="%1$s" value="%2$s"%3$s%4$s%5$s class="regular-text" />',
            esc_attr($args['name']),
            esc_attr($value),
            $min,
            $max,
            $step
        );
        
        if (!empty($description)) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    /**
     * Affiche un champ de type email
     */
    public function render_email_field($args) {
        $value = isset($args['value']) ? $args['value'] : '';
        $description = isset($args['description']) ? $args['description'] : '';
        
        echo sprintf(
            '<input type="email" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />',
            esc_attr($args['name']),
            esc_attr($value)
        );
        
        if (!empty($description)) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }
    
    /**
     * Affiche la page du calendrier
     */
    public function display_calendar_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Récupérer les prestataires pour le filtre
        global $wpdb;
        $table_prestataires = $wpdb->prefix . 'calrdv_prestataires';
        $prestataires = $wpdb->get_results("SELECT id, nom FROM $table_prestataires WHERE actif = 1 ORDER BY nom");
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'admin/views/calendar.php';
    }
    
    /**
     * Affiche la page de liste des RDV
     */
    public function display_list_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'admin/views/rdv-list.php';
    }
    
    /**
     * Affiche la page des prestataires
     */
    public function display_prestataires_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'admin/views/prestataires.php';
    }
    
    /**
     * Affiche la page des paramètres
     */
    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Inclure le template
        include CAL_RDV_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Ajoute des liens d'action dans la page des plugins
     */
    public function add_plugin_action_links($links) {
        $action_links = array(
            'settings' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                admin_url('admin.php?page=calendrier-rdv-parametres'),
                esc_attr__('Voir les paramètres', 'calendrier-rdv'),
                __('Paramètres', 'calendrier-rdv')
            ),
            'docs' => sprintf(
                '<a href="%s" aria-label="%s" target="_blank">%s</a>',
                'https://docs.sansolutions.com/calendrier-rdv',
                esc_attr__('Voir la documentation', 'calendrier-rdv'),
                __('Documentation', 'calendrier-rdv')
            )
        );
        
        return array_merge($action_links, $links);
    }
}
