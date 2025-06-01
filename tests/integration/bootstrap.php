<?php
/**
 * Bootstrap pour les tests d'intégration
 */

// Charger l'autoloader de Composer
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Définir les constantes nécessaires pour les tests
define('CALENDRIER_RDV_PLUGIN_DIR', dirname(__DIR__, 2) . '/');

// Configuration de l'environnement de test
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Simuler les fonctions WordPress de base
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $errors = [];
        private $error_data = [];
        
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->add($code, $message, $data);
            }
        }
        
        public function add($code, $message, $data = '') {
            $this->errors[$code][] = $message;
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }
        
        public function get_error_messages($code = '') {
            if (empty($code)) {
                $all_messages = [];
                foreach ($this->errors as $messages) {
                    $all_messages = array_merge($all_messages, $messages);
                }
                return $all_messages;
            }
            return $this->errors[$code] ?? [];
        }
        
        public function get_error_data($code = '') {
            if (empty($code)) {
                return $this->error_data;
            }
            return $this->error_data[$code] ?? null;
        }
        
        public function has_errors() {
            return !empty($this->errors);
        }
    }
}

// Simuler la base de données
global $wpdb;

class MockWPDB {
    public $prefix = 'wp_';
    public $last_query = '';
    public $insert_id = 1;
    public $insert_data = [];
    public $tables = [];
    
    public function __construct() {
        // Initialiser les tables
        $this->tables = [
            'wp_rdv_appointments' => [],
            'wp_rdv_services' => [],
            'wp_rdv_providers' => []
        ];
    }
    
    public function query($query) {
        $this->last_query = $query;
        
        // Simuler la création de table
        if (strpos($query, 'CREATE TABLE') !== false) {
            return true;
        }
        
        // Simuler l'insertion
        if (strpos($query, 'INSERT INTO') !== false) {
            return true;
        }
        
        // Simuler la mise à jour
        if (strpos($query, 'UPDATE') !== false) {
            return 1; // Nombre de lignes affectées
        }
        
        // Simuler la suppression
        if (strpos($query, 'DELETE') !== false) {
            return 1; // Nombre de lignes supprimées
        }
        
        return true;
    }
    
    public function get_results($query, $output = OBJECT) {
        $this->last_query = $query;
        
        // Simuler une requête SELECT
        if (strpos($query, 'SELECT') !== false) {
            // Retourner une structure vide pour les tests
            return [];
        }
        
        return [];
    }
    
    public function get_row($query, $output = OBJECT, $y = 0) {
        $this->last_query = $query;
        
        // Simuler une requête SELECT ... LIMIT 1
        if (strpos($query, 'SELECT') !== false) {
            // Retourner une structure vide pour les tests
            return (object)[
                'id' => 1,
                'name' => 'Test',
                'status' => 'active'
            ];
        }
        
        return null;
    }
    
    public function insert($table, $data, $format = null) {
        $this->insert_data = $data;
        return true;
    }
    
    public function update($table, $data, $where, $format = null, $where_format = null) {
        return true;
    }
    
    public function delete($table, $where, $where_format = null) {
        return true;
    }
    
    public function insert_id() {
        return $this->insert_id++;
    }
    
    public function prepare($query, ...$args) {
        // Implémentation minimale pour les tests
        return $query;
    }
    
    public function get_col($query = null) {
        return [];
    }
    
    public function get_var($query = null, $x = 0, $y = 0) {
        return null;
    }
}

// Initialiser la base de données simulée
$wpdb = new MockWPDB();

// Fonctions utilitaires
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        return new class($data, $status_code) {
            public $data;
            public $status;
            
            public function __construct($data = null, $status = 200) {
                $this->data = ['success' => true, 'data' => $data];
                $this->status = $status;
            }
            
            public function get_data() {
                return $this->data;
            }
            
            public function get_status() {
                return $this->status;
            }
        };
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {
        return new class($data, $status_code) {
            public $data;
            public $status;
            
            public function __construct($data = null, $status = 400) {
                $this->data = ['success' => false, 'data' => $data];
                $this->status = $status;
            }
            
            public function get_data() {
                return $this->data;
            }
            
            public function get_status() {
                return $this->status;
            }
        };
    }
}

