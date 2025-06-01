<?php
/**
 * Fichier principal du plugin Calendrier RDV
 *
 * @package Calendrier_RDV
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du plugin
 */
class Calendrier_RDV {
    
    /**
     * Instance unique de la classe
     *
     * @var Calendrier_RDV
     */
    private static $instance = null;
    
    /**
     * Gestionnaire de base de données
     *
     * @var CalRdv_DB
     */
    public $db;
    
    /**
     * Gestionnaire de shortcodes
     *
     * @var CalRdv_Shortcodes
     */
    public $shortcodes;
    
    /**
     * Gestionnaire d'API REST
     *
     * @var CalRdv_REST_API
     */
    public $rest_api;
    
    /**
     * Gestionnaire d'emails
     *
     * @var CalRdv_Emails
     */
    public $emails;
    
    /**
     * Gestionnaire de rendez-vous
     *
     * @var CalRdv_Appointment_Manager
     */
    public $appointments;
    
    /**
     * Gestionnaire de fuseaux horaires
     *
     * @var CalRdv_Timezone_Handler
     */
    public $timezone_handler;
    
    /**
     * Gestionnaire de liste d'attente
     *
     * @var CalRdv_Waitlist_Handler
     */
    public $waitlist_handler;
    
    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        // Ne rien mettre ici
    }
    
    /**
     * Empêcher le clonage de l'instance
     */
    private function __clone() {
        // Ne rien mettre ici
    }
    
    /**
     * Empêcher la désérialisation de l'instance
     */
    public function __wakeup() {
        // Ne rien mettre ici
    }
    
    /**
     * Obtenir l'instance unique de la classe
     *
     * @return Calendrier_RDV
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }
    
    /**
     * Initialiser le plugin
     */
    private function init() {
        // Définir les constantes
        $this->define_constants();
        
        // Charger les fichiers nécessaires
        $this->includes();
        
        // Initialiser le gestionnaire de rendez-vous
        $this->appointments = CalRdv_Appointment_Manager::get_instance();
        
                
        // Initialiser les hooks
        $this->init_hooks();
        
        // Initialiser les composants
        $this->init_components();
    }
    
    /**
     * Définir les constantes du plugin
     */
    private function define_constants() {
        // Version du plugin
        if (!defined('CAL_RDV_VERSION')) {
            define('CAL_RDV_VERSION', '1.0.0');
        }
        
        // Chemin du plugin
        if (!defined('CAL_RDV_PLUGIN_DIR')) {
            define('CAL_RDV_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
        }
        
        // URL du plugin
        if (!defined('CAL_RDV_PLUGIN_URL')) {
            define('CAL_RDV_PLUGIN_URL', plugin_dir_url(dirname(__FILE__)));
        }
        
        // Nom du fichier du plugin
        if (!defined('CAL_RDV_PLUGIN_BASENAME')) {
            define('CAL_RDV_PLUGIN_BASENAME', plugin_basename(dirname(dirname(__FILE__)) . '/calendrier-rdv.php'));
        }
        
        // Chemin des templates
        if (!defined('CAL_RDV_TEMPLATE_PATH')) {
            define('CAL_RDV_TEMPLATE_PATH', CAL_RDV_PLUGIN_DIR . 'templates/');
        }
    }
    
    /**
     * Inclure les fichiers nécessaires
     */
    private function includes() {
        // Fonctions utilitaires
        require_once CAL_RDV_PLUGIN_DIR . 'includes/functions-core.php';
        require_once CAL_RDV_PLUGIN_DIR . 'includes/functions-timezone.php';
        require_once CAL_RDV_PLUGIN_DIR . 'includes/functions-waitlist.php';
        
        // Classes principales
        require_once CAL_RDV_PLUGIN_DIR . 'includes/class-db.php';
        require_once CAL_RDV_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once CAL_RDV_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once CAL_RDV_PLUGIN_DIR . 'includes/class-emails.php';
        require_once CAL_RDV_PLUGIN_DIR . 'includes/class-timezone-handler.php';
        require_once CAL_RDV_PLUGIN_DIR . 'includes/class-waitlist-handler.php';
        
        // Classes de sécurité
        require_once CAL_RDV_PLUGIN_DIR . 'src/Core/Security/AjaxSecurity.php';
        
        // Gestionnaires AJAX
        require_once CAL_RDV_PLUGIN_DIR . 'src/Core/Api/AjaxHandlers.php';
        
        // Admin
        if (is_admin()) {
            require_once CAL_RDV_PLUGIN_DIR . 'admin/class-admin.php';
        }
    }
    
    /**
     * Initialiser les hooks WordPress
     */
    private function init_hooks() {
        // Activation et désactivation
        register_activation_hook(CAL_RDV_PLUGIN_DIR . 'calendrier-rdv.php', array($this, 'activate'));
        register_deactivation_hook(CAL_RDV_PLUGIN_DIR . 'calendrier-rdv.php', array($this, 'deactivate'));
        
        // Chargement des traductions
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        
        // Initialiser les composants après que tous les plugins soient chargés
        add_action('plugins_loaded', array($this, 'init_components'), 15);
        
        // Enregistrer les scripts et styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts_and_styles'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts_and_styles'));
        
        // Initialiser les shortcodes
        add_action('init', array($this, 'init_shortcodes'));
        
        // Initialiser l'API REST
        add_action('rest_api_init', array($this, 'init_rest_api'));
    }

    /**
     * Initialiser les composants du plugin
     */
    public function init_components() {
        // Gestionnaire de base de données
        $this->db = CalRdv_DB::get_instance();
        // Gestionnaire de fuseaux horaires
        $this->timezone_handler = new Calendrier_RDV_Timezone_Handler();
        // Gestionnaire de liste d'attente
        $this->waitlist_handler = new Calendrier_RDV_Waitlist_Handler($this->db);
        // Gestionnaire d'emails
        $this->emails = CalRdv_Emails::get_instance();
        // Initialiser les gestionnaires AJAX
        \CalendrierRdv\Core\Api\AjaxHandlers::init();
        // Initialiser les shortcodes
        $this->shortcodes = new CalRdv_Shortcodes($this->waitlist_handler, $this->timezone_handler);
        // Initialiser l'admin si nécessaire
        if (is_admin()) {
            new CalRdv_Admin($this->db, $this->waitlist_handler);
        }
    }

    /**
     * Initialiser l'API REST
     */
    public function init_rest_api() {
        $this->rest_api = new CalRdv_REST_API($this->db, $this->waitlist_handler, $this->timezone_handler);
        $this->rest_api->register_routes();
    }
    
    /**
     * Enregistrer les scripts et styles pour le frontend
     */
    public function register_scripts_and_styles() {
        // Styles
        wp_register_style(
            'calendrier-rdv-frontend',
            CAL_RDV_PLUGIN_URL . 'public/css/calendrier-rdv-public.css',
            array(),
            CAL_RDV_VERSION
        );
        
        // Scripts AJAX
        wp_register_script(
            'cal-rdv-ajax',
            CAL_RDV_PLUGIN_URL . 'assets/js/cal-rdv-ajax.js',
            array('jquery'),
            CAL_RDV_VERSION,
            true
        );
        
        // Script principal
        wp_register_script(
            'calendrier-rdv-frontend',
            CAL_RDV_PLUGIN_URL . 'public/js/calendrier-rdv-public.js',
            array('jquery', 'jquery-ui-datepicker', 'moment', 'cal-rdv-ajax'),
            CAL_RDV_VERSION,
            true
        );
        
        // Localisation des scripts AJAX
        wp_localize_script(
            'cal-rdv-ajax',
            'calRdvAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cal_rdv_ajax_nonce'),
                'i18n' => array(
                    'error' => __('Une erreur est survenue', 'calendrier-rdv'),
                    'success' => __('Opération réussie', 'calendrier-rdv')
                )
            )
        );
        
        // Localisation des scripts
        wp_localize_script(
            'calendrier-rdv-frontend',
            'calendrierRdvVars',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('calendrier_rdv_nonce'),
                'timezone' => wp_timezone_string(),
                'dateFormat' => get_option('date_format'),
                'timeFormat' => get_option('time_format'),
                'waitlistEnabled' => $this->waitlist_handler->is_enabled() ? 'yes' : 'no',
                'i18n' => array(
                    'loading' => __('Chargement...', 'calendrier-rdv'),
                    'error' => __('Une erreur est survenue. Veuillez réessayer.', 'calendrier-rdv'),
                    'invalidEmail' => __('Veuillez entrer une adresse email valide.', 'calendrier-rdv'),
                    'requiredField' => __('Ce champ est obligatoire.', 'calendrier-rdv'),
                    'noSlotsAvailable' => __('Aucun créneau disponible', 'calendrier-rdv'),
                    'selectTime' => __('Sélectionnez un horaire', 'calendrier-rdv'),
                    'full' => __('Complet', 'calendrier-rdv'),
                    'waitlistAddSuccess' => __('Vous avez été ajouté à la liste d\'attente avec succès.', 'calendrier-rdv'),
                    'waitlistRemoveSuccess' => __('Vous avez été retiré de la liste d\'attente.', 'calendrier-rdv'),
                    'waitlistError' => __('Une erreur est survenue lors de la gestion de la liste d\'attente.', 'calendrier-rdv'),
                    'slotNoLongerAvailable' => __('Ce créneau n\'est plus disponible.', 'calendrier-rdv'),
                    'confirmLeaveWaitlist' => __('Êtes-vous sûr de vouloir quitter la liste d\'attente ?', 'calendrier-rdv'),
                )
            )
        );
        
        // Ajouter le style pour le datepicker
        wp_register_style(
            'jquery-ui',
            '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
            array(),
            '1.13.2'
        );
        
        // Moment.js pour la gestion des dates
        wp_register_script('moment', '//cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js', array(), '2.29.1', true);
        wp_register_script('moment-timezone', '//cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.33/moment-timezone-with-data-10-year-range.min.js', array('moment'), '0.5.33', true);
    }
    
    /**
     * Enregistrer les scripts et styles pour l'admin
     */
    public function register_admin_scripts_and_styles($hook) {
        // Ne charger que sur les pages du plugin
        if (strpos($hook, 'calendrier-rdv') === false) {
            return;
        }
        
        // Styles admin
        wp_enqueue_style(
            'calendrier-rdv-admin',
            CAL_RDV_PLUGIN_URL . 'admin/css/calendrier-rdv-admin.css',
            array('wp-color-picker'),
            CAL_RDV_VERSION
        );
        
        // Scripts admin
        wp_enqueue_script(
            'calendrier-rdv-admin',
            CAL_RDV_PLUGIN_URL . 'admin/js/calendrier-rdv-admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
            CAL_RDV_VERSION,
            true
        );
        
        // Localisation des scripts admin
        wp_localize_script(
            'calendrier-rdv-admin',
            'calendrierRdvAdminVars',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('calendrier_rdv_admin_nonce'),
                'i18n' => array(
                    'confirmDelete' => __('Êtes-vous sûr de vouloir supprimer cet élément ?', 'calendrier-rdv'),
                    'saving' => __('Enregistrement...', 'calendrier-rdv'),
                    'saved' => __('Enregistré', 'calendrier-rdv'),
                    'error' => __('Erreur', 'calendrier-rdv')
                )
            )
        );
    }
    
    /**
     * Charger les fichiers de traduction
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'calendrier-rdv',
            false,
            dirname(plugin_basename(__FILE__)) . '/../languages/'
        );
    }
    
    /**
     * Activer le plugin
     */
    public function activate() {
        // Créer les tables de la base de données
        $this->db->create_tables();
        
        // Planifier les événements récurrents
        if (!wp_next_scheduled('calendrier_rdv_daily_event')) {
            wp_schedule_event(time(), 'daily', 'calendrier_rdv_daily_event');
        }
        
        // Définir l'option de version pour les futures mises à jour
        if (!get_option('calendrier_rdv_version')) {
            add_option('calendrier_rdv_version', CAL_RDV_VERSION);
        } else {
            update_option('calendrier_rdv_version', CAL_RDV_VERSION);
        }
        
        // Ajouter la capacité de gérer les rendez-vous pour les administrateurs
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_calendrier_rdv');
        }
    }
    
    /**
     * Désactiver le plugin
     */
    public function deactivate() {
        // Désactiver les événements planifiés
        wp_clear_scheduled_hook('calendrier_rdv_daily_event');
        
        // Nettoyer les options si nécessaire
        // delete_option('calendrier_rdv_settings');
    }
}

/**
 * Fonction pour accéder à l'instance principale du plugin
 *
 * @return Calendrier_RDV
 */
function calendrier_rdv() {
    return Calendrier_RDV::get_instance();
}

// Démarrer le plugin
calendrier_rdv();
