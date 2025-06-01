<?php
/**
 * Gestion des tâches planifiées pour le plugin Calendrier RDV
 * 
 * @package CalendrierRdv
 * @version 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}

class CalRdv_Cron_Handler {
    /**
     * Hook de la tâche planifiée
     */
    const CRON_HOOK = 'calrdv_process_email_queue';
    
    /**
     * Intervalle par défaut (en minutes)
     */
    const DEFAULT_INTERVAL = 15;
    
    /**
     * Instance de la classe (singleton)
     * 
     * @var CalRdv_Cron_Handler|null
     */
    private static $instance = null;
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        // Ne rien faire directement
    }
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return CalRdv_Cron_Handler
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialise le gestionnaire de tâches planifiées
     */
    public function init() {
        // Ajouter l'intervalle personnalisé
        add_filter('cron_schedules', [$this, 'add_cron_interval']);
        
        // Planifier la tâche si elle ne l'est pas déjà
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'calrdv_fifteen_minutes', self::CRON_HOOK);
        }
        
        // Ajouter le hook de la tâche
        add_action(self::CRON_HOOK, [$this, 'process_email_queue']);
        
        // Nettoyage hebdomadaire
        if (!wp_next_scheduled('calrdv_weekly_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'calrdv_weekly_cleanup');
        }
        add_action('calrdv_weekly_cleanup', [$this, 'weekly_cleanup']);
    }
    
    /**
     * Nettoie les ressources lors de la désactivation du plugin
     */
    public function deactivate() {
        // Désactiver la tâche planifiée
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
        
        // Désactiver le nettoyage hebdomadaire
        $timestamp = wp_next_scheduled('calrdv_weekly_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'calrdv_weekly_cleanup');
        }
    }
    
    /**
     * Ajoute un intervalle personnalisé pour les tâches planifiées
     * 
     * @param array $schedules Intervalles existants
     * @return array Intervalles mis à jour
     */
    public function add_cron_interval($schedules) {
        $schedules['calrdv_fifteen_minutes'] = [
            'interval' => self::DEFAULT_INTERVAL * 60, // en secondes
            'display' => sprintf(__('Toutes les %d minutes', 'calendrier-rdv'), self::DEFAULT_INTERVAL)
        ];
        
        return $schedules;
    }
    
    /**
     * Traite la file d'attente des emails
     */
    public function process_email_queue() {
        // Inclure la classe de file d'attente si nécessaire
        if (!class_exists('CalRdv_Email_Queue')) {
            require_once CAL_RDV_PLUGIN_DIR . 'includes/class-email-queue.php';
        }
        
        $queue = CalRdv_Email_Queue::get_instance();
        $results = $queue->process_queue(10); // Traiter 10 emails maximum
        
        // Journaliser les résultats si nécessaire
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Calendrier RDV - Tâche planifiée exécutée : ' . print_r($results, true));
        }
        
        return $results;
    }
    
    /**
     * Effectue un nettoyage hebdomadaire
     */
    public function weekly_cleanup() {
        // Inclure la classe de file d'attente si nécessaire
        if (!class_exists('CalRdv_Email_Queue')) {
            require_once CAL_RDV_PLUGIN_DIR . 'includes/class-email-queue.php';
        }
        
        $queue = CalRdv_Email_Queue::get_instance();
        $deleted = $queue->cleanup_old_failures(30); // Supprimer les entrées de plus de 30 jours
        
        // Journaliser le nettoyage
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('Calendrier RDV - Nettoyage hebdomadaire : %d entrées supprimées', $deleted));
        }
        
        return $deleted;
    }
}

/**
 * Initialise le gestionnaire de tâches planifiées
 */
function calrdv_init_cron_handler() {
    $cron_handler = CalRdv_Cron_Handler::get_instance();
    $cron_handler->init();
}
add_action('init', 'calrdv_init_cron_handler');

/**
 * Nettoie les tâches planifiées lors de la désactivation du plugin
 */
function calrdv_deactivate_cron_handler() {
    $cron_handler = CalRdv_Cron_Handler::get_instance();
    $cron_handler->deactivate();
}
register_deactivation_hook(CAL_RDV_PLUGIN_FILE, 'calrdv_deactivate_cron_handler');
