<?php
/**
 * Fichier de désinstallation du plugin Calendrier de Rendez-vous
 * 
 * Ce fichier est appelé automatiquement lorsque le plugin est supprimé
 * depuis l'administration WordPress.
 * 
 * @package CalendrierRDV
 */

// Si ce n'est pas WordPress qui appelle ce fichier, on sort
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Suppression des options
$options_to_delete = [
    'rdv_db_version',
    'rdv_settings',
    'rdv_notify_admin',
    'rdv_email_subject',
    'rdv_email_from_name',
    'rdv_email_from_email',
    'rdv_reminder_days_before',
    'rdv_reminder_time',
    'rdv_working_hours',
    'rdv_working_days',
    'rdv_holidays',
    'rdv_slot_duration',
    'rdv_advance_booking_days',
    'rdv_buffer_time'
];

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Suppression des tables de la base de données
global $wpdb;
$tables = [
    $wpdb->prefix . 'rdv_events',
    $wpdb->prefix . 'rdv_prestataires',
    $wpdb->prefix . 'rdv_services',
    $wpdb->prefix . 'rdv_holidays'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Suppression des rôles et capacités
$roles = ['rdv_manager', 'rdv_prestataire'];

foreach ($roles as $role) {
    remove_role($role);
}

// Suppression des cron jobs
wp_clear_scheduled_hook('rdv_daily_maintenance');
wp_clear_scheduled_hook('rdv_send_reminders');
wp_clear_scheduled_hook('rdv_cleanup_old_bookings');

// Suppression des fichiers temporaires
$upload_dir = wp_upload_dir();
$export_dir = trailingslashit($upload_dir['basedir']) . 'rdv-exports/';

if (file_exists($export_dir)) {
    $files = glob($export_dir . '*');
    
    if (is_array($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    rmdir($export_dir);
}

// Nettoyage des transients
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%\_transient_rdv_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%\_transient_timeout_rdv_%'");

// Suppression des métadonnées utilisateur
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'rdv_%'");

// Suppression des logs d'erreurs
delete_option('rdv_error_logs');

// Journalisation de la désinstallation
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Plugin Calendrier de Rendez-vous désinstallé et toutes les données ont été supprimées.');
}