// Simuler WP_REST_Request
class WP_REST_Request {
        public $params = [];
        public $query_params = [];
        public $route = '';
        public $method = 'GET';
        public $body = '';
        public $headers = [];
        
        public function __construct($method = 'GET', $route = '') {
            $this->method = $method;
            $this->route = $route;
        }
        
        public function get_param($param) {
            return $this->params[$param] ?? null;
        }
        
        public function set_param($param, $value) {
            $this->params[$param] = $value;
            return $this;
        }
        
        public function get_json_params() {
            if (!empty($this->body)) {
                echo "\n[DEBUG] Body brut reçu :\n" . $this->body . "\n";
                $json = json_decode($this->body, true);
                echo "[DEBUG] Résultat json_decode :\n" . print_r($json, true) . "\n";
                if (is_array($json)) {
                    return $json;
                }
            }
            return $this->params;
        }
        
        public function set_body($body) {
            $this->body = $body;
            return $this;
        }
        public function get_body() {
            return $this->body;
        }
        public function set_header($key, $value) {
            $this->headers[strtolower($key)] = $value;
            return $this;
        }
        public function get_headers() {
            return $this->headers;
        }
        public function set_query_params($params) {
            $this->query_params = array_merge($this->query_params, $params);
            return $this;
        }
        
        public function get_query_params() {
            return $this->query_params;
        }
        
        public function set_route($route) {
            $this->route = $route;
            return $this;
        }
        
        public function get_route() {
            return $this->route;
        }
        
        public function get_method() {
            return $this->method;
        }
        
        public function get_params() {
            return $this->params;
        }
    }

// Simuler WP_REST_Response
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;
        
        public function __construct($data = null, $status = 200) {
            $this->data = $data;
            $this->status = $status;
        }
        
        public function get_data() {
            return $this->data;
        }
        
        public function get_status() {
            return $this->status;
        }
    }
}

// Fonctions utilitaires pour les tests
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = '') {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r =& $args;
        } else {
            wp_parse_str($args, $r);
        }
        
        if (is_array($defaults)) {
            return array_merge($defaults, $r);
        }
        
        return $r;
    }
}

if (!function_exists('wp_parse_str')) {
    function wp_parse_str($string, &$array) {
        parse_str($string, $array);
        
        // Décoder les caractères spéciaux
        $array = array_map('urldecode', $array);
        
        return $array;
    }
}

