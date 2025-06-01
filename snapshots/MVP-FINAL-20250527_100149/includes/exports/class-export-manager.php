<?php
/**
 * Gestionnaire d'exportation des données
 *
 * @package Calendrier_RDV
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant l'exportation des données
 */
class CalRdv_Export_Manager {
    
    /**
     * Instance unique de la classe
     *
     * @var CalRdv_Export_Manager
     */
    private static $instance = null;
    
    /**
     * Format d'exportation disponibles
     *
     * @var array
     */
    private $formats = [];
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        $this->init_formats();
        $this->init_hooks();
    }
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return CalRdv_Export_Manager
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialise les formats d'exportation disponibles
     */
    private function init_formats() {
        $this->formats = [
            'csv' => [
                'name' => __('CSV', 'calendrier-rdv'),
                'description' => __('Format CSV (valeurs séparées par des virgules)', 'calendrier-rdv'),
                'mime_type' => 'text/csv',
                'extension' => 'csv',
                'handler' => [$this, 'export_csv'],
            ],
            'excel' => [
                'name' => __('Excel', 'calendrier-rdv'),
                'description' => __('Format Excel (XLSX)', 'calendrier-rdv'),
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extension' => 'xlsx',
                'handler' => [$this, 'export_excel'],
            ],
        ];
        
        // Permettre d'ajouter d'autres formats
        $this->formats = apply_filters('calendrier_rdv_export_formats', $this->formats);
    }
    
    /**
     * Initialise les hooks WordPress
     */
    private function init_hooks() {
        // AJAX pour l'exportation
        add_action('wp_ajax_calendrier_rdv_export_data', [$this, 'ajax_export_data']);
        
        // Admin
        add_action('admin_init', [$this, 'process_export_request']);
    }
    
    /**
     * Obtenir les formats d'exportation disponibles
     * 
     * @return array
     */
    public function get_formats() {
        return $this->formats;
    }
    
    /**
     * Traite une demande d'exportation via AJAX
     */
    public function ajax_export_data() {
        // Vérifier le nonce
        check_ajax_referer('calendrier_rdv_export_nonce', 'nonce');
        
        // Vérifier les permissions
        if (!current_user_can('edit_appointments')) {
            wp_send_json_error([
                'message' => __('Vous n\'avez pas les permissions nécessaires pour exporter les données.', 'calendrier-rdv'),
            ]);
        }
        
        // Récupérer les paramètres
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $data_type = isset($_POST['data_type']) ? sanitize_text_field($_POST['data_type']) : 'appointments';
        $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
        
        // Valider le format
        if (!isset($this->formats[$format])) {
            wp_send_json_error([
                'message' => __('Format d\'exportation non valide.', 'calendrier-rdv'),
            ]);
        }
        
        // Générer l'URL d'exportation
        $export_url = add_query_arg([
            'action' => 'calendrier_rdv_export',
            'format' => $format,
            'data_type' => $data_type,
                'filters' => urlencode(json_encode($filters)),
                'nonce' => wp_create_nonce('calendrier_rdv_export'),
        ], admin_url('admin.php'));
        
        wp_send_json_success([
            'export_url' => $export_url,
        ]);
    }
    
    /**
     * Traite une demande d'exportation directe
     */
    public function process_export_request() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'calendrier_rdv_export') {
            return;
        }
        
        // Vérifier le nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'calendrier_rdv_export')) {
            wp_die(__('Lien d\'exportation non valide ou expiré.', 'calendrier-rdv'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('edit_appointments')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires pour exporter les données.', 'calendrier-rdv'));
        }
        
        // Récupérer les paramètres
        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'csv';
        $data_type = isset($_GET['data_type']) ? sanitize_text_field($_GET['data_type']) : 'appointments';
        $filters = isset($_GET['filters']) ? json_decode(urldecode($_GET['filters']), true) : [];
        
        // Valider le format
        if (!isset($this->formats[$format])) {
            wp_die(__('Format d\'exportation non valide.', 'calendrier-rdv'));
        }
        
        // Récupérer les données à exporter
        $data = $this->get_data_for_export($data_type, $filters);
        
        // Générer le fichier d'exportation
        $handler = $this->formats[$format]['handler'];
        if (is_callable($handler)) {
            call_user_func($handler, $data, $data_type, $filters);
        } else {
            wp_die(__('Gestionnaire d\'exportation non valide.', 'calendrier-rdv'));
        }
        
        // Terminer l'exécution
        exit;
    }
    
    /**
     * Récupère les données à exporter
     * 
     * @param string $data_type Type de données (appointments, services, providers, etc.)
     * @param array $filters Filtres à appliquer
     * @return array
     */
    private function get_data_for_export($data_type, $filters = []) {
        $data = [];
        
        switch ($data_type) {
            case 'appointments':
                $data = $this->get_appointments_data($filters);
                break;
                
            case 'services':
                $data = $this->get_services_data($filters);
                break;
                
            case 'providers':
                $data = $this->get_providers_data($filters);
                break;
                
            case 'customers':
                $data = $this->get_customers_data($filters);
                break;
                
            default:
                // Permettre d'ajouter d'autres types de données
                $data = apply_filters('calendrier_rdv_export_data_' . $data_type, $data, $filters);
                break;
        }
        
        return $data;
    }
    
    /**
     * Récupère les données des rendez-vous
     * 
     * @param array $filters Filtres à appliquer
     * @return array
     */
    private function get_appointments_data($filters = []) {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'cal_rdv_appointments';
        $customers_table = $wpdb->prefix . 'cal_rdv_customers';
        $services_table = $wpdb->prefix . 'cal_rdv_services';
        $providers_table = $wpdb->prefix . 'cal_rdv_providers';
        
        $where_clauses = [];
        $query_args = [];
        
        // Filtre par date
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $where_clauses[] = 'a.start_date >= %s';
            $query_args[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $where_clauses[] = 'a.start_date <= %s';
            $query_args[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Filtre par statut
        if (isset($filters['status']) && !empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = array_fill(0, count($filters['status']), '%s');
                $where_clauses[] = 'a.status IN (' . implode(', ', $placeholders) . ')';
                $query_args = array_merge($query_args, $filters['status']);
            } else {
                $where_clauses[] = 'a.status = %s';
                $query_args[] = $filters['status'];
            }
        }
        
        // Filtre par prestataire
        if (isset($filters['provider_id']) && !empty($filters['provider_id'])) {
            $where_clauses[] = 'a.provider_id = %d';
            $query_args[] = intval($filters['provider_id']);
        }
        
        // Filtre par service
        if (isset($filters['service_id']) && !empty($filters['service_id'])) {
            $where_clauses[] = 'a.service_id = %d';
            $query_args[] = intval($filters['service_id']);
        }
        
        // Construire la clause WHERE
        $where = '';
        if (!empty($where_clauses)) {
            $where = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Récupérer les données
        $query = $wpdb->prepare(
            "SELECT 
                a.*,
                c.name AS customer_name,
                c.email AS customer_email,
                c.phone AS customer_phone,
                s.name AS service_name,
                s.price AS service_price,
                p.name AS provider_name
            FROM 
                $appointments_table AS a
            LEFT JOIN 
                $customers_table AS c ON a.customer_id = c.ID
            LEFT JOIN 
                $services_table AS s ON a.service_id = s.ID
            LEFT JOIN 
                $providers_table AS p ON a.provider_id = p.ID
            $where
            ORDER BY 
                a.start_date DESC",
            $query_args
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Formater les données
        $data = [];
        $data[] = [
            __('ID', 'calendrier-rdv'),
            __('Date', 'calendrier-rdv'),
            __('Heure', 'calendrier-rdv'),
            __('Client', 'calendrier-rdv'),
            __('Email', 'calendrier-rdv'),
            __('Téléphone', 'calendrier-rdv'),
            __('Service', 'calendrier-rdv'),
            __('Prestataire', 'calendrier-rdv'),
            __('Prix', 'calendrier-rdv'),
            __('Statut', 'calendrier-rdv'),
            __('Créé le', 'calendrier-rdv'),
        ];
        
        foreach ($results as $row) {
            $start_date = new DateTime($row['start_date']);
            
            $data[] = [
                $row['ID'],
                $start_date->format('d/m/Y'),
                $start_date->format('H:i'),
                $row['customer_name'],
                $row['customer_email'],
                $row['customer_phone'],
                $row['service_name'],
                $row['provider_name'],
                number_format((float) $row['price'], 2, ',', ' ') . ' €',
                $this->get_appointment_status_label($row['status']),
                date('d/m/Y H:i', strtotime($row['created_at'])),
            ];
        }
        
        return $data;
    }
    
    /**
     * Récupère les données des services
     * 
     * @param array $filters Filtres à appliquer
     * @return array
     */
    private function get_services_data($filters = []) {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'cal_rdv_services';
        
        $where_clauses = [];
        $query_args = [];
        
        // Filtre par statut
        if (isset($filters['status']) && !empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = array_fill(0, count($filters['status']), '%s');
                $where_clauses[] = 'status IN (' . implode(', ', $placeholders) . ')';
                $query_args = array_merge($query_args, $filters['status']);
            } else {
                $where_clauses[] = 'status = %s';
                $query_args[] = $filters['status'];
            }
        }
        
        // Construire la clause WHERE
        $where = '';
        if (!empty($where_clauses)) {
            $where = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Récupérer les données
        $query = $wpdb->prepare(
            "SELECT * FROM $services_table $where ORDER BY name ASC",
            $query_args
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Formater les données
        $data = [];
        $data[] = [
            __('ID', 'calendrier-rdv'),
            __('Nom', 'calendrier-rdv'),
            __('Description', 'calendrier-rdv'),
            __('Durée (min)', 'calendrier-rdv'),
            __('Prix', 'calendrier-rdv'),
            __('Capacité', 'calendrier-rdv'),
            __('Statut', 'calendrier-rdv'),
            __('Créé le', 'calendrier-rdv'),
        ];
        
        foreach ($results as $row) {
            $data[] = [
                $row['ID'],
                $row['name'],
                $row['description'],
                $row['duration'],
                number_format((float) $row['price'], 2, ',', ' ') . ' €',
                $row['capacity'],
                $row['status'],
                date('d/m/Y H:i', strtotime($row['created_at'])),
            ];
        }
        
        return $data;
    }
    
    /**
     * Récupère les données des prestataires
     * 
     * @param array $filters Filtres à appliquer
     * @return array
     */
    private function get_providers_data($filters = []) {
        global $wpdb;
        
        $providers_table = $wpdb->prefix . 'cal_rdv_providers';
        
        // Récupérer les données
        $results = $wpdb->get_results(
            "SELECT * FROM $providers_table ORDER BY name ASC",
            ARRAY_A
        );
        
        // Formater les données
        $data = [];
        $data[] = [
            __('ID', 'calendrier-rdv'),
            __('Nom', 'calendrier-rdv'),
            __('Email', 'calendrier-rdv'),
            __('Téléphone', 'calendrier-rdv'),
            __('Utilisateur WordPress', 'calendrier-rdv'),
            __('Actif', 'calendrier-rdv'),
            __('Créé le', 'calendrier-rdv'),
        ];
        
        foreach ($results as $row) {
            $user_info = '';
            if (!empty($row['user_id'])) {
                $user = get_userdata($row['user_id']);
                if ($user) {
                    $user_info = $user->user_login . ' (ID: ' . $user->ID . ')';
                }
            }
            
            $data[] = [
                $row['ID'],
                $row['name'],
                $row['email'],
                $row['phone'],
                $user_info,
                !empty($row['active']) ? __('Oui', 'calendrier-rdv') : __('Non', 'calendrier-rdv'),
                date('d/m/Y H:i', strtotime($row['created_at'])),
            ];
        }
        
        return $data;
    }
    
    /**
     * Récupère les données des clients
     * 
     * @param array $filters Filtres à appliquer
     * @return array
     */
    private function get_customers_data($filters = []) {
        global $wpdb;
        
        $customers_table = $wpdb->prefix . 'cal_rdv_customers';
        $appointments_table = $wpdb->prefix . 'cal_rdv_appointments';
        
        // Récupérer les données
        $results = $wpdb->get_results(
            "SELECT 
                c.*,
                COUNT(a.ID) AS appointment_count,
                MAX(a.start_date) AS last_appointment
            FROM 
                $customers_table AS c
            LEFT JOIN 
                $appointments_table AS a ON c.ID = a.customer_id
            GROUP BY 
                c.ID
            ORDER BY 
                c.name ASC",
            ARRAY_A
        );
        
        // Formater les données
        $data = [];
        $data[] = [
            __('ID', 'calendrier-rdv'),
            __('Nom', 'calendrier-rdv'),
            __('Email', 'calendrier-rdv'),
            __('Téléphone', 'calendrier-rdv'),
            __('Adresse', 'calendrier-rdv'),
            __('Nombre de RDV', 'calendrier-rdv'),
            __('Dernier RDV', 'calendrier-rdv'),
            __('Créé le', 'calendrier-rdv'),
        ];
        
        foreach ($results as $row) {
            $last_appointment = !empty($row['last_appointment']) 
                ? date('d/m/Y', strtotime($row['last_appointment']))
                : '-';
            
            $data[] = [
                $row['ID'],
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['address'],
                $row['appointment_count'],
                $last_appointment,
                date('d/m/Y H:i', strtotime($row['created_at'])),
            ];
        }
        
        return $data;
    }
    
    /**
     * Exporte les données au format CSV
     * 
     * @param array $data Données à exporter
     * @param string $data_type Type de données
     * @param array $filters Filtres appliqués
     */
    public function export_csv($data, $data_type, $filters = []) {
        $filename = $this->get_export_filename($data_type, 'csv');
        
        // Définir les en-têtes HTTP
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Créer un pointeur de fichier
        $output = fopen('php://output', 'w');
        
        // Ajouter le BOM pour UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // Exporter les données
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporte les données au format Excel
     * 
     * @param array $data Données à exporter
     * @param string $data_type Type de données
     * @param array $filters Filtres appliqués
     */
    public function export_excel($data, $data_type, $filters = []) {
        // Vérifier si la bibliothèque PhpSpreadsheet est disponible
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback vers CSV si PhpSpreadsheet n'est pas disponible
            $this->export_csv($data, $data_type, $filters);
            return;
        }
        
        $filename = $this->get_export_filename($data_type, 'xlsx');
        
        // Créer un nouvel objet Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Définir le titre de l'onglet en fonction du type de données
        $sheet_title = $this->get_data_type_label($data_type);
        $sheet->setTitle(substr($sheet_title, 0, 31)); // Excel limite les titres d'onglet à 31 caractères
        
        // Remplir les données
        $row_index = 1;
        foreach ($data as $row) {
            $col_index = 1;
            foreach ($row as $value) {
                $sheet->setCellValueByColumnAndRow($col_index, $row_index, $value);
                $col_index++;
            }
            $row_index++;
        }
        
        // Mettre en forme le tableau
        $last_column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($data[0]));
        $last_row = count($data);
        
        // Mettre en forme l'en-tête
        $sheet->getStyle('A1:' . $last_column . '1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $last_column . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1:' . $last_column . '1')->getFill()->getStartColor()->setRGB('DDDDDD');
        
        // Ajuster la largeur des colonnes
        foreach (range('A', $last_column) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Ajouter un filtre automatique
        $sheet->setAutoFilter('A1:' . $last_column . '1');
        
        // Définir les en-têtes HTTP
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Créer un objet Writer pour sauvegarder le fichier
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Génère un nom de fichier pour l'exportation
     * 
     * @param string $data_type Type de données
     * @param string $extension Extension du fichier
     * @return string
     */
    private function get_export_filename($data_type, $extension) {
        $type_label = $this->get_data_type_label($data_type);
        $date = date('Y-m-d_His');
        
        return sanitize_file_name(
            sprintf(
                'calendrier-rdv_%1$s_%2$s.%3$s',
                strtolower($type_label),
                $date,
                $extension
            )
        );
    }
    
    /**
     * Obtenir le libellé d'un type de données
     * 
     * @param string $data_type Type de données
     * @return string
     */
    private function get_data_type_label($data_type) {
        $labels = [
            'appointments' => __('Rendez-vous', 'calendrier-rdv'),
            'services' => __('Services', 'calendrier-rdv'),
            'providers' => __('Prestataires', 'calendrier-rdv'),
            'customers' => __('Clients', 'calendrier-rdv'),
        ];
        
        return isset($labels[$data_type]) ? $labels[$data_type] : $data_type;
    }
    
    /**
     * Obtenir le libellé d'un statut de rendez-vous
     * 
     * @param string $status Statut
     * @return string
     */
    private function get_appointment_status_label($status) {
        $labels = [
            'pending' => __('En attente', 'calendrier-rdv'),
            'confirmed' => __('Confirmé', 'calendrier-rdv'),
            'cancelled' => __('Annulé', 'calendrier-rdv'),
            'completed' => __('Terminé', 'calendrier-rdv'),
            'no-show' => __('Absent', 'calendrier-rdv'),
        ];
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
}

/**
 * Fonction utilitaire pour accéder au gestionnaire d'exportation
 * 
 * @return CalRdv_Export_Manager
 */
function calendrier_rdv_export_manager() {
    return CalRdv_Export_Manager::get_instance();
}

// Initialiser le gestionnaire d'exportation
add_action('plugins_loaded', ['CalRdv_Export_Manager', 'get_instance']);
