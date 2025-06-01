<?php
/**
 * Gestion des menus d'administration pour Calendrier RDV
 *
 * @package Calendrier_RDV
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant les menus d'administration
 */
class CalRdv_Admin_Menu {
    
    /**
     * Instance unique de la classe
     *
     * @var CalRdv_Admin_Menu
     */
    private static $instance = null;
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return CalRdv_Admin_Menu
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
        add_action('admin_menu', [$this, 'register_menus']);
    }
    
    /**
     * Enregistre les menus d'administration
     */
    public function register_menus() {
        // Menu principal
        add_menu_page(
            __('Calendrier RDV', 'calendrier-rdv'),
            __('Calendrier RDV', 'calendrier-rdv'),
            'edit_appointments',
            'calendrier-rdv',
            [$this, 'render_dashboard_page'],
            'dashicons-calendar-alt',
            30
        );
        
        // Sous-menus
        add_submenu_page(
            'calendrier-rdv',
            __('Tableau de bord', 'calendrier-rdv'),
            __('Tableau de bord', 'calendrier-rdv'),
            'edit_appointments',
            'calendrier-rdv',
            [$this, 'render_dashboard_page']
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Rendez-vous', 'calendrier-rdv'),
            __('Rendez-vous', 'calendrier-rdv'),
            'edit_appointments',
            'calendrier-rdv-appointments',
            [$this, 'render_appointments_page']
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Services', 'calendrier-rdv'),
            __('Services', 'calendrier-rdv'),
            'edit_services',
            'calendrier-rdv-services',
            [$this, 'render_services_page']
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Prestataires', 'calendrier-rdv'),
            __('Prestataires', 'calendrier-rdv'),
            'edit_providers',
            'calendrier-rdv-providers',
            [$this, 'render_providers_page']
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Clients', 'calendrier-rdv'),
            __('Clients', 'calendrier-rdv'),
            'edit_appointments',
            'calendrier-rdv-customers',
            [$this, 'render_customers_page']
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Paiements', 'calendrier-rdv'),
            __('Paiements', 'calendrier-rdv'),
            'edit_appointments',
            'calendrier-rdv-payments',
            [$this, 'render_payments_page']
        );

        // Nouveau menu pour le journal d'activité (Phase 2)
        add_submenu_page(
            'calendrier-rdv',
            __('Journal d\'activité', 'calendrier-rdv'),
            __('Journal d\'activité', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv-logs',
            'calendrier_rdv_admin_logs_page'
        );
        
        // Page d'exportation des données (Phase 2)
        add_submenu_page(
            'calendrier-rdv',
            __('Exporter les données', 'calendrier-rdv'),
            __('Exporter', 'calendrier-rdv'),
            'edit_appointments',
            'calendrier-rdv-export',
            'calendrier_rdv_export_page'
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Paramètres', 'calendrier-rdv'),
            __('Paramètres', 'calendrier-rdv'),
            'manage_calendar_settings',
            'calendrier-rdv-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'calendrier-rdv',
            __('Outils', 'calendrier-rdv'),
            __('Outils', 'calendrier-rdv'),
            'manage_calendar_settings',
            'calendrier-rdv-tools',
            [$this, 'render_tools_page']
        );
    }
    
    /**
     * Affiche la page du tableau de bord
     */
    public function render_dashboard_page() {
        include_once CALENDRIER_RDV_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    /**
     * Affiche la page des rendez-vous
     */
    public function render_appointments_page() {
        include_once CALENDRIER_RDV_PLUGIN_DIR . 'admin/appointments.php';
    }
    
    /**
     * Affiche la page des services
     */
    public function render_services_page() {
        include_once CALENDRIER_RDV_PLUGIN_DIR . 'admin/services.php';
    }
    
    /**
     * Affiche la page des prestataires
     */
    public function render_providers_page() {
        include_once CALENDRIER_RDV_PLUGIN_DIR . 'admin/providers.php';
    }
    
    /**
     * Affiche la page des clients
     */
    public function render_customers_page() {
        include_once CALENDRIER_RDV_PLUGIN_DIR . 'admin/customers.php';
    }
    
    /**
     * Affiche la page des paiements
     */
    public function render_payments_page() {
        include_once CALENDRIER_RDV_PLUGIN_DIR . 'admin/payments.php';
    }
    
    /**
     * Affiche la page des paramètres
     */
    public function render_settings_page() {
        include_once CALENDRIER_RDV_PLUGIN_DIR . 'admin/settings.php';
    }
    
    /**
     * Affiche la page des outils
     */
    public function render_tools_page() {
        include_once CALENDRIER_RDV_PLUGIN_DIR . 'admin/tools.php';
    }
}

/**
 * Fonction utilitaire pour accéder aux menus d'administration
 * 
 * @return CalRdv_Admin_Menu
 */
function calendrier_rdv_admin_menu() {
    return CalRdv_Admin_Menu::get_instance();
}

// Initialiser les menus d'administration
add_action('plugins_loaded', ['CalRdv_Admin_Menu', 'get_instance']);
