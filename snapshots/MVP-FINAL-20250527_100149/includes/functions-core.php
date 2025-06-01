<?php
/**
 * Fonctions principales du plugin Calendrier RDV
 *
 * @package CalendrierRdv
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Récupère les créneaux disponibles pour un prestataire donné
 *
 * @param int $provider_id ID du prestataire
 * @param string $date Date au format Y-m-d
 * @return array Liste des créneaux disponibles
 */
function calendrier_rdv_get_available_slots($provider_id, $date) {
    global $wpdb;
    
    // Tableau pour stocker les créneaux disponibles
    $available_slots = array();
    
    // Récupérer les horaires de travail du prestataire
    $working_hours = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}calendrier_rdv_working_hours 
             WHERE provider_id = %d 
             AND day_of_week = %d",
            $provider_id,
            date('N', strtotime($date))
        )
    );
    
    // Récupérer les rendez-vous existants pour ce jour
    $appointments = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}calendrier_rdv_appointments 
             WHERE provider_id = %d 
             AND DATE(appointment_date) = %s",
            $provider_id,
            $date
        )
    );
    
    // Convertir les rendez-vous en tableau de créneaux occupés
    $booked_slots = array();
    foreach ($appointments as $appointment) {
        $start_time = date('H:i', strtotime($appointment->appointment_date));
        $end_time = date('H:i', strtotime($appointment->appointment_date . ' +' . $appointment->duration . ' minutes'));
        
        $booked_slots[] = array(
            'start' => $start_time,
            'end' => $end_time
        );
    }
    
    // Générer les créneaux disponibles
    foreach ($working_hours as $hours) {
        $start_time = date('H:i', strtotime($hours->start_time));
        $end_time = date('H:i', strtotime($hours->end_time));
        
        // Durée du créneau (en minutes)
        $slot_duration = 30; // Par défaut 30 minutes
        
        // Générer les créneaux
        $current_time = $start_time;
        while (strtotime($current_time) < strtotime($end_time)) {
            $slot_end_time = date('H:i', strtotime($current_time . ' +' . $slot_duration . ' minutes'));
            
            // Vérifier si le créneau est disponible
            $is_available = true;
            foreach ($booked_slots as $booked) {
                if (
                    (strtotime($current_time) >= strtotime($booked['start']) && strtotime($current_time) < strtotime($booked['end'])) ||
                    (strtotime($slot_end_time) > strtotime($booked['start']) && strtotime($slot_end_time) <= strtotime($booked['end'])) ||
                    (strtotime($current_time) <= strtotime($booked['start']) && strtotime($slot_end_time) >= strtotime($booked['end']))
                ) {
                    $is_available = false;
                    break;
                }
            }
            
            if ($is_available) {
                $available_slots[] = array(
                    'start' => $current_time,
                    'end' => $slot_end_time
                );
            }
            
            $current_time = $slot_end_time;
        }
    }
    
    return $available_slots;
}

/**
 * Formate une date selon les paramètres de WordPress
 *
 * @param string $date Date à formater
 * @param string $format Format de date (date, time, datetime)
 * @return string Date formatée
 */
function calendrier_rdv_format_date($date, $format = 'datetime') {
    $date_obj = new DateTime($date);
    
    switch ($format) {
        case 'date':
            return $date_obj->format(get_option('date_format'));
        case 'time':
            return $date_obj->format(get_option('time_format'));
        case 'datetime':
        default:
            return $date_obj->format(get_option('date_format') . ' ' . get_option('time_format'));
    }
}

/**
 * Vérifie si un créneau est disponible
 *
 * @param int $provider_id ID du prestataire
 * @param string $date Date et heure du rendez-vous (Y-m-d H:i:s)
 * @param int $duration Durée du rendez-vous en minutes
 * @return bool True si le créneau est disponible, false sinon
 */
