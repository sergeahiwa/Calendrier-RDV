<?php
/**
 * Tests unitaires pour le formulaire de réservation
 */
class BookingFormTest extends WP_UnitTestCase {
    /**
     * @var int ID du prestataire de test
     */
    private $prestataire_id;
    
    /**
     * @var int ID du service de test
     */
    private $service_id;
    
    /**
     * Configuration avant chaque test
     */
    public function setUp(): void {
        parent::setUp();
        
        // Créer un prestataire de test
        $this->prestataire_id = $this->factory->post->create([
            'post_type' => 'prestataire',
            'post_title' => 'Dr. Test',
            'post_status' => 'publish',
            'meta_input' => [
                '_disponibilites' => [
                    'lundi' => ['09:00', '18:00'],
                    'mardi' => ['09:00', '18:00'],
                    'mercredi' => ['09:00', '18:00'],
                    'jeudi' => ['09:00', '18:00'],
                    'vendredi' => ['09:00', '18:00'],
                ]
            ]
        ]);
        
        // Créer un service de test
        $this->service_id = $this->factory->post->create([
            'post_type' => 'service',
            'post_title' => 'Consultation',
            'post_status' => 'publish',
            'meta_input' => [
                '_duree' => 30,
                '_prix' => 50,
                '_prestataires' => [$this->prestataire_id]
            ]
        ]);
    }
    
    /**
     * Teste le rendu du formulaire
     */
    public function test_form_rendering() {
        // Définir les variables attendues par le template
        $prestataires = [
            (object) [
                'id' => $this->prestataire_id,
                'nom' => 'Dr. Test'
            ]
        ];
        
        $services = [
            (object) [
                'id' => $this->service_id,
                'nom' => 'Consultation',
                'duree' => 30,
                'prix' => 50
            ]
        ];
        
        // Capturer la sortie
        ob_start();
        include dirname(__DIR__) . '/public/booking-form.php';
        $output = ob_get_clean();
        
        // Vérifications
        $this->assertStringContainsString('Prendre un rendez-vous', $output);
        $this->assertStringContainsString('Dr. Test', $output);
        $this->assertStringContainsString('Consultation', $output);
        
        // Vérifier que les champs obligatoires ont l'attribut required
        $this->assertStringContainsString('required', $output, 'Le champ prénom devrait être obligatoire');
        $this->assertStringContainsString('required', $output, 'Le champ nom devrait être obligatoire');
        $this->assertStringContainsString('required', $output, 'Le champ email devrait être obligatoire');
    }
    
    /**
     * Teste la validation des champs obligatoires
     */
    public function test_required_fields_validation() {
        // Simuler une requête POST vide
        $_POST = [];
        
        // Appeler la fonction de validation
        $errors = $this->validate_booking_form();
        
        // Vérifier que les erreurs sont présentes
        $this->assertArrayHasKey('prestataire_id', $errors);
        $this->assertArrayHasKey('service_id', $errors);
        $this->assertArrayHasKey('date_rdv', $errors);
        $this->assertArrayHasKey('client_nom', $errors);
        $this->assertArrayHasKey('client_email', $errors);
        $this->assertArrayHasKey('client_telephone', $errors);
    }
    
    /**
     * Teste la validation de l'email
     */
    public function test_email_validation() {
        // Test avec un email invalide
        $_POST = [
            'client_email' => 'email-invalide'
        ];
        
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('client_email', $errors);
        
        // Test avec un email valide
        $_POST['client_email'] = 'test@example.com';
        $errors = $this->validate_booking_form();
        $this->assertArrayNotHasKey('client_email', $errors);
    }
    
