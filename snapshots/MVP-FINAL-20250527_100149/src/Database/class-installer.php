<?php
/**
 * Gestion de l'installation et de la mise à jour de la base de données
 *
 * @package CalendrierRdv\Database
 */

namespace CalendrierRdv\Database;

use CalendrierRdv\Database\QueryBuilder;

/**
 * Gère l'installation et la mise à jour de la base de données
 */
class Installer {
    /**
     * Instance du QueryBuilder
     *
     * @var QueryBuilder
     */
    private $query_builder;
    /**
     * Version de la base de données
     *
     * @var string
     */
    private $db_version = '1.0.0';

    /**
     * Option pour stocker la version de la base de données
     *
     * @var string
     */
    private $db_version_option = 'calendrier_rdv_db_version';

    /**
     * Préfixe des tables
     *
     * @var string
     */
    private $table_prefix;

    /**
     * Constructeur
     */
    public function __construct() {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . 'cal_rdv_';
        $this->query_builder = new QueryBuilder('');
    }

    /**
     * Exécute l'installation
     */
    public function install() {
        // Vérifie si une mise à jour est nécessaire
        $current_version = get_option($this->db_version_option, '0');
        
        // Si la version est à jour, on ne fait rien
        if (version_compare($current_version, $this->db_version, '>=')) {
            return;
        }

        // Crée les tables
        $this->create_tables();
        
        // Met à jour la version de la base de données
        update_option($this->db_version_option, $this->db_version);
        
        // Déclenche l'action d'installation
        do_action('calendrier_rdv_installed');
    }

    /**
     * Crée les tables de la base de données
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des services
        $sql_services = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}services (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration int(11) NOT NULL DEFAULT 30 COMMENT 'Durée en minutes',
            price decimal(10,2) DEFAULT NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Table des prestataires
        $sql_providers = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}providers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT NULL,
            description text,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Table des horaires d'ouverture
        $sql_business_hours = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}business_hours (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_id bigint(20) DEFAULT NULL COMMENT 'NULL pour les horaires par défaut',
            day_of_week tinyint(1) NOT NULL COMMENT '0 (dimanche) à 6 (samedi)',
            start_time time NOT NULL,
            end_time time NOT NULL,
            is_working_day tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider_id (provider_id),
            KEY day_of_week (day_of_week)
        ) $charset_collate;";

        // Table des rendez-vous
        $sql_appointments = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}appointments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id bigint(20) NOT NULL,
            provider_id bigint(20) NOT NULL,
            customer_id bigint(20) DEFAULT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) DEFAULT NULL,
            customer_notes text,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'confirmed' COMMENT 'pending, confirmed, cancelled, completed',
            price decimal(10,2) DEFAULT NULL,
            payment_status varchar(20) DEFAULT 'pending' COMMENT 'pending, paid, refunded, cancelled',
            payment_method varchar(50) DEFAULT NULL,
            payment_id varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY service_id (service_id),
            KEY provider_id (provider_id),
            KEY customer_id (customer_id),
            KEY start_date (start_date),
            KEY status (status)
        ) $charset_collate;";

        // Table des exceptions (fermetures exceptionnelles)
        $sql_exceptions = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}exceptions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_id bigint(20) DEFAULT NULL COMMENT 'NULL pour tous les prestataires',
            title varchar(255) NOT NULL,
            description text,
            start_date date NOT NULL,
            end_date date NOT NULL,
            is_recurring tinyint(1) NOT NULL DEFAULT 0,
            recurrence_type varchar(20) DEFAULT NULL COMMENT 'yearly, monthly, weekly',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider_id (provider_id),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";

        // Table des temps de pause
        $sql_breaks = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}breaks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_id bigint(20) NOT NULL,
            title varchar(255) DEFAULT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            days_of_week varchar(20) DEFAULT NULL COMMENT 'Comma-separated: 0,1,2,3,4,5,6',
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            is_recurring tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider_id (provider_id)
        ) $charset_collate;";

        // Table des listes d'attente
        $sql_waitlist = "CREATE TABLE IF NOT EXISTS {$this->table_prefix}waitlist (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id bigint(20) NOT NULL,
            provider_id bigint(20) NOT NULL,
            customer_id bigint(20) DEFAULT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) DEFAULT NULL,
            notes text,
            preferred_date date DEFAULT NULL,
            preferred_time time DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'waiting' COMMENT 'waiting, notified, booked, cancelled',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY service_id (service_id),
            KEY provider_id (provider_id),
            KEY status (status)
        ) $charset_collate;";

        // Exécution des requêtes
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta($sql_services);
        dbDelta($sql_providers);
        dbDelta($sql_business_hours);
        dbDelta($sql_appointments);
        dbDelta($sql_exceptions);
        dbDelta($sql_breaks);
        dbDelta($sql_waitlist);
        
        // Ajout des données par défaut si les tables sont vides
        $this->seed_default_data();
    }

    /**
     * Ajoute des données par défaut
     */
    private function seed_default_data() {
        global $wpdb;
        
        // Vérifie si des services existent déjà
        $services_count = $this->query_builder->raw(
            "SELECT COUNT(*) FROM {$this->table_prefix}services"
        );
        
        if ($services_count == 0) {
            // Ajout d'un service par défaut
            $this->query_builder = new QueryBuilder('services');
            $this->query_builder->insert([
                /* translators: Default service name created during plugin installation. */
                'name' => __('Consultation', 'calendrier-rdv'),
                /* translators: Default service description created during plugin installation. */
                'description' => __('Consultation standard', 'calendrier-rdv'),
                'duration' => 30,
                'price' => 50.00,
                'active' => true
            ]);
        }
        
        // Vérifie si des horaires d'ouverture par défaut existent
        $business_hours_count = $this->query_builder->raw(
            "SELECT COUNT(*) FROM {$this->table_prefix}business_hours WHERE provider_id IS NULL"
        );
        
        if ($business_hours_count == 0) {
            // Jours de la semaine (0=dimanche, 6=samedi)
            $weekdays = range(1, 5); // Lundi à vendredi
            $hours = [
                'start' => '09:00:00',
                'end' => '18:00:00',
                'lunch_start' => '12:30:00',
                'lunch_end' => '13:30:00'
            ];
            
            // Ajout des horaires pour chaque jour de la semaine
            $this->query_builder = new QueryBuilder('business_hours');
            
            foreach ($weekdays as $day) {
                // Matin
                $this->query_builder->insert([
                    'day_of_week' => $day,
                    'start_time' => $hours['start'],
                    'end_time' => $hours['lunch_start'],
                    'is_working_day' => 1,
                    'provider_id' => null
                ]);
                
                // Après-midi
                $this->query_builder->insert([
                    'day_of_week' => $day,
                    'start_time' => $hours['lunch_end'],
                    'end_time' => $hours['end'],
                    'is_working_day' => 1,
                    'provider_id' => null
                ]);
            }
        }
    }

