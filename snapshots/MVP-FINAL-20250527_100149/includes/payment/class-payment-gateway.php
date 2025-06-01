<?php
/**
 * Classe abstraite pour les passerelles de paiement
 *
 * @package Calendrier_RDV
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe abstraite pour les passerelles de paiement
 */
abstract class CalRdv_Payment_Gateway {
    
    /**
     * ID unique de la passerelle
     *
     * @var string
     */
    protected $id;
    
    /**
     * Titre de la passerelle à afficher
     *
     * @var string
     */
    protected $title;
    
    /**
     * Description de la passerelle à afficher
     *
     * @var string
     */
    protected $description;
    
    /**
     * Indique si la passerelle est activée
     *
     * @var bool
     */
    protected $enabled;
    
    /**
     * Indique si la passerelle est en mode test
     *
     * @var bool
     */
    protected $test_mode;
    
    /**
     * Options de la passerelle
     *
     * @var array
     */
    protected $options;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->init_settings();
        $this->init_hooks();
    }
    
    /**
     * Initialise les paramètres de la passerelle
     */
    protected function init_settings() {
        $options = get_option('calendrier_rdv_payment_' . $this->id, []);
        
        $this->options = wp_parse_args($options, $this->get_default_settings());
        $this->enabled = isset($this->options['enabled']) ? (bool) $this->options['enabled'] : false;
        $this->test_mode = isset($this->options['test_mode']) ? (bool) $this->options['test_mode'] : true;
        $this->title = isset($this->options['title']) ? $this->options['title'] : $this->get_default_title();
        $this->description = isset($this->options['description']) ? $this->options['description'] : '';
    }
    
    /**
     * Initialise les hooks WordPress
     */
    protected function init_hooks() {
        if ($this->is_enabled()) {
            add_filter('calendrier_rdv_payment_gateways', [$this, 'add_to_gateways']);
            add_action('calendrier_rdv_payment_gateway_settings_' . $this->id, [$this, 'output_settings']);
            add_action('calendrier_rdv_process_payment_' . $this->id, [$this, 'process_payment'], 10, 2);
        }
    }
    
    /**
     * Ajoute cette passerelle à la liste des passerelles disponibles
     * 
     * @param array $gateways Liste des passerelles
     * @return array
     */
    public function add_to_gateways($gateways) {
        $gateways[$this->id] = $this;
        return $gateways;
    }
    
    /**
     * Indique si la passerelle est activée
     * 
     * @return bool
     */
    public function is_enabled() {
        return $this->enabled;
    }
    
    /**
     * Indique si la passerelle est en mode test
     * 
     * @return bool
     */
    public function is_test_mode() {
        return $this->test_mode;
    }
    
    /**
     * Obtenir l'ID de la passerelle
     * 
     * @return string
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Obtenir le titre de la passerelle
     * 
     * @return string
     */
    public function get_title() {
        return $this->title;
    }
    
    /**
     * Obtenir la description de la passerelle
     * 
     * @return string
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Obtenir une option de la passerelle
     * 
     * @param string $key Clé de l'option
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public function get_option($key, $default = '') {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
    
    /**
     * Obtenir toutes les options de la passerelle
     * 
     * @return array
     */
    public function get_options() {
        return $this->options;
    }
    
    /**
     * Obtenir le titre par défaut de la passerelle
     * 
     * @return string
     */
    abstract public function get_default_title();
    
    /**
     * Obtenir les paramètres par défaut de la passerelle
     * 
     * @return array
     */
    abstract public function get_default_settings();
    
    /**
     * Afficher les champs de configuration de la passerelle
     */
    abstract public function output_settings();
    
    /**
     * Traiter un paiement
     * 
     * @param int $booking_id ID de la réservation
     * @param array $data Données de paiement
     * @return array Résultat du traitement
     */
    abstract public function process_payment($booking_id, $data);
    
    /**
     * Afficher les champs de paiement dans le formulaire
     */
    abstract public function payment_fields();
    
    /**
     * Valider les champs de paiement
     * 
     * @param array $data Données du formulaire
     * @return bool|WP_Error True si valide, WP_Error sinon
     */
    abstract public function validate_fields($data);
    
    /**
     * Traiter un remboursement
     * 
     * @param int $booking_id ID de la réservation
     * @param float $amount Montant à rembourser
     * @param string $reason Raison du remboursement
     * @return bool|WP_Error True si réussi, WP_Error sinon
     */
    abstract public function process_refund($booking_id, $amount = null, $reason = '');
    
    /**
     * Obtenir les scripts à inclure pour le frontend
     * 
     * @return array Liste des scripts
     */
    abstract public function get_payment_scripts();
}
