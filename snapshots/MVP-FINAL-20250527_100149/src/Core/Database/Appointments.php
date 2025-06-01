<?php

namespace CalendrierRdv\Core\Database;

use WP_REST_Request;
use WP_REST_Response;

class Appointments {
	/**
	 * Créer un nouveau rendez-vous
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function createAppointment( WP_REST_Request $request ): WP_REST_Response {
		// Vérifier les permissions
		if ( ! check_ajax_referer( 'calendrier_rdv_nonce', false, false ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Nonce invalide', 'calendrier-rdv' ) ),
				403
			);
		}

		// Récupérer les données
		$data = $request->get_json_params();

		// Validation des données
		$required_fields = array( 'service_id', 'provider_id', 'date', 'time' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) ) {
				return new WP_REST_Response(
					array( 'message' => sprintf( __( 'Le champ %s est requis', 'calendrier-rdv' ), $field ) ),
					400
				);
			}
		}

		// Vérifier la disponibilité
		if ( ! self::checkAvailability( $data['provider_id'], $data['date'], $data['time'] ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Ce créneau n\'est pas disponible', 'calendrier-rdv' ) ),
				400
			);
		}

		// Préparer les données
		$appointment_data = array(
			'service_id'       => intval( $data['service_id'] ),
			'provider_id'      => intval( $data['provider_id'] ),
			'appointment_date' => sanitize_text_field( $data['date'] ),
			'appointment_time' => sanitize_text_field( $data['time'] ),
			'customer_name'    => sanitize_text_field( $data['customer_name'] ?? '' ),
			'customer_email'   => sanitize_email( $data['customer_email'] ?? '' ),
			'customer_phone'   => sanitize_text_field( $data['customer_phone'] ?? '' ),
			'notes'            => sanitize_textarea_field( $data['notes'] ?? '' ),
			'status'           => 'pending',
			'created_at'       => current_time( 'mysql' ),
			'updated_at'       => current_time( 'mysql' ),
		);

		// Insérer le rendez-vous
		global $wpdb;
		$result = $wpdb->insert(
			$wpdb->prefix . 'rdv_appointments',
			$appointment_data
		);

		if ( $result === false ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Erreur lors de la création du rendez-vous', 'calendrier-rdv' ) ),
				500
			);
		}

		$appointment_id = $wpdb->insert_id;

		// Vérifier que l'ID est valide avant d'envoyer les notifications
		if ( $appointment_id > 0 ) {
			// Envoyer les notifications (désactivé en mode test)
			if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
				self::sendNotifications( $appointment_id );
			}

			return new WP_REST_Response(
				array(
					'id'      => $appointment_id,
					'message' => __( 'Rendez-vous créé avec succès', 'calendrier-rdv' ),
				),
				201
			);
		}

		return new WP_REST_Response(
			array( 'message' => __( 'Erreur lors de la création du rendez-vous', 'calendrier-rdv' ) ),
			500
		);
	}

	/**
	 * Vérifier la disponibilité d'un créneau
	 *
	 * @param int    $provider_id
	 * @param string $date
	 * @param string $time
	 * @return bool
	 */
	private static function checkAvailability( int $provider_id, string $date, string $time ): bool {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT COUNT(*) as count 
             FROM {$wpdb->prefix}rdv_appointments 
             WHERE provider_id = %d 
             AND appointment_date = %s 
             AND appointment_time = %s 
             AND status != 'cancelled'",
			$provider_id,
			$date,
			$time
		);

		error_log( 'SQL Query: ' . $query );
		error_log( 'Provider ID: ' . $provider_id );
		error_log( 'Date: ' . $date );
		error_log( 'Time: ' . $time );

		$result = $wpdb->get_row( $query );
		error_log( 'Query result: ' . print_r( $result, true ) );

		if ( $result === null ) {
			error_log( 'Query returned null, checking last error: ' . $wpdb->last_error );
			return true; // Par défaut, considérer le créneau comme disponible en cas d'erreur
		}

