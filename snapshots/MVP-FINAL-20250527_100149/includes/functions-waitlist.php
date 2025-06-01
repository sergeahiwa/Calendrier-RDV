<?php
/**
 * Fonctions d'aide pour la gestion des listes d'attente
 *
 * @package Calendrier_RDV
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Vérifie si la liste d'attente est activée
 * 
 * @return bool True si activée, false sinon
 */
function calendrier_rdv_is_waitlist_enabled() {
    return 'yes' === get_option('calendrier_rdv_waitlist_enabled', 'yes');
}

/**
 * Ajoute un utilisateur à la liste d'attente pour un créneau
 * 
 * @param array $data Données de la réservation
 * @return int|WP_Error ID de l'entrée en liste d'attente ou WP_Error en cas d'échec
 */
function calendrier_rdv_add_to_waitlist($data) {
    global $wpdb;
    
    $defaults = [
        'service_id'    => 0,
        'date'          => '',
        'start_time'    => '',
        'end_time'      => '',
        'name'          => '',
        'email'         => '',
        'phone'         => '',
        'user_id'       => 0,
        'notes'         => '',
    ];
    
    $data = wp_parse_args($data, $defaults);
    
    // Validation des données
    if (empty($data['service_id']) || empty($data['date']) || empty($data['start_time']) || empty($data['email'])) {
        return new WP_Error('missing_required_fields', __('Tous les champs obligatoires doivent être remplis.', 'calendrier-rdv'));
    }
    
    // Vérifier si l'utilisateur est déjà dans la liste d'attente pour ce créneau
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}rdv_waitlist 
        WHERE email = %s 
        AND service_id = %d 
        AND date = %s 
        AND start_time = %s 
        AND status = 'waiting'",
        $data['email'],
        $data['service_id'],
        $data['date'],
        $data['start_time']
    ));
    
    if ($existing) {
        return new WP_Error('already_on_waitlist', __('Vous êtes déjà sur la liste d\'attente pour ce créneau.', 'calendrier-rdv'));
    }
    
    // Récupérer la position actuelle dans la file d'attente
    $position = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}rdv_waitlist 
        WHERE service_id = %d 
        AND date = %s 
        AND start_time = %s 
        AND status = 'waiting'",
        $data['service_id'],
        $data['date'],
        $data['start_time']
    ));
    
    // Générer un jeton unique pour cette entrée
    $token = wp_generate_password(32, false);
    
    // Insérer dans la base de données
    $result = $wpdb->insert(
        $wpdb->prefix . 'rdv_waitlist',
        [
            'service_id'    => $data['service_id'],
            'date'          => $data['date'],
            'start_time'    => $data['start_time'],
            'end_time'      => $data['end_time'],
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'user_id'       => $data['user_id'],
            'position'      => $position + 1,
            'status'        => 'waiting',
            'token'         => $token,
            'notes'         => $data['notes'],
            'created_at'    => current_time('mysql', true),
            'updated_at'    => current_time('mysql', true),
        ],
        ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s']
    );
    
    if (false === $result) {
        return new WP_Error('database_error', __('Une erreur est survenue lors de l\'ajout à la liste d\'attente.', 'calendrier-rdv'));
    }
    
    $waitlist_id = $wpdb->insert_id;
    
    // Déclencher une action pour les extensions
    do_action('calendrier_rdv_added_to_waitlist', $waitlist_id, $data);
    
    // Envoyer un email de confirmation
    calendrier_rdv_send_waitlist_confirmation_email($waitlist_id);
    
    return $waitlist_id;
}

/**
 * Supprime un utilisateur de la liste d'attente
 * 
 * @param int $waitlist_id ID de l'entrée dans la liste d'attente
 * @return bool|WP_Error True en cas de succès, WP_Error en cas d'échec
 */
function calendrier_rdv_remove_from_waitlist($waitlist_id) {
    global $wpdb;
    
    $waitlist_id = absint($waitlist_id);
    
    if (empty($waitlist_id)) {
        return new WP_Error('invalid_id', __('ID de liste d\'attente invalide.', 'calendrier-rdv'));
    }
    
    // Vérifier si l'entrée existe
    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rdv_waitlist WHERE id = %d",
        $waitlist_id
    ));
    
    if (!$entry) {
        return new WP_Error('not_found', __('Entrée de liste d\'attente introuvable.', 'calendrier-rdv'));
    }
    
    // Marquer comme supprimé au lieu de supprimer physiquement
    $result = $wpdb->update(
        $wpdb->prefix . 'rdv_waitlist',
        [
            'status' => 'cancelled',
            'updated_at' => current_time('mysql', true),
        ],
        ['id' => $waitlist_id],
        ['%s', '%s'],
        ['%d']
    );
    
    if (false === $result) {
        return new WP_Error('database_error', __('Impossible de supprimer de la liste d\'attente.', 'calendrier-rdv'));
    }
    
    // Mettre à jour les positions des autres entrées
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}rdv_waitlist 
        SET position = position - 1 
        WHERE service_id = %d 
        AND date = %s 
        AND start_time = %s 
        AND position > %d 
        AND status = 'waiting'",
        $entry->service_id,
        $entry->date,
        $entry->start_time,
        $entry->position
    ));
    
    // Déclencher une action pour les extensions
    do_action('calendrier_rdv_removed_from_waitlist', $waitlist_id, $entry);
    
    return true;
}

