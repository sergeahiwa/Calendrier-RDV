<?php
/**
 * Configuration de l'API REST pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Définit les endpoints de l'API REST
 * 
 * @return array
 */
function cal_rdv_get_api_endpoints() {
    $version = 'v1';
    $namespace = 'calendrier-rdv/' . $version;
    
    return [
        // Endpoints pour les rendez-vous
        'appointments' => [
            'route' => '/appointments',
            'args' => [
                'methods' => 'GET',
                'callback' => 'cal_rdv_api_get_appointments',
                'permission_callback' => function() {
                    return cal_rdv_current_user_can('read_appointment');
                },
            ],
            'endpoints' => [
                'single' => [
                    'route' => '/(?P<id>\d+)',
                    'args' => [
                        'methods' => 'GET',
                        'callback' => 'cal_rdv_api_get_appointment',
                        'permission_callback' => function($request) {
                            return cal_rdv_current_user_can('read_appointment');
                        },
                        'args' => [
                            'id' => [
                                'validate_callback' => 'is_numeric',
                                'required' => true,
                            ],
                        ],
                    ],
                    'endpoints' => [
                        'confirm' => [
                            'route' => '/confirm',
                            'args' => [
                                'methods' => 'POST',
                                'callback' => 'cal_rdv_api_confirm_appointment',
                                'permission_callback' => function($request) {
                                    return cal_rdv_current_user_can('edit_appointment');
                                },
                            ],
                        ],
                        'cancel' => [
                            'route' => '/cancel',
                            'args' => [
                                'methods' => 'POST',
                                'callback' => 'cal_rdv_api_cancel_appointment',
                                'permission_callback' => function($request) {
                                    return cal_rdv_current_user_can('edit_appointment');
                                },
                            ],
                        ],
                    ],
                ],
                'create' => [
                    'route' => '/create',
                    'args' => [
                        'methods' => 'POST',
                        'callback' => 'cal_rdv_api_create_appointment',
                        'permission_callback' => function() {
                            return cal_rdv_current_user_can('publish_appointments');
                        },
                    ],
                ],
                'availability' => [
                    'route' => '/availability',
                    'args' => [
                        'methods' => 'GET',
                        'callback' => 'cal_rdv_api_get_availability',
                        'permission_callback' => '__return_true',
                    ],
                ],
            ],
        ],
        
        // Endpoints pour les prestataires
        'providers' => [
            'route' => '/providers',
            'args' => [
                'methods' => 'GET',
                'callback' => 'cal_rdv_api_get_providers',
                'permission_callback' => function() {
                    return cal_rdv_current_user_can('read_provider');
                },
            ],
            'endpoints' => [
                'single' => [
                    'route' => '/(?P<id>\d+)',
                    'args' => [
                        'methods' => 'GET',
                        'callback' => 'cal_rdv_api_get_provider',
                        'permission_callback' => function($request) {
                            return cal_rdv_current_user_can('read_provider');
                        },
                        'args' => [
                            'id' => [
                                'validate_callback' => 'is_numeric',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                'services' => [
                    'route' => '/(?P<id>\d+)/services',
                    'args' => [
                        'methods' => 'GET',
                        'callback' => 'cal_rdv_api_get_provider_services',
                        'permission_callback' => '__return_true',
                        'args' => [
                            'id' => [
                                'validate_callback' => 'is_numeric',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                'schedule' => [
                    'route' => '/(?P<id>\d+)/schedule',
                    'args' => [
                        'methods' => 'GET',
                        'callback' => 'cal_rdv_api_get_provider_schedule',
                        'permission_callback' => '__return_true',
                        'args' => [
                            'id' => [
                                'validate_callback' => 'is_numeric',
                                'required' => true,
                            ],
                            'start_date' => [
                                'validate_callback' => function($param) {
                                    return strtotime($param) !== false;
                                },
                                'required' => false,
                                'default' => date('Y-m-d'),
                            ],
                            'end_date' => [
                                'validate_callback' => function($param) {
                                    return strtotime($param) !== false;
                                },
                                'required' => false,
                                'default' => date('Y-m-d', strtotime('+1 month')),
                            ],
                        ],
                    ],
                ],
            ],
        ],
        
        // Endpoints pour les services
        'services' => [
            'route' => '/services',
            'args' => [
                'methods' => 'GET',
                'callback' => 'cal_rdv_api_get_services',
                'permission_callback' => '__return_true',
            ],
            'endpoints' => [
                'single' => [
                    'route' => '/(?P<id>\d+)',
                    'args' => [
                        'methods' => 'GET',
                        'callback' => 'cal_rdv_api_get_service',
                        'permission_callback' => '__return_true',
                        'args' => [
                            'id' => [
                                'validate_callback' => 'is_numeric',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                'providers' => [
                    'route' => '/(?P<id>\d+)/providers',
                    'args' => [
                        'methods' => 'GET',
                        'callback' => 'cal_rdv_api_get_service_providers',
                        'permission_callback' => '__return_true',
                        'args' => [
                            'id' => [
                                'validate_callback' => 'is_numeric',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        
        // Endpoints pour les paramètres
        'settings' => [
            'route' => '/settings',
            'args' => [
                'methods' => 'GET',
                'callback' => 'cal_rdv_api_get_settings',
                'permission_callback' => function() {
                    return cal_rdv_current_user_can('manage_calendar_settings');
                },
            ],
            'endpoints' => [
                'update' => [
                    'route' => '/update',
                    'args' => [
                        'methods' => 'POST',
                        'callback' => 'cal_rdv_api_update_settings',
                        'permission_callback' => function() {
                            return cal_rdv_current_user_can('manage_calendar_settings');
                        },
                    ],
                ],
            ],
        ],
    ];
}

/**
 * Initialise les endpoints de l'API REST
 * 
 * @return void
 */
function cal_rdv_init_rest_api() {
    // Vérifier si l'API REST est disponible
    if (!function_exists('register_rest_route')) {
        return;
    }
    
    // Enregistrer les endpoints
    $endpoints = cal_rdv_get_api_endpoints();
    cal_rdv_register_rest_endpoints('calendrier-rdv/v1', $endpoints);
}
add_action('rest_api_init', 'cal_rdv_init_rest_api');

/**
 * Enregistre récursivement les endpoints REST
 * 
 * @param string $namespace Namespace de base
 * @param array $endpoints Tableau des endpoints à enregistrer
 * @param string $parent_route Route parente (utilisée pour la récursion)
 * @return void
 */
function cal_rdv_register_rest_endpoints($namespace, $endpoints, $parent_route = '') {
    foreach ($endpoints as $key => $endpoint) {
        $route = $parent_route . $endpoint['route'];
        
        // Enregistrer l'endpoint actuel
        register_rest_route($namespace, $route, $endpoint['args']);
        
        // Enregistrer les sous-endpoints de manière récursive
        if (!empty($endpoint['endpoints'])) {
            cal_rdv_register_rest_endpoints($namespace, $endpoint['endpoints'], $route);
        }
    }
}

/**
 * Génère une réponse API standard
 * 
 * @param mixed $data Données à renvoyer
 * @param int $status_code Code HTTP de statut
 * @param string $message Message de statut
 * @return WP_REST_Response
 */
function cal_rdv_api_response($data = null, $status_code = 200, $message = '') {
    $response = [
        'success' => $status_code >= 200 && $status_code < 300,
        'data' => $data,
    ];
    
    if (!empty($message)) {
        $response['message'] = $message;
    }
    
    return new WP_REST_Response($response, $status_code);
}

/**
 * Génère une réponse d'erreur API standard
 * 
 * @param string $message Message d'erreur
 * @param int $status_code Code HTTP de statut
 * @param array $errors Détails des erreurs de validation (optionnel)
 * @return WP_Error
 */
function cal_rdv_api_error($message, $status_code = 400, $errors = []) {
    $error_data = [
        'status' => $status_code,
    ];
    
    if (!empty($errors)) {
        $error_data['errors'] = $errors;
    }
    
    return new WP_Error(
        'cal_rdv_api_error',
        $message,
        $error_data
    );
}

/**
 * Valide un jeton d'autorisation API
 * 
 * @param WP_REST_Request $request Requête API
 * @return bool|WP_Error True si valide, WP_Error sinon
 */
function cal_rdv_validate_api_auth($request) {
    // Vérifier l'en-tête d'autorisation
    $auth_header = $request->get_header('Authorization');
    
    if (empty($auth_header)) {
        return cal_rdv_api_error(
            __('En-tête d\'autorisation manquant', 'calendrier-rdv'),
            401
        );
    }
    
    // Vérifier le format du jeton (Bearer token)
    if (strpos($auth_header, 'Bearer ') !== 0) {
        return cal_rdv_api_error(
            __('Format de jeton d\'autorisation invalide', 'calendrier-rdv'),
            401
        );
    }
    
    $token = substr($auth_header, 7);
    
    // Ici, vous devriez valider le jeton avec votre système d'authentification
    // Par exemple, vérifier s'il s'agit d'un jeton JWT valide ou d'une clé API
    
    // Pour l'instant, on suppose que le jeton est valide
    return true;
}

/**
 * Enregistre les meta fields pour les endpoints personnalisés
 * 
 * @return void
 */
function cal_rdv_register_rest_fields() {
    // Meta fields pour les rendez-vous
    register_rest_field(
        'appointment',
        'meta',
        [
            'get_callback' => 'cal_rdv_get_appointment_meta',
            'update_callback' => 'cal_rdv_update_appointment_meta',
            'schema' => null,
        ]
    );
    
    // Meta fields pour les prestataires
    register_rest_field(
        'provider',
        'meta',
        [
            'get_callback' => 'cal_rdv_get_provider_meta',
            'update_callback' => 'cal_rdv_update_provider_meta',
            'schema' => null,
        ]
    );
    
    // Meta fields pour les services
    register_rest_field(
        'service',
        'meta',
        [
            'get_callback' => 'cal_rdv_get_service_meta',
            'update_callback' => 'cal_rdv_update_service_meta',
            'schema' => null,
        ]
    );
}
add_action('rest_api_init', 'cal_rdv_register_rest_fields');

/**
 * Callback pour récupérer les meta d'un rendez-vous
 * 
 * @param array $object Objet du rendez-vous
 * @return array
 */
function cal_rdv_get_appointment_meta($object) {
    $appointment_id = $object['id'];
    
    // Récupérer les meta du rendez-vous
    $meta = [
        'customer_name' => get_post_meta($appointment_id, '_appointment_customer_name', true),
        'customer_email' => get_post_meta($appointment_id, '_appointment_customer_email', true),
        'customer_phone' => get_post_meta($appointment_id, '_appointment_customer_phone', true),
        'provider_id' => get_post_meta($appointment_id, '_appointment_provider_id', true),
        'service_id' => get_post_meta($appointment_id, '_appointment_service_id', true),
        'start_date' => get_post_meta($appointment_id, '_appointment_start_date', true),
        'end_date' => get_post_meta($appointment_id, '_appointment_end_date', true),
        'status' => get_post_meta($appointment_id, '_appointment_status', true),
        'price' => get_post_meta($appointment_id, '_appointment_price', true),
        'notes' => get_post_meta($appointment_id, '_appointment_notes', true),
    ];
    
    return $meta;
}

/**
 * Callback pour mettre à jour les meta d'un rendez-vous
 * 
 * @param mixed $value Valeur à mettre à jour
 * @param WP_Post $object Objet du rendez-vous
 * @param string $field_name Nom du champ
 * @return bool|WP_Error
 */
function cal_rdv_update_appointment_meta($value, $object, $field_name) {
    if (!is_array($value)) {
        return false;
    }
    
    $appointment_id = $object->ID;
    $meta_keys = [
        'customer_name' => '_appointment_customer_name',
        'customer_email' => '_appointment_customer_email',
        'customer_phone' => '_appointment_customer_phone',
        'provider_id' => '_appointment_provider_id',
        'service_id' => '_appointment_service_id',
        'start_date' => '_appointment_start_date',
        'end_date' => '_appointment_end_date',
        'status' => '_appointment_status',
        'price' => '_appointment_price',
        'notes' => '_appointment_notes',
    ];
    
    foreach ($value as $key => $val) {
        if (array_key_exists($key, $meta_keys)) {
            update_post_meta($appointment_id, $meta_keys[$key], $val);
        }
    }
    
    return true;
}

// Note: Les fonctions pour les prestataires et les services suivent le même modèle
// mais sont omises pour des raisons de concision.
