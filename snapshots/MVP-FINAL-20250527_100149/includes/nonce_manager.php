<?php
/**
 * Gestionnaire des nonces pour le plugin Calendrier RDV
 */

class NonceManager {
    private static $instance = null;
    private $actions = [];
    
    private function __construct() {
        // Actions nécessitant un nonce
        $this->actions = [
            'create_appointment' => 'calendrier_rdv_create_appointment',
            'update_appointment' => 'calendrier_rdv_update_appointment',
            'delete_appointment' => 'calendrier_rdv_delete_appointment',
            'get_services' => 'calendrier_rdv_get_services',
            'get_providers' => 'calendrier_rdv_get_providers',
            'get_appointments' => 'calendrier_rdv_get_appointments'
        ];
    }
    
    public static function getInstance(): NonceManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function createNonce(string $action): string {
        if (isset($this->actions[$action])) {
            return wp_create_nonce($this->actions[$action]);
        }
        return '';
    }
    
    public function verifyNonce(string $nonce, string $action): bool {
        if (isset($this->actions[$action])) {
            return wp_verify_nonce($nonce, $this->actions[$action]);
        }
        return false;
    }
    
    public function checkNonce(string $nonce, string $action): void {
        if (!$this->verifyNonce($nonce, $action)) {
            wp_die(
                __('Nonce invalide. Veuillez rafraîchir la page et réessayer.', 'calendrier-rdv'),
                __('Erreur de sécurité', 'calendrier-rdv'),
                ['response' => 403]
            );
        }
    }
    
    public function getNonceField(string $action): string {
        $nonce = $this->createNonce($action);
        return sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            esc_attr($this->actions[$action]),
            esc_attr($nonce)
        );
    }
    
    public function getNonceScript(string $action): string {
        $nonce = $this->createNonce($action);
        return sprintf(
            'window.calendrierRdvNonce = {
                %s: "%s"
            };',
            esc_js($this->actions[$action]),
            esc_js($nonce)
        );
    }
}
