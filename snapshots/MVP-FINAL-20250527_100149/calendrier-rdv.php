<?php
/**
 * Plugin Name: Calendrier RDV
 * Plugin URI: https://sansolutions.com/
 * Description: Système de réservation multi-prestataires avec calendrier, notifications, AJAX/REST, gestion des fuseaux horaires et liste d'attente.
 * Version: 1.2.0
 * Author: SAN Digital Solutions
 * Author URI: https://sansolutions.com/
 * Text Domain: calendrier-rdv
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package CalendrierRdv
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes du plugin
if (!defined('CAL_RDV_PLUGIN_DIR')) {
    define('CAL_RDV_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('CALENDRIER_RDV_VERSION')) {
    define('CALENDRIER_RDV_VERSION', '1.3.0'); // Ou la version actuelle de votre plugin
}
if (!defined('CALENDRIER_RDV_PLUGIN_FILE')) {
    define('CALENDRIER_RDV_PLUGIN_FILE', __FILE__);
}

// Vérifier les dépendances PHP
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        printf(
            __('Calendrier RDV nécessite PHP 7.4 ou supérieur. Vous utilisez actuellement PHP %s. Veuillez mettre à jour votre version de PHP.', 'calendrier-rdv'),
            PHP_VERSION
        );
        echo '</p></div>';
    });
    return;
}

// Inclure les dépendances principales
require_once __DIR__ . '/includes/class-rate-limiter.php';
require_once __DIR__ . '/includes/class-email-monitor.php';
require_once __DIR__ . '/includes/class-cache-manager.php';
require_once __DIR__ . '/includes/class-query-optimizer.php';

// Charger l'autoloader Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        _e('Les dépendances de Calendrier RDV ne sont pas installées. Veuillez exécuter "composer install".', 'calendrier-rdv');
        echo '</p></div>';
    });
    return;
}

/**
 * Charge les fichiers de traduction
 */
