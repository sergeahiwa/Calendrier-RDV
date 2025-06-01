<?php
/**
 * Configuration de la base de données pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Récupère les préfixes des tables de la base de données
 * 
 * @return array
 */
function cal_rdv_get_table_prefixes() {
    global $wpdb;
    
    $prefix = $wpdb->prefix . 'cal_rdv_';
    
    return [
        'appointments' => $prefix . 'appointments',
        'appointment_meta' => $prefix . 'appointmentmeta',
        'services' => $prefix . 'services',
        'service_meta' => $prefix . 'servicemeta',
        'providers' => $prefix . 'providers',
        'provider_meta' => $prefix . 'providermeta',
        'customers' => $prefix . 'customers',
        'customer_meta' => $prefix . 'customermeta',
        'schedules' => $prefix . 'schedules',
        'schedule_exceptions' => $prefix . 'schedule_exceptions',
        'payments' => $prefix . 'payments',
        'payment_meta' => $prefix . 'paymentmeta',
        'notifications' => $prefix . 'notifications',
        'settings' => $prefix . 'settings',
    ];
}

/**
 * Crée les tables nécessaires dans la base de données
 * 
 * @return void
 */
function cal_rdv_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_prefixes = cal_rdv_get_table_prefixes();
    
    // Table des rendez-vous
    $sql_appointments = "CREATE TABLE IF NOT EXISTS {$table_prefixes['appointments']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id bigint(20) UNSIGNED DEFAULT 0,
        customer_id bigint(20) UNSIGNED NOT NULL,
        provider_id bigint(20) UNSIGNED NOT NULL,
        service_id bigint(20) UNSIGNED NOT NULL,
        location_id bigint(20) UNSIGNED DEFAULT 0,
        start_date datetime NOT NULL,
        end_date datetime NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        price decimal(10,2) DEFAULT 0.00,
        notes longtext,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        KEY customer_id (customer_id),
        KEY provider_id (provider_id),
        KEY service_id (service_id),
        KEY status (status),
        KEY start_date (start_date),
        KEY end_date (end_date)
    ) $charset_collate;";
    
    // Table des métadonnées des rendez-vous
    $sql_appointment_meta = "CREATE TABLE IF NOT EXISTS {$table_prefixes['appointment_meta']} (
        meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        appointment_id bigint(20) UNSIGNED NOT NULL,
        meta_key varchar(255) DEFAULT NULL,
        meta_value longtext,
        PRIMARY KEY  (meta_id),
        KEY appointment_id (appointment_id),
        KEY meta_key (meta_key(191))
    ) $charset_collate;";
    
    // Table des services
    $sql_services = "CREATE TABLE IF NOT EXISTS {$table_prefixes['services']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description longtext,
        duration int(11) NOT NULL DEFAULT 30,
        price decimal(10,2) DEFAULT 0.00,
        capacity int(11) NOT NULL DEFAULT 1,
        color varchar(7) DEFAULT '#3498db',
        status varchar(20) NOT NULL DEFAULT 'publish',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        KEY status (status)
    ) $charset_collate;";
    
    // Table des métadonnées des services
    $sql_service_meta = "CREATE TABLE IF NOT EXISTS {$table_prefixes['service_meta']} (
        meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id bigint(20) UNSIGNED NOT NULL,
        meta_key varchar(255) DEFAULT NULL,
        meta_value longtext,
        PRIMARY KEY  (meta_id),
        KEY service_id (service_id),
        KEY meta_key (meta_key(191))
    ) $charset_collate;";
    
    // Table des prestataires
    $sql_providers = "CREATE TABLE IF NOT EXISTS {$table_prefixes['providers']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(30),
        bio longtext,
        avatar_id bigint(20) UNSIGNED DEFAULT 0,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        KEY user_id (user_id),
        KEY email (email),
        KEY status (status)
    ) $charset_collate;";
    
    // Table des métadonnées des prestataires
    $sql_provider_meta = "CREATE TABLE IF NOT EXISTS {$table_prefixes['provider_meta']} (
        meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        provider_id bigint(20) UNSIGNED NOT NULL,
        meta_key varchar(255) DEFAULT NULL,
        meta_value longtext,
        PRIMARY KEY  (meta_id),
        KEY provider_id (provider_id),
        KEY meta_key (meta_key(191))
    ) $charset_collate;";
    
    // Table des clients
    $sql_customers = "CREATE TABLE IF NOT EXISTS {$table_prefixes['customers']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED DEFAULT NULL,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(30),
        notes longtext,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        KEY user_id (user_id),
        KEY email (email),
        KEY status (status)
    ) $charset_collate;";
    
    // Table des métadonnées des clients
    $sql_customer_meta = "CREATE TABLE IF NOT EXISTS {$table_prefixes['customer_meta']} (
        meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) UNSIGNED NOT NULL,
        meta_key varchar(255) DEFAULT NULL,
        meta_value longtext,
        PRIMARY KEY  (meta_id),
        KEY customer_id (customer_id),
        KEY meta_key (meta_key(191))
    ) $charset_collate;";
    
    // Table des horaires des prestataires
    $sql_schedules = "CREATE TABLE IF NOT EXISTS {$table_prefixes['schedules']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        provider_id bigint(20) UNSIGNED NOT NULL,
        day_of_week tinyint(1) NOT NULL,
        start_time time NOT NULL,
        end_time time NOT NULL,
        is_working tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        KEY provider_id (provider_id),
        KEY day_of_week (day_of_week)
    ) $charset_collate;";
    
    // Table des exceptions d'horaire
    $sql_schedule_exceptions = "CREATE TABLE IF NOT EXISTS {$table_prefixes['schedule_exceptions']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        provider_id bigint(20) UNSIGNED NOT NULL,
        service_id bigint(20) UNSIGNED DEFAULT NULL,
        date date NOT NULL,
        start_time time DEFAULT NULL,
        end_time time DEFAULT NULL,
        is_working tinyint(1) NOT NULL DEFAULT 0,
        description varchar(255) DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        KEY provider_id (provider_id),
        KEY service_id (service_id),
        KEY date (date)
    ) $charset_collate;";
    
    // Table des paiements
    $sql_payments = "CREATE TABLE IF NOT EXISTS {$table_prefixes['payments']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        appointment_id bigint(20) UNSIGNED NOT NULL,
        customer_id bigint(20) UNSIGNED NOT NULL,
        provider_id bigint(20) UNSIGNED NOT NULL,
        amount decimal(10,2) NOT NULL,
        tax_amount decimal(10,2) DEFAULT 0.00,
        total_amount decimal(10,2) NOT NULL,
        payment_method varchar(50) NOT NULL,
        transaction_id varchar(100) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        paid_at datetime DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        KEY appointment_id (appointment_id),
        KEY customer_id (customer_id),
        KEY provider_id (provider_id),
        KEY status (status),
        KEY transaction_id (transaction_id)
    ) $charset_collate;";
    
    // Table des métadonnées des paiements
    $sql_payment_meta = "CREATE TABLE IF NOT EXISTS {$table_prefixes['payment_meta']} (
        meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        payment_id bigint(20) UNSIGNED NOT NULL,
        meta_key varchar(255) DEFAULT NULL,
        meta_value longtext,
        PRIMARY KEY  (meta_id),
        KEY payment_id (payment_id),
        KEY meta_key (meta_key(191))
    ) $charset_collate;";
    
    // Table des notifications
    $sql_notifications = "CREATE TABLE IF NOT EXISTS {$table_prefixes['notifications']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        type varchar(50) NOT NULL,
        recipient varchar(255) NOT NULL,
        subject varchar(255) NOT NULL,
        message longtext NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        sent_at datetime DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        KEY type (type),
        KEY recipient (recipient(100)),
        KEY status (status)
    ) $charset_collate;";
    
    // Table des paramètres
    $sql_settings = "CREATE TABLE IF NOT EXISTS {$table_prefixes['settings']} (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        option_name varchar(191) NOT NULL,
        option_value longtext,
        autoload varchar(20) NOT NULL DEFAULT 'yes',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        UNIQUE KEY option_name (option_name)
    ) $charset_collate;";
    
    // Table de relation entre services et prestataires
    $sql_service_providers = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cal_rdv_service_providers (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        service_id bigint(20) UNSIGNED NOT NULL,
        provider_id bigint(20) UNSIGNED NOT NULL,
        price_override decimal(10,2) DEFAULT NULL,
        duration_override int(11) DEFAULT NULL,
        capacity_override int(11) DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (ID),
        UNIQUE KEY service_provider (service_id, provider_id),
        KEY service_id (service_id),
        KEY provider_id (provider_id)
    ) $charset_collate;";
    
    // Exécution des requêtes SQL
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_appointments);
    dbDelta($sql_appointment_meta);
    dbDelta($sql_services);
    dbDelta($sql_service_meta);
    dbDelta($sql_providers);
    dbDelta($sql_provider_meta);
    dbDelta($sql_customers);
    dbDelta($sql_customer_meta);
    dbDelta($sql_schedules);
    dbDelta($sql_schedule_exceptions);
    dbDelta($sql_payments);
    dbDelta($sql_payment_meta);
    dbDelta($sql_notifications);
    dbDelta($sql_settings);
    dbDelta($sql_service_providers);
    
    // Mettre à jour la version de la base de données
    update_option('cal_rdv_db_version', CAL_RDV_DB_VERSION);
}

