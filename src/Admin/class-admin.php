<?php
/**
 * Gestion de l'administration du plugin
 *
 * @package CalendrierRdv\Admin
 */

namespace CalendrierRdv\Admin;

use CalendrierRdv\Common\AssetsManager;
use CalendrierRdv\Common\NonceManager;

/**
 * Gestion de l'interface d'administration
 */
class Admin {
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
		$this->nonce_manager  = NonceManager::get_instance();

		$this->init_hooks();
	}

	/**
	 * Initialise les hooks WordPress
	 */
	private function init_hooks() {
		// Menu d'administration
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Initialisation des assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Sauvegarde des paramètres
		add_action( 'admin_post_save_calendrier_rdv_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Ajoute le menu d'administration
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Calendrier RDV', 'calendrier-rdv' ),
			__( 'Calendrier RDV', 'calendrier-rdv' ),
			'manage_options',
			'calendrier-rdv',
			array( $this, 'render_admin_page' ),
			'dashicons-calendar-alt',
			30
		);

		// Sous-menus
		add_submenu_page(
			'calendrier-rdv',
			__( 'Paramètres', 'calendrier-rdv' ),
			__( 'Paramètres', 'calendrier-rdv' ),
			'manage_options',
			'calendrier-rdv-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Charge les assets de l'administration
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'calendrier-rdv' ) === false ) {
			return;
		}

		// Chargement des assets via le gestionnaire
		$this->assets_manager->enqueue_admin_assets();
	}

	/**
	 * Affiche la page principale d'administration
	 */
	public function render_admin_page() {
		// Vérification des droits
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Rendu de la vue
		include_once CAL_RDV_PLUGIN_DIR . 'src/Admin/views/dashboard.php';
	}

	/**
	 * Affiche la page des paramètres
	 */
	public function render_settings_page() {
		// Vérification des droits
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Récupération des options
		$options = get_option( 'calendrier_rdv_settings', array() );

		// Rendu de la vue
		include_once CAL_RDV_PLUGIN_DIR . 'src/Admin/views/settings.php';
	}

	/**
	 * Sauvegarde les paramètres
	 */
	public function save_settings() {
		// Vérification du nonce
		if ( ! isset( $_POST['cal_rdv_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['cal_rdv_settings_nonce'] ), 'save_calendrier_rdv_settings' ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'calendrier-rdv' ) );
		}

		// Vérification des droits
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les droits nécessaires.', 'calendrier-rdv' ) );
		}

		// Récupération et validation des données
		$settings = array();

		if ( isset( $_POST['cal_rdv_settings'] ) ) {
			$settings = array_map( 'sanitize_text_field', wp_unslash( $_POST['cal_rdv_settings'] ) );
		}

		// Sauvegarde des options
		update_option( 'calendrier_rdv_settings', $settings );

		// Redirection avec message de succès
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'calendrier-rdv-settings',
					'settings-updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
