<?php
/**
 * Test unitaire pour CalRdv_Appointment_Manager
 */
class AppointmentManagerTest extends WP_UnitTestCase {
    
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
            'user_email' => 'test@example.com'
        ]);
        
        // Créer un service de test
        $this->test_service_id = $this->factory->post->create([
            'post_type' => 'service',
            'post_title' => 'Consultation de test',
            'post_status' => 'publish'
        ]);
        
        // Ajouter des méta-données au service
        update_post_meta($this->test_service_id, '_duree', 60); // 60 minutes
    }
    
    public function test_create_appointment() {
        // Préparer les données de test
        $appointment_data = [
            'client_id' => 1,
            'provider_id' => $this->test_provider_id,
            'service_id' => $this->test_service_id,
            'start_datetime' => '2025-06-01 10:00:00',
            'end_datetime' => '2025-06-01 11:00:00',
            'status' => 'pending',
            'notes' => 'Test de création de rendez-vous'
        ];
        
        // Créer le rendez-vous
        $appointment_id = $this->appointment_manager->create_appointment($appointment_data);
        
        // Vérifier que le résultat est un entier (ID du rendez-vous)
        $this->assertIsInt($appointment_id);
        $this->assertGreaterThan(0, $appointment_id);
        
        // Vérifier que le rendez-vous existe dans la base de données
        global $wpdb;
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}rdv_appointments WHERE id = %d", $appointment_id)
        );
        
        $this->assertNotNull($appointment);
        $this->assertEquals('pending', $appointment->status);
        $this->assertEquals($appointment_data['notes'], $appointment->notes);
    }
    
    public function test_get_appointment() {
        // Créer un rendez-vous de test
        $appointment_data = [
            'client_id' => 1,
            'provider_id' => $this->test_provider_id,
            'service_id' => $this->test_service_id,
            'start_datetime' => '2025-06-01 10:00:00',
            'end_datetime' => '2025-06-01 11:00:00',
            'status' => 'confirmed',
            'notes' => 'Test de récupération de rendez-vous'
        ];
        
        $appointment_id = $this->appointment_manager->create_appointment($appointment_data);
        
        // Récupérer le rendez-vous
        $appointment = $this->appointment_manager->get_appointment($appointment_id);
        
        // Vérifier que le rendez-vous a été correctement récupéré
        $this->assertIsObject($appointment);
        $this->assertEquals($appointment_id, $appointment->id);
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertEquals($appointment_data['notes'], $appointment->notes);
    }
    
    public function test_update_appointment() {
        // Créer un rendez-vous de test
        $appointment_data = [
            'client_id' => 1,
            'provider_id' => $this->test_provider_id,
            'service_id' => $this->test_service_id,
            'start_datetime' => '2025-06-01 10:00:00',
            'end_datetime' => '2025-06-01 11:00:00',
            'status' => 'pending',
            'notes' => 'Test de mise à jour de rendez-vous'
        ];
        
        $appointment_id = $this->appointment_manager->create_appointment($appointment_data);
        
        // Mettre à jour le rendez-vous
        $update_data = [
            'status' => 'confirmed',
            'notes' => 'Rendez-vous confirmé'
        ];
        
        $result = $this->appointment_manager->update_appointment($appointment_id, $update_data);
        $this->assertTrue($result);
        
        // Vérifier que le rendez-vous a été mis à jour
        $updated_appointment = $this->appointment_manager->get_appointment($appointment_id);
        $this->assertEquals('confirmed', $updated_appointment->status);
        $this->assertEquals('Rendez-vous confirmé', $updated_appointment->notes);
    }
    
    public function test_delete_appointment() {
        // Créer un rendez-vous de test
        $appointment_data = [
            'client_id' => 1,
            'provider_id' => $this->test_provider_id,
            'service_id' => $this->test_service_id,
            'start_datetime' => '2025-06-01 10:00:00',
            'end_datetime' => '2025-06-01 11:00:00',
            'status' => 'pending',
            'notes' => 'Test de suppression de rendez-vous'
        ];
        
        $appointment_id = $this->appointment_manager->create_appointment($appointment_data);
        
        // Supprimer le rendez-vous
        $result = $this->appointment_manager->delete_appointment($appointment_id);
        $this->assertTrue($result);
        
        // Vérifier que le rendez-vous a été supprimé
        $deleted_appointment = $this->appointment_manager->get_appointment($appointment_id);
        $this->assertNull($deleted_appointment);
    }
}