/**
 * Récupère les entrées en liste d'attente pour un créneau donné
 * 
 * @param int $service_id ID du service
 * @param string $date Date au format Y-m-d
 * @param string $start_time Heure de début au format H:i:s
 * @return array Tableau des entrées en liste d'attente
 */
function calendrier_rdv_get_waitlist_entries($service_id, $date = '', $start_time = '') {
    global $wpdb;
    
    $service_id = absint($service_id);
    $where = ['service_id = %d'];
    $params = [$service_id];
    
    if (!empty($date)) {
        $where[] = 'date = %s';
        $params[] = $date;
        
        if (!empty($start_time)) {
            $where[] = 'start_time = %s';
            $params[] = $start_time;
        }
    }
    
    $where[] = "status = 'waiting'";
    
    $query = "SELECT * FROM {$wpdb->prefix}rdv_waitlist WHERE " . implode(' AND ', $where) . " ORDER BY position ASC";
    
    if (count($params) > 1) {
        $query = $wpdb->prepare($query, $params);
    } elseif (count($params) === 1) {
        $query = $wpdb->prepare($query, $params[0]);
    }
    
    return $wpdb->get_results($query);
}

/**
 * Vérifie si un créneau a une liste d'attente
 * 
 * @param int $service_id ID du service
 * @param string $date Date au format Y-m-d
 * @param string $start_time Heure de début au format H:i:s
 * @return bool True si le créneau a une liste d'attente, false sinon
 */
function calendrier_rdv_has_waitlist($service_id, $date, $start_time) {
    $entries = calendrier_rdv_get_waitlist_entries($service_id, $date, $start_time);
    return !empty($entries);
}

/**
 * Récupère la position d'un utilisateur dans la liste d'attente
 * 
 * @param string $email Adresse email de l'utilisateur
 * @param int $service_id ID du service
 * @param string $date Date au format Y-m-d
 * @param string $start_time Heure de début au format H:i:s
 * @return int|false Position dans la liste d'attente ou false si non trouvé
 */
function calendrier_rdv_get_waitlist_position($email, $service_id, $date, $start_time) {
    global $wpdb;
    
    $position = $wpdb->get_var($wpdb->prepare(
        "SELECT position FROM {$wpdb->prefix}rdv_waitlist 
        WHERE email = %s 
        AND service_id = %d 
        AND date = %s 
        AND start_time = %s 
        AND status = 'waiting'",
        $email,
        $service_id,
        $date,
        $start_time
    ));
    
    return $position ? (int) $position : false;
}

/**
 * Envoie un email de confirmation d'inscription à la liste d'attente
 * 
 * @param int $waitlist_id ID de l'entrée dans la liste d'attente
 * @return bool True si l'email a été envoyé, false sinon
 */
function calendrier_rdv_send_waitlist_confirmation_email($waitlist_id) {
    global $wpdb;
    
    $waitlist_entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rdv_waitlist WHERE id = %d",
        $waitlist_id
    ));
    
    if (!$waitlist_entry) {
        return false;
    }
    
    // Récupérer les détails du service
    $service = get_post($waitlist_entry->service_id);
    
    if (!$service) {
        return false;
    }
    
    // Préparer les données pour l'email
    $to = $waitlist_entry->email;
    $subject = sprintf(__('Confirmation d\'inscription à la liste d\'attente - %s', 'calendrier-rdv'), get_bloginfo('name'));
    
    // Charger le template d'email
    ob_start();
    include CAL_RDV_PLUGIN_DIR . 'templates/emails/waitlist_confirmation.php';
    $message = ob_get_clean();
    
    // En-têtes de l'email
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
    ];
    
    // Envoyer l'email
    $sent = wp_mail($to, $subject, $message, $headers);
    
    // Mettre à jour la date de notification si l'email a été envoyé avec succès
    if ($sent) {
        $wpdb->update(
            $wpdb->prefix . 'rdv_waitlist',
            ['notified_at' => current_time('mysql', true)],
            ['id' => $waitlist_id],
            ['%s'],
            ['%d']
        );
    }
    
    return $sent;
}

