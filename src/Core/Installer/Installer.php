<?php

namespace CalendrierRdv\Core\Installer;

use CalendrierRdv\Core\Assets\AssetsManager;
use CalendrierRdv\Core\Module\CalendrierRdvModule;

class Installer {
    private static $instance = null;
    
    public static function getInstance(): Installer {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
    private function __wakeup() {}

    public function install(): void {
        $this->createTables();
        $this->registerModule();
        $this->registerAssets();
        $this->setOptions();
    }

    public function upgrade(): void {
        $this->checkTables();
        $this->updateModule();
        $this->updateAssets();
        $this->updateOptions();
    }

    private function createTables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des services
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rdv_services (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            duration int NOT NULL,
            price decimal(10,2) DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Table des prestataires
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rdv_providers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(100) DEFAULT NULL,
            address text DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Table des rendez-vous
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rdv_appointments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id bigint(20) NOT NULL,
            provider_id bigint(20) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    private function registerModule(): void {
        if (!class_exists('ET_Builder_Module')) {
            return;
        }
        
        new CalendrierRdvModule();
    }

    private function registerAssets(): void {
        AssetsManager::getInstance()->register();
    }

    private function setOptions(): void {
        add_option('calendrier_rdv_version', CALENDRIER_RDV_VERSION);
        add_option('calendrier_rdv_db_version', CALENDRIER_RDV_DB_VERSION);
    }

    private function checkTables(): void {
        global $wpdb;
        
        // Vérifier et mettre à jour les tables si nécessaire
        $tables = array(
            $wpdb->prefix . 'rdv_services',
            $wpdb->prefix . 'rdv_providers',
            $wpdb->prefix . 'rdv_appointments'
        );
        
        foreach ($tables as $table) {
            if (!$wpdb->get_var("SHOW TABLES LIKE '$table'")) {
                $this->createTables();
                break;
            }
        }
    }

    private function updateModule(): void {
        if (!class_exists('ET_Builder_Module')) {
            return;
        }
        
        // Vérifier et mettre à jour les champs du module si nécessaire
        $module = new CalendrierRdvModule();
        $fields = $module->get_fields();
        
        // Logique de mise à jour des champs
    }

    private function updateAssets(): void {
        // Vérifier et mettre à jour les assets si nécessaire
        AssetsManager::getInstance()->update();
    }

    private function updateOptions(): void {
        // Vérifier et mettre à jour les options si nécessaire
        update_option('calendrier_rdv_version', CALENDRIER_RDV_VERSION);
        update_option('calendrier_rdv_db_version', CALENDRIER_RDV_DB_VERSION);
    }

    public function uninstall(): void {
        $this->dropTables();
        $this->removeOptions();
    }

    private function dropTables(): void {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'rdv_services',
            $wpdb->prefix . 'rdv_providers',
            $wpdb->prefix . 'rdv_appointments'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    private function removeOptions(): void {
        delete_option('calendrier_rdv_version');
        delete_option('calendrier_rdv_db_version');
        // Ajouter ici d'autres options à supprimer si nécessaire
    }
}