function calendrier_rdv_load_textdomain() {
    // Chemin vers le dossier des traductions
    $domain = 'calendrier-rdv';
    $locale = apply_filters('plugin_locale', is_admin() ? get_user_locale() : get_locale(), $domain);
    
    // Chargement de la traduction dans wp-content/languages/plugins/calendrier-rdv-fr_FR.mo
    load_textdomain($domain, WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo');
    
    // Chargement de la traduction dans le dossier des langues du plugin
    $mofile = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/languages/' . $domain . '-' . $locale . '.mo';
    
    if (file_exists($mofile)) {
        load_textdomain($domain, $mofile);
    }
    
    // Ancienne méthode pour compatibilité
    load_plugin_textdomain(
        'calendrier-rdv',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}

// Initialiser le plugin
function init_calendrier_rdv() {
    // Charger les traductions
    calendrier_rdv_load_textdomain();
    
    // Initialiser le rate limiter
    $rate_limiter = calrdv_get_rate_limiter();
    
    // Initialiser les variables globales pour les traductions JS
    add_action('wp_enqueue_scripts', 'calendrier_rdv_localize_scripts', 5);
    add_action('admin_enqueue_scripts', 'calendrier_rdv_localize_scripts', 5);
    
    return \CalendrierRdv\Plugin::get_instance();
}

/**
 * Localise les scripts avec les variables nécessaires
 */
function calendrier_rdv_localize_scripts() {
    wp_localize_script(
        'calendrier-rdv-frontend',
        'calendrierRdvVars',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pluginUrl' => plugin_dir_url(__FILE__),
            'restUrl' => esc_url_raw(rest_url('calendrier-rdv/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'locale' => get_locale(),
            'localeShort' => substr(get_locale(), 0, 2),
            'translations' => array(
                // Ajoutez ici les traductions critiques qui doivent être disponibles immédiatement
                'loading' => __('Chargement...', 'calendrier-rdv'),
                'error' => __('Une erreur est survenue', 'calendrier-rdv'),
                'success' => __('Opération réussie', 'calendrier-rdv'),
            )
        )
    );
}

/**
 * Ajoute la balise HTML avec l'attribut lang
 */
function calendrier_rdv_add_html_lang_attribute($output) {
    if (!is_admin()) {
        $locale = get_locale();
        $output = str_replace('lang="', 'lang="' . $locale . '" ', $output);
    }
    return $output;
}
add_filter('language_attributes', 'calendrier_rdv_add_html_lang_attribute');

// Démarrer le plugin
// [CORRIGE] Suppression du hook fautif vers une fonction inexistante
// add_action('plugins_loaded', 'CalendrierRdv\\init_calendrier_rdv', 5);

// Charger les traductions tôt dans le chargement des plugins
add_action('plugins_loaded', 'calendrier_rdv_load_textdomain', 1);

// Initialisation du plugin
add_action('plugins_loaded', function() {
    // Initialiser l'installateur
    $installer = \CalendrierRdv\Core\Installer\Installer::getInstance();
    
    // Vérifier si c'est une installation ou une mise à jour
    if (!get_option('calendrier_rdv_db_version')) {
        $installer->install();
    } else {
        $installer->upgrade();
    }
});

// Enregistrement des endpoints REST
add_action('rest_api_init', function() {
    // Endpoint pour les services
    register_rest_route('calendrier-rdv/v1', '/services', [
        'methods' => 'GET',
        'callback' => function(WP_REST_Request $request) {
            return \CalendrierRdv\Core\Database\Services::getServices($request);
        },
        'permission_callback' => '__return_true',
    ]);

    // Endpoint pour les prestataires
    register_rest_route('calendrier-rdv/v1', '/providers', [
        'methods' => 'GET',
        'callback' => function(WP_REST_Request $request) {
            return \CalendrierRdv\Core\Database\Providers::getProviders($request);
        },
        'permission_callback' => '__return_true',
    ]);

    // Endpoint pour les rendez-vous
    register_rest_route('calendrier-rdv/v1', '/appointments', [
        'methods' => 'POST',
        'callback' => function(WP_REST_Request $request) {
            return \CalendrierRdv\Core\Database\Appointments::createAppointment($request);
        },
        'permission_callback' => function() {
            return check_ajax_referer('calendrier_rdv_nonce', false, false);
        },
    ]);
});

// Nettoyage du plugin
register_uninstall_hook(__FILE__, 'calendrier_rdv_uninstall');



// Activation du plugin
register_activation_hook(__FILE__, function() {
    // TODO: Réimplémenter la désinstallation proprement (classe Installer manquante)
    // \CalendrierRdv\Core\Installer\Installer::getInstance()->uninstall();
});

// Désactivation du plugin
register_deactivation_hook(__FILE__, function() {
    // Nettoyer les tâches planifiées
    wp_clear_scheduled_hook('calendrier_rdv_send_reminders');
    wp_clear_scheduled_hook('calendrier_rdv_cleanup_logs');
    wp_clear_scheduled_hook('calendrier_rdv_check_waitlist_availability');
    wp_clear_scheduled_hook('calendrier_rdv_daily_event');
});

// Inclure la classe de gestion de la base de données (fixe l'erreur constructeur privé)
require_once CAL_RDV_PLUGIN_DIR . 'includes/class-db.php';
// Inclure la classe d'installation (préparation future)
require_once CAL_RDV_PLUGIN_DIR . 'includes/class-calrdv-installer.php';

// Exécuter la logique d'installation si la classe existe
if (class_exists('CalRdv_Installer')) {
    CalRdv_Installer::run();
}
// Inclure le gestionnaire de rendez-vous
require_once CAL_RDV_PLUGIN_DIR . 'includes/class-appointment-manager.php';
// Inclure le gestionnaire de fuseaux horaires (fixe l'erreur Class not found)
require_once CAL_RDV_PLUGIN_DIR . 'includes/class-timezone-handler.php'; // Définit Calendrier_RDV_Timezone_Handler
// Inclure le gestionnaire de liste d'attente (fixe l'erreur Class not found)
require_once CAL_RDV_PLUGIN_DIR . 'includes/class-waitlist-handler.php'; // Définit CalRdv_Waitlist_Handler
// Inclure la classe principale du plugin
require_once CAL_RDV_PLUGIN_DIR . 'includes/class-calendrier-rdv.php';

// Charger les fonctionnalités d'exportation (Phase 2)
require_once CAL_RDV_PLUGIN_DIR . 'includes/exports/class-export-manager.php';
require_once CAL_RDV_PLUGIN_DIR . 'admin/export-page.php';

// Initialiser le plugin
function calendrier_rdv_init() {
    // Initialiser le plugin principal
    $calendrier_rdv = Calendrier_RDV::get_instance();
    
    // Charger les fichiers de traduction
    load_plugin_textdomain(
        'calendrier-rdv',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
    
    return $calendrier_rdv;
}

// Démarrer l'application
add_action('plugins_loaded', 'calendrier_rdv_init', 5);

// Fonction de désinstallation
function calendrier_rdv_uninstall() {
    global $wpdb;
    
    // Supprimer les options
    delete_option('calendrier_rdv_db_version');
    delete_option('calendrier_rdv_page_id');
    delete_option('calendrier_rdv_waitlist_enabled');
    delete_option('calendrier_rdv_timezone');
    delete_option('calendrier_rdv_max_waitlist_per_slot');
    delete_option('calendrier_rdv_waitlist_notification_email');
    
    // Supprimer les tables personnalisées
    $tables = array(
        $wpdb->prefix . 'rdv_waitlist',
        $wpdb->prefix . 'rdv_appointments',
        $wpdb->prefix . 'rdv_services',
        $wpdb->prefix . 'rdv_providers',
        $wpdb->prefix . 'rdv_customers'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Nettoyer les tâches planifiées
    $cron_hooks = array(
        'calendrier_rdv_send_reminders',
        'calendrier_rdv_cleanup_logs',
        'calendrier_rdv_check_waitlist_availability',
        'calendrier_rdv_daily_event'
    );
    
    foreach ($cron_hooks as $hook) {
        wp_clear_scheduled_hook($hook);
    }
    
    // Supprimer les pages créées
    $page_id = get_option('calendrier_rdv_page_id');
    if ($page_id) {
        wp_delete_post($page_id, true);
    }
    
    // Supprimer les transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%calendrier_rdv_%'");
    
    // Supprimer les rôles et capacités personnalisés
    $roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('manage_calendrier_rdv');
            $role->remove_cap('edit_appointments');
            $role->remove_cap('delete_appointments');
            $role->remove_cap('edit_others_appointments');
            $role->remove_cap('publish_appointments');
            $role->remove_cap('read_private_appointments');
        }
    }
    
    // Supprimer les options de la base de données
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'calendrier_rdv_%'");
}

// Enregistrer la fonction de désinstallation
register_uninstall_hook(__FILE__, 'calendrier_rdv_uninstall');

// Activation du plugin
register_activation_hook(__FILE__, function() {
    // [SUPPRIMÉ] Installateur CalRdv_Installer (classe inexistante)
    // TODO: Réimplémenter l'installateur si besoin
// require_once CAL_RDV_PLUGIN_DIR . 'includes/class-installer.php';
    
    // [SUPPRIMÉ] Initialiser l'installateur via la méthode statique (classe CalRdv_Installer manquante)
    // TODO: Réimplémenter l'installation proprement
    // $installer = CalRdv_Installer::get_instance();
    // $installer->install();
    
    // Planifier les tâches récurrentes
    if (!wp_next_scheduled('calendrier_rdv_send_reminders')) {
        wp_schedule_event(time(), 'hourly', 'calendrier_rdv_send_reminders');
    }
    
    if (!wp_next_scheduled('calendrier_rdv_cleanup_logs')) {
        wp_schedule_event(time(), 'daily', 'calendrier_rdv_cleanup_logs');
    }
    
    // Planifier la vérification quotidienne des listes d'attente
    if (!wp_next_scheduled('calendrier_rdv_check_waitlist_availability')) {
        wp_schedule_event(time(), 'daily', 'calendrier_rdv_check_waitlist_availability');
    }
    
    // Planifier l'événement quotidien pour les tâches de maintenance
    if (!wp_next_scheduled('calendrier_rdv_daily_event')) {
        wp_schedule_event(time(), 'daily', 'calendrier_rdv_daily_event');
    }
    
    // Créer la page de réservation si elle n'existe pas
    $page_title = __('Prendre un rendez-vous', 'calendrier-rdv');
    $page_slug = 'prendre-rendez-vous';
    $shortcode = '[calendrier_rdv_booking]';
    
    $existing_page = get_page_by_path($page_slug, OBJECT, 'page');
    
    if (!$existing_page) {
        $page_id = wp_insert_post([
            'post_title'     => $page_title,
            'post_name'      => $page_slug,
            'post_content'   => $shortcode,
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
            'ping_status'    => 'closed'
        ]);
        
        if (!is_wp_error($page_id)) {
            update_option('calendrier_rdv_booking_page_id', $page_id);
        }
    }
});

// 🔔 Hook pour rappels automatiques (cron prêt à l'emploi)
add_action('calendrier_rdv_check_reminders', function() {
    if (defined('CALENDRIER_RDV_DEBUG') && CALENDRIER_RDV_DEBUG) {
        error_log('Hook calendrier_rdv_check_reminders exécuté.');
    }
    // Ici, appeler la fonction de vérification/envoi des rappels
});

// 🧩 Shortcode
add_shortcode('calendrier_rdv', function() {
    // Fallback : si la page a été supprimée, la recréer automatiquement
    $page_id = get_option('calendrier_rdv_page_id');
    if ($page_id && !get_post($page_id)) {
        // Page supprimée, on la recrée
        $page_title = __('Calendrier des Rendez-vous', 'calendrier-rdv');
        $shortcode = '[calendrier_rdv]';
        $page_id = wp_insert_post([
            'post_title'     => $page_title,
            'post_content'   => $shortcode,
            'post_status'    => 'publish',
            'post_type'      => 'page',
        ]);
        if ($page_id) update_option('calendrier_rdv_page_id', $page_id);
        if (function_exists('calrdv_log')) calrdv_log('Page calendrier recréée automatiquement.');
    }
    ob_start();
    include CAL_RDV_PLUGIN_DIR . 'public/affichage-calendrier.php';
    return ob_get_clean();
});

// 📂 Includes
require_once CAL_RDV_PLUGIN_DIR . 'includes/calendar-functions.php';
require_once CAL_RDV_PLUGIN_DIR . 'includes/flash.php';
require_once CAL_RDV_PLUGIN_DIR . 'includes/rest-api.php';

// Charger l'interface d'administration
// Sécurité admin renforcée via hook
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'calendrier-rdv'));
    }
});

