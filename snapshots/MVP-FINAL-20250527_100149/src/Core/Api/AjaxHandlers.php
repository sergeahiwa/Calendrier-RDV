<?php
/**
 * Gestionnaires AJAX pour le plugin Calendrier RDV
 *
 * @package CalendrierRdv\Core\Api
 */

namespace CalendrierRdv\Core\Api;

use CalendrierRdv\Core\Security\AjaxSecurity;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Classe de gestion des requêtes AJAX
 */
class AjaxHandlers {
	/**
	 * Initialise les gestionnaires AJAX
	 */
	public static function init() {
		// Exemple de gestionnaire AJAX pour la récupération des rendez-vous
		AjaxSecurity::register_ajax_handler(
			'cal_rdv_get_appointments',
			array( __CLASS__, 'handle_get_appointments' ),
			'read' // Capacité requise
		);

		// Exemple de gestionnaire AJAX pour la création de rendez-vous
		AjaxSecurity::register_ajax_handler(
			'cal_rdv_create_appointment',
			array( __CLASS__, 'handle_create_appointment' ),
			'edit_posts' // Capacité requise
		);

		// Exemple de gestionnaire AJAX public (sans authentification requise)
		AjaxSecurity::register_ajax_handler(
			'cal_rdv_check_availability',
			array( __CLASS__, 'handle_check_availability' ),
			'', // Aucune capacité requise
			true // Accessible sans être connecté
		);
	}

	/**
	 * Gère la récupération des rendez-vous
	 *
	 * @param WP_REST_Request $request Requête AJAX
	 * @return void
	 */
	public static function handle_get_appointments( WP_REST_Request $request ) {
		$params = $request->get_params();

		// Validation des paramètres
		if ( empty( $params['start_date'] ) || empty( $params['end_date'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Les dates de début et de fin sont requises', 'calendrier-rdv' ),
				),
				400
			);
			return;
		}

		try {
			// Ici, vous devriez appeler votre logique métier pour récupérer les rendez-vous
			// C'est un exemple simplifié
			$appointments = array(); // Remplacer par l'appel à votre modèle

			wp_send_json_success(
				array(
					'appointments' => $appointments,
				)
			);

		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				),
				500
			);
		}
	}

	/**
	 * Gère la création d'un rendez-vous
	 *
	 * @param WP_REST_Request $request Requête AJAX
	 * @return void
	 */
	public static function handle_create_appointment( WP_REST_Request $request ) {
		$params = $request->get_params();

		// Validation des paramètres
		$required_fields = array( 'service_id', 'provider_id', 'start_datetime', 'customer_name', 'customer_email' );
		$missing_fields  = array();

		foreach ( $required_fields as $field ) {
			if ( empty( $params[ $field ] ) ) {
				$missing_fields[] = $field;
			}
		}

		if ( ! empty( $missing_fields ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						__( 'Les champs suivants sont requis : %s', 'calendrier-rdv' ),
						implode( ', ', $missing_fields )
					),
				),
				400
			);
			return;
		}

		// Validation de l'email
		if ( ! is_email( $params['customer_email'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Adresse email invalide', 'calendrier-rdv' ),
				),
				400
			);
			return;
		}

		try {
			// Ici, vous devriez appeler votre logique métier pour créer le rendez-vous
			// C'est un exemple simplifié
			$appointment_id = 0; // Remplacer par l'appel à votre modèle

			if ( $appointment_id ) {
				wp_send_json_success(
					array(
						'message'        => __( 'Rendez-vous créé avec succès', 'calendrier-rdv' ),
						'appointment_id' => $appointment_id,
					)
				);
			} else {
				throw new \Exception( __( 'Échec de la création du rendez-vous', 'calendrier-rdv' ) );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				),
				500
			);
		}
	}

	/**
	 * Vérifie la disponibilité d'un créneau
	 *
	 * @param WP_REST_Request $request Requête AJAX
	 * @return void
	 */
	public static function handle_check_availability( WP_REST_Request $request ) {
		$params = $request->get_params();

		// Validation des paramètres
		if ( empty( $params['service_id'] ) || empty( $params['provider_id'] ) || empty( $params['datetime'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Paramètres manquants', 'calendrier-rdv' ),
				),
				400
			);
			return;
		}

		try {
			// Ici, vous devriez appeler votre logique métier pour vérifier la disponibilité
			// C'est un exemple simplifié
			$is_available = true; // Remplacer par l'appel à votre modèle

			wp_send_json_success(
				array(
					'available' => $is_available,
					'message'   => $is_available
						? __( 'Créneau disponible', 'calendrier-rdv' )
						: __( 'Créneau indisponible', 'calendrier-rdv' ),
				)
			);

		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				),
				500
			);
		}
	}

	/**
	 * Enregistre les scripts JavaScript avec les paramètres AJAX
	 */
	public static function enqueue_scripts() {
		// Enregistrement du script principal AJAX
		wp_register_script(
			'cal-rdv-ajax',
			CAL_RDV_PLUGIN_URL . 'assets/js/cal-rdv-ajax.js',
			array( 'jquery' ),
			CAL_RDV_VERSION,
			true
		);

		// Localisation des paramètres AJAX
		AjaxSecurity::localize_script(
			'cal-rdv-ajax',
			array(
				'i18n' => array(
					'error'   => __( 'Une erreur est survenue', 'calendrier-rdv' ),
					'success' => __( 'Opération réussie', 'calendrier-rdv' ),
				),
			)
		);

		// Chargement du script dans le frontend
		if ( ! is_admin() ) {
			wp_enqueue_script( 'cal-rdv-ajax' );
		}
	}
}
