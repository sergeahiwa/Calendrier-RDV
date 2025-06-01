<?php
/**
 * Fichier de désinstallation du plugin Calendrier RDV
 *
 * Ce fichier est appelé lorsque le plugin est supprimé du tableau de bord WordPress.
 * Il supprime toutes les données du plugin, y compris les options, les tables et les fichiers.
 *
 * @package CalendrierRdv
 */

// Sécurité : empêcher l'accès direct
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Vérifier les capacités utilisateur
if (!current_user_can('delete_plugins')) {
    wp_die(
        __('Vous n\'avez pas les droits nécessaires pour effectuer cette action.', 'calendrier-rdv'),
        __('Erreur', 'calendrier-rdv'),
        ['response' => 403]
    );
}

// Inclure les fichiers nécessaires
$plugin_dir = plugin_dir_path(__FILE__);

// Vérifier si le fichier de désinstallation est appelé directement
if (!defined('WP_UNINSTALL_PLUGIN') || !current_user_can('delete_plugins')) {
    exit;
}

// Supprimer les options
$options_to_delete = [
    'calendrier_rdv_version',
    'calendrier_rdv_installed',
    'calendrier_rdv_booking_page_id',
    'calendrier_rdv_dashboard_page_id',
    'calendrier_rdv_settings',
    'calendrier_rdv_activation_redirect',
];

foreach ($options_to_delete as $option) {
    delete_option($option);
    delete_site_option($option);
}

// Supprimer les métadonnées utilisateur
$user_meta_keys = [
    'calendrier_rdv_user_timezone',
    'calendrier_rdv_notification_prefs',
    'calendrier_rdv_calendar_view',
];

$users = get_users([
    'fields' => 'ID',
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => 'calendrier_rdv_user_timezone',
            'compare' => 'EXISTS',
        ],
        [
            'key' => 'calendrier_rdv_notification_prefs',
            'compare' => 'EXISTS',
        ],
        [
            'key' => 'calendrier_rdv_calendar_view',
            'compare' => 'EXISTS',
        ],
    ],
]);

foreach ($users as $user_id) {
    foreach ($user_meta_keys as $meta_key) {
        delete_user_meta($user_id, $meta_key);
    }
}

// Supprimer les tables de la base de données
global $wpdb;

$tables = [
    $wpdb->prefix . 'calendrier_rdv_appointments',
    $wpdb->prefix . 'calendrier_rdv_availabilities',
    $wpdb->prefix . 'calendrier_rdv_logs',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Supprimer les pages créées par le plugin
$pages_to_delete = [
    get_option('calendrier_rdv_booking_page_id'),
    get_option('calendrier_rdv_dashboard_page_id'),
];

foreach ($pages_to_delete as $page_id) {
    if ($page_id && get_post($page_id)) {
        wp_delete_post($page_id, true);
    }
}

// Supprimer les tâches planifiées
$crons = _get_cron_array();

foreach ($crons as $timestamp => $cron) {
    foreach ($cron as $hook => $events) {
        if (strpos($hook, 'calendrier_rdv_') === 0) {
            wp_clear_scheduled_hook($hook);
        }
    }
}

// Supprimer les fichiers uploadés par le plugin
$upload_dir = wp_upload_dir();
$calendrier_rdv_dir = trailingslashit($upload_dir['basedir']) . 'calendrier-rdv/';

if (file_exists($calendrier_rdv_dir)) {
    // Supprimer tous les fichiers et dossiers dans le répertoire
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($calendrier_rdv_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $action = $fileinfo->isDir() ? 'rmdir' : 'unlink';
        @$action($fileinfo->getRealPath());
    }

    // Supprimer le répertoire principal
    @rmdir($calendrier_rdv_dir);
}

// Nettoyer le cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

// Supprimer les transients
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM $wpdb->options 
        WHERE option_name LIKE %s 
        OR option_name LIKE %s",
        '_transient_calendrier_rdv_%',
        '_transient_timeout_calendrier_rdv_%'
    )
);

// Supprimer les rôles personnalisés
remove_role('calendrier_rdv_provider');

// Supprimer les capacités des rôles existants
$roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];

foreach ($roles as $role_name) {
    $role = get_role($role_name);
    
    if ($role) {
        // Supprimer les capacités spécifiques
        $capabilities = [
            'manage_calendrier_rdv',
            'edit_calendrier_rdv_settings',
            'view_calendrier_rdv_reports',
            'manage_calendrier_rdv_providers',
            'manage_calendrier_rdv_appointments',
            'edit_calendrier_rdv_availability',
            'view_calendrier_rdv_calendar',
        ];
        
        foreach ($capabilities as $cap) {
            $role->remove_cap($cap);
        }
    }
}

// Journaliser la désinstallation
if (function_exists('calendrier_rdv_log')) {
    calendrier_rdv_log('Plugin Calendrier RDV désinstallé avec succès', 'info');
}
