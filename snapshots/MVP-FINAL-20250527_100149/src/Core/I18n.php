<?php
/**
 * Gestion des traductions et textes internationaux
 *
 * @package CalendrierRdv\Core
 */

namespace CalendrierRdv\Core;

class I18n {
    /**
     * @var array Tableau des textes traduits
     */
    private static $texts = [];
    
    /**
     * @var string Langue courante
     */
    private static $locale = 'fr_FR';
    
    /**
     * Initialise le système de traduction
     * 
     * @param string $locale Langue à utiliser (par défaut: fr_FR)
     * @return void
     */
    public static function init($locale = 'fr_FR') {
        self::$locale = $locale;
        
        // Charger les textes de base
        self::loadTexts();
        
        // Initialiser les traductions WordPress
        add_action('init', [__CLASS__, 'loadTextdomain']);
    }
    
    /**
     * Charge les textes à partir des fichiers de configuration
     * 
     * @return void
     */
    private static function loadTexts() {
        // Charger les textes par défaut
        $defaultTexts = require CAL_RDV_PLUGIN_DIR . 'config/i18n.php';
        
        // Fusionner avec les textes existants
        self::$texts = array_merge_recursive(self::$texts, $defaultTexts);
        
        // Permettre aux thèmes et plugins de filtrer les textes
        self::$texts = apply_filters('cal_rdv_i18n_texts', self::$texts);
    }
    
    /**
     * Charge le fichier de traduction WordPress
     * 
     * @return void
     */
    public static function loadTextdomain() {
        load_plugin_textdomain(
            'calendrier-rdv',
            false,
            dirname(plugin_basename(CAL_RDV_PLUGIN_FILE)) . '/languages/'
        );
    }
    
    /**
     * Récupère un texte traduit
     * 
     * @param string $key Clé du texte (ex: 'general.save_changes')
     * @param array $args Arguments à passer à la fonction de traduction
     * @return string Texte traduit
     */
    public static function get($key, $args = []) {
        // Vérifier si c'est une traduction WordPress
        if (strpos($key, 'wp:') === 0) {
            $wp_key = substr($key, 3);
            return __($wp_key, 'calendrier-rdv');
        }
        
        // Récupérer le texte dans le tableau des textes
        $keys = explode('.', $key);
        $text = self::arrayGet(self::$texts, $keys, $key);
        
        // Si c'est un tableau, le convertir en chaîne
        if (is_array($text)) {
            $text = implode(' ', $text);
        }
        
        // Remplacer les arguments
        if (!empty($args) && is_array($args)) {
            foreach ($args as $k => $v) {
                $text = str_replace('{' . $k . '}', $v, $text);
            }
        }
        
        return $text;
    }
    
    /**
     * Affiche un texte traduit
     * 
     * @param string $key Clé du texte
     * @param array $args Arguments à passer à la fonction de traduction
     * @return void
     */
    public static function e($key, $args = []) {
        echo self::get($key, $args);
    }
    
    /**
     * Récupère une valeur dans un tableau multidimensionnel à partir d'une clé en notation pointée
     * 
     * @param array $array Tableau source
     * @param array $keys Tableau de clés
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    private static function arrayGet($array, $keys, $default = '') {
        $current = $array;
        
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }
        
        return $current ?: $default;
    }
    
    /**
     * Récupère tous les textes d'une section
     * 
     * @param string $section Section à récupérer
     * @return array
     */
    public static function getSection($section) {
        return isset(self::$texts[$section]) ? self::$texts[$section] : [];
    }
    
    /**
     * Récupère la langue courante
     * 
     * @return string
     */
    public static function getLocale() {
        return self::$locale;
    }
    
    /**
     * Définit la langue courante
     * 
     * @param string $locale Code de la langue (ex: fr_FR)
     * @return void
     */
    public static function setLocale($locale) {
        self::$locale = $locale;
    }
    
    /**
     * Formate une date selon la locale
     * 
     * @param string $date Date au format Y-m-d H:i:s
     * @param string $format Format de sortie (par défaut: format défini dans les paramètres)
     * @return string Date formatée
     */
    public static function formatDate($date, $format = null) {
        if (is_null($format)) {
            $format = get_option('date_format');
        }
        
        $timestamp = strtotime($date);
        
        // Formater la date selon la locale
        $formatted_date = date_i18n($format, $timestamp);
        
        return $formatted_date;
    }
    
    /**
     * Formate une heure selon la locale
     * 
     * @param string $time Heure au format H:i:s
     * @param string $format Format de sortie (par défaut: format défini dans les paramètres)
     * @return string Heure formatée
     */
    public static function formatTime($time, $format = null) {
        if (is_null($format)) {
            $format = get_option('time_format');
        }
        
        $timestamp = strtotime($time);
        
        // Formater l'heure selon la locale
        $formatted_time = date_i18n($format, $timestamp);
        
        return $formatted_time;
    }
    
    /**
     * Formate une date et une heure selon la locale
     * 
     * @param string $datetime Date et heure au format Y-m-d H:i:s
     * @param string $format Format de sortie (par défaut: format défini dans les paramètres)
     * @return string Date et heure formatées
     */
    public static function formatDateTime($datetime, $format = null) {
        if (is_null($format)) {
            $format = sprintf('%1$s %2$s', 
                get_option('date_format'), 
                get_option('time_format')
            );
        }
        
        $timestamp = strtotime($datetime);
        
        // Formater la date et l'heure selon la locale
        $formatted_datetime = date_i18n($format, $timestamp);
        
        return $formatted_datetime;
    }
}
