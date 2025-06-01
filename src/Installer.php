<?php

namespace CalendrierRDV;

/**
 * Gère l'installation et la désinstallation du plugin.
 */
class Installer {
    /**
     * Exécute l'installation du plugin.
     */
    public function install() {
        // Vérifier les dépendances.
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        // Créer les tables de la base de données.
        $this->create_tables();

        // Ajouter les rôles et capacités.
        $this->add_roles_and_capabilities();

        // Ajouter les options par défaut.
        $this->add_default_options();
    }

    /**
     * Crée les tables de la base de données.
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // Table des prestataires.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rdv_providers (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Table des services.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rdv_services (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            duration INT(11) NOT NULL DEFAULT 30,
            price DECIMAL(10,2) DEFAULT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Table des rendez-vous.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rdv_appointments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            provider_id BIGINT(20) UNSIGNED NOT NULL,
            service_id BIGINT(20) UNSIGNED NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(100) NOT NULL,
            customer_phone VARCHAR(20) DEFAULT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY provider_id (provider_id),
            KEY service_id (service_id),
            KEY start_time (start_time)
        ) $charset_collate;";

        // Table des disponibilités.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rdv_availability (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            provider_id BIGINT(20) UNSIGNED NOT NULL,
            day_of_week TINYINT(1) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY provider_id (provider_id),
            KEY day_of_week (day_of_week)
        ) $charset_collate;

        // Table des jours fériés.
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rdv_holidays (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            holiday_date DATE NOT NULL,
            recurring TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY holiday_date (holiday_date)
        ) $charset_collate;

        // Exécuter les requêtes.
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }


    /**
     * Ajoute les rôles et capacités nécessaires.
     */
    private function add_roles_and_capabilities() {
        // Rôle prestataire.
        add_role(
            'rdv_provider',
            __('Prestataire', 'calendrier-rdv'),
            [
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => true,
            ]
        );

        // Rôle secrétaire.
        add_role(
            'rdv_secretary',
            __('Secrétaire', 'calendrier-rdv'),
            [
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => false,
                'upload_files' => true,
                'manage_rdv' => true,
            ]
        );

        // Capacités pour les administrateurs.
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_rdv');
            $admin_role->add_cap('manage_rdv_providers');
            $admin_role->add_cap('manage_rdv_services');
            $admin_role->add_cap('manage_rdv_appointments');
            $admin_role->add_cap('manage_rdv_settings');
        }

        // Capacités pour les secrétaires.
        $secretary_role = get_role('rdv_secretary');
        if ($secretary_role) {
            $secretary_role->add_cap('manage_rdv');
            $secretary_role->add_cap('manage_rdv_appointments');
        }
    }

    /**
     * Ajoute les options par défaut.
     */
    private function add_default_options() {
        // Options générales.
        $default_options = [
            'rdv_time_slot' => 30,
            'rdv_work_days' => [1, 2, 3, 4, 5], // Lundi à vendredi
            'rdv_work_hours_start' => '09:00',
            'rdv_work_hours_end' => '18:00',
            'rdv_allow_weekend' => 'no',
            'rdv_allow_past_dates' => 'no',
            'rdv_require_confirmation' => 'yes',
            'rdv_send_notifications' => 'yes',
            'rdv_company_name' => get_bloginfo('name'),
            'rdv_company_email' => get_bloginfo('admin_email'),
            'rdv_company_phone' => '',
            'rdv_company_address' => '',
            'rdv_company_logo' => '',
            'rdv_currency' => 'EUR',
            'rdv_currency_position' => 'right',
            'rdv_date_format' => 'd/m/Y',
            'rdv_time_format' => 'H:i',
            'rdv_week_starts_on' => '1', // Lundi
            'rdv_delete_data_on_uninstall' => 'no',
        ];

        foreach ($default_options as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }
}
