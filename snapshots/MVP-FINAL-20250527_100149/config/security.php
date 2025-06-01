<?php
/**
 * Configuration de sécurité pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vérifie si l'utilisateur actuel a les capacités nécessaires
 * 
 * @param string $capability La capacité à vérifier
 * @param int|null $user_id L'ID de l'utilisateur (optionnel, utilise l'utilisateur actuel par défaut)
 * @return bool
 */
function cal_rdv_current_user_can($capability, $user_id = null) {
    // Les administrateurs ont toutes les capacités
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Vérifier la capacité spécifique
    return user_can($user_id, $capability);
}

/**
 * Vérifie un nonce de sécurité
 * 
 * @param string $nonce Le nonce à vérifier
 * @param string $action L'action associée au nonce
 * @param string $query_arg L'argument de requête contenant le nonce (par défaut : '_wpnonce')
 * @return bool|int
 */
function cal_rdv_verify_nonce($nonce, $action = -1, $query_arg = '_wpnonce') {
    // Si le nonce est vide, essayer de le récupérer de la requête
    if (empty($nonce)) {
        $nonce = isset($_REQUEST[$query_arg]) ? $_REQUEST[$query_arg] : '';
    }
    
    return wp_verify_nonce($nonce, $action);
}

/**
 * Génère un nonce de sécurité
 * 
 * @param string $action L'action associée au nonce
 * @return string Le nonce généré
 */
function cal_rdv_create_nonce($action = -1) {
    return wp_create_nonce($action);
}

/**
 * Nettoie et valide une valeur d'entrée
 * 
 * @param mixed $value La valeur à nettoyer
 * @param string $type Le type de nettoyage à appliquer (text, email, url, int, float, bool, html, raw)
 * @return mixed La valeur nettoyée
 */
function cal_rdv_clean($value, $type = 'text') {
    if (is_null($value)) {
        return null;
    }
    
    switch ($type) {
        case 'text':
            return sanitize_text_field($value);
            
        case 'email':
            return sanitize_email($value);
            
        case 'url':
            return esc_url_raw($value);
            
        case 'int':
            return intval($value);
            
        case 'float':
            return floatval($value);
            
        case 'bool':
            return (bool) $value;
            
        case 'html':
            return wp_kses_post($value);
            
        case 'raw':
            return $value;
            
        default:
            return sanitize_text_field($value);
    }
}

/**
 * Valide et nettoie un tableau de données
 * 
 * @param array $data Les données à valider
 * @param array $rules Les règles de validation
 * @return array Un tableau contenant les données nettoyées et les erreurs éventuelles
 */
function cal_rdv_validate($data, $rules) {
    $cleaned = [];
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = isset($data[$field]) ? $data[$field] : null;
        $rules = explode('|', $rule);
        $field_errors = [];
        
        foreach ($rules as $r) {
            $params = [];
            
            // Vérifier si la règle a des paramètres
            if (strpos($r, ':') !== false) {
                list($r, $param_str) = explode(':', $r, 2);
                $params = explode(',', $param_str);
            }
            
            // Appliquer la règle de validation
            $validation_result = cal_rdv_apply_validation_rule($r, $value, $params, $data);
            
            if ($validation_result !== true) {
                $field_errors[] = $validation_result;
            }
        }
        
        if (!empty($field_errors)) {
            $errors[$field] = $field_errors;
        } else {
            $cleaned[$field] = $value;
        }
    }
    
    return [
        'data' => $cleaned,
        'errors' => $errors,
        'is_valid' => empty($errors),
    ];
}

/**
 * Applique une règle de validation
 * 
 * @param string $rule Le nom de la règle
 * @param mixed $value La valeur à valider
 * @param array $params Les paramètres de la règle
 * @param array $data Toutes les données du formulaire (pour les règles dépendantes)
 * @return bool|string True si la validation réussit, un message d'erreur sinon
 */
function cal_rdv_apply_validation_rule($rule, $value, $params = [], $data = []) {
    switch ($rule) {
        case 'required':
            if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
                return __('Ce champ est obligatoire.', 'calendrier-rdv');
            }
            return true;
            
        case 'email':
            if (!empty($value) && !is_email($value)) {
                return __('Veuillez entrer une adresse email valide.', 'calendrier-rdv');
            }
            return true;
            
        case 'url':
            if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                return __('Veuillez entrer une URL valide.', 'calendrier-rdv');
            }
            return true;
            
        case 'numeric':
            if (!empty($value) && !is_numeric($value)) {
                return __('Ce champ doit être un nombre.', 'calendrier-rdv');
            }
            return true;
            
        case 'integer':
            if (!empty($value) && !is_numeric($value) || $value != (int)$value) {
                return __('Ce champ doit être un nombre entier.', 'calendrier-rdv');
            }
            return true;
            
        case 'min':
            if (!empty($value) && is_numeric($value) && $value < $params[0]) {
                return sprintf(__('La valeur doit être supérieure ou égale à %s.', 'calendrier-rdv'), $params[0]);
            }
            return true;
            
        case 'max':
            if (!empty($value) && is_numeric($value) && $value > $params[0]) {
                return sprintf(__('La valeur doit être inférieure ou égale à %s.', 'calendrier-rdv'), $params[0]);
            }
            return true;
            
        case 'min_length':
            if (!empty($value) && mb_strlen($value) < $params[0]) {
                return sprintf(__('Ce champ doit contenir au moins %s caractères.', 'calendrier-rdv'), $params[0]);
            }
            return true;
            
        case 'max_length':
            if (!empty($value) && mb_strlen($value) > $params[0]) {
                return sprintf(__('Ce champ ne doit pas dépasser %s caractères.', 'calendrier-rdv'), $params[0]);
            }
            return true;
            
        case 'matches':
            $other_field = $params[0];
            if (!empty($value) && (!isset($data[$other_field]) || $value !== $data[$other_field])) {
                return __('Les champs ne correspondent pas.', 'calendrier-rdv');
            }
            return true;
            
        case 'in':
            if (!empty($value) && !in_array($value, $params)) {
                return sprintf(__('La valeur doit être parmi : %s', 'calendrier-rdv'), implode(', ', $params));
            }
            return true;
            
        case 'date':
            if (!empty($value) && !strtotime($value)) {
                return __('Veuillez entrer une date valide.', 'calendrier-rdv');
            }
            return true;
            
        case 'time':
            if (!empty($value) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)) {
                return __('Veuillez entrer une heure valide (HH:MM).', 'calendrier-rdv');
            }
            return true;
            
        default:
            // Permettre aux extensions d'ajouter leurs propres règles de validation
            return apply_filters('cal_rdv_validation_rule_' . $rule, true, $value, $params, $data);
    }
}