/**
 * Supprime les tables de la base de données
 * 
 * @param bool $force Supprimer même si l'option de conservation des données est activée
 * @return void
 */
function cal_rdv_drop_tables($force = false) {
    global $wpdb;
    
    // Vérifier si on doit conserver les données
    if (!$force && get_option('cal_rdv_keep_data_on_uninstall')) {
        return;
    }
    
    $table_prefixes = cal_rdv_get_table_prefixes();
    
    // Supprimer les tables
    foreach ($table_prefixes as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Supprimer la table de relation
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cal_rdv_service_providers");
    
    // Supprimer les options
    delete_option('cal_rdv_db_version');
    delete_option('cal_rdv_installed_time');
    delete_option('cal_rdv_keep_data_on_uninstall');
}

/**
 * Met à jour la structure de la base de données si nécessaire
 * 
 * @return void
 */
function cal_rdv_update_database() {
    $current_db_version = get_option('cal_rdv_db_version', '1.0.0');
    
    // Mettre à jour vers la version 1.1.0
    if (version_compare($current_db_version, '1.1.0', '<')) {
        // Ajouter des colonnes ou des tables si nécessaire
        // ...
        
        // Mettre à jour la version
        update_option('cal_rdv_db_version', '1.1.0');
    }
    
    // Ajouter d'autres mises à jour pour les versions futures
    // ...
}

/**
 * Initialise la base de données lors de l'activation du plugin
 * 
 * @return void
 */
function cal_rdv_install_database() {
    // Créer les tables
    cal_rdv_create_tables();
    
    // Mettre à jour la structure si nécessaire
    cal_rdv_update_database();
    
    // Enregistrer la date d'installation
    if (!get_option('cal_rdv_installed_time')) {
        update_option('cal_rdv_installed_time', current_time('mysql'));
    }
}
register_activation_hook(CAL_RDV_PLUGIN_FILE, 'cal_rdv_install_database');

/**
 * Nettoie la base de données lors de la désinstallation du plugin
 * 
 * @return void
 */
function cal_rdv_uninstall_database() {
    // Supprimer les tables si l'option est activée
    cal_rdv_drop_tables();
}
register_uninstall_hook(CAL_RDV_PLUGIN_FILE, 'cal_rdv_uninstall_database');

/**
 * Vérifie si la base de données est à jour
 * 
 * @return bool
 */
function cal_rdv_is_database_up_to_date() {
    $current_db_version = get_option('cal_rdv_db_version', '1.0.0');
    return version_compare($current_db_version, CAL_RDV_DB_VERSION, '>=');
}

/**
 * Vérifie et met à jour la base de données si nécessaire
 * 
 * @return void
 */
function cal_rdv_check_database() {
    if (!cal_rdv_is_database_up_to_date()) {
        cal_rdv_update_database();
    }
}
add_action('plugins_loaded', 'cal_rdv_check_database');
