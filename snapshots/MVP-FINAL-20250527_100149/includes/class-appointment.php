<?php
/**
 * Gestion des rendez-vous
 *
 * @package Calendrier_RDV
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe gérant les rendez-vous
 */
class CalRdv_Appointment {
    /**
     * ID du rendez-vous
     *
     * @var int
     */
    public $id = 0;

    /**
     * ID du client
     *
     * @var int
     */
    public $client_id = 0;

    /**
     * ID du prestataire
     *
     * @var int
     */
    public $provider_id = 0;

    /**
     * ID du service
     *
     * @var int
     */
    public $service_id = 0;

    /**
     * Date et heure de début du rendez-vous
     *
     * @var DateTime
     */
    public $start_datetime;

    /**
     * Date et heure de fin du rendez-vous
     *
     * @var DateTime
     */
    public $end_datetime;

    /**
     * Statut du rendez-vous
     *
     * @var string
     */
    public $status = 'scheduled'; // scheduled, confirmed, cancelled, completed, no_show

    /**
     * Notes additionnelles
     *
     * @var string
     */
    public $notes = '';

    /**
     * Date de création
     *
     * @var DateTime
     */
    public $created_at;

    /**
     * Date de mise à jour
     *
     * @var DateTime
     */
    public $updated_at;

    /**
     * Constructeur
     *
     * @param int|array $appointment ID du rendez-vous ou données
     */
    public function __construct($appointment = 0) {
        if (is_numeric($appointment) && $appointment > 0) {
            $this->get($appointment);
        } elseif (is_array($appointment)) {
            $this->set_props($appointment);
        }
    }