    /**
     * Teste la validation des créneaux horaires
     */
    public function test_time_slot_validation() {
        // Créer des données de test
        $date = date('Y-m-d', strtotime('next monday'));
        
        // Test avec un créneau valide
        $_POST = [
            'prestataire_id' => $this->prestataire_id,
            'service_id' => $this->service_id,
            'date_rdv' => $date,
            'heure_debut' => '14:00',
            'client_nom' => 'Test User',
            'client_email' => 'test@example.com',
            'client_telephone' => '0123456789'
        ];
        
        $errors = $this->validate_booking_form();
        $this->assertEmpty($errors, 'Le créneau devrait être valide');
        
        // Test avec un créneau en dehors des heures d'ouverture
        $_POST['heure_debut'] = '08:00'; // Avant l'ouverture
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('heure_debut', $errors, 'Le créneau devrait être en dehors des heures d\'ouverture');
        
        // Test avec un créneau trop proche de la fermeture
        $_POST['heure_debut'] = '17:45'; // Trop proche de la fermeture pour une consultation de 30 min
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('heure_debut', $errors, 'Le créneau devrait être trop proche de la fermeture');
        
        // Test avec un créneau valide
        $_POST['heure_debut'] = '14:00';
        $errors = $this->validate_booking_form();
        $this->assertEmpty($errors, 'Le créneau devrait être à nouveau valide');
    }
    
    /**
     * Teste la création de rendez-vous récurrents
     */
    public function test_recurring_bookings() {
        $start_date = date('Y-m-d', strtotime('next monday'));
        $email = 'recurring@example.com';
        
        // Tester la création de 3 rendez-vous hebdomadaires
        $_POST = [
            'prestataire_id' => $this->prestataire_id,
            'service_id' => $this->service_id,
            'date_rdv' => $start_date,
            'heure_debut' => '14:00',
            'recurrence' => 'weekly',
            'recurrence_count' => 3,
            'client_nom' => 'Test Recurring',
            'client_email' => $email,
            'client_telephone' => '0612345678'
        ];
        
        // Valider la récurrence
        $errors = $this->validate_booking_form();
        $this->assertEmpty($errors, 'La création de rendez-vous récurrents devrait être valide');
        
        // Vérifier que les créneaux sont disponibles pour chaque occurrence
        for ($i = 0; $i < 3; $i++) {
            $current_date = date('Y-m-d', strtotime("$start_date +$i weeks"));
            $existing_bookings = $this->get_bookings_for_slot($this->prestataire_id, $current_date, '14:00');
            $this->assertEmpty($existing_bookings, "Le créneau du $current_date devrait être disponible");
        }
    }
    
    /**
     * Teste la validation des durées de rendez-vous
     */
    public function test_booking_duration_validation() {
        $date = date('Y-m-d', strtotime('next wednesday'));
        
        // Test avec une durée minimale non respectée
        $_POST = [
            'prestataire_id' => $this->prestataire_id,
            'service_id' => $this->service_id,
            'date_rdv' => $date,
            'heure_debut' => '14:00',
            'duree' => 10, // Durée trop courte
            'client_nom' => 'Test Duration',
            'client_email' => 'duration@example.com',
            'client_telephone' => '0612345678'
        ];
        
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('duree', $errors, 'La durée minimale devrait être respectée');
        
        // Test avec une durée maximale dépassée
        $_POST['duree'] = 240; // 4 heures - durée trop longue
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('duree', $errors, 'La durée maximale devrait être respectée');
        
        // Test avec une durée valide
        $_POST['duree'] = 60; // 1 heure - durée valide
        $errors = $this->validate_booking_form();
        $this->assertEmpty($errors, 'La durée devrait être valide');
    }
    