    /**
     * Effectue les mises à jour nécessaires
     */
    public function update() {
        $current_version = get_option($this->db_version_option, '0');
        
        // Exemple de mise à jour pour une version spécifique
        if (version_compare($current_version, '1.0.1', '<')) {
            // Code de mise à jour pour la version 1.0.1
            $this->update_to_1_0_1();
            
            // Met à jour la version
            update_option($this->db_version_option, '1.0.1');
        }
        
        // Mettre à jour la version actuelle si nécessaire
        if (version_compare($current_version, $this->db_version, '<')) {
            update_option($this->db_version_option, $this->db_version);
        }
    }
    
    /**
     * Exemple de méthode de mise à jour pour une version spécifique
     */
    private function update_to_1_0_1() {
        global $wpdb;
        
        // Vérification de l'existence de la colonne customer_notes
        $table_name = $this->table_prefix . 'appointments';
        $column_exists = $this->query_builder->raw(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'customer_notes'",
            [DB_NAME, $table_name]
        );
        
        if (empty($column_exists)) {
            $this->query_builder->raw(
                "ALTER TABLE {$table_name} 
                 ADD COLUMN customer_notes text AFTER customer_phone"
            );
        }
    }

    /**
     * Désinstalle le plugin
     */
    public static function uninstall() {
        // Suppression des options
        delete_option('calendrier_rdv_db_version');
        delete_option('calendrier_rdv_settings');
        
        // Suppression des tables si l'option est activée
        if (defined('CAL_RDV_REMOVE_ALL_DATA') && CAL_RDV_REMOVE_ALL_DATA === true) {
            self::drop_tables();
        }
    }
    
    /**
     * Supprime les tables de la base de données
     */
    private static function drop_tables() {
        global $wpdb;
        
        $tables = [
            'services',
            'providers',
            'business_hours',
            'appointments',
            'exceptions',
            'breaks',
            'waitlist',
        ];
        
        $query_builder = new QueryBuilder('');
        
        foreach ($tables as $table) {
            $query_builder->raw("DROP TABLE IF EXISTS {$wpdb->prefix}cal_rdv_{$table}");
        }
    }
}
