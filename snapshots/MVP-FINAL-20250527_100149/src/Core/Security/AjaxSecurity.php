<?php
/**
 * Gestion de la sécurité AJAX
 *
 * @package CalendrierRdv\Core\Security
 */

namespace CalendrierRdv\Core\Security;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Classe de gestion de la sécurité pour les requêtes AJAX
 */
class AjaxSecurity {
    /**
     * Action AJAX par défaut
     * 
     * @var string
     */
    private $action;
    
    /**
     * Nonce pour la vérification
     * 
     * @var string
     */
    private $nonce;
    
    /**
     * Capacité requise pour l'action
     * 
     * @var string
     */
    private $capability;
    
    /**
     * Constructeur
     * 
     * @param string $action Action AJAX
     * @param string $capability Capacité requise (optionnel)
     */
    public function __construct(string $action, string $capability = '') {
        $this->action = $action;
        $this->capability = $capability;
    }
    
    /**
     * Vérifie la requête AJAX
     * 
     * @param WP_REST_Request $request Requête REST
     * @return bool|WP_Error True si la vérification est réussie, WP_Error sinon
     */
    public function verify_request(WP_REST_Request $request) {
        // Vérification du nonce
        $nonce = $request->get_header('X-WP-Nonce') ?: $request->get_param('_wpnonce');
        
        if (!$nonce || !wp_verify_nonce($nonce, $this->action)) {
            return new WP_Error(
                'invalid_nonce',
                /* translators: Error message displayed when a security nonce check fails for an AJAX request. */
                __('Nonce de sécurité invalide', 'calendrier-rdv'),
                ['status' => 403]
            );
        }
        
        // Vérification des permissions si une capacité est requise
        if ($this->capability && !current_user_can($this->capability)) {
            return new WP_Error(
                'insufficient_permissions',
                /* translators: Error message displayed when a user does not have sufficient permissions for an AJAX request. */
                __('Permissions insuffisantes', 'calendrier-rdv'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Enregistre le script AJAX avec les paramètres de sécurité
     * 
     * @param string $handle Handle du script
     * @param array $additional_params Paramètres supplémentaires
     * @return void
     */
    public static function localize_script(string $handle, array $additional_params = []) {
        $params = array_merge(
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce($handle . '_action'),
                'is_user_logged_in' => is_user_logged_in(),
            ],
            $additional_params
        );
        
        wp_localize_script($handle, 'ajax_' . $handle, $params);
    }
    
    /**
     * Gère les réponses d'erreur
     * 
     * @param WP_Error $error Erreur à traiter
     * @return WP_REST_Response Réponse d'erreur formatée
     */
    public static function handle_error(WP_Error $error): WP_REST_Response {
        $error_data = $error->get_error_data();
        $status = isset($error_data['status']) ? $error_data['status'] : 500;
        
        return new WP_REST_Response(
            [
                'success' => false,
                'message' => $error->get_error_message(),
                'code' => $error->get_error_code(),
                'data' => $error_data
            ],
            $status
        );
    }
    
    /**
     * Enregistre un gestionnaire AJAX
     * 
     * @param string $action Action AJAX
     * @param callable $callback Fonction de rappel
     * @param string $capability Capacité requise (optionnel)
     * @param bool $nopriv Si vrai, accessible sans être connecté
     * @return void
     */
    public static function register_ajax_handler(
        string $action, 
        callable $callback, 
        string $capability = '',
        bool $nopriv = false
    ): void {
        $handler = function() use ($action, $callback, $capability) {
            $security = new self($action, $capability);
            $request = new WP_REST_Request('POST', '');
            $request->set_body_params($_POST);
            
            $verification = $security->verify_request($request);
            
            if (is_wp_error($verification)) {
                wp_send_json_error([
                    'message' => $verification->get_error_message(),
                    'code' => $verification->get_error_code()
                ], $verification->get_error_data()['status'] ?? 500);
                return;
            }
            
            try {
                call_user_func($callback, $request);
            } catch (\Exception $e) {
                wp_send_json_error([
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ], 500);
            }
        };
        
        add_action('wp_ajax_' . $action, $handler);
        
        if ($nopriv) {
            add_action('wp_ajax_nopriv_' . $action, $handler);
        }
    }
}