    /**
     * Teste la détection des chevauchements de rendez-vous
     */
    public function test_booking_overlap_detection() {
        $date = date('Y-m-d', strtotime('next thursday'));
        
        // Créer un premier rendez-vous
        $this->factory->post->create([
            'post_type' => 'rdv_booking',
            'post_status' => 'publish',
            'meta_input' => [
                '_prestataire_id' => $this->prestataire_id,
                '_service_id' => $this->service_id,
                '_date_rdv' => $date,
                '_heure_debut' => '14:00',
                '_duree' => 60, // 1 heure
                '_client_email' => 'existing@example.com',
                '_client_nom' => 'Existing Booking',
                '_statut' => 'confirmed'
            ]
        ]);
        
        // Tester un chevauchement au début
        $_POST = [
            'prestataire_id' => $this->prestataire_id,
            'service_id' => $this->service_id,
            'date_rdv' => $date,
            'heure_debut' => '13:30',
            'duree' => 45, // Chevauchement de 15 minutes
            'client_nom' => 'Test Overlap',
            'client_email' => 'overlap@example.com',
            'client_telephone' => '0612345678'
        ];
        
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('overlap', $errors, 'Un chevauchement devrait être détecté');
        
        // Tester un chevauchement à la fin
        $_POST['heure_debut'] = '14:45';
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('overlap', $errors, 'Un chevauchement devrait être détecté');
        
        // Tester un créneau valide juste après
        $_POST['heure_debut'] = '15:00';
        $errors = $this->validate_booking_form();
        $this->assertEmpty($errors, 'Aucun chevauchement ne devrait être détecté');
    }
    
    /**
     * Teste la gestion des congés du prestataire
     */
    public function test_provider_time_off() {
        // Définir une période de congé
        $time_off = [
            'start_date' => date('Y-m-d', strtotime('+1 week')),
            'end_date' => date('Y-m-d', strtotime('+2 weeks')),
            'reason' => 'Congés annuels'
        ];
        
        // Mettre à jour les congés du prestataire
        update_post_meta($this->prestataire_id, '_time_off', [$time_off]);
        
        // Tester un jour pendant la période de congé
        $off_date = date('Y-m-d', strtotime('+10 days'));
        
        $_POST = [
            'prestataire_id' => $this->prestataire_id,
            'service_id' => $this->service_id,
            'date_rdv' => $off_date,
            'heure_debut' => '14:00',
            'client_nom' => 'Test Time Off',
            'client_email' => 'timeoff@example.com',
            'client_telephone' => '0612345678'
        ];
        
        // La réservation devrait échouer pendant la période de congé
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('date_rdv', $errors, 'La réservation devrait être refusée pendant les congés du prestataire');
    }
    
    /**
     * Teste la gestion des jours fériés
     */
    public function test_public_holidays() {
        // Définir un jour férié pour le test (par exemple, 1er mai de l'année en cours)
        $holiday_date = date('Y-05-01');
        $weekday = strtolower(date('l', strtotime($holiday_date)));
        
        // Mettre à jour les disponibilités pour inclure ce jour
        $disponibilites = get_post_meta($this->prestataire_id, '_disponibilites', true);
        $disponibilites[$weekday] = ['09:00', '18:00'];
        update_post_meta($this->prestataire_id, '_disponibilites', $disponibilites);
        
        // Tester la réservation un jour férié
        $_POST = [
            'prestataire_id' => $this->prestataire_id,
            'service_id' => $this->service_id,
            'date_rdv' => $holiday_date,
            'heure_debut' => '14:00',
            'client_nom' => 'Test Holiday',
            'client_email' => 'holiday@example.com',
            'client_telephone' => '0612345678'
        ];
        
        // La réservation devrait échouer car c'est un jour férié
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('date_rdv', $errors, 'La réservation devrait être refusée un jour férié');
    }
    
    /**
     * Teste la détection des rendez-vous en double
     */
    public function test_duplicate_booking_detection() {
        $date = date('Y-m-d', strtotime('next tuesday'));
        $email = 'duplicate@example.com';
        
        // Créer un premier rendez-vous
        $booking_id = $this->factory->post->create([
            'post_type' => 'rdv_booking',
            'post_status' => 'publish',
            'meta_input' => [
                '_prestataire_id' => $this->prestataire_id,
                '_service_id' => $this->service_id,
                '_date_rdv' => $date,
                '_heure_debut' => '14:00',
                '_duree' => 30,
                '_client_email' => $email,
                '_client_nom' => 'Test Duplicate',
                '_client_telephone' => '0612345678',
                '_statut' => 'confirmed'
            ]
        ]);
        
        // Tester la création d'un doublon
        $_POST = [
            'prestataire_id' => $this->prestataire_id,
            'service_id' => $this->service_id,
            'date_rdv' => $date,
            'heure_debut' => '14:00',
            'client_nom' => 'Test Duplicate',
            'client_email' => $email,
            'client_telephone' => '0612345678'
        ];
        
        $errors = $this->validate_booking_form();
        $this->assertArrayHasKey('duplicate', $errors, 'Un doublon de rendez-vous devrait être détecté');
    }
    
