<?php
/**
 * Fonctions d'aide pour la gestion des fuseaux horaires
 *
 * @package Calendrier_RDV
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Récupère le fuseau horaire du site
 *
 * @return DateTimeZone Objet DateTimeZone
 */
function calendrier_rdv_get_timezone() {
    $timezone_string = get_option('timezone_string');
    
    if (!empty($timezone_string)) {
        return new DateTimeZone($timezone_string);
    }
    
    $offset = (float) get_option('gmt_offset');
    $hours = (int) $offset;
    $minutes = ($offset - $hours) * 60;
    $sign = ($offset >= 0) ? '+' : '-';
    $abs_hour = abs($hours);
    $abs_min = abs($minutes);
    
    $tz_string = sprintf('%s%02d%02d', $sign, $abs_hour, $abs_min);
    
    return new DateTimeZone($tz_string);
}

/**
 * Convertit une date/heure d'un fuseau horaire à un autre
 *
 * @param string $date_string Date/heure à convertir
 * @param string $from_tz Fuseau horaire source (par défaut: UTC)
 * @param string $to_tz Fuseau horaire de destination (par défaut: fuseau du site)
 * @param string $format Format de sortie (par défaut: 'Y-m-d H:i:s')
 * @return string Date/heure convertie
 */
function calendrier_rdv_convert_timezone($date_string, $from_tz = 'UTC', $to_tz = '', $format = 'Y-m-d H:i:s') {
    if (empty($date_string)) {
        return '';
    }
    
    if (empty($to_tz)) {
        $to_tz = calendrier_rdv_get_timezone()->getName();
    }
    
    try {
        $date = new DateTime($date_string, new DateTimeZone($from_tz));
        $date->setTimezone(new DateTimeZone($to_tz));
        return $date->format($format);
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Erreur de conversion de fuseau horaire : ' . $e->getMessage());
        }
        return $date_string;
    }
}

/**
 * Formate une date/heure selon le format du site
 *
 * @param string $date_string Date/heure à formater
 * @param string $format Format de sortie (vide pour utiliser le format par défaut)
 * @param string $timezone Fuseau horaire (par défaut: fuseau du site)
 * @return string Date/heure formatée
 */
function calendrier_rdv_format_datetime($date_string, $format = '', $timezone = '') {
    if (empty($date_string)) {
        return '';
    }
    
    if (empty($format)) {
        $format = get_option('date_format') . ' ' . get_option('time_format');
    }
    
    if (empty($timezone)) {
        $timezone = calendrier_rdv_get_timezone()->getName();
    }
    
    try {
        $date = new DateTime($date_string, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($timezone));
        return $date->format($format);
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Erreur de formatage de date : ' . $e->getMessage());
        }
        return $date_string;
    }
}

/**
 * Récupère la liste des fuseaux horaires disponibles
 *
 * @return array Tableau des fuseaux horaires au format 'identifiant' => 'Nom formaté'
 */
function calendrier_rdv_get_timezones() {
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    $timezone_offsets = [];
    
    foreach ($timezones as $timezone) {
        try {
            $tz = new DateTimeZone($timezone);
            $offset = $tz->getOffset(new DateTime()) / 3600; // en heures
            $timezone_offsets[$timezone] = $offset;
        } catch (Exception $e) {
            continue;
        }
    }
    
    // Trier par décalage
    asort($timezone_offsets);
    
    // Formater pour l'affichage
    $formatted_timezones = [];
    
    foreach ($timezone_offsets as $timezone => $offset) {
        $offset_prefix = $offset < 0 ? '-' : '+';
        $offset_formatted = $offset_prefix . str_pad(abs($offset), 2, '0', STR_PAD_LEFT) . ':00';
        $timezone_name = str_replace(['_', '/'], [' ', ' / '], $timezone);
        $formatted_timezones[$timezone] = sprintf('(UTC%1$s) %2$s', $offset_formatted, $timezone_name);
    }
    
    return $formatted_timezones;
}

/**
 * Récupère le décalage horaire actuel par rapport à UTC
 *
 * @param string $timezone Fuseau horaire (par défaut: fuseau du site)
 * @return string Décalage horaire au format ±HH:MM
 */
function calendrier_rdv_get_timezone_offset($timezone = '') {
    if (empty($timezone)) {
        $timezone = calendrier_rdv_get_timezone()->getName();
    }
    
    try {
        $time = new DateTime('now', new DateTimeZone($timezone));
        $offset = $time->format('P'); // Format: ±HH:MM
        return $offset;
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Erreur de récupération du décalage horaire : ' . $e->getMessage());
        }
        return '+00:00';
    }
}

/**
 * Convertit une date/heure en timestamp Unix
 *
 * @param string $date_string Date/heure à convertir
 * @param string $timezone Fuseau horaire de la date source (par défaut: UTC)
 * @return int Timestamp Unix ou 0 en cas d'erreur
 */
function calendrier_rdv_datetime_to_timestamp($date_string, $timezone = 'UTC') {
    if (empty($date_string)) {
        return 0;
    }
    
    try {
        $date = new DateTime($date_string, new DateTimeZone($timezone));
        return $date->getTimestamp();
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Erreur de conversion en timestamp : ' . $e->getMessage());
        }
        return 0;
    }
}

/**
 * Vérifie si une date est dans le futur
 *
 * @param string $date_string Date à vérifier
 * @param string $timezone Fuseau horaire de la date (par défaut: fuseau du site)
 * @return bool True si la date est dans le futur, false sinon
 */
