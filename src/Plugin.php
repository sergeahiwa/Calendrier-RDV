<?php
/**
 * Fichier principal du plugin Calendrier RDV
 *
 * @package    CalendrierRdv
 * @since      1.0.0
 * @author     Votre Nom <votre.email@example.com>
 */

namespace CalendrierRdv;

// Si ce fichier est appelé directement, on sort immédiatement.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Définition des constantes du plugin.
define( 'CAL_RDV_VERSION', '1.2.0' );
define( 'CAL_RDV_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
define( 'CAL_RDV_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
define( 'CAL_RDV_PLUGIN_BASENAME', plugin_basename( dirname( __DIR__ ) . '/calendrier-rdv.php' ) );

/**
 * Classe principale du plugin
 */
class Plugin {
	/**
	 * Instance du plugin
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Constructeur privé pour empêcher l'instanciation directe
	 */
	private function __construct() {
		// Initialisation des hooks
		$this->init_hooks();
	}

	/**
	 * Initialise les hooks WordPress.
	 */
	private function init_hooks() {
		// Hooks d'activation/désactivation.
		register_activation_hook( CAL_RDV_PLUGIN_DIR . 'calendrier-rdv.php', [ $this, 'activate' ] );
		register_deactivation_hook( CAL_RDV_PLUGIN_DIR . 'calendrier-rdv.php', [ $this, 'deactivate' ] );

		// Initialisation du plugin.
		add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
	}

	/**
	 * Initialise le plugin.
	 */
	public function init_plugin() {
		// Chargement des fichiers nécessaires.
		$this->includes();

		// Initialisation des composants.
		$this->init_components();
	}

	/**
	 * Inclut les fichiers nécessaires.
	 */
	private function includes() {
		// Fichiers communs.
		require_once CAL_RDV_PLUGIN_DIR . 'src/Common/class-assets-manager.php';
		require_once CAL_RDV_PLUGIN_DIR . 'src/Common/class-nonce-manager.php';

		// Initialisation de l'administration.
		if ( is_admin() ) {
			require_once CAL_RDV_PLUGIN_DIR . 'src/Admin/class-admin.php';
		}

		// Initialisation du front-end.
		require_once CAL_RDV_PLUGIN_DIR . 'src/Public/class-public.php';

		// Initialisation de l'API REST.
		require_once CAL_RDV_PLUGIN_DIR . 'src/Api/class-rest-controller.php';
	}

	/**
	 * Initialise les composants du plugin.
	 */
	private function init_components() {
		// Initialisation des gestionnaires.
		\CalendrierRdv\Common\AssetsManager::get_instance();
		\CalendrierRdv\Common\NonceManager::get_instance();

		// Initialisation de l'administration.
		if ( is_admin() ) {
			new \CalendrierRdv\Admin\Admin();
		}

		// Initialisation du front-end.
		new \CalendrierRdv\Public\Public_Handler();

		// Initialisation de l'API REST.
		new \CalendrierRdv\Api\RestController();
	}

	/**
	 * Activation du plugin.
	 */
	public function activate() {
		// Création des tables de la base de données.
		require_once CAL_RDV_PLUGIN_DIR . 'src/Database/class-installer.php';
		$installer = new \CalendrierRdv\Database\Installer();
		$installer->install();

		// Planification des tâches récurrentes.
		if ( ! wp_next_scheduled( 'cal_rdv_daily_tasks' ) ) {
			wp_schedule_event( time(), 'daily', 'cal_rdv_daily_tasks' );
		}
	}

	/**
	 * Désactivation du plugin.
	 */
	public function deactivate() {
		// Nettoyage des tâches planifiées.
		wp_clear_scheduled_hook( 'cal_rdv_daily_tasks' );
	}

	/**
	 * Récupère l'instance du plugin.
	 *
	 * @return Plugin Instance du plugin.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

/**
 * Initialise le plugin.
 *
 * @since 1.0.0
 * @return void
 */
function cal_rdv_bootstrap() {
    \CalendrierRdv\Plugin::get_instance();
}

// Démarrer le plugin.
add_action( 'plugins_loaded', 'cal_rdv_bootstrap' );