function calendrier_rdv_is_slot_available($provider_id, $date, $duration = 30) {
    global $wpdb;
    
    // Date du rendez-vous
    $appointment_date = date('Y-m-d', strtotime($date));
    
    // Heure de début et de fin du rendez-vous
    $start_time = date('H:i:s', strtotime($date));
    $end_time = date('H:i:s', strtotime($date . ' +' . $duration . ' minutes'));
    
    // Vérifier si le prestataire travaille ce jour-là
    $day_of_week = date('N', strtotime($date));
    $working_hours = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}calendrier_rdv_working_hours 
             WHERE provider_id = %d 
             AND day_of_week = %d 
             AND TIME(%s) >= TIME(start_time) 
             AND TIME(%s) <= TIME(end_time)",
            $provider_id,
            $day_of_week,
            $start_time,
            $end_time
        )
    );
    
    if ($working_hours == 0) {
        return false; // Le prestataire ne travaille pas à ce moment
    }
    
    // Vérifier s'il n'y a pas de rendez-vous qui chevauche
    $overlapping_appointments = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}calendrier_rdv_appointments 
             WHERE provider_id = %d 
             AND DATE(appointment_date) = %s 
             AND (
                 (TIME(appointment_date) <= %s AND TIME(DATE_ADD(appointment_date, INTERVAL duration MINUTE)) > %s) OR
                 (TIME(appointment_date) < %s AND TIME(DATE_ADD(appointment_date, INTERVAL duration MINUTE)) >= %s) OR
                 (TIME(appointment_date) >= %s AND TIME(appointment_date) < %s)
             )",
            $provider_id,
            $appointment_date,
            $start_time,
            $start_time,
            $end_time,
            $end_time,
            $start_time,
            $end_time
        )
    );
    
    return ($overlapping_appointments == 0);
}

/**
 * Récupère les informations d'un prestataire
 *
 * @param int $provider_id ID du prestataire
 * @return object|null Informations du prestataire ou null si non trouvé
 */
function calendrier_rdv_get_provider($provider_id) {
    global $wpdb;
    
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}calendrier_rdv_providers WHERE id = %d",
            $provider_id
        )
    );
}

/**
 * Récupère les informations d'un service
 *
 * @param int $service_id ID du service
 * @return object|null Informations du service ou null si non trouvé
 */
function calendrier_rdv_get_service($service_id) {
    global $wpdb;
    
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}calendrier_rdv_services WHERE id = %d",
            $service_id
        )
    );
}

/**
 * Récupère tous les prestataires
 *
 * @param array $args Arguments optionnels (service_id, active)
 * @return array Liste des prestataires
 */
function calendrier_rdv_get_providers($args = array()) {
    global $wpdb;
    
    $defaults = array(
        'service_id' => 0,
        'active' => true
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $query = "SELECT * FROM {$wpdb->prefix}calendrier_rdv_providers WHERE 1=1";
    
    if ($args['active']) {
        $query .= " AND active = 1";
    }
    
    if ($args['service_id'] > 0) {
        // Requête pour récupérer les prestataires qui offrent un service spécifique
        $query = $wpdb->prepare(
            "SELECT p.* FROM {$wpdb->prefix}calendrier_rdv_providers p
             JOIN {$wpdb->prefix}calendrier_rdv_provider_services ps ON p.id = ps.provider_id
             WHERE ps.service_id = %d AND p.active = %d",
            $args['service_id'],
            $args['active'] ? 1 : 0
        );
    }
    
    return $wpdb->get_results($query);
}

/**
 * Récupère tous les services
 *
 * @param array $args Arguments optionnels (provider_id, active)
 * @return array Liste des services
 */
function calendrier_rdv_get_services($args = array()) {
    global $wpdb;
    
    $defaults = array(
        'provider_id' => 0,
        'active' => true
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $query = "SELECT * FROM {$wpdb->prefix}calendrier_rdv_services WHERE 1=1";
    
    if ($args['active']) {
        $query .= " AND active = 1";
    }
    
    if ($args['provider_id'] > 0) {
        // Requête pour récupérer les services offerts par un prestataire spécifique
        $query = $wpdb->prepare(
            "SELECT s.* FROM {$wpdb->prefix}calendrier_rdv_services s
             JOIN {$wpdb->prefix}calendrier_rdv_provider_services ps ON s.id = ps.service_id
             WHERE ps.provider_id = %d AND s.active = %d",
            $args['provider_id'],
            $args['active'] ? 1 : 0
        );
    }
    
    return $wpdb->get_results($query);
}
