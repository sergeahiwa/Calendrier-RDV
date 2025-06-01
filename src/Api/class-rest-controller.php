<?php
/**
 * Contrôleur REST pour l'API du plugin
 *
 * @package CalendrierRdv\Api
 */

namespace CalendrierRdv\Api;

use CalendrierRdv\Appointments\Appointments; // Ligne corrigée
use CalendrierRdv\Common\NonceManager;

/**
 * Gestion des points d'entrée de l'API REST
 */
class Rest_Controller {
	/**
	 * Namespace de l'API
	 *
	 * @var string
	 */
	private $namespace = 'calendrier-rdv/v1';

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
		$this->nonce_manager = NonceManager::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialise les hooks WordPress
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Enregistre les routes de l'API
	 */
	public function register_routes() {
		// Récupération des créneaux disponibles
		register_rest_route(
			$this->namespace,
			'/time-slots',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_time_slots' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'date'        => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return (bool) strtotime( $param );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
					'service_id'  => array(
						'required'          => true,
						'validate_callback' => 'is_numeric',
						'sanitize_callback' => 'absint',
					),
					'provider_id' => array(
						'required'          => false,
						'validate_callback' => 'is_numeric',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Création d'un rendez-vous
		register_rest_route(
			$this->namespace,
			'/bookings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_booking' ),
				'permission_callback' => array( $this, 'check_booking_permissions' ),
			)
		);

		// Annulation d'un rendez-vous
		register_rest_route(
			$this->namespace,
			'/bookings/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'cancel_booking' ),
				'permission_callback' => array( $this, 'check_booking_permissions' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => 'is_numeric',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Récupération des rendez-vous pour le calendrier d'administration
		register_rest_route(
			$this->namespace,
			'/admin/appointments',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_admin_appointments' ),
				'permission_callback' => array( $this, 'check_admin_permissions' ),
				'args'                => array(
					'start' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return (bool) strtotime( $param );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
					'end'   => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return (bool) strtotime( $param );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Vérifie les permissions pour les opérations sur les rendez-vous
	 *
	 * @param \WP_REST_Request $request Requête REST
	 * @return bool|\WP_Error
	 */
	public function check_booking_permissions( \WP_REST_Request $request ) {
		// Vérification du nonce pour les utilisateurs non connectés
		if ( ! is_user_logged_in() ) {
			$nonce = $request->get_header( 'X-WP-Nonce' );
			if ( ! $this->nonce_manager->verify_nonce( $nonce, 'wp_rest' ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'Accès non autorisé.', 'calendrier-rdv' ),
					array( 'status' => 403 )
				);
			}
			return true;
		}

		// Pour les utilisateurs connectés, on vérifie les capacités
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Vous n\'avez pas les droits nécessaires.', 'calendrier-rdv' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Vérifie les permissions pour les opérations sur les rendez-vous dans le calendrier d'administration
	 *
	 * @param \WP_REST_Request $request Requête REST
	 * @return bool|\WP_Error
	 */
	public function check_admin_permissions( \WP_REST_Request $request ) {
		// Pour les utilisateurs connectés, on vérifie les capacités
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Vous n\'avez pas les droits nécessaires.', 'calendrier-rdv' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Récupère les créneaux disponibles
	 *
	 * @param \WP_REST_Request $request Requête REST
	 * @return \WP_REST_Response
	 */
	public function get_time_slots( \WP_REST_Request $request ) {
		$date        = $request->get_param( 'date' );
		$service_id  = $request->get_param( 'service_id' );
		$provider_id = $request->get_param( 'provider_id', 0 );

		try {
			// Ici, vous devrez implémenter la logique de récupération des créneaux
			// $slots = $this->get_available_slots($date, $service_id, $provider_id);

			// Exemple de réponse simulée
			$slots = array(
				'09:00',
				'10:00',
				'11:00',
				'14:00',
				'15:00',
				'16:00',
			);

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => array(
						'date'        => $date,
						'service_id'  => $service_id,
						'provider_id' => $provider_id,
						'slots'       => $slots,
					),
				)
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Crée un nouveau rendez-vous
	 *
	 * @param \WP_REST_Request $request Requête REST
	 * @return \WP_REST_Response
	 */
	public function create_booking( \WP_REST_Request $request ) {
		$params = $request->get_json_params();

		try {
			// Validation des données
			$required_fields = array( 'date', 'time', 'service_id', 'name', 'email', 'phone' );
			$missing_fields  = array();

			foreach ( $required_fields as $field ) {
				if ( empty( $params[ $field ] ) ) {
					$missing_fields[] = $field;
				}
			}

			if ( ! empty( $missing_fields ) ) {
				throw new \Exception(
					sprintf(
						__( 'Les champs suivants sont requis : %s', 'calendrier-rdv' ),
						implode( ', ', $missing_fields )
					)
				);
			}

			// Validation de l'email
			if ( ! is_email( $params['email'] ) ) {
				throw new \Exception( __( 'L\'adresse email fournie n\'est pas valide.', 'calendrier-rdv' ) );
			}

			// Ici, vous devrez implémenter la logique de création du rendez-vous
			// $booking_id = $this->create_booking($params);
			$booking_id = 1; // Temporaire pour les tests

			// Envoi des emails de confirmation
			// $this->send_confirmation_email($params, $booking_id);

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => array(
						'booking_id' => $booking_id,
						'message'    => __( 'Votre rendez-vous a été enregistré avec succès !', 'calendrier-rdv' ),
					),
				)
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				400
			);
		}
	}

	/**
	 * Annule un rendez-vous
	 *
	 * @param \WP_REST_Request $request Requête REST
	 * @return \WP_REST_Response
	 */
	public function cancel_booking( \WP_REST_Request $request ) {
		$booking_id = $request->get_param( 'id' );

		try {
			// Ici, vous devrez implémenter la logique d'annulation du rendez-vous
			// $result = $this->cancel_booking($booking_id);
			$result = true; // Temporaire pour les tests

			if ( ! $result ) {
				throw new \Exception( __( 'Impossible d\'annuler le rendez-vous.', 'calendrier-rdv' ) );
			}

			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Le rendez-vous a été annulé avec succès.', 'calendrier-rdv' ),
				)
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				400
			);
		}
	}

	/**
	 * Récupère les rendez-vous pour le calendrier d'administration
	 *
	 * @param \WP_REST_Request $request Requête REST
	 * @return \WP_REST_Response
	 */
	public function get_admin_appointments( \WP_REST_Request $request ) {
		try {
			$start = $request->get_param( 'start' );
			$end   = $request->get_param( 'end' );

			$appointments = Appointments::get_formatted_appointments_for_calendar( $start, $end );

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => $appointments,
				)
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}
}
