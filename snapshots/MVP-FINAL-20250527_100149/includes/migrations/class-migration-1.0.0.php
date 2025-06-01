<?php
/**
 * Migration 1.0.0
 * 
 * Ajout de la table rdv_email_failures pour la gestion des échecs d'envoi d'emails
 * 
 * @package CalendrierRdv
 * @version 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}

class CalRdv_Migration_1_0_0 {
    /**
     * Version de la migration
     */
    const VERSION = '1.0.0';
    
    /**
     * Exécute la migration
     * 
     * @return array Résultat de la migration
     */
    public static function run() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'rdv_email_failures';
        
        // Vérifier si la table existe déjà
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            return [
                'success' => true,
                'message' => "La table $table_name existe déjà.",
                'skipped' => true
            ];
        }
        
        // Requête SQL pour créer la table
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipient_email varchar(255) NOT NULL,
            recipient_name varchar(255) DEFAULT '',
            subject varchar(255) NOT NULL,
            error_code varchar(50) NOT NULL,
            error_message text,
            email_data longtext,
            retry_count int(11) NOT NULL DEFAULT 0,
            max_retries int(11) NOT NULL DEFAULT 3,
            status enum('pending','failed','retrying','sent') NOT NULL DEFAULT 'pending',
            scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_attempt datetime DEFAULT NULL,
            next_retry datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recipient_email (recipient_email),
            KEY status (status),
            KEY next_retry (next_retry)
        ) $charset_collate;";
        
        // Inclure le fichier nécessaire pour dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Exécuter la requête
        dbDelta($sql);
        
        // Vérifier les erreurs
        $error = $wpdb->last_error;
        if (!empty($error)) {
            return [
                'success' => false,
                'message' => "Erreur lors de la création de la table : $error",
                'error' => $error
            ];
        }
        
        return [
            'success' => true,
            'message' => "La table $table_name a été créée avec succès."
        ];
    }
    
    /**
     * Annule la migration
     * 
     * @return array Résultat de l'annulation
     */
    public static function rollback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rdv_email_failures';
        
        // Sauvegarder les données avant suppression (optionnel)
        // ...
        
        // Supprimer la table
        $result = $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => "Erreur lors de la suppression de la table : " . $wpdb->last_error
            ];
        }
        
        return [
            'success' => true,
            'message' => "La table $table_name a été supprimée avec succès."
        ];
    }
}