/**
 * Notifie le premier utilisateur de la liste d'attente lorsqu'une place se libère
 * 
 * @param int $service_id ID du service
 * @param string $date Date au format Y-m-d
 * @param string $start_time Heure de début au format H:i:s
 * @return bool|WP_Error True en cas de succès, WP_Error en cas d'échec
 */
function calendrier_rdv_notify_next_in_waitlist($service_id, $date, $start_time) {
    global $wpdb;
    
    // Commencer une transaction
    $wpdb->query('START TRANSACTION');
    
    try {
        // Verrouiller la table pour éviter les conditions de course
        $wpdb->query("SELECT * FROM {$wpdb->prefix}rdv_waitlist WHERE service_id = %d AND date = %s AND start_time = %s AND status = 'waiting' ORDER BY position ASC LIMIT 1 FOR UPDATE", 
            $service_id, $date, $start_time);
        
        // Récupérer la première entrée en attente
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rdv_waitlist 
            WHERE service_id = %d 
            AND date = %s 
            AND start_time = %s 
            AND status = 'waiting' 
            ORDER BY position ASC 
            LIMIT 1",
            $service_id,
            $date,
            $start_time
        ));
        
        if (!$entry) {
            $wpdb->query('COMMIT');
            return new WP_Error('empty_waitlist', __('Aucun utilisateur en attente pour ce créneau.', 'calendrier-rdv'));
        }
        
        // Mettre à jour le statut de l'entrée
        $updated = $wpdb->update(
            $wpdb->prefix . 'rdv_waitlist',
            [
                'status' => 'notified',
                'updated_at' => current_time('mysql', true),
                'notified_at' => current_time('mysql', true),
            ],
            ['id' => $entry->id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if (false === $updated) {
            throw new Exception(__('Impossible de mettre à jour l\'entrée de la liste d\'attente.', 'calendrier-rdv'));
        }
        
        // Mettre à jour les positions des autres entrées
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}rdv_waitlist 
            SET position = position - 1 
            WHERE service_id = %d 
            AND date = %s 
            AND start_time = %s 
            AND position > %d 
            AND status = 'waiting'",
            $service_id,
            $date,
            $start_time,
            $entry->position
        ));
        
        // Valider la transaction
        $wpdb->query('COMMIT');
        
        // Envoyer l'email de notification
        $sent = calendrier_rdv_send_waitlist_available_email($entry->id);
        
        if (!$sent) {
            error_log(sprintf('Échec de l\'envoi de l\'email de notification pour l\'entrée de liste d\'attente #%d', $entry->id));
        }
        
        return true;
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $wpdb->query('ROLLBACK');
        return new WP_Error('waitlist_error', $e->getMessage());
    }
}

/**
 * Envoie un email de notification de disponibilité à un utilisateur en liste d'attente
 * 
 * @param int $waitlist_id ID de l'entrée dans la liste d'attente
 * @return bool True si l'email a été envoyé, false sinon
 */
function calendrier_rdv_send_waitlist_available_email($waitlist_id) {
    global $wpdb;
    
    $waitlist_entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rdv_waitlist WHERE id = %d",
        $waitlist_id
    ));
    
    if (!$waitlist_entry) {
        return false;
    }
    
    // Récupérer les détails du service
    $service = get_post($waitlist_entry->service_id);
    
    if (!$service) {
        return false;
    }
    
    // Générer un lien de réservation unique avec un jeton
    $booking_url = add_query_arg([
        'action' => 'book_from_waitlist',
        'waitlist_id' => $waitlist_id,
        'token' => $waitlist_entry->token,
    ], home_url('/'));
    
    // Préparer les données pour l'email
    $to = $waitlist_entry->email;
    $subject = sprintf(__('Une place s\'est libérée pour votre rendez-vous - %s', 'calendrier-rdv'), get_bloginfo('name'));
    
    // Charger le template d'email
    ob_start();
    include CAL_RDV_PLUGIN_DIR . 'templates/emails/waitlist_availability.php';
    $message = ob_get_clean();
    
    // En-têtes de l'email
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>',
    ];
    
    // Envoyer l'email
    $sent = wp_mail($to, $subject, $message, $headers);
    
    return $sent;
}

/**
 * Vérifie si un utilisateur peut rejoindre la liste d'attente pour un créneau
 * 
 * @param int $service_id ID du service
 * @param string $date Date au format Y-m-d
 * @param string $start_time Heure de début au format H:i:s
 * @param string $email Adresse email de l'utilisateur
 * @return bool|WP_Error True si l'utilisateur peut rejoindre, WP_Error sinon
 */
