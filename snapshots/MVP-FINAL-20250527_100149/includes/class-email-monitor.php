<?php
/**
 * Classe de surveillance de la file d'attente des emails
 */
class CalRdv_Email_Monitor {
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Seuil d'alerte pour le nombre d'échecs
     */
    private $failure_threshold = 10;
    
    /**
     * Seuil d'alerte pour l'âge des échecs (en heures)
     */
    private $age_threshold = 24;
    
    /**
     * Constructeur privé (singleton)
     */
    private function __construct() {
        // Initialisation des hooks
        add_action('calrdv_daily_health_check', [$this, 'check_queue_health']);
        
        // Planifier la tâche quotidienne si elle ne l'est pas déjà
        if (!wp_next_scheduled('calrdv_daily_health_check')) {
            wp_schedule_event(time(), 'daily', 'calrdv_daily_health_check');
        }
    }
    
    /**
     * Récupère l'instance unique de la classe
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Vérifie l'état de santé de la file d'attente
     */
    public function check_queue_health() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rdv_email_failures';
        
        // Récupérer les statistiques actuelles
        $stats = [
            'pending' => $this->get_queue_stats('pending'),
            'retrying' => $this->get_queue_stats('retrying'),
            'failed' => $this->get_queue_stats('failed'),
            'old_failures' => $this->get_old_failures_count(),
            'total' => $this->get_total_queue_size()
        ];
        
        // Vérifier les conditions d'alerte
        $alerts = [];
        
        // Trop d'échecs en attente
        if ($stats['pending']['count'] > $this->failure_threshold) {
            $alerts[] = sprintf(
                'Alerte : %d emails en attente dans la file (seuil: %d)',
                $stats['pending']['count'],
                $this->failure_threshold
            );
        }
        
        // Échecs trop anciens
        if ($stats['old_failures'] > 0) {
            $alerts[] = sprintf(
                'Alerte : %d échecs non traités depuis plus de %d heures',
                $stats['old_failures'],
                $this->age_threshold
            );
        }
        
        // Envoyer une alerte si nécessaire
        if (!empty($alerts)) {
            $this->send_alert_email($alerts, $stats);
        }
        
        // Journaliser les statistiques
        $this->log_stats($stats);
        
        return $stats;
    }
    
    /**
     * Récupère les statistiques pour un statut donné
     */
    private function get_queue_stats($status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rdv_email_failures';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as count,
                MIN(created_at) as oldest,
                MAX(created_at) as newest
             FROM {$table_name}
             WHERE status = %s",
            $status
        ), ARRAY_A);
        
        return [
            'count' => (int) $result->count,
            'oldest' => $result->oldest,
            'newest' => $result->newest
        ];
    }
    
    /**
     * Compte les échecs trop anciens
     */
    private function get_old_failures_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rdv_email_failures';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$table_name}
             WHERE status IN ('pending', 'retrying')
             AND created_at < %s",
            date('Y-m-d H:i:s', strtotime("-{$this->age_threshold} hours"))
        ));
    }
    
    /**
     * Récupère la taille totale de la file d'attente
     */
    private function get_total_queue_size() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rdv_email_failures';
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    }
    
    /**
     * Envoie une alerte par email
     */
    private function send_alert_email($alerts, $stats) {
        $to = get_option('admin_email');
        $subject = '⚠️ Alerte : Problèmes détectés dans la file d\'attente des emails';
        
        // Construire le message
        $message = "Bonjour,\n\n";
        $message .= "Des problèmes ont été détectés dans la file d'attente des emails :\n\n";
        
        foreach ($alerts as $alert) {
            $message .= "- {$alert}\n";
        }
        
        $message .= "\nStatistiques actuelles :\n";
        $message .= "- Total des emails dans la file : " . $stats['total'] . "\n";
        $message .= "- En attente : " . $stats['pending']['count'] . "\n";
        $message .= "- En cours de réessai : " . $stats['retrying']['count'] . "\n";
        $message .= "- Échecs définitifs : " . $stats['failed']['count'] . "\n";
        
        $message .= "\n-- \n";
        $message .= get_bloginfo('name') . "\n";
        $message .= site_url();
        
        // Envoyer l'email
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Journalise les statistiques
     */
    private function log_stats($stats) {
        $log_message = sprintf(
            '[%s] Statistiques file d\'attente - Total: %d, En attente: %d, En cours: %d, Échecs: %d',
            current_time('mysql'),
            $stats['total'],
            $stats['pending']['count'],
            $stats['retrying']['count'],
            $stats['failed']['count']
        );
        
        if (function_exists('calrdv_log_info')) {
            calrdv_log_info('email_queue_stats', $log_message);
        } else {
            error_log($log_message);
        }
    }
    
    /**
     * Nettoie les tâches planifiées à la désactivation
     */
    public static function deactivate() {
        $timestamp = wp_next_scheduled('calrdv_daily_health_check');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'calrdv_daily_health_check');
        }
    }
}

// Initialiser le moniteur
add_action('plugins_loaded', function() {
    CalRdv_Email_Monitor::get_instance();
});

// Nettoyage à la désactivation
register_deactivation_hook(__FILE__, [CalRdv_Email_Monitor::class, 'deactivate']);
