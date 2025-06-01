<?php
/**
 * Gestionnaire des assets du plugin
 *
 * @package CalendrierRdv\Common
 */

namespace CalendrierRdv\Common;

/**
 * Gestion des assets (CSS/JS) du plugin
 */
class AssetsManager {
	/**
	 * Instance du gestionnaire
	 *
	 * @var AssetsManager
	 */
	private static $instance = null;

	/**
	 * Version des assets pour le cache busting
	 *
	 * @var string
	 */
	private $asset_version;

	/**
	 * Constructeur privé pour le singleton
	 */
	private function __construct() {
		$this->asset_version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : CAL_RDV_VERSION;
		$this->init_hooks();
	}

	/**
	 * Initialise les hooks WordPress
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
	}

	/**
	 * Enregistre les assets front-end
	 */
	public function register_public_assets() {
		// CSS
		wp_register_style(
			'cal-rdv-public',
			CAL_RDV_PLUGIN_URL . 'public/css/calendrier-rdv-public.css',
			array(),
			$this->asset_version
		);

		// JS
		wp_register_script(
			'cal-rdv-public',
			CAL_RDV_PLUGIN_URL . 'public/js/calendrier-rdv-public.js',
			array( 'jquery' ),
			$this->asset_version,
			true
		);

		// Localisation des données pour le JS
		wp_localize_script(
			'cal-rdv-public',
			'calRdvData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cal_rdv_public_nonce' ),
				'i18n'    => array(
					'error' => __( 'Une erreur est survenue', 'calendrier-rdv' ),
				),
			)
		);
	}

	/**
	 * Enregistre les assets d'administration
	 */
	public function register_admin_assets( $hook ) {
		// On ne charge les assets que sur les pages du plugin
		if ( strpos( $hook, 'calendrier-rdv' ) === false ) {
			return;
		}

		// CSS Admin
		wp_register_style(
			'cal-rdv-admin',
			CAL_RDV_PLUGIN_URL . 'admin/css/calendrier-rdv-admin.css',
			array(),
			$this->asset_version
		);

		// JS Admin
		wp_register_script(
			'cal-rdv-admin',
			CAL_RDV_PLUGIN_URL . 'admin/js/calendrier-rdv-admin.js',
			array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable' ),
			$this->asset_version,
			true
		);

		// Localisation des données pour le JS Admin
		wp_localize_script(
			'cal-rdv-admin',
			'calRdvAdminData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cal_rdv_admin_nonce' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Êtes-vous sûr de vouloir supprimer cet élément ?', 'calendrier-rdv' ),
					'error'         => __( 'Une erreur est survenue', 'calendrier-rdv' ),
				),
			)
		);

		// Chargement des assets
		wp_enqueue_style( 'cal-rdv-admin' );
		wp_enqueue_script( 'cal-rdv-admin' );
	}

	/**
	 * Récupère l'instance du gestionnaire
	 *
	 * @return AssetsManager
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