function calendrier_rdv_can_join_waitlist($service_id, $date, $start_time, $email) {
    // Vérifier si la liste d'attente est activée
    if (!calendrier_rdv_is_waitlist_enabled()) {
        return new WP_Error('waitlist_disabled', __('La liste d\'attente n\'est pas activée pour ce service.', 'calendrier-rdv'));
    }
    
    // Vérifier si l'utilisateur est déjà dans la file d'attente
    $position = calendrier_rdv_get_waitlist_position($email, $service_id, $date, $start_time);
    
    if (false !== $position) {
        return new WP_Error('already_on_waitlist', sprintf(__('Vous êtes déjà en position %d sur la liste d\'attente pour ce créneau.', 'calendrier-rdv'), $position));
    }
    
    // Vérifier si la file d'attente est pleine
    $max_waitlist = (int) get_option('calendrier_rdv_max_waitlist_per_slot', 10);
    $current_waitlist = count(calendrier_rdv_get_waitlist_entries($service_id, $date, $start_time));
    
    if ($current_waitlist >= $max_waitlist) {
        return new WP_Error('waitlist_full', __('La liste d\'attente pour ce créneau est complète.', 'calendrier-rdv'));
    }
    
    return true;
}

/**
 * Traite une annulation de réservation et notifie la liste d'attente si nécessaire
 * 
 * @param int $booking_id ID de la réservation annulée
 * @return bool|WP_Error True en cas de succès, WP_Error en cas d'échec
 */
function calendrier_rdv_handle_booking_cancellation($booking_id) {
    global $wpdb;
    
    // Récupérer les détails de la réservation annulée
    $booking = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}reservations WHERE id = %d",
        $booking_id
    ));
    
    if (!$booking) {
        return new WP_Error('booking_not_found', __('Réservation introuvable.', 'calendrier-rdv'));
    }
    
    // Vérifier s'il y a des personnes en liste d'attente pour ce créneau
    $waitlist_entries = calendrier_rdv_get_waitlist_entries(
        $booking->prestataire, // service_id
        $booking->date_rdv,    // date
        $booking->heure_rdv    // start_time
    );
    
    if (empty($waitlist_entries)) {
        return true; // Rien à faire
    }
    
    // Notifier la première personne de la liste d'attente
    $result = calendrier_rdv_notify_next_in_waitlist(
        $booking->prestataire,
        $booking->date_rdv,
        $booking->heure_rdv
    );
    
    if (is_wp_error($result)) {
        return $result;
    }
    
    return true;
}

/**
 * Vérifie périodiquement les créneaux disponibles pour les listes d'attente
 */
function calendrier_rdv_check_waitlist_availability() {
    global $wpdb;
    
    // Récupérer tous les créneaux avec des listes d'attente
    $waitlist_slots = $wpdb->get_results(
        "SELECT DISTINCT service_id, date, start_time 
        FROM {$wpdb->prefix}rdv_waitlist 
        WHERE status = 'waiting' 
        AND date >= CURDATE()"
    );
    
    if (empty($waitlist_slots)) {
        return;
    }
    
    foreach ($waitlist_slots as $slot) {
        // Vérifier si le créneau est maintenant disponible
        $available = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$wpdb->prefix}reservations 
            WHERE prestataire = %d 
            AND date_rdv = %s 
            AND heure_rdv = %s 
            AND statut = 'confirmed'",
            $slot->service_id,
            $slot->date,
            $slot->start_time
        ));
        
        if ($available > 0) {
            continue; // Le créneau est toujours occupé
        }
        
        // Le créneau est disponible, notifier la première personne de la liste d'attente
        calendrier_rdv_notify_next_in_waitlist(
            $slot->service_id,
            $slot->date,
            $slot->start_time
        );
    }
}
add_action('calendrier_rdv_check_waitlist_availability', 'calendrier_rdv_check_waitlist_availability');

/**
 * Supprime automatiquement les anciennes entrées de liste d'attente
 */
function calendrier_rdv_cleanup_old_waitlist_entries() {
    global $wpdb;
    
    // Supprimer les entrées de plus de 30 jours
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}rdv_waitlist 
            WHERE (status = 'cancelled' OR status = 'expired' OR (status = 'notified' AND notified_at < DATE_SUB(NOW(), INTERVAL 7 DAY)))
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )
    );
    
    // Marquer comme expirées les entrées notifiées il y a plus de 48h
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}rdv_waitlist 
            SET status = 'expired', updated_at = %s 
            WHERE status = 'notified' 
            AND notified_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)",
            current_time('mysql', true)
        )
    );
}
add_action('calendrier_rdv_cleanup_logs', 'calendrier_rdv_cleanup_old_waitlist_entries');
