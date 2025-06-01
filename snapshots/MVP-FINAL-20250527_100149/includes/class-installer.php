<?php
// =============================================
// Fichier : includes/class-installer.php
// Description : Gère l'installation et la mise à jour du plugin
// Auteur : SAN Digital Solutions
// =============================================

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class CalRdv_Installer {
    /**
     * Version actuelle du schéma de base de données
     */
    const DB_VERSION = '1.0.0';

    /**
     * Instance unique (singleton)
     */
    private static $instance = null;

    /**
     * Constructeur privé
     */
    private function __construct() {
        // Empêcher l'instanciation directe
    }

    /**
     * Récupère l'instance unique
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Exécute l'installation
     */
    public function install() {
        global $wpdb;
        
        // Vérifier si c'est une nouvelle installation
        $current_version = get_option('calendrier_rdv_db_version', '0');
        $needs_update = version_compare($current_version, self::DB_VERSION, '<');
        
        // Activer la journalisation des erreurs
        $wpdb->show_errors();
        $errors = array();
        
        try {
            // Créer les tables si nécessaire
            if ($needs_update) {
                $tables_created = $this->create_tables();
                
                if ($tables_created) {
                    update_option('calendrier_rdv_db_version', self::DB_VERSION);
                    
                    // Insérer les données par défaut
                    $this->insert_default_data();
                    
                    // Mettre à jour les options si nécessaire
                    if ($current_version === '0') {
                        // Nouvelle installation
                        update_option('calendrier_rdv_installed', current_time('mysql'));
                        update_option('calendrier_rdv_updated', current_time('mysql'));
                    } else {
                        // Mise à jour
                        update_option('calendrier_rdv_updated', current_time('mysql'));
                    }
                } else {
                    $errors[] = 'Échec de la création des tables de la base de données.';
                }
            }

            // Planifier les tâches récurrentes
            $this->schedule_events();

            // Créer les pages nécessaires
            $this->create_pages();
            
            // Mettre à jour les capacités des rôles
            $this->setup_roles();
            
            // Vider les caches si nécessaire
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de l\'installation : ' . $e->getMessage();
        }
        
        // Enregistrer les erreurs si nécessaire
        if (!empty($errors)) {
            update_option('calendrier_rdv_install_errors', $errors);
            error_log('Erreurs d\'installation Calendrier RDV : ' . print_r($errors, true));
            return false;
        }
        
        return true;
    }

    /**
     * Crée les tables de la base de données
     * 
     * @return bool True si les tables ont été créées avec succès, false sinon
     */
    private function create_tables() {
        global $wpdb;
        
        // Vérifier les droits d'écriture
        if (!current_user_can('activate_plugins')) {
            error_log('Permissions insuffisantes pour créer les tables de la base de données');
            return false;
        }
        
        // Charger le schéma SQL
        $schema_file = dirname(dirname(__FILE__)) . '/sql/schema.sql';
        
        if (!file_exists($schema_file)) {
            error_log('Fichier de schéma SQL introuvable : ' . $schema_file);
            return false;
        }

        // Lire le contenu du fichier
        $sql = file_get_contents($schema_file);
        
        if (empty($sql)) {
            error_log('Le fichier de schéma SQL est vide : ' . $schema_file);
            return false;
        }
        
        // Remplacer le préfixe des tables
        $prefix = $wpdb->prefix . 'calrdv_';
        $sql = str_replace('{PREFIX}', $prefix, $sql);
        
        // Remplacer le type de moteur de base de données si nécessaire
        $engine = 'InnoDB';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $engine = 'MyISAM';
        }
        $sql = str_replace('ENGINE=InnoDB', 'ENGINE=' . $engine, $sql);
        
        // Désactiver la vérification des clés étrangères
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        
        // Exécuter les requêtes
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Diviser les requêtes pour une meilleure gestion des erreurs
        $queries = explode(';', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $result = $wpdb->query($query);
                if ($result === false) {
                    error_log('Erreur SQL : ' . $wpdb->last_error);
                    error_log('Requête en échec : ' . $query);
                }
            }
        }
        
        // Réactiver la vérification des clés étrangères
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        
        // Vérifier que toutes les tables ont été créées
        $tables = array(
            $prefix . 'appointments',
            $prefix . 'services',
            $prefix . 'providers',
            $prefix . 'availability',
            $prefix . 'holidays',
            $prefix . 'settings',
            $prefix . 'logs',
            $prefix . 'appointment_meta'
        );
        
        $missing_tables = array();
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            error_log('Tables manquantes après installation : ' . implode(', ', $missing_tables));
            return false;
        }
        
        return true;
    }

    /**
     * Planifie les tâches récurrentes
     */
    private function schedule_events() {
        // Planifier l'envoi des rappels - toutes les heures
        if (!wp_next_scheduled('calendrier_rdv_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'calendrier_rdv_send_reminders');
        }
        
        // Planifier le nettoyage des logs - tous les jours à minuit
        if (!wp_next_scheduled('calendrier_rdv_cleanup_logs')) {
            wp_schedule_event(strtotime('tomorrow'), 'daily', 'calendrier_rdv_cleanup_logs');
        }
        
        // Planifier la vérification des créneaux disponibles - toutes les 6 heures
        if (!wp_next_scheduled('calendrier_rdv_check_availability')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS * 6, 'six_hours', 'calendrier_rdv_check_availability');
        }
        
        // Planifier la notification des rendez-vous du jour - tous les matins à 8h
        if (!wp_next_scheduled('calendrier_rdv_daily_notifications')) {
            wp_schedule_event(strtotime('today 8:00'), 'daily', 'calendrier_rdv_daily_notifications');
        }
    }

    /**
     * Crée les pages nécessaires
     */
    private function create_pages() {
        // Page de prise de rendez-vous
        $pages = array(
            array(
                'title' => __('Prendre un rendez-vous', 'calendrier-rdv'),
                'slug' => 'prendre-rendez-vous',
                'content' => '[calendrier_rdv]',
                'option' => 'calendrier_rdv_page_id'
            ),
            array(
                'title' => __('Mon compte', 'calendrier-rdv'),
                'slug' => 'mon-compte',
                'content' => '[calendrier_rdv_my_account]',
                'option' => 'calendrier_rdv_myaccount_page_id'
            ),
            array(
                'title' => __('Confirmation de rendez-vous', 'calendrier-rdv'),
                'slug' => 'confirmation-rendez-vous',
                'content' => '[calendrier_rdv_booking_confirmation]',
                'option' => 'calendrier_rdv_confirmation_page_id'
            )
        );
        
        foreach ($pages as $page_data) {
            $page_check = get_page_by_path($page_data['slug']);
            
            if (!$page_check) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_author' => get_current_user_id() ?: 1
                ));
                
                if ($page_id && !is_wp_error($page_id)) {
                    update_option($page_data['option'], $page_id);
                }
            } else {
                // Mettre à jour l'ID de la page si elle existe déjà
                update_option($page_data['option'], $page_check->ID);
            }
        }
        
        // Mettre à jour l'option de la page de compte dans les réglages
        $myaccount_page_id = get_option('calendrier_rdv_myaccount_page_id');
        if ($myaccount_page_id) {
            update_option('calendrier_rdv_myaccount_page_id', $myaccount_page_id);
        }
    }

    /**
     * Nettoie les données lors de la désinstallation
     */
    public static function uninstall($delete_data = false) {
        global $wpdb;
        
        // Supprimer les tâches planifiées
        $cron_hooks = array(
            'calendrier_rdv_send_reminders',
            'calendrier_rdv_cleanup_logs',
            'calendrier_rdv_check_availability',
            'calendrier_rdv_daily_notifications'
        );
        
        foreach ($cron_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
        
        // Si l'option de suppression des données est activée
        if ($delete_data) {
            // Supprimer les options
            $options = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'calendrier_rdv_%'");
            
            foreach ($options as $option) {
                delete_option($option);
            }
            
            // Supprimer les métas des utilisateurs
            $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'calendrier_rdv_%'");
            
            // Supprimer les tables
            $tables = array(
                'appointments',
                'services',
                'providers',
                'availability',
                'holidays',
                'settings',
                'logs',
                'appointment_meta'
            );
            
            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}calrdv_{$table}");
            }
            
            // Supprimer les pages créées
            $pages = array(
                'calendrier_rdv_page_id',
                'calendrier_rdv_myaccount_page_id',
                'calendrier_rdv_confirmation_page_id'
            );
            
            foreach ($pages as $page_option) {
                $page_id = get_option($page_option);
                if ($page_id) {
                    wp_delete_post($page_id, true);
                }
                delete_option($page_option);
            }
        }
    }
    
    /**
     * Insère les données par défaut dans la base de données
     */
    private function insert_default_data() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'calrdv_';
        
        // Vérifier si des données existent déjà
        $has_services = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}services") > 0;
        
        if (!$has_services) {
            // Insérer un service par défaut
            $wpdb->insert(
                $prefix . 'services',
                array(
                    'name' => __('Consultation', 'calendrier-rdv'),
                    'slug' => 'consultation',
                    'description' => __('Consultation standard', 'calendrier-rdv'),
                    'duration' => 30,
                    'price' => 0.00,
                    'color' => '#3a87ad',
                    'is_active' => 1
                ),
                array('%s', '%s', '%s', '%d', '%f', '%s', '%d')
            );
        }
        
        // Insérer des paramètres par défaut
        $default_settings = array(
            'timezone' => 'Europe/Paris',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'start_of_week' => 1, // Lundi
            'min_booking_notice' => 2, // Heures
            'max_booking_months' => 3,
            'email_from_name' => get_bloginfo('name'),
            'email_from_address' => get_bloginfo('admin_email'),
            'email_admin_notification' => 1,
            'email_client_notification' => 1,
            'email_reminder_enabled' => 1,
            'email_reminder_time' => 24, // Heures avant le RDV
            'terms_page_id' => 0,
            'privacy_page_id' => 0
        );
        
        foreach ($default_settings as $key => $value) {
            if ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}settings WHERE setting_key = %s", $key)) == 0) {
                $wpdb->insert(
                    $prefix . 'settings',
                    array(
                        'setting_key' => $key,
                        'setting_value' => is_array($value) ? json_encode($value) : $value,
                        'setting_group' => 'general',
                        'setting_type' => is_numeric($value) ? 'integer' : (is_bool($value) ? 'boolean' : 'string'),
                        'is_autoload' => 1
                    )
                );
            }
        }
    }
    
    /**
     * Configure les rôles et les capacités
     */
    private function setup_roles() {
        // Rôle administrateur
        $admin_capabilities = array(
            'manage_calendrier_rdv' => true,
            'edit_appointments' => true,
            'delete_appointments' => true,
            'edit_others_appointments' => true,
            'publish_appointments' => true,
            'manage_services' => true,
            'manage_providers' => true,
            'manage_settings' => true,
            'view_reports' => true
        );
        
        // Rôle prestataire
        $provider_capabilities = array(
            'manage_calendrier_rdv' => true,
            'edit_appointments' => true,
            'delete_appointments' => true,
            'publish_appointments' => true,
            'view_reports' => true
        );
        
        // Rôle client
        $customer_capabilities = array(
            'make_appointments' => true,
            'view_own_appointments' => true,
            'cancel_own_appointments' => true
        );
        
        // Mettre à jour les rôles existants
        $roles = array(
            'administrator' => $admin_capabilities,
            'editor' => $provider_capabilities,
            'author' => $customer_capabilities,
            'subscriber' => $customer_capabilities
        );
        
        foreach ($roles as $role_name => $capabilities) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap => $grant) {
                    $role->add_cap($cap, $grant);
                }
            }
        }
    }
}

// Initialiser l'installateur
function calendrier_rdv_installer() {
    return CalRdv_Installer::get_instance();
}

// Enregistrer les hooks d'activation et de désactivation
register_activation_hook(CAL_RDV_PLUGIN_FILE, array(calendrier_rdv_installer(), 'install'));
register_uninstall_hook(CAL_RDV_PLUGIN_FILE, array('CalRdv_Installer', 'uninstall'));
