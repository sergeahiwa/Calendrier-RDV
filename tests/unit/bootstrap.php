<?php
/**
 * Bootstrap pour les tests unitaires
 */

// Charger l'autoloader de Composer
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Définir les constantes nécessaires pour les tests
define('CALENDRIER_RDV_PLUGIN_DIR', dirname(__DIR__, 2) . '/');

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Simuler l'environnement WordPress
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        public $params = [];
        public $json_params = [];
        
        public function get_param($param) {
            return $this->params[$param] ?? null;
        }
        
        public function set_param($param, $value) {
            $this->params[$param] = $value;
        }
        
        public function get_json_params() {
            return $this->json_params;
        }
        
        public function set_json_params($params) {
            $this->json_params = $params;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;
        
        public function __construct($data = null, $status = 200) {
            $this->data = $data;
            $this->status = $status;
        }
        
        public function get_status() {
            return $this->status;
        }
        
        public function get_data() {
            return $this->data;
        }
    }
}

// Simuler la base de données
global $wpdb;

class MockWPDB {
    public $prefix = 'wp_';
    public $last_query = '';
    public $last_error = '';
    public $insert_id = 0;
    public $rows_affected = 0;
    public $tables = [];
    private $query_results = [];
    
    public function __construct() {
        // Initialiser les tables
        $this->tables = [
            'wp_rdv_providers' => [],
            'wp_rdv_services' => [],
            'wp_rdv_appointments' => []
        ];
        $this->insert_id = 0;
    }
    
    public function query($query) {
        $this->last_query = $query;
        
        // Simuler la création de table
        if (strpos($query, 'CREATE TABLE') !== false) {
            return true;
        }
        
        // Simuler la suppression de table
        if (strpos($query, 'DROP TABLE') !== false) {
            return true;
        }
        
        // Simuler la troncature de table
        if (strpos($query, 'TRUNCATE TABLE') !== false) {
            $table = trim(str_replace('TRUNCATE TABLE', '', $query));
            if (isset($this->tables[$table])) {
                $this->tables[$table] = [];
                return true;
            }
            return false;
        }
        
        return true;
    }
    
    public function insert($table, $data, $format = null) {
        $this->last_query = "INSERT INTO $table ...";
        $this->insert_id = rand(1, 1000); // Générer un ID aléatoire
        
        // Ajouter les données à la table
        $row = (object)array_merge(['id' => $this->insert_id], $data);
        $this->tables[$table][$this->insert_id] = $row;
        $this->rows_affected = 1;
        
        return true;
    }
    
    public function update($table, $data, $where, $format = null, $where_format = null) {
        $this->last_query = "UPDATE $table ...";
        $this->rows_affected = 1;
        
        // Cas spécial pour les tests d'annulation de rendez-vous
        if (strpos($table, 'rdv_appointments') !== false && isset($data['status']) && $data['status'] === 'cancelled') {
            // Mettre à jour le statut du rendez-vous dans les résultats futurs
            if (isset($where['id'])) {
                $id = $where['id'];
                
                // Créer un objet avec le statut mis à jour pour les futures requêtes
                $updated_appointment = new \stdClass();
                $updated_appointment->id = $id;
                $updated_appointment->service_id = 1;
                $updated_appointment->provider_id = 1;
                $updated_appointment->appointment_date = '2025-05-23';
                $updated_appointment->appointment_time = '14:00:00';
                $updated_appointment->customer_name = 'Test User';
                $updated_appointment->customer_email = 'test@example.com';
                $updated_appointment->customer_phone = '0123456789';
                $updated_appointment->notes = '';
                $updated_appointment->status = 'cancelled';
                $updated_appointment->created_at = date('Y-m-d H:i:s');
                $updated_appointment->updated_at = date('Y-m-d H:i:s');
                
                // Stocker l'objet mis à jour
                $this->tables[$this->prefix . 'rdv_appointments'][$id] = $updated_appointment;
            }
        }
        
        if (isset($this->tables[$table])) {
            foreach ($this->tables[$table] as $id => $row) {
                $match = true;
                foreach ($where as $key => $value) {
                    if (!isset($row->$key) || $row->$key != $value) {
                        $match = false;
                        break;
                    }
                }
                
                if ($match) {
                    foreach ($data as $key => $value) {
                        $this->tables[$table][$id]->$key = $value;
                    }
                    $this->rows_affected++;
                }
            }
        }
        
        return $this->rows_affected > 0;
    }
    
