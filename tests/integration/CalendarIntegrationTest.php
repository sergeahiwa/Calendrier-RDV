<?php
/**
 * Test d'intégration pour le calendrier
 */
class CalendarIntegrationTest extends WP_UnitTestCase {
    
    /**
     * @var CalRdv_Appointment_Manager
     */
    private $appointment_manager;
    
    /**
     * @var int
     */
    private $test_provider_id;
    
    /**
     * @var int
     */
    private $test_service_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Initialiser le gestionnaire de rendez-vous
        $this->appointment_manager = CalRdv_Appointment_Manager::get_instance();
        
        // Créer un prestataire de test
        $this->test_provider_id = $this->factory->user->create([
            'role' => 'prestataire',
            'user_email' => 'provider@example.com',
            'display_name' => 'Dr. Test Provider'
        ]);
        
        // Créer un service de test
        $this->test_service_id = $this->factory->post->create([
            'post_type' => 'service',
            'post_title' => 'Consultation de test',
            'post_status' => 'publish'
        ]);
        
        // Ajouter des méta-données au service
        update_post_meta($this->test_service_id, '_duree', 30); // 30 minutes
    }
    
    public function test_calendar_availability() {
        // Créer un rendez-vous de 10h00 à 10h30
        $this->appointment_manager->create_appointment([
            'client_id' => 1,
            'provider_id' => $this->test_provider_id,
            'service_id' => $this->test_service_id,
            'start_datetime' => '2025-06-01 10:00:00',
            'end_datetime' => '2025-06-01 10:30:00',
            'status' => 'confirmed',
            'notes' => 'Rendez-vous de test'
        ]);
        
        // Vérifier la disponibilité à 10h00 (devrait être indisponible)
        $is_available = $this->appointment_manager->is_time_slot_available(
            $this->test_provider_id,
            '2025-06-01 10:00:00',
            '2025-06-01 10:30:00'
        );
        
        $this->assertFalse($is_available, 'Le créneau devrait être indisponible');
        
        // Vérifier la disponibilité à 11h00 (devrait être disponible)
        $is_available = $this->appointment_manager->is_time_slot_available(
            $this->test_provider_id,
            '2025-06-01 11:00:00',
            '2025-06-01 11:30:00'
        );
        
        $this->assertTrue($is_available, 'Le créneau devrait être disponible');
    }
    
    public function test_calendar_retrieval() {
        // Créer plusieurs rendez-vous
        $appointments = [
            [
                'client_id' => 1,
                'provider_id' => $this->test_provider_id,
                'service_id' => $this->test_service_id,
                'start_datetime' => '2025-06-01 09:00:00',
                'end_datetime' => '2025-06-01 09:30:00',
                'status' => 'confirmed'
            ],
            [
                'client_id' => 1,
                'provider_id' => $this->test_provider_id,
                'service_id' => $this->test_service_id,
                'start_datetime' => '2025-06-01 10:00:00',
                'end_datetime' => '2025-06-01 10:30:00',
                'status' => 'confirmed'
            ]
        ];
        
        foreach ($appointments as $appointment) {
            $this->appointment_manager->create_appointment($appointment);
        }
        
        // Récupérer les rendez-vous pour le 1er juin 2025
        $start_date = '2025-06-01 00:00:00';
        $end_date = '2025-06-01 23:59:59';
        
        $retrieved_appointments = $this->appointment_manager->get_appointments([
            'provider_id' => $this->test_provider_id,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        // Vérifier qu'on a bien récupéré les 2 rendez-vous
        $this->assertCount(2, $retrieved_appointments);
        
        // Vérifier que les rendez-vous sont triés par heure de début
        $this->assertLessThanOrEqual(
            strtotime($retrieved_appointments[1]->start_datetime),
            strtotime($retrieved_appointments[0]->start_datetime)
        );
    }
}