/**
 * Protège contre les attaques XSS en échappant les sorties
 * 
 * @param string $string La chaîne à échapper
 * @return string La chaîne échappée
 */
function cal_rdv_esc($string) {
    return esc_html($string);
}

/**
 * Vérifie si une requête est une requête AJAX
 * 
 * @return bool
 */
function cal_rdv_is_ajax() {
    return defined('DOING_AJAX') && DOING_AJAX;
}

/**
 * Vérifie si une requête est une requête REST
 * 
 * @return bool
 */
function cal_rdv_is_rest() {
    return defined('REST_REQUEST') && REST_REQUEST;
}

/**
 * Vérifie si le plugin est en mode débogage
 * 
 * @return bool
 */
function cal_rdv_is_debug() {
    return defined('WP_DEBUG') && WP_DEBUG;
}

/**
 * Journalise un message de débogage
 * 
 * @param mixed $message Le message à journaliser
 * @param string $type Le type de message (debug, info, notice, warning, error, critical, alert, emergency)
 * @return void
 */
function cal_rdv_log($message, $type = 'debug') {
    if (!cal_rdv_is_debug()) {
        return;
    }
    
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    $log_entry = sprintf(
        '[%1$s] %2$s: %3$s',
        current_time('mysql'),
        strtoupper($type),
        $message . PHP_EOL
    );
    
    error_log($log_entry, 3, WP_CONTENT_DIR . '/calendrier-rdv-debug.log');
}

/**
 * Vérifie si un utilisateur peut accéder à une ressource spécifique
 * 
 * @param string $resource_type Le type de ressource (appointment, provider, service, etc.)
 * @param int $resource_id L'ID de la ressource
 * @param int|null $user_id L'ID de l'utilisateur (optionnel, utilise l'utilisateur actuel par défaut)
 * @return bool
 */
function cal_rdv_can_access_resource($resource_type, $resource_id, $user_id = null) {
    if (is_null($user_id)) {
        $user_id = get_current_user_id();
    }
    
    // Les administrateurs ont accès à tout
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Vérifier l'accès en fonction du type de ressource
    switch ($resource_type) {
        case 'appointment':
            // Un utilisateur ne peut voir que ses propres rendez-vous, sauf s'il est prestataire ou gestionnaire
            if (user_can($user_id, 'edit_others_appointments')) {
                return true;
            }
            
            $appointment = cal_rdv_get_appointment($resource_id);
            if (!$appointment) {
                return false;
            }
            
            // Vérifier si l'utilisateur est le client ou le prestataire
            return $appointment->customer_id == $user_id || 
                   ($appointment->provider_id && $appointment->provider_id == $user_id);
            
        case 'provider':
            // Un utilisateur ne peut voir que son propre profil de prestataire, sauf s'il est gestionnaire
            if (user_can($user_id, 'edit_others_providers')) {
                return true;
            }
            
            return $resource_id == $user_id;
            
        case 'service':
            // Par défaut, tout le monde peut voir les services
            return true;
            
        default:
            // Permettre aux extensions de gérer leurs propres règles d'accès
            return apply_filters('cal_rdv_can_access_resource', false, $resource_type, $resource_id, $user_id);
    }
}

/**
 * Vérifie si une chaîne est un hash sécurisé
 * 
 * @param string $string La chaîne à vérifier
 * @return bool
 */
function cal_rdv_is_hash($string) {
    return preg_match('/^[a-f0-9]{32,}$/i', $string);
}

/**
 * Génère un jeton CSRF
 * 
 * @param string $action L'action associée au jeton
 * @return string Le jeton généré
 */
function cal_rdv_csrf_token($action = '') {
    $token = wp_create_nonce('cal_rdv_csrf_' . $action);
    return $token;
}

/**
 * Vérifie un jeton CSRF
 * 
 * @param string $token Le jeton à vérifier
 * @param string $action L'action associée au jeton
 * @return bool
 */
function cal_rdv_verify_csrf_token($token, $action = '') {
    return (bool) wp_verify_nonce($token, 'cal_rdv_csrf_' . $action);
}
