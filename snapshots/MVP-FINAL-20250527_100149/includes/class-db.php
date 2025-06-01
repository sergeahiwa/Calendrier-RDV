<?php
/**
 * Classe de gestion de la base de données pour le plugin Calendrier RDV
 *
 * @package CalendrierRdv
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe DB pour gérer les opérations de base de données
 */
class CalRdv_DB {
    
    /**
     * Instance unique de la classe
     *
     * @var CalRdv_DB
     */
    private static $instance = null;
    
    /**
     * Préfixe des tables du plugin
     *
     * @var string
     */
    private $prefix;
    
    /**
     * Constructeur
     */
    private function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'calendrier_rdv_';
    }
    
    /**
     * Obtenir l'instance unique de la classe
     *
     * @return CalRdv_DB
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Créer les tables de la base de données
     *
     * @return void
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des prestataires
        $table_providers = $this->prefix . 'providers';
        $sql_providers = "CREATE TABLE $table_providers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT '',
            description text,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Table des services
        $table_services = $this->prefix . 'services';
        $sql_services = "CREATE TABLE $table_services (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text,
            duration int(11) NOT NULL,
            price decimal(10,2) DEFAULT 0.00,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Table de relation prestataires-services
        $table_provider_services = $this->prefix . 'provider_services';
        $sql_provider_services = "CREATE TABLE $table_provider_services (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            provider_id mediumint(9) NOT NULL,
            service_id mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY provider_service (provider_id, service_id),
            KEY provider_id (provider_id),
            KEY service_id (service_id)
        ) $charset_collate;";
        
        // Table des horaires de travail
        $table_working_hours = $this->prefix . 'working_hours';
        $sql_working_hours = "CREATE TABLE $table_working_hours (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            provider_id mediumint(9) NOT NULL,
            day_of_week tinyint(1) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider_id (provider_id),
            KEY day_of_week (day_of_week)
        ) $charset_collate;";
        
        // Table des jours fériés/congés
        $table_holidays = $this->prefix . 'holidays';
        $sql_holidays = "CREATE TABLE $table_holidays (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            provider_id mediumint(9) NOT NULL,
            holiday_date date NOT NULL,
            description varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider_id (provider_id),
            KEY holiday_date (holiday_date)
        ) $charset_collate;";
        
        // Table des rendez-vous
        $table_appointments = $this->prefix . 'appointments';
        $sql_appointments = "CREATE TABLE $table_appointments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            provider_id mediumint(9) NOT NULL,
            service_id mediumint(9) NOT NULL,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) DEFAULT '',
            appointment_date datetime NOT NULL,
            duration int(11) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider_id (provider_id),
            KEY service_id (service_id),
            KEY appointment_date (appointment_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Table de la liste d'attente
        $table_waitlist = $this->prefix . 'waitlist';
        $sql_waitlist = "CREATE TABLE $table_waitlist (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            provider_id mediumint(9) NOT NULL,
            service_id mediumint(9) NOT NULL,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) DEFAULT '',
            preferred_date date NOT NULL,
            preferred_time_start time DEFAULT NULL,
            preferred_time_end time DEFAULT NULL,
            status varchar(20) DEFAULT 'waiting',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY provider_id (provider_id),
            KEY service_id (service_id),
            KEY preferred_date (preferred_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Table des notifications
        $table_notifications = $this->prefix . 'notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            appointment_id mediumint(9) NOT NULL,
            type varchar(20) NOT NULL,
            recipient varchar(100) NOT NULL,
            sent_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY appointment_id (appointment_id),
            KEY type (type),
            KEY status (status)
        ) $charset_collate;";
        
        // Charger le fichier de mise à jour de la base de données
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Créer les tables
        dbDelta($sql_providers);
        dbDelta($sql_services);
        dbDelta($sql_provider_services);
        dbDelta($sql_working_hours);
        dbDelta($sql_holidays);
        dbDelta($sql_appointments);
        dbDelta($sql_waitlist);
        dbDelta($sql_notifications);
        
        // Enregistrer la version de la base de données
        update_option('calendrier_rdv_db_version', '1.0.0');
    }
    
    /**
     * Insérer un enregistrement dans une table
     *
     * @param string $table Nom de la table (sans préfixe)
     * @param array $data Données à insérer
     * @return int|false ID de l'enregistrement inséré ou false en cas d'erreur
     */
    public function insert($table, $data) {
        global $wpdb;
        
        $table_name = $this->prefix . $table;
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Mettre à jour un enregistrement dans une table
     *
     * @param string $table Nom de la table (sans préfixe)
     * @param array $data Données à mettre à jour
     * @param array $where Conditions WHERE
     * @return int|false Nombre de lignes mises à jour ou false en cas d'erreur
     */
    public function update($table, $data, $where) {
        global $wpdb;
        
        $table_name = $this->prefix . $table;
        
        return $wpdb->update($table_name, $data, $where);
    }
    
    /**
     * Supprimer un enregistrement d'une table
     *
     * @param string $table Nom de la table (sans préfixe)
     * @param array $where Conditions WHERE
     * @return int|false Nombre de lignes supprimées ou false en cas d'erreur
     */
    public function delete($table, $where) {
        global $wpdb;
        
        $table_name = $this->prefix . $table;
        
        return $wpdb->delete($table_name, $where);
    }
    
    /**
     * Récupérer un enregistrement d'une table
     *
     * @param string $table Nom de la table (sans préfixe)
     * @param array $where Conditions WHERE
     * @param string $output_type Type de sortie (OBJECT, ARRAY_A, ARRAY_N)
     * @return mixed Enregistrement ou null si non trouvé
     */
    public function get_row($table, $where, $output_type = OBJECT) {
        global $wpdb;
        
        $table_name = $this->prefix . $table;
        
        $conditions = array();
        $values = array();
        
        foreach ($where as $field => $value) {
            $conditions[] = "$field = %s";
            $values[] = $value;
        }
        
        $where_clause = implode(' AND ', $conditions);
        
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE $where_clause LIMIT 1", $values);
        
        return $wpdb->get_row($query, $output_type);
    }
    
    /**
     * Récupérer plusieurs enregistrements d'une table
     *
     * @param string $table Nom de la table (sans préfixe)
     * @param array $where Conditions WHERE (facultatif)
     * @param string $orderby Champ pour le tri (facultatif)
     * @param string $order Direction du tri (ASC ou DESC) (facultatif)
     * @param int $limit Nombre d'enregistrements à récupérer (facultatif)
     * @param int $offset Décalage pour la pagination (facultatif)
     * @param string $output_type Type de sortie (OBJECT, ARRAY_A, ARRAY_N)
     * @return array Enregistrements
     */
    public function get_results($table, $where = array(), $orderby = '', $order = 'ASC', $limit = 0, $offset = 0, $output_type = OBJECT) {
        global $wpdb;
        
        $table_name = $this->prefix . $table;
        
        $query = "SELECT * FROM $table_name";
        
        // Conditions WHERE
        if (!empty($where)) {
            $conditions = array();
            $values = array();
            
            foreach ($where as $field => $value) {
                $conditions[] = "$field = %s";
                $values[] = $value;
            }
            
            $where_clause = implode(' AND ', $conditions);
            $query .= " WHERE $where_clause";
            $query = $wpdb->prepare($query, $values);
        }
        
        // ORDER BY
        if (!empty($orderby)) {
            $query .= " ORDER BY $orderby $order";
        }
        
        // LIMIT
        if ($limit > 0) {
            $query .= " LIMIT $offset, $limit";
        }
        
        return $wpdb->get_results($query, $output_type);
    }
    
    /**
     * Compter le nombre d'enregistrements dans une table
     *
     * @param string $table Nom de la table (sans préfixe)
     * @param array $where Conditions WHERE (facultatif)
     * @return int Nombre d'enregistrements
     */
    public function count($table, $where = array()) {
        global $wpdb;
        
        $table_name = $this->prefix . $table;
        
        $query = "SELECT COUNT(*) FROM $table_name";
        
        // Conditions WHERE
        if (!empty($where)) {
            $conditions = array();
            $values = array();
            
            foreach ($where as $field => $value) {
                $conditions[] = "$field = %s";
                $values[] = $value;
            }
            
            $where_clause = implode(' AND ', $conditions);
            $query .= " WHERE $where_clause";
            $query = $wpdb->prepare($query, $values);
        }
        
        return $wpdb->get_var($query);
    }
}