if (is_admin()) {
    // Charger les fichiers d'administration
    $admin_files = array(
        'admin/class-admin.php',
        'admin/calendrier.php',
        'admin/liste-rdv.php',
        'admin/settings.php',
        'admin/export.php',
        'admin/login.php',
        'admin/logout.php',
        'admin/set-flash.php'
    );
    
    foreach ($admin_files as $file) {
        $file_path = CAL_RDV_PLUGIN_DIR . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    // Ajout du sous-menu Vue calendrier
    add_action('admin_menu', function() {
        add_submenu_page(
            'calendrier-rdv-admin',
            __('Vue calendrier', 'calendrier-rdv'),
            __('Vue calendrier', 'calendrier-rdv'),
            'manage_options',
            'calrdv-admin-calendar',
            function() { require CAL_RDV_PLUGIN_DIR . 'admin/admin-calendrier.php'; }
        );
    });
    
    // Exemple de log si debug
    if (defined('CALENDRIER_RDV_DEBUG') && CALENDRIER_RDV_DEBUG) {
        error_log('Admin calendrier-rdv chargé.');
    }
}

// ⚙️ Handlers
// TODO: Handler AJAX/REST à réimplémenter si besoin
// require_once CAL_RDV_PLUGIN_DIR . 'includes/handlers/class-ajax-handler.php';
// require_once CAL_RDV_PLUGIN_DIR . 'includes/handlers/class-rest-handler.php';

// 📦 Assets
add_action('wp_enqueue_scripts', function() {
    $page_id = get_option('calendrier_rdv_page_id');
    if (is_page($page_id)) {
        wp_enqueue_style('cal-rdv-style', CAL_RDV_PLUGIN_URL . 'public/css/calendrier-rdv-public.css', [], CAL_RDV_VERSION);
        wp_enqueue_script('cal-rdv-script', CAL_RDV_PLUGIN_URL . 'public/js/calendrier-rdv-public.js', ['jquery', 'jquery-ui-datepicker'], CAL_RDV_VERSION, true);
        
        // Localisation des données pour le script
        wp_localize_script('cal-rdv-script', 'calRDVData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => esc_url(rest_url('calendrier-rdv/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'date_format' => get_option('date_format', 'Y-m-d'),
            'time_format' => get_option('time_format', 'H:i'),
            'timezone' => wp_timezone_string(),
            'i18n' => [
                'loading' => __('Chargement...', 'calendrier-rdv'),
                'error' => __('Une erreur est survenue', 'calendrier-rdv'),
                'invalid_date' => __('Date invalide', 'calendrier-rdv'),
                'invalid_time' => __('Heure invalide', 'calendrier-rdv'),
                'required_field' => __('Ce champ est obligatoire', 'calendrier-rdv'),
                'invalid_email' => __('Adresse email invalide', 'calendrier-rdv')
            ]
        ]);
    }
});

