<?php
/**
 * Gestionnaire des assets pour le plugin Calendrier RDV
 */

class AssetsManager {
    private static $instance = null;
    private $config;
    private $registeredStyles = [];
    private $registeredScripts = [];
    
    private function __construct() {
        $this->config = require_once CAL_RDV_PLUGIN_DIR . 'includes/assets_config.php';
    }
    
    public static function getInstance(): AssetsManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function registerAssets(): void {
        $this->registerStyles();
        $this->registerScripts();
    }
    
    private function registerStyles(): void {
        foreach ($this->config['styles'] as $style) {
            if ($this->shouldLoad($style['condition'])) {
                wp_register_style(
                    $style['handle'],
                    $style['src'],
                    $style['deps'],
                    $style['version'],
                    $style['media']
                );
                $this->registeredStyles[] = $style['handle'];
            }
        }
    }
    
    private function registerScripts(): void {
        // Enregistrer React si nécessaire
        if (!wp_script_is('react', 'enqueued')) {
            wp_enqueue_script('react');
            wp_enqueue_script('react-dom');
        }
        
        foreach ($this->config['scripts'] as $script) {
            if ($this->shouldLoad($script['condition'])) {
                wp_register_script(
                    $script['handle'],
                    $script['src'],
                    $script['deps'],
                    $script['version'],
                    $script['in_footer']
                );
                
                // Localisation des scripts principaux
                if ($script['handle'] === 'calendrier-rdv-main') {
                    wp_localize_script($script['handle'], 'calendrierRdvConfig', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('calendrier_rdv_nonce'),
                        'i18n' => [
                            'loading' => __('Chargement...', 'calendrier-rdv'),
                            'error' => __('Une erreur est survenue', 'calendrier-rdv'),
                            'success' => __('Réservation réussie', 'calendrier-rdv'),
                            'no_services' => __('Aucun service disponible', 'calendrier-rdv'),
                            'no_providers' => __('Aucun prestataire disponible', 'calendrier-rdv'),
                            'invalid_date' => __('Date invalide', 'calendrier-rdv'),
                            'invalid_time' => __('Horaire invalide', 'calendrier-rdv'),
                            'required_fields' => __('Veuillez remplir tous les champs requis', 'calendrier-rdv')
                        ]
                    ]);
                }
                
                $this->registeredScripts[] = $script['handle'];
            }
        }
    }
    
    private function shouldLoad($condition): bool {
        if (empty($condition)) return true;
        
        return eval('return ' . $condition . ';');
    }
    
    public function unregisterAssets(): void {
        foreach ($this->registeredStyles as $style) {
            wp_dequeue_style($style);
            wp_deregister_style($style);
        }
        
        foreach ($this->registeredScripts as $script) {
            wp_dequeue_script($script);
            wp_deregister_script($script);
        }
    }
    
    public function getCacheKey($type): string {
        return $this->config['cache']['keys'][$type] ?? '';
    }
    
    public function isCacheEnabled(): bool {
        return $this->config['cache']['enabled'] ?? false;
    }
    
    public function getCacheDuration(): int {
        return $this->config['cache']['duration'] ?? 3600;
    }
}
