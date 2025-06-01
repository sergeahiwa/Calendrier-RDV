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
	public static function createAppointment(WP_REST_Request $request): WP_REST_Response {
		// Vérifier les permissions
		if (!check_ajax_referer('calendrier_rdv_nonce', false, false)) {
			return new WP_REST_Response(
				['message' => __('Nonce invalide', 'calendrier-rdv')],
				403
			);
		}

		// Récupérer les données
		$data = $request->get_json_params();

		// Liste des champs obligatoires avec leurs libellés
		$required_fields = [
			'service_id' => __('service', 'calendrier-rdv'),
			'provider_id' => __('prestataire', 'calendrier-rdv'),
			'date' => __('date', 'calendrier-rdv'),
			'time' => __('heure', 'calendrier-rdv'),
			'duration' => __('durée', 'calendrier-rdv'),
			'customer_name' => __('nom du client', 'calendrier-rdv'),
			'customer_email' => __('email du client', 'calendrier-rdv'),
			'customer_phone' => __('téléphone du client', 'calendrier-rdv')
		];

		// Vérifier les champs obligatoires
		$missing_fields = [];
		foreach ($required_fields as $field => $label) {
			if (empty($data[$field])) {
				$missing_fields[] = $label;
			}
		}

		if (!empty($missing_fields)) {
			return new WP_REST_Response(
				['message' => sprintf(
					__('Champs obligatoires manquants : %s', 'calendrier-rdv'),
					implode(', ', $missing_fields)
				)],
				400
			);
		}

		// Validation de l'email
		if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
			return new WP_REST_Response(
				['message' => __('Format d\'email invalide', 'calendrier-rdv')],
				400
			);
		}

		// Validation de la durée
		$duration = !empty($data['duration']) ? intval($data['duration']) : 30; // 30 minutes par défaut
		$min_duration = 15; // 15 minutes minimum
		$max_duration = 240; // 4 heures maximum

		if ($duration < $min_duration || $duration > $max_duration) {
			return new WP_REST_Response(
				['message' => sprintf(__('La durée doit être comprise entre %d et %d minutes', 'calendrier-rdv'), $min_duration, $max_duration)],
				400
			);
		}

		// Vérifier la disponibilité
		$availability = self::checkAvailability(
			intval($data['provider_id']),
			sanitize_text_field($data['date']),
			sanitize_text_field($data['time']),
			$duration
		);

		if ($availability !== true) {
			return new WP_REST_Response(
				['message' => $availability['message']],
				400
			);
		}

		// Préparer les données
		$appointment_data = [
			'service_id'       => intval($data['service_id']),
			'provider_id'      => intval($data['provider_id']),
			'appointment_date' => sanitize_text_field($data['date']),
			'appointment_time' => sanitize_text_field($data['time']),
			'duration'         => $duration,
			'customer_name'    => sanitize_text_field($data['customer_name']),
			'customer_email'   => sanitize_email($data['customer_email']),
			'customer_phone'   => sanitize_text_field($data['customer_phone']),
			'notes'            => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : '',
			'status'           => 'pending',
			'created_at'       => current_time('mysql'),
			'updated_at'       => current_time('mysql'),
		];

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
	 * @param int $provider_id ID du prestataire
	 * @param string $date Date du rendez-vous (YYYY-MM-DD)
	 * @param string $time Heure du rendez-vous (HH:MM)
	 * @param int $duration Durée en minutes (optionnel, 30 par défaut)
	 * @param int $exclude_id ID de rendez-vous à exclure (pour les mises à jour)
	 * @return array|bool True si disponible, sinon tableau d'erreurs
	 */
	public static function checkAvailability(int $provider_id, string $date, string $time, int $duration = 30, int $exclude_id = 0) {
		global $wpdb;

		// Vérifier si c'est un jour férié
		if (self::isPublicHoliday($date)) {
			return ['error' => 'indisponible_jour_ferie', 'message' => __('Ce jour est un jour férié', 'calendrier-rdv')];
		}

		// Vérifier les congés du prestataire
		if (self::isProviderUnavailable($provider_id, $date)) {
			return ['error' => 'prestataire_indisponible', 'message' => __('Le prestataire est en congé ce jour-là', 'calendrier-rdv')];
		}

		// Vérifier les horaires d'ouverture
		$day_of_week = strtolower(date('l', strtotime($date)));
		$opening_hours = get_post_meta($provider_id, '_disponibilites', true);

		if (empty($opening_hours[$day_of_week])) {
			return ['error' => 'hors_creneaux', 'message' => __('Le prestataire n\'est pas disponible ce jour-là', 'calendrier-rdv')];
		}

		// Vérifier si l'heure est dans les horaires d'ouverture
		$opening_time = $opening_hours[$day_of_week][0];
		$closing_time = $opening_hours[$day_of_week][1];
		$end_time = date('H:i', strtotime("$time +$duration minutes"));

		if ($time < $opening_time || $end_time > $closing_time) {
			return ['error' => 'hors_creneaux', 'message' => sprintf(__('Hors des horaires d\'ouverture (%s - %s)', 'calendrier-rdv'), $opening_time, $closing_time)];
		}

		// Vérifier les chevauchements
		$start_datetime = "$date $time";
		$end_datetime = date('Y-m-d H:i', strtotime("$start_datetime +$duration minutes"));

		$query = $wpdb->prepare(
			"SELECT a.id, a.appointment_date, a.appointment_time, a.duration
			 FROM {$wpdb->prefix}rdv_appointments a
			 WHERE a.provider_id = %d
			 AND a.appointment_date = %s
			 AND a.status != 'cancelled'
			 AND a.id != %d",
			$provider_id,
			$date,
			$exclude_id
		);

		$bookings = $wpdb->get_results($query);

		foreach ($bookings as $booking) {
			$booking_start = strtotime("$booking->appointment_date $booking->appointment_time");
			$booking_end = $booking_start + ($booking->duration * 60);
			$new_booking_start = strtotime("$date $time");
			$new_booking_end = $new_booking_start + ($duration * 60);

			// Vérifier le chevauchement
			if ($new_booking_start < $booking_end && $new_booking_end > $booking_start) {
				return ['error' => 'creneau_indisponible', 'message' => __('Ce créneau est déjà réservé', 'calendrier-rdv')];
			}
		}

		return true;
	}

	/**
	 * Vérifie si une date est un jour férié
	 * 
	 * @param string $date Date au format YYYY-MM-DD
	 * @return bool
	 */
	private static function isPublicHoliday($date) {
		$year = date('Y', strtotime($date));
		$month_day = date('m-d', strtotime($date));

		// Jours fériés fixes
		$fixed_holidays = [
			'01-01', // Nouvel An
			'05-01', // Fête du Travail
			'05-08', // Victoire 1945
			'07-14', // Fête Nationale
			'08-15', // Assomption
			'11-01', // Toussaint
			'11-11', // Armistice
			'12-25'  // Noël
		];

		if (in_array($month_day, $fixed_holidays)) {
			return true;
		}

		// Jours fériés mobiles (Pâques, Ascension, Pentecôte)
		$easter_days = easter_days($year);
		$easter = new \DateTime("$year-03-21 +$easter_days days");

		// Lundi de Pâques
		$easter_monday = clone $easter;
		$easter_monday->modify('+1 day');
		if ($month_day === $easter_monday->format('m-d')) {
			return true;
		}

		// Ascension (39 jours après Pâques)
		$ascension = clone $easter;
		$ascension->modify('+39 days');
		if ($month_day === $ascension->format('m-d')) {
			return true;
		}

		// Lundi de Pentecôte (50 jours après Pâques)
		$pentecote = clone $easter;
		$pentecote->modify('+50 days');
		if ($month_day === $pentecote->format('m-d')) {
			return true;
		}

		return false;
	}

	/**
	 * Vérifie si un prestataire est en congé à une date donnée
	 * 
	 * @param int $provider_id ID du prestataire
	 * @param string $date Date au format YYYY-MM-DD
	 * @return bool True si le prestataire est en congé
	 */
	private static function isProviderUnavailable($provider_id, $date) {
		$time_offs = get_post_meta($provider_id, '_time_off', true) ?: [];

		foreach ($time_offs as $time_off) {
			if ($date >= $time_off['start_date'] && $date <= $time_off['end_date']) {
				return true;
			}
		}

		return false;
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
	public static function updateAppointment(WP_REST_Request $request): WP_REST_Response {
		// Vérifier les permissions
		if (!check_ajax_referer('calendrier_rdv_nonce', false, false)) {
			return new WP_REST_Response(
				['message' => __('Nonce invalide', 'calendrier-rdv')],
				403
			);
		}

		// Récupérer l'ID du rendez-vous et les données
		$appointment_id = $request->get_param('id');
		$data = $request->get_json_params();

		if (!$appointment_id) {
			return new WP_REST_Response(
				['message' => __('ID du rendez-vous manquant', 'calendrier-rdv')],
				400
			);
		}

		if (empty($data)) {
			return new WP_REST_Response(
				['message' => __('Aucune donnée de mise à jour fournie', 'calendrier-rdv')],
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

		if (!$appointment) {
			return new WP_REST_Response(
				['message' => __('Rendez-vous introuvable', 'calendrier-rdv')],
				404
			);
		}

		// Validation des champs obligatoires
		$required_fields = [
			'service_id' => __('service', 'calendrier-rdv'),
			'provider_id' => __('prestataire', 'calendrier-rdv'),
			'date' => __('date', 'calendrier-rdv'),
			'time' => __('heure', 'calendrier-rdv'),
			'duration' => __('durée', 'calendrier-rdv'),
			'customer_name' => __('nom du client', 'calendrier-rdv'),
			'customer_email' => __('email du client', 'calendrier-rdv'),
			'customer_phone' => __('téléphone du client', 'calendrier-rdv')
		];

		// Vérifier les champs obligatoires dans les données fournies
		$missing_fields = [];
		foreach ($required_fields as $field => $label) {
			if (isset($data[$field]) && empty($data[$field])) {
				$missing_fields[] = $label;
			}
		}

		if (!empty($missing_fields)) {
			return new WP_REST_Response(
				['message' => sprintf(
					__('Champs obligatoires manquants : %s', 'calendrier-rdv'),
					implode(', ', $missing_fields)
				)],
				400
			);
		}

		// Validation de l'email si fourni
		if (isset($data['customer_email']) && !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
			return new WP_REST_Response(
				['message' => __('Format d\'email invalide', 'calendrier-rdv')],
				400
			);
		}

		// Validation de la durée si fournie
		if (isset($data['duration'])) {
			$duration = intval($data['duration']);
			$min_duration = 15; // 15 minutes minimum
			$max_duration = 240; // 4 heures maximum

			if ($duration < $min_duration || $duration > $max_duration) {
				return new WP_REST_Response(
					['message' => sprintf(__('La durée doit être comprise entre %d et %d minutes', 'calendrier-rdv'), $min_duration, $max_duration)],
					400
				);
			}
		} else {
			$duration = $appointment['duration'];
		}

		// Si la date, l'heure, la durée ou le prestataire est modifié, vérifier la disponibilité
		$provider_id = isset($data['provider_id']) ? intval($data['provider_id']) : intval($appointment['provider_id']);
		$date = isset($data['date']) ? sanitize_text_field($data['date']) : $appointment['appointment_date'];
		$time = isset($data['time']) ? sanitize_text_field($data['time']) : $appointment['appointment_time'];

		$date_changed = isset($data['date']) && $data['date'] !== $appointment['appointment_date'];
		$time_changed = isset($data['time']) && $data['time'] !== $appointment['appointment_time'];
		$duration_changed = isset($data['duration']) && intval($data['duration']) !== intval($appointment['duration']);
		$provider_changed = isset($data['provider_id']) && intval($data['provider_id']) !== intval($appointment['provider_id']);

		if ($date_changed || $time_changed || $duration_changed || $provider_changed) {
			$availability = self::checkAvailability(
				$provider_id,
				$date,
				$time,
				$duration,
				$appointment_id // Exclure le rendez-vous actuel
			);

			if ($availability !== true) {
				return new WP_REST_Response(
					['message' => $availability['message']],
					400
				);
			}
		}

		// Préparer les données de mise à jour
		$update_data = [];

		// Champs pouvant être mis à jour avec leur méthode de nettoyage
		$allowed_fields = [
			'service_id'     => 'intval',
			'provider_id'    => 'intval',
			'date'           => 'appointment_date',
			'time'           => 'appointment_time',
			'duration'       => 'intval',
			'customer_name'  => 'sanitize_text_field',
			'customer_email' => 'sanitize_email',
			'customer_phone' => 'sanitize_text_field',
			'notes'          => 'sanitize_textarea_field',
			'status'         => 'sanitize_text_field',
		];

		// Traiter chaque champ autorisé
		foreach ($allowed_fields as $field => $process) {
			if (isset($data[$field])) {
				switch ($field) {
					case 'date':
						$update_data['appointment_date'] = sanitize_text_field($data[$field]);
						break;
					case 'time':
						$update_data['appointment_time'] = sanitize_text_field($data[$field]);
						break;
					case 'duration':
						$update_data['duration'] = intval($data[$field]);
						break;
					default:
						$update_data[$field] = $process === 'intval' 
							? intval($data[$field]) 
							: $process($data[$field]);
				}
			}
		}

		// Ajouter la date de mise à jour
		$update_data['updated_at'] = current_time('mysql');

		// Préparer les anciennes données pour la notification
		$old_data = [
			'service_id' => $appointment['service_id'],
			'provider_id' => $appointment['provider_id'],
			'date' => $appointment['appointment_date'],
			'time' => $appointment['appointment_time'],
			'duration' => $appointment['duration'] ?? 30,
			'customer_name' => $appointment['customer_name'],
			'customer_email' => $appointment['customer_email'],
			'customer_phone' => $appointment['customer_phone'],
			'notes' => $appointment['notes'] ?? '',
			'status' => $appointment['status']
		];

		// Mettre à jour le rendez-vous
		$result = $wpdb->update(
			$wpdb->prefix . 'rdv_appointments',
			$update_data,
			['id' => $appointment_id],
			'%s',
			'%d'
		);

		if ($result === false) {
			error_log('Erreur de mise à jour du rendez-vous: ' . $wpdb->last_error);
			return new WP_REST_Response(
				['message' => __('Erreur lors de la mise à jour du rendez-vous', 'calendrier-rdv')],
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
                       a.duration, s.name as service_name, s.duration_minutes, 
                       p.name as provider_name, p.email as provider_email
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
				$start_datetime     = new \DateTime($start_datetime_str);
				$end_datetime       = clone $start_datetime;
				
				// Déterminer la durée : priorité à la durée du rendez-vous, sinon durée du service, sinon 60 minutes par défaut
				$duration = !empty($appointment->duration) ? intval($appointment->duration) : 
						(!empty($appointment->duration_minutes) ? intval($appointment->duration_minutes) : 60);
				
				$end_datetime->add(new \DateInterval('PT' . $duration . 'M'));

				$color = '';
				switch ($appointment->status) {
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

				$formatted_appointments[] = [
					'id' => $appointment->id,
					'title' => ($appointment->customer_name ? esc_html($appointment->customer_name) : 'RDV') .
						($appointment->service_name ? ' - ' . esc_html($appointment->service_name) : ''),
					'start' => $start_datetime->format(\DateTime::ATOM),
					'end' => $end_datetime->format(\DateTime::ATOM),
					'backgroundColor' => $color,
					'borderColor' => $color,
					'duration' => $duration, // Durée en minutes
					'extendedProps' => [
						'serviceId' => $appointment->service_id,
						'providerId' => $appointment->provider_id,
						'serviceName' => esc_html($appointment->service_name),
						'providerName' => esc_html($appointment->provider_name),
						'customerName' => esc_html($appointment->customer_name),
						'status' => $appointment->status,
						'duration' => $duration, // Durée en minutes dans les propriétés étendues
						'startTime' => $appointment->appointment_time, // Heure de début formatée
						'endTime' => $end_datetime->format('H:i'), // Heure de fin calculée
						'providerEmail' => !empty($appointment->provider_email) ? $appointment->provider_email : ''
					]
				];
			}
		}
		return $formatted_appointments;
	}
}