		return $result->count === 0;
	}

	/**
	 * Envoyer les notifications pour un rendez-vous
	 *
	 * @param int $appointment_id
	 */
	private static function sendNotifications( int $appointment_id ): void {
		global $wpdb;

		// Récupérer les détails du rendez-vous
		$appointment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, s.name as service_name, p.name as provider_name 
                 FROM {$wpdb->prefix}rdv_appointments a 
                 JOIN {$wpdb->prefix}rdv_services s ON a.service_id = s.id 
                 JOIN {$wpdb->prefix}rdv_providers p ON a.provider_id = p.id 
                 WHERE a.id = %d",
				$appointment_id
			),
			ARRAY_A
		);

		// Vérifier que les données du rendez-vous sont valides
		if ( ! $appointment || ! is_array( $appointment ) ) {
			error_log( "sendNotifications(): Impossible de récupérer les détails du RDV ID={$appointment_id}" );
			return; // On sort proprement sans envoyer d'emails
		}

		// Envoyer l'email au client
		self::sendCustomerEmail( $appointment );

		// Envoyer l'email au prestataire
		self::sendProviderEmail( $appointment );
	}

	/**
	 * Envoyer l'email au client
	 *
	 * @param array $appointment
	 */
	private static function sendCustomerEmail( array $appointment ): void {
		$to      = $appointment['customer_email'];
		$subject = sprintf( __( 'Confirmation de rendez-vous - %s', 'calendrier-rdv' ), $appointment['service_name'] );

		$message = sprintf(
			__( 'Bonjour %1$s,\n\nVotre rendez-vous est confirmé pour le %2$s à %3$s avec %4$s.\n\nService : %5$s\nDate : %6$s\nHeure : %7$s\n\nNotes : %8$s\n\nCordialement,', 'calendrier-rdv' ),
			$appointment['customer_name'],
			$appointment['provider_name'],
			$appointment['appointment_date'],
			$appointment['appointment_time'],
			$appointment['service_name'],
			$appointment['appointment_date'],
			$appointment['appointment_time'],
			$appointment['notes']
		);

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Envoyer l'email au prestataire
	 *
	 * @param array $appointment
	 */
	private static function sendProviderEmail( array $appointment ): void {
		$to      = $appointment['email'];
		$subject = sprintf( __( 'Nouveau rendez-vous - %s', 'calendrier-rdv' ), $appointment['service_name'] );

		$message = sprintf(
			__( 'Bonjour %1$s,\n\nVous avez un nouveau rendez-vous avec %2$s.\n\nService : %3$s\nDate : %4$s\nHeure : %5$s\nClient : %6$s\nEmail : %7$s\nTéléphone : %8$s\n\nNotes : %9$s\n\nCordialement,', 'calendrier-rdv' ),
			$appointment['provider_name'],
			$appointment['customer_name'],
			$appointment['service_name'],
			$appointment['appointment_date'],
			$appointment['appointment_time'],
			$appointment['customer_name'],
			$appointment['customer_email'],
			$appointment['customer_phone'],
			$appointment['notes']
		);

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Annuler un rendez-vous
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function cancelAppointment( WP_REST_Request $request ): WP_REST_Response {
		// Vérifier les permissions
		if ( ! check_ajax_referer( 'calendrier_rdv_nonce', false, false ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Nonce invalide', 'calendrier-rdv' ) ),
				403
			);
		}

		// Récupérer l'ID du rendez-vous
		$appointment_id = $request->get_param( 'id' );

		if ( ! $appointment_id ) {
			return new WP_REST_Response(
				array( 'message' => __( 'ID du rendez-vous manquant', 'calendrier-rdv' ) ),
				400
			);
		}

		// Vérifier que le rendez-vous existe
		global $wpdb;
		$appointment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rdv_appointments WHERE id = %d",
				$appointment_id
			),
			ARRAY_A
		);

		if ( ! $appointment ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Rendez-vous introuvable', 'calendrier-rdv' ) ),
				404
			);
		}

		// Mettre à jour le statut du rendez-vous
		$result = $wpdb->update(
			$wpdb->prefix . 'rdv_appointments',
			array(
				'status'     => 'cancelled',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $appointment_id )
		);

		if ( $result === false ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Erreur lors de l\'annulation du rendez-vous', 'calendrier-rdv' ) ),
				500
			);
		}

		// Envoyer des notifications d'annulation
		self::sendCancellationNotifications( $appointment_id );

		return new WP_REST_Response(
			array( 'message' => __( 'Rendez-vous annulé avec succès', 'calendrier-rdv' ) ),
			200
		);
	}

	/**
	 * Envoyer les notifications d'annulation pour un rendez-vous
	 *
	 * @param int $appointment_id
	 */
	private static function sendCancellationNotifications( int $appointment_id ): void {
		global $wpdb;

		// Récupérer les détails du rendez-vous
		$appointment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, s.name as service_name, p.name as provider_name, p.email as provider_email 
                 FROM {$wpdb->prefix}rdv_appointments a 
                 JOIN {$wpdb->prefix}rdv_services s ON a.service_id = s.id 
                 JOIN {$wpdb->prefix}rdv_providers p ON a.provider_id = p.id 
                 WHERE a.id = %d",
				$appointment_id
			),
			ARRAY_A
		);

		// Vérifier que les données du rendez-vous sont valides
		if ( ! $appointment || ! is_array( $appointment ) ) {
			error_log( "sendCancellationNotifications(): Impossible de récupérer les détails du RDV ID={$appointment_id}" );
			return; // On sort proprement sans envoyer d'emails
		}

		// Envoyer l'email au client
		self::sendCustomerCancellationEmail( $appointment );

		// Envoyer l'email au prestataire
		self::sendProviderCancellationEmail( $appointment );
	}

	/**
	 * Envoyer l'email d'annulation au client
	 *
	 * @param array $appointment
	 */
	private static function sendCustomerCancellationEmail( array $appointment ): void {
		$to      = $appointment['customer_email'];
		$subject = sprintf( __( 'Annulation de rendez-vous - %s', 'calendrier-rdv' ), $appointment['service_name'] );

		$message = sprintf(
			__( 'Bonjour %1$s,\n\nVotre rendez-vous a été annulé.\n\nService : %2$s\nDate : %3$s\nHeure : %4$s\nPrestataire : %5$s\n\nCordialement,', 'calendrier-rdv' ),
			$appointment['customer_name'],
			$appointment['service_name'],
			$appointment['appointment_date'],
			$appointment['appointment_time'],
			$appointment['provider_name']
		);

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Envoyer l'email d'annulation au prestataire
	 *
	 * @param array $appointment
	 */
	private static function sendProviderCancellationEmail( array $appointment ): void {
		$to      = $appointment['email']; // Utiliser la clé 'email' qui est déjà dans le tableau
		$subject = sprintf( __( 'Annulation de rendez-vous - %s', 'calendrier-rdv' ), $appointment['service_name'] );

		$message = sprintf(
			__( 'Bonjour %1$s,\n\nUn rendez-vous a été annulé.\n\nService : %2$s\nDate : %3$s\nHeure : %4$s\nClient : %5$s\nEmail : %6$s\nTéléphone : %7$s\n\nCordialement,', 'calendrier-rdv' ),
			$appointment['provider_name'],
			$appointment['service_name'],
			$appointment['appointment_date'],
			$appointment['appointment_time'],
			$appointment['customer_name'],
			$appointment['customer_email'],
			$appointment['customer_phone']
		);

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Modifier un rendez-vous existant
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function updateAppointment( WP_REST_Request $request ): WP_REST_Response {
		// Vérifier les permissions
		if ( ! check_ajax_referer( 'calendrier_rdv_nonce', false, false ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Nonce invalide', 'calendrier-rdv' ) ),
				403
			);
		}

		// Récupérer l'ID du rendez-vous
		$appointment_id = $request->get_param( 'id' );

		if ( ! $appointment_id ) {
			return new WP_REST_Response(
				array( 'message' => __( 'ID du rendez-vous manquant', 'calendrier-rdv' ) ),
				400
			);
		}

		// Récupérer les données de mise à jour
		$data = $request->get_json_params();

		if ( empty( $data ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Aucune donnée de mise à jour fournie', 'calendrier-rdv' ) ),
				400
			);
		}

		// Vérifier que le rendez-vous existe
		global $wpdb;
		$appointment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rdv_appointments WHERE id = %d",
				$appointment_id
			),
			ARRAY_A
		);

		if ( ! $appointment ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Rendez-vous introuvable', 'calendrier-rdv' ) ),
				404
			);
		}

		// Si la date ou l'heure est modifiée, vérifier la disponibilité
		if ( ( isset( $data['date'] ) && $data['date'] !== $appointment['appointment_date'] ) ||
			( isset( $data['time'] ) && $data['time'] !== $appointment['appointment_time'] ) ) {

			$provider_id = isset( $data['provider_id'] ) ? $data['provider_id'] : $appointment['provider_id'];
			$date        = isset( $data['date'] ) ? $data['date'] : $appointment['appointment_date'];
			$time        = isset( $data['time'] ) ? $data['time'] : $appointment['appointment_time'];

			// Vérifier la disponibilité (en excluant le rendez-vous actuel)
			$query = $wpdb->prepare(
				"SELECT COUNT(*) as count 
                 FROM {$wpdb->prefix}rdv_appointments 
                 WHERE provider_id = %d 
                 AND appointment_date = %s 
                 AND appointment_time = %s 
                 AND status != 'cancelled'
                 AND id != %d",
				$provider_id,
				$date,
				$time,
				$appointment_id
			);

			$result = $wpdb->get_row( $query );

			if ( $result && $result->count > 0 ) {
				return new WP_REST_Response(
					array( 'message' => __( 'Ce créneau n\'est pas disponible', 'calendrier-rdv' ) ),
					400
				);
			}
		}

		// Préparer les données de mise à jour
		$update_data = array();

		// Champs pouvant être mis à jour
		$allowed_fields = array(
			'service_id'     => 'intval',
			'provider_id'    => 'intval',
			'date'           => 'appointment_date',
			'time'           => 'appointment_time',
			'customer_name'  => 'sanitize_text_field',
			'customer_email' => 'sanitize_email',
			'customer_phone' => 'sanitize_text_field',
			'notes'          => 'sanitize_textarea_field',
			'status'         => 'sanitize_text_field',
		);

		// Traiter chaque champ autorisé
		foreach ( $allowed_fields as $field => $process ) {
			if ( isset( $data[ $field ] ) ) {
				if ( $field === 'date' ) {
					$update_data['appointment_date'] = sanitize_text_field( $data[ $field ] );
				} elseif ( $field === 'time' ) {
					$update_data['appointment_time'] = sanitize_text_field( $data[ $field ] );
				} else {
					$update_data[ $field ] = $process === 'intval' ?
						intval( $data[ $field ] ) :
						$process( $data[ $field ] );
				}
			}
		}

		// Ajouter la date de mise à jour
		$update_data['updated_at'] = current_time( 'mysql' );

		// Mettre à jour le rendez-vous
		$result = $wpdb->update(
			$wpdb->prefix . 'rdv_appointments',
			$update_data,
			array( 'id' => $appointment_id )
		);

		if ( $result === false ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Erreur lors de la mise à jour du rendez-vous', 'calendrier-rdv' ) ),
				500
			);
		}

		// Envoyer des notifications de modification
		if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
			self::sendUpdateNotifications( $appointment_id, $appointment, $update_data );
		}

		return new WP_REST_Response(
			array(
				'id'      => $appointment_id,
				'message' => __( 'Rendez-vous mis à jour avec succès', 'calendrier-rdv' ),
			),
			200
		);
	}

	/**
	 * Envoyer les notifications de modification pour un rendez-vous
	 *
	 * @param int   $appointment_id
	 * @param array $old_data
	 * @param array $new_data
	 */
	private static function sendUpdateNotifications( int $appointment_id, array $old_data, array $new_data ): void {
		global $wpdb;

		// Récupérer les détails du rendez-vous mis à jour
		$appointment = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, s.name as service_name, p.name as provider_name, p.email as provider_email 
                 FROM {$wpdb->prefix}rdv_appointments a 
                 JOIN {$wpdb->prefix}rdv_services s ON a.service_id = s.id 
                 JOIN {$wpdb->prefix}rdv_providers p ON a.provider_id = p.id 
                 WHERE a.id = %d",
				$appointment_id
			),
			ARRAY_A
		);

		// Vérifier que les données du rendez-vous sont valides
		if ( ! $appointment || ! is_array( $appointment ) ) {
			error_log( "sendUpdateNotifications(): Impossible de récupérer les détails du RDV ID={$appointment_id}" );
			return; // On sort proprement sans envoyer d'emails
		}

		// Déterminer si des changements significatifs ont été apportés
		$significant_changes = false;
		$significant_fields  = array( 'appointment_date', 'appointment_time', 'service_id', 'provider_id' );

		foreach ( $significant_fields as $field ) {
			$old_field = $field;
			$new_field = $field;

			// Adapter les noms de champs pour la comparaison
			if ( $field === 'appointment_date' && isset( $new_data['appointment_date'] ) ) {
				$significant_changes = $old_data[ $old_field ] !== $new_data[ $new_field ];
				break;
			}
			if ( $field === 'appointment_time' && isset( $new_data['appointment_time'] ) ) {
				$significant_changes = $old_data[ $old_field ] !== $new_data[ $new_field ];
				break;
			}
			if ( isset( $new_data[ $field ] ) && $old_data[ $field ] != $new_data[ $field ] ) {
				$significant_changes = true;
				break;
			}
		}

		// Si des changements significatifs ont été apportés, envoyer des notifications
		if ( $significant_changes ) {
			// Envoyer l'email au client
			self::sendCustomerUpdateEmail( $appointment, $old_data );

			// Envoyer l'email au prestataire
			self::sendProviderUpdateEmail( $appointment, $old_data );
		}
	}

	/**
	 * Envoyer l'email de mise à jour au client
	 *
	 * @param array $appointment
	 * @param array $old_data
	 */
	private static function sendCustomerUpdateEmail( array $appointment, array $old_data ): void {
		$to      = $appointment['customer_email'];
		$subject = sprintf( __( 'Modification de rendez-vous - %s', 'calendrier-rdv' ), $appointment['service_name'] );

		$message = sprintf(
			__( 'Bonjour %1$s,\n\nVotre rendez-vous a été modifié.\n\nNouvelles informations :\nService : %2$s\nDate : %3$s\nHeure : %4$s\nPrestataire : %5$s\n\nAnciennes informations :\nDate : %6$s\nHeure : %7$s\n\nCordialement,', 'calendrier-rdv' ),
			$appointment['customer_name'],
			$appointment['service_name'],
			$appointment['appointment_date'],
			$appointment['appointment_time'],
			$appointment['provider_name'],
			$old_data['appointment_date'],
			$old_data['appointment_time']
		);

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Envoyer l'email de mise à jour au prestataire
	 *
	 * @param array $appointment
	 * @param array $old_data
	 */
	private static function sendProviderUpdateEmail( array $appointment, array $old_data ): void {
		$to      = $appointment['email'];
		$subject = sprintf( __( 'Modification de rendez-vous - %s', 'calendrier-rdv' ), $appointment['service_name'] );

		$message = sprintf(
			__( 'Bonjour %1$s,\n\nUn rendez-vous a été modifié.\n\nNouvelles informations :\nService : %2$s\nDate : %3$s\nHeure : %4$s\nClient : %5$s\nEmail : %6$s\nTéléphone : %7$s\n\nAnciennes informations :\nDate : %8$s\nHeure : %9$s\n\nCordialement,', 'calendrier-rdv' ),
			$appointment['provider_name'],
			$appointment['service_name'],
			$appointment['appointment_date'],
			$appointment['appointment_time'],
			$appointment['customer_name'],
			$appointment['customer_email'],
			$appointment['customer_phone'],
			$old_data['appointment_date'],
			$old_data['appointment_time']
		);

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Récupère et formate les rendez-vous pour l'affichage dans FullCalendar.
	 *
	 * @param string|null $start_date Date de début (format YYYY-MM-DD), inclusive.
	 * @param string|null $end_date Date de fin (format YYYY-MM-DD), inclusive.
	 * @return array Liste des rendez-vous formatés pour FullCalendar.
	 */
	public static function get_formatted_appointments_for_calendar( $start_date = null, $end_date = null ) {
		global $wpdb;
		$appointments_table = $wpdb->prefix . 'rdv_appointments';
		$services_table     = $wpdb->prefix . 'rdv_services';
		$providers_table    = $wpdb->prefix . 'rdv_providers'; // Assumant que cette table existe

		$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.customer_name, a.status, a.service_id, a.provider_id, 
                       s.name as service_name, s.duration_minutes, 
                       p.name as provider_name 
                FROM {$appointments_table} a 
                LEFT JOIN {$services_table} s ON a.service_id = s.id 
                LEFT JOIN {$providers_table} p ON a.provider_id = p.id";

		$where_clauses = array();
		$params        = array();

		if ( $start_date ) {
			$where_clauses[] = 'a.appointment_date >= %s';
			$params[]        = $start_date;
		}
		if ( $end_date ) {
			// Pour inclure les rendez-vous qui commencent le jour de fin, même si FullCalendar envoie souvent le jour suivant comme 'end'.
			// Si $end_date est le dernier jour du mois affiché, il faut inclure les RDV de ce jour.
			$where_clauses[] = 'a.appointment_date <= %s';
			$params[]        = $end_date;
		}
		// On ne veut pas afficher les rendez-vous annulés par défaut sur le calendrier admin, sauf si spécifié
		$where_clauses[] = "a.status != 'cancelled'";

		if ( ! empty( $where_clauses ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		$sql .= ' ORDER BY a.appointment_date, a.appointment_time ASC';

		if ( ! empty( $params ) ) {
			$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		} else {
			$results = $wpdb->get_results( $sql );
		}

		$formatted_appointments = array();
		if ( $results ) {
			foreach ( $results as $appointment ) {
				$start_datetime_str = $appointment->appointment_date . 'T' . $appointment->appointment_time;
				$start_datetime     = new \DateTime( $start_datetime_str );
				$end_datetime       = clone $start_datetime;
				$duration           = $appointment->duration_minutes ? intval( $appointment->duration_minutes ) : 60; // Durée par défaut de 60 min si non définie
				$end_datetime->add( new \DateInterval( 'PT' . $duration . 'M' ) );

				$color = '';
				switch ( $appointment->status ) {
					case 'pending':
						$color = '#ffc107'; // Jaune
						break;
					case 'confirmed':
						$color = '#28a745'; // Vert
						break;
					// 'cancelled' est filtré par la requête SQL
					case 'completed':
						$color = '#17a2b8'; // Cyan
						break;
					case 'no-show':
						$color = '#dc3545'; // Rouge
						break;
				}

				$formatted_appointments[] = array(
					'id'              => $appointment->id,
					'title'           => ( $appointment->customer_name ? esc_html( $appointment->customer_name ) : 'RDV' ) .
								( $appointment->service_name ? ' - ' . esc_html( $appointment->service_name ) : '' ),
					'start'           => $start_datetime->format( \DateTime::ATOM ), // Format ISO8601
					'end'             => $end_datetime->format( \DateTime::ATOM ),   // Format ISO8601
					'backgroundColor' => $color,
					'borderColor'     => $color,
					'extendedProps'   => array(
						'serviceId'    => $appointment->service_id,
						'providerId'   => $appointment->provider_id,
						'serviceName'  => esc_html( $appointment->service_name ),
						'providerName' => esc_html( $appointment->provider_name ),
						'customerName' => esc_html( $appointment->customer_name ),
						'status'       => $appointment->status,
					),
				);
			}
		}
		return $formatted_appointments;
	}
	// Vous pourriez avoir d'autres méthodes ici, comme getAppointmentById, etc.
}