// 📅 Initialisation des shortcodes
add_shortcode('calendrier_rdv_booking', function() {
    ob_start();
    include CAL_RDV_PLUGIN_DIR . 'public/partials/booking-form.php';
    return ob_get_clean();
});

// 🔄 Initialisation des hooks AJAX
add_action('wp_ajax_calrdv_submit_booking', 'calrdv_handle_booking_submission');
add_action('wp_ajax_nopriv_calrdv_submit_booking', 'calrdv_handle_booking_submission');
add_action('wp_ajax_calrdv_get_available_slots', 'calrdv_get_available_slots');
add_action('wp_ajax_nopriv_calrdv_get_available_slots', 'calrdv_get_available_slots');

// 🌍 Initialisation de l'API REST
add_action('rest_api_init', function() {
    // Endpoint pour la disponibilité
    register_rest_route('calendrier-rdv/v1', '/availability', [
        'methods' => 'GET',
        'callback' => 'calrdv_rest_get_availability',
        'permission_callback' => '__return_true',
        'args' => [
            'service_id' => [
                'required' => true,
                'validate_callback' => 'is_numeric',
                'sanitize_callback' => 'absint',
                'description' => 'ID du service pour lequel vérifier la disponibilité'
            ],
            'date' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return (bool) strtotime($param);
                },
                'description' => 'Date au format YYYY-MM-DD'
            ]
        ]
    ]);
    
    // Autres endpoints REST...
});