// Fonctions utilitaires pour les tests
if (!function_exists('create_test_service')) {
    function create_test_service($args = []) {
        global $wpdb;
        
        $defaults = [
            'post_type' => 'service',
            'post_title' => 'Test Service',
            'post_status' => 'publish',
            'meta_input' => [
                '_duree' => 60,
                '_prix' => 50.00,
                '_prestataires' => []
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $post_id = $wpdb->insert(
            $wpdb->prefix . 'posts',
            [
                'post_type' => 'service',
                'post_title' => $args['post_title'],
                'post_status' => 'publish'
            ]
        );
        
        if ($post_id) {
            // Ajouter les métadonnées
            foreach (($args['meta_input'] ?? []) as $key => $value) {
                $wpdb->insert(
                    $wpdb->prefix . 'postmeta',
                    [
                        'post_id' => $post_id,
                        'meta_key' => $key,
                        'meta_value' => is_array($value) ? serialize($value) : $value
                    ]
                );
            }
            
            return $post_id;
        }
        
        return false;
    }
}

if (!function_exists('create_test_prestataire')) {
    function create_test_prestataire($args = []) {
        global $wpdb;
        
        $defaults = [
            'post_type' => 'prestataire',
            'post_title' => 'Test Provider',
            'post_status' => 'publish',
            'meta_input' => [
                '_disponibilites' => 'all',
                '_duree_rdv' => 30,
                '_pauses' => []
            ]
        ];
        
        $post_id = $wpdb->insert(
            $wpdb->prefix . 'posts',
            [
                'post_type' => 'prestataire',
                'post_title' => $args['post_title'] ?? $defaults['post_title'],
                'post_status' => 'publish'
            ]
        );
        
        if ($post_id) {
            // Ajouter les métadonnées
            foreach (($args['meta_input'] ?? []) as $key => $value) {
                $wpdb->insert(
                    $wpdb->prefix . 'postmeta',
                    [
                        'post_id' => $post_id,
                        'meta_key' => $key,
                        'meta_value' => is_array($value) ? serialize($value) : $value
                    ]
                );
            }
            
            return $post_id;
        }
        
        return false;
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post($post_id, $force_delete = false) {
        global $wpdb;
        
        if ($force_delete) {
            // Supprimer les métadonnées
            $wpdb->delete(
                $wpdb->prefix . 'postmeta',
                ['post_id' => $post_id],
                ['%d']
            );
            
            // Supprimer le post
            return $wpdb->delete(
                $wpdb->prefix . 'posts',
                ['ID' => $post_id],
                ['%d']
            );
        }
        
        // Mise à jour du statut à la place de la suppression
        return $wpdb->update(
            $wpdb->prefix . 'posts',
            ['post_status' => 'trash'],
            ['ID' => $post_id],
            ['%s'],
            ['%d']
        );
    }
}

if (!function_exists('wp_insert_user')) {
    function wp_insert_user($userdata) {
        global $wpdb;
        
        // Générer un ID unique si non fourni
        $user_id = $userdata['ID'] ?? rand(1000, 9999);
        
        // Créer l'utilisateur dans la base de données simulée
        $wpdb->insert(
            $wpdb->prefix . 'users',
            [
                'ID' => $user_id,
                'user_login' => $userdata['user_login'],
                'user_email' => $userdata['user_email'],
                'user_pass' => $userdata['user_pass'] ?? '',
                'user_nicename' => sanitize_title($userdata['user_login']),
                'display_name' => $userdata['display_name'] ?? $userdata['user_login'],
                'user_registered' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        // Ajouter le rôle si spécifié
        if (!empty($userdata['role'])) {
            $wpdb->insert(
                $wpdb->prefix . 'usermeta',
                [
                    'user_id' => $user_id,
                    'meta_key' => $wpdb->prefix . 'capabilities',
                    'meta_value' => serialize([$userdata['role'] => true])
                ],
                ['%d', '%s', '%s']
            );
        }
        
        return $user_id;
    }
}

if (!function_exists('wp_set_current_user')) {
    function wp_set_current_user($id, $name = '') {
        global $current_user;
        
        $current_user = (object) [
            'ID' => $id,
            'display_name' => $name ?: 'Test User',
            'user_email' => 'test@example.com',
            'roles' => ['subscriber']
        ];
        
        return $current_user;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        global $current_user;
        return $current_user->ID ?? 0;
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '-', $title));
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        if ($type === 'mysql') {
            return date('Y-m-d H:i:s');
        }
        return time();
    }
}

if (!function_exists('rest_do_request_simulation')) {
    function rest_do_request_simulation($request) {
    // Log global pour chaque requête
    echo "\n[DEBUG GLOBAL] Méthode reçue : " . $request->get_method() . " | Route reçue : " . $request->get_route() . "\n";
        // Implémentation simplifiée pour les tests
        $route = $request->get_route();
        $method = $request->get_method();
        
        error_log('=== ROUTING DEBUG ===');
        error_log('Méthode: ' . $method);
        error_log('Route: ' . $route);
        error_log('Params: ' . print_r($request->get_params(), true));
        
        // Liste des prestataires
        if ($route === '/calendrier-rdv/v1/providers') {
            return new WP_REST_Response([
                [
                    'id' => 1,
                    'name' => 'Test Provider',
                    'disponibilites' => 'monday,tuesday,wednesday,thursday,friday',
                    'duree_rdv' => 30,
                    'pauses' => [
                        'monday' => ['12:00-13:00'],
                        'tuesday' => ['12:00-13:00']
                    ]
                ]
            ], 200);
        }
        
        // Liste des services ou récupération d'un service spécifique
        if (strpos($route, '/calendrier-rdv/v1/services') === 0) {
            $provider_id = $request->get_param('provider');
            
            // Vérifier si l'URL contient un ID de service (format: /services/123)
            $matches = [];
            if (preg_match('#/calendrier-rdv/v1/services/(\d+)$#', $route, $matches)) {
                $service_id = (int)$matches[1];
                
                // Simuler un service non trouvé pour les tests d'erreur
                if ($service_id === 999999) {
                    return new WP_REST_Response(['error' => 'Service non trouvé'], 404);
                }
                
                $services = [
                    [
                        'id' => $service_id,
                        'title' => 'Test Service ' . $service_id,
                        'duree' => 60,
                        'prix' => 50.00,
                        'providers' => $provider_id ? [(int)$provider_id] : [1, 2, 3]
                    ]
                ];
                
                // Retourner le service demandé
                return new WP_REST_Response($services[0], 200);
            }
            
            // Si pas d'ID dans l'URL, retourner la liste des services
            $services = [
                [
                    'id' => 1,
                    'title' => 'Test Service 1',
                    'duree' => 60,
                    'prix' => 50.00,
                    'providers' => $provider_id ? [$provider_id] : [1, 2, 3]
                ]
            ];
            
            return new WP_REST_Response($services, 200);
        }
        
        // Création d'un service
        if ($route === '/calendrier-rdv/v1/services' && $request->get_method() === 'POST') {
            // Debug : afficher le body brut et les params JSON décodés
            echo "\n[DEBUG] get_body() :\n" . $request->get_body() . "\n";
            $params = $request->get_json_params();
            echo "[DEBUG] get_json_params() :\n" . print_r($params, true) . "\n";
            $missing_fields = [];
            foreach (['title', 'duree', 'prix', 'providers'] as $field) {
                if (!isset($params[$field]) || $params[$field] === '' || $params[$field] === [] || $params[$field] === null) {
                    $missing_fields[] = $field;
                }
            }
            if (!empty($missing_fields)) {
                return new WP_REST_Response([
                    'error' => 'Données manquantes: ' . implode(', ', $missing_fields),
                    'code' => 'missing_required_params',
                    'missing_fields' => $missing_fields
                ], 400);
            }
            // Valider que le titre n'est pas vide après trim
            if (trim($params['title']) === '') {
                return new WP_REST_Response([
                    'error' => 'Le titre ne peut pas être vide',
                    'code' => 'missing_required_params',
                    'missing_fields' => ['title']
                ], 400);
            }
            
            // Valider que le titre n'est pas vide
            if (trim($params['title']) === '') {
                return new WP_REST_Response(['error' => 'Le titre ne peut pas être vide', 'code' => 'missing_required_params'], 400);
            }
            
            // Valider que la durée est un nombre positif
            if (!is_numeric($params['duree']) || $params['duree'] <= 0) {
                return new WP_REST_Response(['error' => 'La durée doit être un nombre positif', 'code' => 'invalid_duration'], 400);
            }
            
            // Valider que le prix est un nombre positif
            if (!is_numeric($params['prix']) || $params['prix'] < 0) {
                return new WP_REST_Response(['error' => 'Le prix doit être un nombre positif', 'code' => 'invalid_price'], 400);
            }
            
            // Valider le format des fournisseurs si spécifiés
            if (isset($params['providers'])) {
                if (!is_array($params['providers'])) {
                    return new WP_REST_Response(['error' => 'Format de fournisseurs invalide', 'code' => 'invalid_providers_format'], 400);
                }
                
                // Vérifier si les fournisseurs existent (simulé)
                foreach ($params['providers'] as $provider_id) {
                    if ($provider_id === 999999) {
                        return new WP_REST_Response(['error' => 'Un ou plusieurs fournisseurs sont invalides', 'code' => 'invalid_provider'], 400);
                    }
                }
            } else {
                // Par défaut, utiliser le fournisseur de test
                $params['providers'] = [1];
            }
            
            // Simuler la création en base de données
            $service = [
                'id' => rand(100, 999),
                'title' => $params['title'],
                'duree' => $params['duree'],
                'prix' => $params['prix'],
                'providers' => $params['providers'] ?? []
            ];
            
            return new WP_REST_Response($service, 201);
        }
        
        // Suppression d'un service
        if (preg_match('#/calendrier-rdv/v1/services/(\d+)$#', $route, $matches) && $request->get_method() === 'DELETE') {
            $service_id = (int)$matches[1];
            
            // Vérifier si le service existe déjà (simulé)
            static $deleted_services = [];
            
            // Vérifier si c'est un service de test valide (1-100) ou déjà supprimé
            if (in_array($service_id, $deleted_services) || $service_id < 1 || $service_id > 100) {
                return new WP_REST_Response(['error' => 'Service non trouvé', 'code' => 'service_not_found'], 404);
            }
            
            // Vérifier si l'utilisateur a les droits pour supprimer ce service
            if (!current_user_can('delete_posts')) {
                return new WP_REST_Response(['error' => 'Action non autorisée', 'code' => 'unauthorized'], 403);
            }
            
            // Marquer comme supprimé
            $deleted_services[] = $service_id;
            
            // Simuler la suppression en base de données
            return new WP_REST_Response(['success' => true], 200);
        }
        
        // Gestion des rendez-vous
        if (strpos($route, '/calendrier-rdv/v1/appointments') === 0) {
            // Création d'un rendez-vous
            if ($request->get_method() === 'POST') {
                $params = $request->get_json_params();
                
                // Validation des données requises
                if (empty($params['service_id']) || empty($params['provider_id']) || empty($params['start_time'])) {
                    return new WP_REST_Response(['error' => 'Données manquantes'], 400);
                }
                
                // Vérifier la disponibilité du créneau
                $start_time = strtotime($params['start_time']);
                $day = strtolower(date('l', $start_time));
                
                // Simuler la création en base de données
                $appointment = [
                    'id' => rand(1000, 9999),
                    'service_id' => $params['service_id'],
                    'provider_id' => $params['provider_id'],
                    'customer_id' => get_current_user_id(),
                    'start_time' => date('Y-m-d H:i:s', $start_time),
                    'end_time' => date('Y-m-d H:i:s', strtotime('+60 minutes', $start_time)),
                    'status' => 'confirmed',
                    'customer_note' => $params['customer_note'] ?? ''
                ];
                
                return new WP_REST_Response($appointment, 201);
            }
            
            // Annulation d'un rendez-vous
            if (preg_match('#/calendrier-rdv/v1/appointments/(\d+)/cancel$#', $route, $matches) && $request->get_method() === 'PUT') {
                $appointment_id = $matches[1];
                
                // Simuler la mise à jour en base de données
                $appointment = [
                    'id' => $appointment_id,
                    'status' => 'cancelled'
                ];
                
                return new WP_REST_Response($appointment, 200);
            }
        }
        
        // Récupération des créneaux disponibles
        if ($route === '/calendrier-rdv/v1/availability/slots' && $request->get_method() === 'GET') {
            $params = $request->get_query_params();
            
            // Générer des créneaux de test
            $slots = [];
            $start_date = strtotime($params['start_date']);
            $end_date = strtotime($params['end_date']);
            
            // Générer des créneaux de 30 minutes entre 9h et 17h
            for ($day = $start_date; $day <= $end_date; $day = strtotime('+1 day', $day)) {
                $day_name = strtolower(date('l', $day));
                
                // Ne générer des créneaux que du lundi au vendredi
                if (in_array($day_name, ['saturday', 'sunday'])) {
                    continue;
                }
                
                for ($hour = 9; $hour < 17; $hour++) {
                    $time = strtotime("$hour:00", $day);
                    $slots[] = [
                        'start' => date('Y-m-d\TH:i:s', $time),
                        'end' => date('Y-m-d\TH:i:s', strtotime('+60 minutes', $time)),
                        'available' => true
                    ];
                }
            }
            
            return new WP_REST_Response($slots, 200);
        }
        
        return new WP_REST_Response(['error' => 'Route non trouvée'], 404);
    }
}
