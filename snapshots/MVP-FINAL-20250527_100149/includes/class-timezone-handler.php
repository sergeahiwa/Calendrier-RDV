<?php
/**
 * Gestion des fuseaux horaires et de la capacité des créneaux
 */
class Calendrier_RDV_Timezone_Handler {
    private $timezone;
    private $timezone_string;
    private $timezone_offset;
    
    public function __construct() {
        $this->init_timezone();
        $this->init_hooks();
    }
    
    private function init_timezone() {
        // Récupérer le fuseau horaire du site
        $this->timezone_string = get_option('timezone_string');
        $this->timezone_offset = get_option('gmt_offset');
        
        // Définir le fuseau horaire par défaut
        if ($this->timezone_string) {
            $this->timezone = new DateTimeZone($this->timezone_string);
        } else {
            // Utiliser le décalage horaire si le fuseau n'est pas défini
            $this->timezone = new DateTimeZone($this->get_timezone_string_from_offset($this->timezone_offset));
        }
        
        // Définir le fuseau horaire par défaut pour toutes les fonctions de date/heure
        date_default_timezone_set($this->timezone->getName());
    }
    
    private function init_hooks() {
        // Filtrer les créneaux disponibles en fonction de la capacité
        add_filter('calendrier_rdv_available_slots', [$this, 'filter_available_slots_by_capacity'], 10, 3);
        
        // Convertir les dates/heures dans le fuseau horaire du site
        add_filter('calendrier_rdv_format_datetime', [$this, 'format_datetime_for_display'], 10, 3);
        
        // Convertir les dates/heures en UTC pour le stockage
        add_filter('calendrier_rdv_save_datetime', [$this, 'convert_to_utc'], 10, 2);
    }
    
    /**
     * Convertit un décalage horaire en chaîne de fuseau horaire
     */
    private function get_timezone_string_from_offset($offset) {
        $offset = (float) $offset;
        $hours = (int) $offset;
        $minutes = ($offset - $hours) * 60;
        $sign = ($offset >= 0) ? '+' : '-';
        $abs_hour = abs($hours);
        $abs_min = abs($minutes);
        
        return sprintf('%s%02d%02d', $sign, $abs_hour, $abs_min);
    }
    
    /**
     * Filtre les créneaux disponibles en fonction de la capacité
     */
    public function filter_available_slots_by_capacity($slots, $service_id, $date) {
        global $wpdb;
        
        if (empty($slots)) {
            return $slots;
        }
        
        // Récupérer la capacité maximale pour ce service
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT max_capacity, duration FROM {$wpdb->prefix}rdv_services WHERE id = %d",
            $service_id
        ));
        
        if (!$service || !$service->max_capacity) {
            return $slots; // Pas de limite de capacité
        }
        
        // Récupérer le nombre de réservations pour chaque créneau
        $booked_slots = $this->get_booked_slots_count($service_id, $date);
        
        // Filtrer les créneaux complets
        $available_slots = [];
        
        foreach ($slots as $slot) {
            $slot_key = $slot['start_time'] . '-' . $slot['end_time'];
            $booked_count = isset($booked_slots[$slot_key]) ? $booked_slots[$slot_key] : 0;
            
            if ($booked_count < $service->max_capacity) {
                $slot['available_capacity'] = $service->max_capacity - $booked_count;
                $available_slots[] = $slot;
            }
        }
        
        return $available_slots;
    }
    
    /**
     * Récupère le nombre de réservations pour chaque créneau horaire
     */
    private function get_booked_slots_count($service_id, $date) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                TIME_FORMAT(heure_debut, '%H:%i') as start_time,
                TIME_FORMAT(ADDTIME(heure_debut, SEC_TO_TIME(duree * 60)), '%H:%i') as end_time,
                COUNT(*) as booked_count
            FROM {$wpdb->prefix}rdv_booking
            WHERE service_id = %d
            AND date_rdv = %s
            AND status NOT IN ('cancelled', 'rejected')
            GROUP BY start_time, end_time",
            $service_id,
            $date
        ), ARRAY_A);
        
        $booked_slots = [];
        
        foreach ($results as $row) {
            $slot_key = $row['start_time'] . '-' . $row['end_time'];
            $booked_slots[$slot_key] = (int) $row['booked_count'];
        }
        
        return $booked_slots;
    }
    
    /**
     * Formate une date/heure pour l'affichage
     */
    public function format_datetime_for_display($datetime, $format = 'datetime', $timezone = null) {
        if (empty($datetime)) {
            return '';
        }
        
        $timezone = $timezone ? new DateTimeZone($timezone) : $this->timezone;
        
        try {
            $date = new DateTime($datetime, new DateTimeZone('UTC'));
            $date->setTimezone($timezone);
            
            switch ($format) {
                case 'date':
                    return $date->format(get_option('date_format'));
                    
                case 'time':
                    return $date->format(get_option('time_format'));
                    
                case 'datetime':
                default:
                    return $date->format(get_option('date_format') . ' ' . get_option('time_format'));
            }
        } catch (Exception $e) {
            return $datetime; // En cas d'erreur, retourner la valeur d'origine
        }
    }
    
    /**
     * Convertit une date/heure en UTC pour le stockage
     */
    public function convert_to_utc($datetime, $format = 'Y-m-d H:i:s') {
        if (empty($datetime)) {
            return '';
        }
        
        try {
            $date = new DateTime($datetime, $this->timezone);
            $date->setTimezone(new DateTimeZone('UTC'));
            return $date->format($format);
        } catch (Exception $e) {
            return $datetime; // En cas d'erreur, retourner la valeur d'origine
        }
    }
    
    /**
     * Récupère la liste des fuseaux horaires disponibles
     */
    public static function get_timezones() {
        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $timezone_offsets = [];
        
        foreach ($timezones as $timezone) {
            $tz = new DateTimeZone($timezone);
            $offset = $tz->getOffset(new DateTime()) / 3600; // En heures
            $timezone_offsets[$timezone] = $offset;
        }
        
        // Trier par décalage
        asort($timezone_offsets);
        
        // Générer un tableau formaté pour les options de sélection
        $timezone_list = [];
        
        foreach ($timezone_offsets as $timezone => $offset) {
            $offset_prefix = $offset < 0 ? '-' : '+';
            $offset_formatted = $offset_prefix . str_pad(abs($offset), 2, '0', STR_PAD_LEFT) . ':00';
            $timezone_list[$timezone] = '(UTC' . $offset_formatted . ') ' . str_replace('_', ' ', $timezone);
        }
        
        return $timezone_list;
    }
    
    /**
     * Récupère le fuseau horaire actuel du site
     */
    public function get_current_timezone() {
        return [
            'timezone' => $this->timezone->getName(),
            'timezone_string' => $this->timezone_string,
            'gmt_offset' => $this->timezone_offset,
        ];
    }
}

// Initialiser le gestionnaire de fuseaux horaires
function calendrier_rdv_init_timezone_handler() {
    new Calendrier_RDV_Timezone_Handler();
}
add_action('plugins_loaded', 'calendrier_rdv_init_timezone_handler');