/**
 * Met en file d'attente les scripts et styles pour le frontend
 * et localise les scripts avec les variables nécessaires.
 */
function calendrier_rdv_enqueue_frontend_scripts() {
    // S'assurer que le script i18n-config.js est bien à cet emplacement
    // et qu'il n'a pas de dépendances non déclarées ici.
    wp_enqueue_script(
        'calendrier-rdv-i18n-config',
        plugin_dir_url(CALENDRIER_RDV_PLUGIN_FILE) . 'assets/js/i18n-config.js',
        [], // Ajoutez ici les handles des dépendances si i18n-config.js en a (ex: 'i18next', 'react-i18next')
        CALENDRIER_RDV_VERSION,
        true // Charger dans le pied de page
    );

    $localized_vars = [
        'pluginUrl'     => trailingslashit(plugin_dir_url(CALENDRIER_RDV_PLUGIN_FILE)),
        'ajaxUrl'       => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('calendrier_rdv_ajax_nonce'), // Changez 'calendrier_rdv_ajax_nonce' si vous avez un nonce plus spécifique
        'currentLang'   => substr(get_locale(), 0, 2), // ex: 'fr', 'en'
        'dateFormat'    => get_option('date_format'),
        'timeFormat'    => get_option('time_format'),
        // Ajoutez d'autres variables nécessaires au frontend ici
    ];

    wp_localize_script('calendrier-rdv-i18n-config', 'calendrierRdvVars', $localized_vars);

    // Vous pouvez également mettre en file d'attente d'autres scripts ou styles frontend ici
    // Exemple pour un fichier CSS principal :
    // wp_enqueue_style(
    //     'calendrier-rdv-frontend-styles',
    //     plugin_dir_url(CALENDRIER_RDV_PLUGIN_FILE) . 'assets/css/frontend.css',
    //     [],
    //     CALENDRIER_RDV_VERSION
    // );
}
add_action('wp_enqueue_scripts', 'calendrier_rdv_enqueue_frontend_scripts');
