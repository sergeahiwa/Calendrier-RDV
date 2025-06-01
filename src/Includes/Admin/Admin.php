<?php
/**
 * Gestion de l'administration du plugin Calendrier RDV
 *
 * @package     CalendrierRdv\Includes\Admin
 * @since       1.0.0
 * @author      Votre Nom <votre.email@example.com>
 */

namespace CalendrierRdv\Includes\Admin;

// Si ce fichier est appelé directement, on sort immédiatement.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale pour l'administration du plugin
 */
class Admin {
    /**
     * Instance de la classe AdminMenu
     *
     * @var AdminMenu
     */
    private $admin_menu;

    /**
     * Initialise la classe d'administration
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialise les hooks WordPress
     */
    private function init_hooks() {
        // Charger les fichiers d'administration uniquement si on est dans l'admin
        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('admin_init', [$this, 'init_admin']);
        }
    }

    /**
     * Initialise les composants d'administration
     */
    public function init_admin() {
        // Initialiser le menu d'administration
        $this->admin_menu = new AdminMenu();
        $this->admin_menu->init();

        // Initialiser d'autres composants d'administration ici
        // $this->init_other_components();
    }

    /**
     * Charge les assets (CSS/JS) pour l'administration
     *
     * @param string $hook_suffix Le hook de la page actuelle
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Ne charger que sur les pages du plugin
        if (strpos($hook_suffix, 'calendrier-rdv') === false) {
            return;
        }

        // Enregistrer et charger le CSS d'administration
        wp_register_style(
            'cal-rdv-admin-style',
            CAL_RDV_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CAL_RDV_VERSION,
            'all'
        );
        wp_enqueue_style('cal-rdv-admin-style');

        // Enregistrer et charger le JS d'administration
        wp_register_script(
            'cal-rdv-admin-script',
            CAL_RDV_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'],
            CAL_RDV_VERSION,
            true
        );
        
        // Localisation des chaînes pour le JS
        wp_localize_script('cal-rdv-admin-script', 'cal_rdv_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cal_rdv_admin_nonce'),
            'i18n' => [
                'confirm_delete' => __('Êtes-vous sûr de vouloir supprimer cet élément ?', 'calendrier-rdv'),
                'error' => __('Une erreur est survenue. Veuillez réessayer.', 'calendrier-rdv'),
            ]
        ]);
        
        wp_enqueue_script('cal-rdv-admin-script');

        // Charger les styles pour jQuery UI
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }

    /**
     * Affiche une notification d'administration
     *
     * @param string $message Le message à afficher
     * @param string $type    Le type de notification (error, warning, success, info)
     * @param bool   $is_dismissible Si la notification peut être masquée
     */
    public static function add_notice($message, $type = 'info', $is_dismissible = true) {
        $class = 'notice notice-' . esc_attr($type);
        if ($is_dismissible) {
            $class .= ' is-dismissible';
        }
        
        printf(
            '<div class="%1$s"><p>%2$s</p></div>',
            esc_attr($class),
            wp_kses_post($message)
        );
    }

    /**
     * Vérifie si l'utilisateur actuel a la capacité requise
     *
     * @param string $capability La capacité requise
     * @return bool
     */
    public static function current_user_can($capability = 'manage_options') {
        return current_user_can($capability);
    }
}