    public function delete($table, $where, $where_format = null) {
        $this->last_query = "DELETE FROM $table ...";
        
        if (!isset($this->tables[$table])) {
            return false;
        }
        
        $deleted = 0;
        foreach ($this->tables[$table] as $id => $row) {
            $match = true;
            foreach ($where as $key => $value) {
                if ($row->$key != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                unset($this->tables[$table][$id]);
                $deleted++;
            }
        }
        
        $this->rows_affected = $deleted;
        return $deleted > 0;
    }
    
    private $availability_check_count = 0;
    public $mockUnavailableSlot = false;
    
    public function reset_availability_check_count() {
        $this->availability_check_count = 0;
    }
    
    public function set_availability_check_count($count) {
        $this->availability_check_count = $count;
    }
    
    public function get_row($query, $output = OBJECT, $y = 0) {
        $this->last_query = $query;
        
        // Si c'est une requête de comptage pour la disponibilité
        if (strpos($query, 'COUNT(*) as count') !== false) {
            $result = new \stdClass();
            
            // Cas spécial pour le test testUpdateAppointmentWithUnavailableSlot
            if ($this->mockUnavailableSlot && strpos($query, "SELECT COUNT(*) as count") !== false) {
                // Simuler un créneau déjà réservé pour le test de mise à jour
                $result->count = 1;
                return $result;
            }
            
            // Pour le test testCheckAvailability, on simule qu'au deuxième appel le créneau n'est plus disponible
            if (strpos($query, "appointment_date = '2025-05-23'") !== false && 
                strpos($query, "appointment_time = '14:00:00'") !== false) {
                
                $this->availability_check_count++;
                
                if ($this->availability_check_count > 1) {
                    // Au deuxième appel, on simule un créneau déjà réservé
                    $result->count = 1;
                } else {
                    // Au premier appel, le créneau est disponible
                    $result->count = 0;
                }
            } else {
                // Pour les autres requêtes, aucun rendez-vous trouvé
                $result->count = 0;
            }
            
            return $result;
        }
        
        // Si c'est une requête pour récupérer un rendez-vous spécifique par ID
        if (preg_match('/FROM\s+[\w`]+rdv_appointments\s+WHERE\s+id\s*=\s*(\d+)/i', $query, $matches)) {
            $id = intval($matches[1]);
            
            // Simuler un rendez-vous inexistant pour les tests d'erreur
            if ($id === 9999) {
                return null;
            }
            
            // Créer un objet rendez-vous
            if ($output === ARRAY_A) {
                return [
                    'id' => $id,
                    'service_id' => 1,
                    'provider_id' => 1,
                    'appointment_date' => '2025-05-23',
                    'appointment_time' => '14:00:00',
                    'customer_name' => 'Test User',
                    'customer_email' => 'test@example.com',
                    'customer_phone' => '0123456789',
                    'notes' => '',
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $result = new \stdClass();
                $result->id = $id;
                $result->service_id = 1;
                $result->provider_id = 1;
                $result->appointment_date = '2025-05-23';
                $result->appointment_time = '14:00:00';
                $result->customer_name = 'Test User';
                $result->customer_email = 'test@example.com';
                $result->customer_phone = '0123456789';
                $result->notes = '';
                $result->status = 'pending';
                $result->created_at = date('Y-m-d H:i:s');
                $result->updated_at = date('Y-m-d H:i:s');
                return $result;
            }
        }
        
        // Si c'est une requête pour récupérer les détails d'un rendez-vous avec JOIN
        if (strpos($query, 'rdv_appointments a') !== false && strpos($query, 'JOIN') !== false) {
            // Simuler un rendez-vous avec toutes les informations nécessaires
            if ($output === ARRAY_A) {
                return [
                    'id' => 1,
                    'service_id' => 1,
                    'provider_id' => 1,
                    'appointment_date' => '2025-05-23',
                    'appointment_time' => '14:00:00',
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@example.com',
                    'customer_phone' => '123456789',
                    'notes' => 'Test notes',
                    'status' => 'pending',
                    'created_at' => '2025-05-23 00:00:00',
                    'updated_at' => '2025-05-23 00:00:00',
                    'service_name' => 'Test Service',
                    'provider_name' => 'Test Provider',
                    'email' => 'provider@example.com'
                ];
            } else {
                $result = new \stdClass();
                $result->id = 1;
                $result->service_id = 1;
                $result->provider_id = 1;
                $result->appointment_date = '2025-05-23';
                $result->appointment_time = '14:00:00';
                $result->customer_name = 'John Doe';
                $result->customer_email = 'john@example.com';
                $result->customer_phone = '123456789';
                $result->notes = 'Test notes';
                $result->status = 'pending';
                $result->created_at = '2025-05-23 00:00:00';
                $result->updated_at = '2025-05-23 00:00:00';
                $result->service_name = 'Test Service';
                $result->provider_name = 'Test Provider';
                $result->email = 'provider@example.com';
                return $result;
            }
        }
        
        // Extraire le nom de la table
        if (preg_match('/FROM\s+`?(\w+)`?/i', $query, $matches)) {
            $table = $matches[1];
            
            // Si c'est une requête sur la table des prestataires
            if (strpos($table, 'providers') !== false) {
                if (preg_match('/WHERE\s+id\s*=\s*(\d+)/i', $query, $id_matches)) {
                    $id = intval($id_matches[1]);
                    if (isset($this->tables[$this->prefix . 'rdv_providers'][$id])) {
                        return $this->tables[$this->prefix . 'rdv_providers'][$id];
                    }
                }
                
                // Si on demande tous les prestataires
                if (strpos($query, 'WHERE') === false) {
                    if ($output === ARRAY_A) {
                        return array_map(function($item) {
                            return (array)$item;
                        }, $this->tables[$this->prefix . 'rdv_providers']);
                    }
                    return $this->tables[$this->prefix . 'rdv_providers'];
                }
            }
        }
        
        // Pour les autres requêtes, retourner null
        return null;
    }
    
    public function get_results($query, $output = OBJECT) {
        $this->last_query = $query;
        
        // Extraire le nom de la table
        if (preg_match('/FROM\s+`?(\w+)`?/i', $query, $matches)) {
            $table = $matches[1];
            
            if (isset($this->tables[$table])) {
                $results = [];
                foreach ($this->tables[$table] as $row) {
                    $results[] = $output === ARRAY_A ? (array)$row : $row;
                }
                return $results;
            }
        }
        
        return [];
    }
    
    public function prepare($query, ...$args) {
        $this->last_query = $query;
        
        // Remplacer les placeholders %s, %d, etc. par les valeurs fournies
        foreach ($args as $arg) {
            $query = preg_replace('/%[sdf]/', is_numeric($arg) ? $arg : "'$arg'", $query, 1);
        }
        
        return $query;
    }
    
    public function insert_id() {
        return $this->insert_id++;
    }
}

// Initialiser la base de données simulée
$wpdb = new MockWPDB();

// Définir la fonction de traduction
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

// Définir les constantes WordPress nécessaires
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

// Définir les fonctions utilisateur WordPress
// Variable globale pour simuler la fonction current_user_can
$current_user_can_return = true;

if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) {
        global $current_user_can_return;
        return $current_user_can_return; // Utilise la valeur de la variable globale
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        return new WP_REST_Response(['success' => true, 'data' => $data], $status_code ?? 200);
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {
        wp_send_json($data, $status_code);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return $str; // Pour les tests, on retourne la chaîne telle quelle
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return $str; // Pour les tests, on retourne la chaîne telle quelle
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) {
        // Vérifier si le nonce est valide
        $nonce = '';
        if (isset($_SERVER['HTTP_X_WP_NONCE'])) {
            $nonce = $_SERVER['HTTP_X_WP_NONCE'];
        } elseif (isset($_REQUEST['_wpnonce'])) {
            $nonce = $_REQUEST['_wpnonce'];
        }
        
        return $nonce === 'test-nonce';
    }
}

if (!function_exists('wp_send_json')) {
    function wp_send_json($response, $status_code = null) {
        if (!is_null($status_code)) {
            status_header($status_code);
        }
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        echo json_encode($response);
        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_die();
        } else {
            die();
        }
    }
}