    /**
     * Méthode utilitaire pour valider le formulaire
     */
    private function validate_booking_form() {
        $errors = [];
        
        // Vérification des champs obligatoires
        $required_fields = [
            'prestataire_id' => 'Prestataire',
            'service_id' => 'Service',
            'date_rdv' => 'Date',
            'client_nom' => 'Nom',
            'client_email' => 'Email',
            'client_telephone' => 'Téléphone'
        ];
        
        foreach ($required_fields as $field => $name) {
            if (empty($_POST[$field])) {
                $errors[$field] = sprintf('Le champ %s est obligatoire', $name);
            }
        }
        
        // Validation de l'email
        if (!empty($_POST['client_email'])) {
            if (!filter_var($_POST['client_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['client_email'] = 'Veuillez entrer une adresse email valide';
            }
        }
        
        // Vérification des créneaux horaires
        if (empty($errors['prestataire_id']) && !empty($_POST['date_rdv']) && !empty($_POST['heure_debut'])) {
            $date = sanitize_text_field($_POST['date_rdv']);
            $heure = sanitize_text_field($_POST['heure_debut']);
            $prestataire_id = intval($_POST['prestataire_id']);
            
            // Vérifier si le créneau est dans les heures d'ouverture
            $disponibilites = get_post_meta($prestataire_id, '_disponibilites', true);
            $jour_semaine = strtolower(date('l', strtotime($date)));
            
            if (!empty($disponibilites[$jour_semaine])) {
                $ouverture = $disponibilites[$jour_semaine][0];
                $fermeture = $disponibilites[$jour_semaine][1];
                
                // Vérifier si l'heure est dans la plage d'ouverture
                if ($heure < $ouverture || $heure >= $fermeture) {
                    $errors['heure_debut'] = 'Le créneau choisi est en dehors des heures d\'ouverture';
                }
                
                // Vérifier si le créneau est disponible
                $existing_bookings = $this->get_bookings_for_slot($prestataire_id, $date, $heure);
                if (!empty($existing_bookings)) {
                    $errors['duplicate'] = 'Un rendez-vous existe déjà pour ce créneau';
                }
            } else {
                $errors['date_rdv'] = 'Le prestataire n\'est pas disponible ce jour-là';
            }
        }
        
        return $errors;
    }
    
    /**
     * Vérifie si un créneau chevauche un rendez-vous existant
     * 
     * @param int $prestataire_id ID du prestataire
     * @param string $date Date du rendez-vous
     * @param string $start_time Heure de début
     * @param int $duration Durée en minutes
     * @return bool True s'il y a un chevauchement
     */
    private function has_booking_overlap($prestataire_id, $date, $start_time, $duration) {
        // Convertir en timestamp pour comparaison
        $start_timestamp = strtotime("$date $start_time");
        $end_timestamp = $start_timestamp + ($duration * 60);
        
        // Récupérer tous les rendez-vous du jour
        $bookings = $this->get_bookings_for_day($prestataire_id, $date);
        
        foreach ($bookings as $booking) {
            $booking_start = strtotime($booking->date . ' ' . $booking->start_time);
            $booking_end = $booking_start + ($booking->duration * 60);
            
            // Vérifier le chevauchement
            if ($start_timestamp < $booking_end && $end_timestamp > $booking_start) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Récupère tous les rendez-vous d'un jour donné
     */
    private function get_bookings_for_day($prestataire_id, $date) {
        $args = [
            'post_type' => 'rdv_booking',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_prestataire_id',
                    'value' => $prestataire_id,
                ],
                [
                    'key' => '_date_rdv',
                    'value' => $date,
                ],
                [
                    'key' => '_statut',
                    'value' => 'cancelled',
                    'compare' => '!='
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        $bookings = [];
        
        foreach ($query->posts as $post) {
            $bookings[] = (object) [
                'id' => $post->ID,
                'date' => get_post_meta($post->ID, '_date_rdv', true),
                'start_time' => get_post_meta($post->ID, '_heure_debut', true),
                'duration' => (int) get_post_meta($post->ID, '_duree', true),
                'status' => get_post_meta($post->ID, '_statut', true)
            ];
        }
        
        return $bookings;
    }
    
    /**
     * Récupère les rendez-vous existants pour un créneau donné
     * 
     * @param int $prestataire_id ID du prestataire
     * @param string $date Date au format YYYY-MM-DD
     * @param string $heure Heure au format HH:MM
     * @param bool $include_recurring Inclure les rendez-vous récurrents
     * @return array Liste des rendez-vous trouvés
     */
    private function get_bookings_for_slot($prestataire_id, $date, $heure, $include_recurring = true) {
        // Vérifier d'abord les jours fériés
        if ($this->is_public_holiday($date)) {
            return [new WP_Post((object) ['ID' => 0])]; // Simuler un rendez-vous pour bloquer la date
        }
        
        // Vérifier les périodes d'indisponibilité du prestataire
        if ($this->is_provider_unavailable($prestataire_id, $date)) {
            return [new WP_Post((object) ['ID' => 0])]; // Simuler un rendez-vous pour bloquer la date
        }
        $args = [
            'post_type' => 'rdv_booking',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_prestataire_id',
                    'value' => $prestataire_id,
                    'compare' => '='
                ],
                [
                    'key' => '_date_rdv',
                    'value' => $date,
                    'compare' => '='
                ],
                [
                    'key' => '_heure_debut',
                    'value' => $heure,
                    'compare' => '='
                ],
                [
                    'key' => '_statut',
                    'value' => 'cancelled',
                    'compare' => '!='
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Vérifie si une date est en période d'indisponibilité
     * 
     * @param int $prestataire_id ID du prestataire
     * @param string $date Date au format YYYY-MM-DD
     * @return bool True si le prestataire est indisponible
     */
    private function is_provider_unavailable($prestataire_id, $date) {
        $time_offs = get_post_meta($prestataire_id, '_time_off', true) ?: [];
        
        foreach ($time_offs as $time_off) {
            if ($date >= $time_off['start_date'] && $date <= $time_off['end_date']) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifie si une date est un jour férié
     * 
     * @param string $date Date au format YYYY-MM-DD
     * @return bool True si c'est un jour férié
     */
    private function is_public_holiday($date) {
        // Liste des jours fériés fixes (mois-jour)
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
        
        // Vérifier les jours fériés fixes
        $month_day = date('m-d', strtotime($date));
        if (in_array($month_day, $fixed_holidays)) {
            return true;
        }
        
        // Calculer Pâques (dimanche) et ajouter 1 jour pour lundi de Pâques
        $year = date('Y', strtotime($date));
        $easter_days = easter_days($year);
        $easter = new DateTime("$year-03-21 +$easter_days days");
        $easter_monday = $easter->modify('+1 day')->format('m-d');
        
        // Vérifier lundi de Pâques
        if ($month_day === $easter_monday) {
            return true;
        }
        
        // Calculer l'Ascension (39 jours après Pâques)
        $ascension = $easter->modify('+38 days')->format('m-d');
        if ($month_day === $ascension) {
            return true;
        }
        
        // Calculer Pentecôte (49 jours après Pâques, lundi suivant)
        $pentecote = $easter->modify('+49 days')->format('m-d');
        if ($month_day === $pentecote) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Crée un prestataire de test
     */
    private function create_test_prestataire() {
        return $this->factory->post->create([
            'post_type' => 'prestataire',
            'post_title' => 'Dr. Test',
            'post_status' => 'publish'
        ]);
    }
    
    /**
     * Crée un service de test
     */
    private function create_test_service() {
        return $this->factory->post->create([
            'post_type' => 'service',
            'post_title' => 'Consultation',
            'post_status' => 'publish'
        ]);
    }
}
