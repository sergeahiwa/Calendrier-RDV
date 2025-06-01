<?php
/**
 * Gestionnaire de nonces pour la sécurité
 *
 * @package CalendrierRdv\Common
 */

namespace CalendrierRdv\Common;

/**
 * Gestion des nonces pour la sécurité des requêtes
 */
class NonceManager {
	/**
	 * Instance du gestionnaire
	 *
	 * @var NonceManager
	 */
	private static $instance = null;

	/**
	 * Préfixe pour les actions de nonce
	 *
	 * @var string
	 */
	private $nonce_prefix = 'cal_rdv_nonce_';

	/**
	 * Durée de vie des nonces (en secondes)
	 *
	 * @var int
	 */
	private $nonce_lifetime = 3600; // 1 heure

	/**
	 * Constructeur privé pour le singleton
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialise les hooks WordPress
	 */
	private function init_hooks() {
		// Filtre pour la durée de vie des nonces
		add_filter( 'nonce_life', array( $this, 'set_nonce_lifetime' ) );
	}

	/**
	 * Définit la durée de vie des nonces
	 *
	 * @param int $lifetime Durée de vie actuelle
	 * @return int Nouvelle durée de vie
	 */
	public function set_nonce_lifetime( $lifetime ) {
		return $this->nonce_lifetime;
	}

	/**
	 * Crée un nonce
	 *
	 * @param string $action Action associée au nonce
	 * @return string Le nonce généré
	 */
	public function create_nonce( $action = '' ) {
		return wp_create_nonce( $this->nonce_prefix . $action );
	}

	/**
	 * Vérifie un nonce
	 *
	 * @param string $nonce Le nonce à vérifier
	 * @param string $action L'action associée
	 * @return bool|int False si le nonce est invalide, 1 si valide, 2 si expiré
	 */
	public function verify_nonce( $nonce, $action = '' ) {
		return wp_verify_nonce( $nonce, $this->nonce_prefix . $action );
	}

	/**
	 * Vérifie un nonce dans une requête AJAX
	 *
	 * @param string $action L'action du nonce
	 * @param string $nonce_param Nom du paramètre contenant le nonce
	 * @param bool   $die Si true, arrête l'exécution en cas d'échec
	 * @return bool True si le nonce est valide, false sinon
	 */
	public function check_ajax_nonce( $action = '', $nonce_param = 'nonce', $die = true ) {
		$nonce = isset( $_REQUEST[ $nonce_param ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_param ] ) ) : '';

		if ( ! $this->verify_nonce( $nonce, $action ) ) {
			if ( $die ) {
				wp_send_json_error(
					array(
						'message' => __( 'La vérification de sécurité a échoué. Veuillez rafraîchir la page et réessayer.', 'calendrier-rdv' ),
					)
				);
			}
			return false;
		}

		return true;
	}

	/**
	 * Récupère l'instance du gestionnaire
	 *
	 * @return NonceManager
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