if (!function_exists('status_header')) {
    function status_header($code) {
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
        $status_codes = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error'
        ];
        
        if (isset($status_codes[$code])) {
            header("$protocol $code " . $status_codes[$code]);
        }
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = []) {
        die($message);
    }
}

if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', false);
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        // Pour les tests, on retourne des valeurs par défaut
        $options = [
            'blog_charset' => 'UTF-8',
            'timezone_string' => 'Europe/Paris'
        ];
        return $options[$option] ?? $default;
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        return date($type === 'mysql' ? 'Y-m-d H:i:s' : 'U');
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
        // Simuler l'envoi d'email en mode test
        return true;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        // Pour les tests, retourner une valeur par défaut
        if ($key === 'provider_data') {
            return [
                (object)[
                    'meta_id' => 1,
                    'post_id' => $post_id,
                    'meta_key' => $key,
                    'meta_value' => serialize([
                        'name' => 'Test Provider',
                        'email' => 'test@example.com',
                        'phone' => '0123456789',
                        'address' => '123 Test Street',
                        'active' => true
                    ])
                ]
            ];
        }
        return $single ? '' : [];
    }
}

// Si la classe WP_REST_Request n'existe pas, on la définit
if (!class_exists('WP_REST_Request', false)) {
    class WP_REST_Request {
        public $params = [];
        public $json_params = [];
        public $headers = [];
        public $method = '';
        public $route = '';
        
        public function __construct($method = '', $route = '') {
            $this->method = $method;
            $this->route = $route;
        }
        
        public function get_param($param) {
            return $this->params[$param] ?? null;
        }
        
        public function set_param($param, $value) {
            $this->params[$param] = $value;
        }
        
        public function get_json_params() {
            return $this->json_params;
        }
        
        public function set_json_params($params) {
            $this->json_params = $params;
        }
        
        public function set_body($body) {
            $this->json_params = json_decode($body, true);
            $this->params = array_merge($this->params, $this->json_params);
        }
        
        public function set_header($key, $value) {
            $this->headers[$key] = $value;
            // Mettre à jour la variable $_SERVER pour les fonctions comme check_ajax_referer
            $server_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$server_key] = $value;
        }
        
        public function get_header($key) {
            return $this->headers[$key] ?? null;
        }
    }
}