function calendrier_rdv_is_future_date($date_string, $timezone = '') {
    if (empty($date_string)) {
        return false;
    }
    
    if (empty($timezone)) {
        $timezone = calendrier_rdv_get_timezone()->getName();
    }
    
    try {
        $date = new DateTime($date_string, new DateTimeZone($timezone));
        $now = new DateTime('now', new DateTimeZone($timezone));
        return $date > $now;
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Erreur de vérification de date future : ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Calcule la différence entre deux dates
 *
 * @param string $start_date Date de début
 * @param string $end_date Date de fin
 * @param string $unit Unité de retour (days, hours, minutes, seconds)
 * @param string $timezone Fuseau horaire (par défaut: fuseau du site)
 * @return int|float Différence dans l'unité spécifiée
 */
function calendrier_rdv_date_diff($start_date, $end_date, $unit = 'minutes', $timezone = '') {
    if (empty($start_date) || empty($end_date)) {
        return 0;
    }
    
    if (empty($timezone)) {
        $timezone = calendrier_rdv_get_timezone()->getName();
    }
    
    try {
        $start = new DateTime($start_date, new DateTimeZone($timezone));
        $end = new DateTime($end_date, new DateTimeZone($timezone));
        $diff = $start->diff($end);
        
        switch ($unit) {
            case 'days':
                return (int) $diff->format('%r%a');
                
            case 'hours':
                return $diff->days * 24 + $diff->h + $diff->i / 60 + $diff->s / 3600;
                
            case 'minutes':
                return $diff->days * 24 * 60 + $diff->h * 60 + $diff->i + $diff->s / 60;
                
            case 'seconds':
                return $diff->days * 24 * 3600 + $diff->h * 3600 + $diff->i * 60 + $diff->s;
                
            default:
                return $diff->days;
        }
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Erreur de calcul de différence de dates : ' . $e->getMessage());
        }
        return 0;
    }
}

/**
 * Ajoute un intervalle à une date
 *
 * @param string $date_string Date de base
 * @param int $value Valeur à ajouter
 * @param string $unit Unité (years, months, days, hours, minutes, seconds)
 * @param string $timezone Fuseau horaire (par défaut: fuseau du site)
 * @return string Nouvelle date au format Y-m-d H:i:s
 */
function calendrier_rdv_date_add($date_string, $value, $unit = 'days', $timezone = '') {
    if (empty($date_string)) {
        return '';
    }
    
    if (empty($timezone)) {
        $timezone = calendrier_rdv_get_timezone()->getName();
    }
    
    try {
        $date = new DateTime($date_string, new DateTimeZone($timezone));
        $interval_spec = 'P'; // Period
        
        switch ($unit) {
            case 'years':
                $interval_spec .= absint($value) . 'Y';
                break;
                
            case 'months':
                $interval_spec .= absint($value) . 'M';
                break;
                
            case 'days':
                $interval_spec .= absint($value) . 'D';
                break;
                
            case 'hours':
            case 'minutes':
            case 'seconds':
                $interval_spec = 'PT'; // Period Time
                $interval_spec .= absint($value) . strtoupper(substr($unit, 0, 1));
                break;
                
            default:
                $interval_spec .= absint($value) . 'D';
        }
        
        $interval = new DateInterval($interval_spec);
        
        if ($value < 0) {
            $interval->invert = 1;
        }
        
        $date->add($interval);
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Erreur d\'ajout de durée à une date : ' . $e->getMessage());
        }
        return $date_string;
    }
}

/**
 * Vérifie si une date est un jour de week-end
 *
 * @param string $date_string Date à vérifier
 * @param array $weekend_days Jours de week-end (0=dimanche, 6=samedi)
 * @param string $timezone Fuseau horaire (par défaut: fuseau du site)
 * @return bool True si c'est un jour de week-end, false sinon
 */
function calendrier_rdv_is_weekend($date_string, $weekend_days = [0, 6], $timezone = '') {
    if (empty($date_string)) {
        return false;
    }
    
    if (empty($weekend_days) || !is_array($weekend_days)) {
        $weekend_days = [0, 6]; // Dimanche et samedi par défaut
    }
    
    if (empty($timezone)) {
        $timezone = calendrier_rdv_get_timezone()->getName();
    }
    
    try {
        $date = new DateTime($date_string, new DateTimeZone($timezone));
        $day_of_week = (int) $date->format('w'); // 0 (dimanche) à 6 (samedi)
        return in_array($day_of_week, $weekend_days, true);
    } catch (Exception $e) {
        if (WP_DEBUG) {
            error_log('Erreur de vérification de jour de week-end : ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Formate une durée en minutes en format lisible
 *
 * @param int $minutes Durée en minutes
 * @return string Durée formatée (ex: "2h 30min")
 */
function calendrier_rdv_format_duration($minutes) {
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    $parts = [];
    
    if ($hours > 0) {
        $parts[] = sprintf(_n('%d heure', '%d heures', $hours, 'calendrier-rdv'), $hours);
    }
    
    if ($remaining_minutes > 0) {
        $parts[] = sprintf(_n('%d minute', '%d minutes', $remaining_minutes, 'calendrier-rdv'), $remaining_minutes);
    }
    
    if (empty($parts)) {
        return '0 ' . __('minutes', 'calendrier-rdv');
    }
    
    return implode(' ', $parts);
}
