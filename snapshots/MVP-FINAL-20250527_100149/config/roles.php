<?php
/**
 * Configuration des rôles et capacités pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Définit les rôles et capacités personnalisés pour le plugin
 *
 * @return void
 */
function cal_rdv_setup_roles() {
    // Rôle d'administrateur du calendrier (peut tout gérer)
    add_role(
        'calendar_manager',
        __('Gestionnaire de calendrier', 'calendrier-rdv'),
        [
            'read' => true,
            'level_7' => true,
        ]
    );

    // Rôle de prestataire (peut gérer ses propres rendez-vous)
    add_role(
        'service_provider',
        __('Prestataire', 'calendrier-rdv'),
        [
            'read' => true,
            'level_2' => true,
        ]
    );

    // Récupérer les rôles
    $admin_role = get_role('administrator');
    $manager_role = get_role('calendar_manager');
    $provider_role = get_role('service_provider');
    $editor_role = get_role('editor');

    // Capacités pour les rendez-vous
    $appointment_caps = [
        // Lecture
        'read_appointment',
        'read_private_appointments',
        'read_others_appointments',
        'edit_appointment',
        'edit_appointments',
        'edit_others_appointments',
        'edit_published_appointments',
        'publish_appointments',
        'delete_appointment',
        'delete_appointments',
        'delete_private_appointments',
        'delete_published_appointments',
        'delete_others_appointments',
    ];

    // Capacités pour les prestataires
    $provider_caps = [
        'manage_providers',
        'edit_providers',
        'edit_others_providers',
        'publish_providers',
        'read_private_providers',
        'delete_providers',
        'edit_provider',
        'delete_provider',
        'read_provider',
    ];

    // Capacités pour les services
    $service_caps = [
        'manage_services',
        'edit_services',
        'edit_others_services',
        'publish_services',
        'read_private_services',
        'delete_services',
        'edit_service',
        'delete_service',
        'read_service',
    ];

    // Capacités pour les paramètres
    $settings_caps = [
        'manage_calendar_settings',
    ];

    // Toutes les capacités
    $all_caps = array_merge(
        $appointment_caps,
        $provider_caps,
        $service_caps,
        $settings_caps
    );

    // Ajouter les capacités à l'administrateur
    if ($admin_role) {
        foreach ($all_caps as $cap) {
            $admin_role->add_cap($cap);
        }
    }

    // Ajouter les capacités au gestionnaire de calendrier
    if ($manager_role) {
        foreach ($all_caps as $cap) {
            $manager_role->add_cap($cap);
        }
    }

    // Ajouter les capacités à l'éditeur
    if ($editor_role) {
        foreach ($appointment_caps as $cap) {
            $editor_role->add_cap($cap);
        }
        $editor_role->add_cap('read_private_services');
        $editor_role->add_cap('read_private_providers');
    }

    // Ajouter les capacités au prestataire
    if ($provider_role) {
        $provider_caps = [
            'read_appointment',
            'edit_appointments',
            'edit_published_appointments',
            'publish_appointments',
            'delete_appointments',
            'delete_published_appointments',
            'read_private_services',
            'read_private_providers',
        ];
        
        foreach ($provider_caps as $cap) {
            $provider_role->add_cap($cap);
        }
    }
}

/**
 * Supprime les rôles et capacités personnalisés
 *
 * @return void
 */
function cal_rdv_remove_roles() {
    // Supprimer les rôles personnalisés
    remove_role('calendar_manager');
    remove_role('service_provider');

    // Récupérer les rôles existants
    $admin_role = get_role('administrator');
    $editor_role = get_role('editor');

    // Liste de toutes les capacités à supprimer
    $all_caps = [
        // Capacités des rendez-vous
        'read_appointment',
        'read_private_appointments',
        'read_others_appointments',
        'edit_appointment',
        'edit_appointments',
        'edit_others_appointments',
        'edit_published_appointments',
        'publish_appointments',
        'delete_appointment',
        'delete_appointments',
        'delete_private_appointments',
        'delete_published_appointments',
        'delete_others_appointments',
        
        // Capacités des prestataires
        'manage_providers',
        'edit_providers',
        'edit_others_providers',
        'publish_providers',
        'read_private_providers',
        'delete_providers',
        'edit_provider',
        'delete_provider',
        'read_provider',
        
        // Capacités des services
        'manage_services',
        'edit_services',
        'edit_others_services',
        'publish_services',
        'read_private_services',
        'delete_services',
        'edit_service',
        'delete_service',
        'read_service',
        
        // Capacités des paramètres
        'manage_calendar_settings',
    ];

    // Supprimer les capacités de l'administrateur
    if ($admin_role) {
        foreach ($all_caps as $cap) {
            $admin_role->remove_cap($cap);
        }
    }

    // Supprimer les capacités de l'éditeur
    if ($editor_role) {
        foreach ($all_caps as $cap) {
            $editor_role->remove_cap($cap);
        }
    }
}

/**
 * Vérifie si l'utilisateur actuel a une capacité spécifique
 *
 * @param string $capability La capacité à vérifier
 * @param int|null $user_id L'ID de l'utilisateur (optionnel, utilise l'utilisateur actuel par défaut)
 * @return bool
 */
function cal_rdv_current_user_can($capability, $user_id = null) {
    // Les administrateurs ont toutes les capacités
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Vérifier la capacité spécifique
    return user_can($user_id, $capability);
}

/**
 * Vérifie si l'utilisateur actuel peut gérer les rendez-vous
 *
 * @param int|null $user_id L'ID de l'utilisateur (optionnel)
 * @return bool
 */
function cal_rdv_can_manage_appointments($user_id = null) {
    return cal_rdv_current_user_can('edit_appointments', $user_id);
}

/**
 * Vérifie si l'utilisateur actuel peut gérer les prestataires
 *
 * @param int|null $user_id L'ID de l'utilisateur (optionnel)
 * @return bool
 */
function cal_rdv_can_manage_providers($user_id = null) {
    return cal_rdv_current_user_can('manage_providers', $user_id);
}

/**
 * Vérifie si l'utilisateur actuel peut gérer les services
 *
 * @param int|null $user_id L'ID de l'utilisateur (optionnel)
 * @return bool
 */
function cal_rdv_can_manage_services($user_id = null) {
    return cal_rdv_current_user_can('manage_services', $user_id);
}

/**
 * Vérifie si l'utilisateur actuel peut gérer les paramètres
 *
 * @param int|null $user_id L'ID de l'utilisateur (optionnel)
 * @return bool
 */
function cal_rdv_can_manage_settings($user_id = null) {
    return cal_rdv_current_user_can('manage_calendar_settings', $user_id);
}

/**
 * Initialise les rôles et capacités lors de l'activation du plugin
 */
register_activation_hook(CAL_RDV_PLUGIN_FILE, 'cal_rdv_setup_roles');

/**
 * Nettoie les rôles et capacités lors de la désactivation du plugin
 */
register_deactivation_hook(CAL_RDV_PLUGIN_FILE, function() {
    // Ne pas supprimer les rôles pour éviter les problèmes
    // cal_rdv_remove_roles();
});

/**
 * Nettoie les rôles et capacités lors de la désinstallation du plugin
 */
register_uninstall_hook(CAL_RDV_PLUGIN_FILE, 'cal_rdv_remove_roles');
