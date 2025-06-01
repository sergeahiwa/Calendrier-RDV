<?php
/**
 * Gestion du front-end du plugin
 *
 * @package CalendrierRdv\Public
 */

namespace CalendrierRdv\Public;

use CalendrierRdv\Common\AssetsManager;
use CalendrierRdv\Common\NonceManager;

/**
 * Gestion des fonctionnalités front-end
 */
class Public_Handler {
    /**
     * Instance du gestionnaire d'assets
     *
     * @var AssetsManager
     */
    private $assets_manager;

    /**
     * Instance du gestionnaire de nonces
     *
     * @var NonceManager
     */
    private $nonce_manager;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->assets_manager = AssetsManager::get_instance();
        $this->nonce_manager = NonceManager::get_instance();
        
        $this->init_hooks();
    }

    /**
     * Initialise les hooks WordPress
     */
    private function init_hooks() {
        // Enregistrement des shortcodes
        add_shortcode('calendrier_rdv', [$this, 'render_calendar_shortcode']);
        
        // Traitement des formulaires
        add_action('wp_ajax_cal_rdv_submit_booking', [$this, 'handle_booking_submission']);
        add_action('wp_ajax_nopriv_cal_rdv_submit_booking', [$this, 'handle_booking_submission']);
        
        // Chargement des assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
    }

    /**
     * Enregistre et charge les assets front-end
     */
    public function enqueue_public_assets() {
        $this->assets_manager->enqueue_public_assets();
    }

    /**
     * Affiche le calendrier via un shortcode
     *
     * @param array $atts Attributs du shortcode
     * @return string HTML du calendrier
     */
    public function render_calendar_shortcode($atts) {
        // Récupération des attributs avec valeurs par défaut
        $atts = shortcode_atts([
            'provider_id' => 0,
            'service_id' => 0,
            'show_title' => 'yes',
        ], $atts, 'calendrier_rdv');

        // Début de la mise en mémoire tampon
        ob_start();

        // Inclusion du template
        include CAL_RDV_PLUGIN_DIR . 'src/Public/views/calendar.php';

        // Retour du contenu du buffer
        return ob_get_clean();
    }

    /**
     * Traite la soumission d'un formulaire de réservation
     */
    public function handle_booking_submission() {
        // Vérification du nonce
        if (!isset($_POST['nonce']) || !$this->nonce_manager->verify_nonce($_POST['nonce'], 'cal_rdv_booking')) {
            wp_send_json_error([
                /* translators: Error message displayed when a security check fails on a public form. */
                'message' => __('La vérification de sécurité a échoué. Veuillez rafraîchir la page et réessayer.', 'calendrier-rdv')
            ]);
        }

        // Validation des données
        $data = [];
        $required_fields = ['date', 'time', 'provider_id', 'service_id', 'name', 'email', 'phone'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error([
                    /* translators: %s: Name of the required form field. Error message for a missing field. */
                    'message' => sprintf(__('Le champ %s est requis.', 'calendrier-rdv'), $field)
                ]);
            }
            $data[$field] = sanitize_text_field(wp_unslash($_POST[$field]));
        }

        // Validation de l'email
        if (!is_email($data['email'])) {
            wp_send_json_error([
                /* translators: Error message displayed when an invalid email address is provided. */
                'message' => __('Veuillez fournir une adresse email valide.', 'calendrier-rdv')
            ]);
        }

        try {
            // Ici, vous devrez implémenter la logique de création du rendez-vous
            // $booking_id = $this->create_booking($data);
            $booking_id = 1; // Temporaire pour les tests

            // Envoi des emails de confirmation
            // $this->send_confirmation_email($data, $booking_id);

            wp_send_json_success([
                /* translators: Success message displayed after a new appointment is successfully booked. */
                'message' => __('Votre rendez-vous a été enregistré avec succès !', 'calendrier-rdv'),
                'booking_id' => $booking_id
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                /* translators: Generic error message displayed when an unexpected error occurs while trying to book an appointment. */
                'message' => __('Une erreur est survenue lors de l\'enregistrement de votre rendez-vous.', 'calendrier-rdv')
            ]);
        }
    }
}
