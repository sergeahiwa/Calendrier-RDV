<?php
/**
 * Fichier de configuration du plugin Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes du plugin
define('CAL_RDV_VERSION', '1.0.0');
define('CAL_RDV_PLUGIN_DIR', plugin_dir_path(__FILE__) . '../');
define('CAL_RDV_PLUGIN_URL', plugin_dir_url(__FILE__) . '../');
define('CAL_RDV_PLUGIN_BASENAME', plugin_basename(dirname(__DIR__) . '/calendrier-rdv.php'));
define('CAL_RDV_PLUGIN_FILE', dirname(__DIR__) . '/calendrier-rdv.php');

// Chemins des dossiers
define('CAL_RDV_INCLUDES_DIR', CAL_RDV_PLUGIN_DIR . 'includes/');
define('CAL_RDV_ADMIN_DIR', CAL_RDV_PLUGIN_DIR . 'src/Admin/');
define('CAL_RDV_PUBLIC_DIR', CAL_RDV_PLUGIN_DIR . 'src/Public/');
define('CAL_RDV_TEMPLATES_DIR', CAL_RDV_PLUGIN_DIR . 'templates/');
define('CAL_RDV_LANGUAGES_DIR', CAL_RDV_PLUGIN_DIR . 'languages/');
define('CAL_RDV_ASSETS_DIR', CAL_RDV_PLUGIN_DIR . 'assets/');
define('CAL_RDV_ASSETS_URL', CAL_RDV_PLUGIN_URL . 'assets/');

// Chemins des assets
define('CAL_RDV_CSS_URL', CAL_RDV_ASSETS_URL . 'css/');
define('CAL_RDV_JS_URL', CAL_RDV_ASSETS_URL . 'js/');
define('CAL_RDV_IMAGES_URL', CAL_RDV_ASSETS_URL . 'images/');

// Préfixes pour les tables de base de données
define('CAL_RDV_DB_PREFIX', 'cal_rdv_');

// Rôles et capacités
define('CAL_RDV_ADMIN_CAPABILITY', 'manage_calendar_rdv');
define('CAL_RDV_PROVIDER_CAPABILITY', 'manage_calendar_rdv_provider');

// Statuts des rendez-vous
define('CAL_RDV_STATUS_PENDING', 'pending');
define('CAL_RDV_STATUS_CONFIRMED', 'confirmed');
define('CAL_RDV_STATUS_CANCELLED', 'cancelled');
define('CAL_RDV_STATUS_COMPLETED', 'completed');

// Options de configuration
$cal_rdv_options = [
    'version' => CAL_RDV_VERSION,
    'db_version' => '1.0.0',
    'installed_on' => current_time('mysql'),
];

// Charger les configurations par défaut
$defaults = require __DIR__ . '/defaults.php';

// Fusionner avec les options existantes
$cal_rdv_options = array_merge($cal_rdv_options, $defaults);

/**
 * Fonction utilitaire pour obtenir un paramètre de configuration
 *
 * @param string $key Clé de configuration
 * @param mixed $default Valeur par défaut si la clé n'existe pas
 * @return mixed
 */
function cal_rdv_get_option($key, $default = null) {
    global $cal_rdv_options;
    
    // Si la clé est au format pointé (ex: 'general.date_format')
    if (strpos($key, '.') !== false) {
        $keys = explode('.', $key);
        $value = $cal_rdv_options;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    // Sinon, chercher directement la clé
    return isset($cal_rdv_options[$key]) ? $cal_rdv_options[$key] : $default;
}

/**
 * Fonction utilitaire pour définir un paramètre de configuration
 *
 * @param string $key Clé de configuration
 * @param mixed $value Valeur à définir
 * @return bool True si la mise à jour a réussi, false sinon
 */
function cal_rdv_update_option($key, $value) {
    global $cal_rdv_options;
    
    // Si la clé est au format pointé (ex: 'general.date_format')
    if (strpos($key, '.') !== false) {
        $keys = explode('.', $key);
        $last_key = array_pop($keys);
        $array = &$cal_rdv_options;
        
        foreach ($keys as $k) {
            if (!isset($array[$k]) || !is_array($array[$k])) {
                $array[$k] = [];
            }
            $array = &$array[$k];
        }
        
        $array[$last_key] = $value;
    } else {
        $cal_rdv_options[$key] = $value;
    }
    
    // Mettre à jour l'option dans la base de données
    return update_option('cal_rdv_options', $cal_rdv_options);
}

/**
 * Fonction utilitaire pour charger un template
 *
 * @param string $template_name Nom du template (sans l'extension .php)
 * @param array $args Variables à passer au template
 * @param string $template_path Chemin personnalisé vers les templates
 * @param string $default_path Chemin par défaut si le template n'est pas trouvé
 * @return void
 */
function cal_rdv_get_template($template_name, $args = [], $template_path = '', $default_path = '') {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    $template = '';
    
    // Chemin vers le template dans le thème
    if (!$template_path) {
        $template_path = 'calendrier-rdv/';
    }
    
    // Chemin par défaut dans le plugin
    if (!$default_path) {
        $default_path = CAL_RDV_TEMPLATES_DIR;
    }
    
    // Chercher le template dans le thème
    $template = locate_template([
        trailingslashit($template_path) . $template_name . '.php',
        $template_name . '.php',
    ]);
    
    // Si le template n'est pas trouvé dans le thème, utiliser le template par défaut
    if (!$template && file_exists($default_path . $template_name . '.php')) {
        $template = $default_path . $template_name . '.php';
    }
    
    // Permettre aux thèmes et plugins de filtrer le chemin du template
    $template = apply_filters('cal_rdv_get_template', $template, $template_name, $args, $template_path, $default_path);
    
    if ($template) {
        include $template;
    }
}

/**
 * Fonction utilitaire pour obtenir l'URL d'un asset
 *
 * @param string $path Chemin relatif de l'asset
 * @return string URL complète de l'asset
 */
function cal_rdv_asset_url($path) {
    return CAL_RDV_ASSETS_URL . ltrim($path, '/');
}

// Initialiser les traductions
add_action('plugins_loaded', function() {
    // Charger les traductions
    load_plugin_textdomain(
        'calendrier-rdv',
        false,
        dirname(plugin_basename(CAL_RDV_PLUGIN_FILE)) . '/languages/'
    );
    
    // Initialiser le système de traduction
    if (class_exists('CalendrierRdv\\Core\\I18n')) {
        CalendrierRdv\Core\I18n::init(get_locale());
    }
});