    /**
     * Récupère un rendez-vous par son ID
     *
     * @param int $appointment_id ID du rendez-vous
     * @return bool True si trouvé, false sinon
     */
    public function get($appointment_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rdv_appointments';
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $appointment_id),
            ARRAY_A
        );

        if ($appointment) {
            $this->set_props($appointment);
            return true;
        }

        return false;
    }

    /**
     * Définit les propriétés de l'objet
     *
     * @param array $props Propriétés à définir
     */
    protected function set_props($props) {
        $props = (array) $props;
        
        // Définir les propriétés
        foreach ($props as $key => $value) {
            if (property_exists($this, $key)) {
                // Convertir les dates en objets DateTime
                if (in_array($key, ['start_datetime', 'end_datetime', 'created_at', 'updated_at']) && !empty($value)) {
                    try {
                        $this->$key = new DateTime($value);
                    } catch (Exception $e) {
                        $this->$key = null;
                    }
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Enregistre le rendez-vous en base de données
     *
     * @return int|false ID du rendez-vous ou false en cas d'erreur
     */
    public function save() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rdv_appointments';
        $now = current_time('mysql');
        
        // Préparer les données
        $data = [
            'client_id'    => $this->client_id,
            'provider_id'  => $this->provider_id,
            'service_id'   => $this->service_id,
            'status'       => $this->status,
            'notes'        => $this->notes,
            'updated_at'   => $now,
        ];
        
        // Formater les dates
        $date_fields = ['start_datetime', 'end_datetime'];
        foreach ($date_fields as $field) {
            if ($this->$field instanceof DateTime) {
                $data[$field] = $this->$field->format('Y-m-d H:i:s');
            } elseif (!empty($this->$field)) {
                $data[$field] = $this->$field;
            } else {
                $data[$field] = null;
            }
        }
        
        // Insertion ou mise à jour
        if ($this->id > 0) {
            // Mise à jour
            $result = $wpdb->update(
                $table_name,
                $data,
                ['id' => $this->id],
                ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            
            return $result !== false ? $this->id : false;
        } else {
            // Nouvelle entrée
            $data['created_at'] = $now;
            
            $result = $wpdb->insert(
                $table_name,
                $data,
                ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            
            if ($result) {
                $this->id = $wpdb->insert_id;
                return $this->id;
            }
            
            return false;
        }
    }

    /**
     * Supprime le rendez-vous
     *
     * @return bool True si réussi, false sinon
     */
    public function delete() {
        if (!$this->id) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rdv_appointments';
        
        $result = $wpdb->delete(
            $table_name,
            ['id' => $this->id],
            ['%d']
        );
        
        if ($result) {
            $this->id = 0;
            return true;
        }
        
        return false;
    }

    /**
     * Vérifie si le créneau est disponible
     *
     * @param int $provider_id ID du prestataire
     * @param DateTime $start_date Date et heure de début
     * @param DateTime $end_date Date et heure de fin
     * @param int $exclude_appointment_id ID du rendez-vous à exclure (pour les mises à jour)
     * @return bool True si disponible, false sinon
     */
    public static function is_slot_available($provider_id, $start_date, $end_date, $exclude_appointment_id = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rdv_appointments';
        $start = $start_date->format('Y-m-d H:i:s');
        $end = $end_date->format('Y-m-d H:i:s');
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE provider_id = %d 
            AND status NOT IN ('cancelled', 'no_show')
            AND (
                (start_datetime < %s AND end_datetime > %s) OR
                (start_datetime < %s AND end_datetime > %s) OR
                (start_datetime >= %s AND end_datetime <= %s)
            )
            AND id != %d",
            $provider_id,
            $end, $start,
            $start, $end,
            $start, $end,
            intval($exclude_appointment_id)
        );
        
        $count = (int) $wpdb->get_var($query);
        
        return $count === 0;
    }

    /**
     * Récupère les rendez-vous selon des critères
     *
     * @param array $args Arguments de recherche
     * @return array Tableau d'objets CalRdv_Appointment
     */
    public static function get_appointments($args = []) {
        global $wpdb;
        
        $defaults = [
            'provider_id' => 0,
            'client_id'   => 0,
            'service_id'  => 0,
            'status'      => '',
            'start_date'  => '',
            'end_date'    => '',
            'orderby'     => 'start_datetime',
            'order'       => 'ASC',
            'number'      => -1,
            'offset'      => 0,
        ];
        
        $args = wp_parse_args($args, $defaults);
        $where = ['1=1'];
        $values = [];
        
        // Construire la requête
        if ($args['provider_id']) {
            $where[] = 'provider_id = %d';
            $values[] = $args['provider_id'];
        }
        
        if ($args['client_id']) {
            $where[] = 'client_id = %d';
            $values[] = $args['client_id'];
        }
        
        if ($args['service_id']) {
            $where[] = 'service_id = %d';
            $values[] = $args['service_id'];
        }
        
        if ($args['status']) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where[] = "status IN ($placeholders)";
                $values = array_merge($values, $args['status']);
            } else {
                $where[] = 'status = %s';
                $values[] = $args['status'];
            }
        }
        
        if ($args['start_date']) {
            $where[] = 'start_datetime >= %s';
            $values[] = $args['start_date'];
        }
        
        if ($args['end_date']) {
            $where[] = 'end_datetime <= %s';
            $values[] = $args['end_date'];
        }
        
        // Construire la requête SQL
        $table_name = $wpdb->prefix . 'rdv_appointments';
        $query = "SELECT * FROM $table_name WHERE " . implode(' AND ', $where);
        
        // Ajouter le tri
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $orderby = in_array($args['orderby'], ['id', 'client_id', 'provider_id', 'service_id', 'start_datetime', 'end_datetime', 'status', 'created_at']) ? $args['orderby'] : 'start_datetime';
        $query .= " ORDER BY $orderby $order";
        
        // Ajouter la pagination
        if ($args['number'] > 0) {
            $query .= ' LIMIT %d';
            $values[] = $args['number'];
            
            if ($args['offset'] > 0) {
                $query .= ' OFFSET %d';
                $values[] = $args['offset'];
            }
        }
        
        // Préparer et exécuter la requête
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        $results = $wpdb->get_results($query);
        $appointments = [];
        
        foreach ($results as $result) {
            $appointments[] = new self($result);
        }
        
        return $appointments;
    }
}
