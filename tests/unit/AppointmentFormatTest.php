<?php

namespace CalendrierRdv\Tests\Unit;

use CalendrierRdv\Core\Database\Appointments;
use WP_Mock\Tools\TestCase;

class AppointmentFormatTest extends TestCase {
    
    public function testDurationIsIncludedInFormattedAppointments() {
        // Créer un mock de la base de données
        global $wpdb;
        $wpdb = $this->getMockBuilder('stdClass')
            ->setMethods(['get_results', 'prepare', 'get_var'])
            ->getMock();
            
        // Configurer les données de test
        $test_appointment = (object) [
            'id' => 1,
            'appointment_date' => '2025-06-01',
            'appointment_time' => '10:00:00',
            'customer_name' => 'Test Client',
            'status' => 'confirmed',
            'service_id' => 1,
            'provider_id' => 1,
            'duration' => 45,
            'service_name' => 'Test Service',
            'provider_name' => 'Test Provider',
            'provider_email' => 'provider@test.com',
            'duration_minutes' => 45
        ];
        
        // Configurer le mock pour retourner nos données de test
        $wpdb->prefix = 'wp_';
        $wpdb->expects($this->any())
            ->method('get_results')
            ->willReturn([$test_appointment]);
            
        // Appeler la méthode à tester
        $appointments = Appointments::get_formatted_appointments_for_calendar('2025-06-01', '2025-06-02');
        
        // Vérifier que le rendez-vous a été formaté correctement
        $this->assertCount(1, $appointments, 'Un seul rendez-vous devrait être retourné');
        
        $appointment = $appointments[0];
        
        // Vérifier la durée au niveau racine
        $this->assertEquals(45, $appointment['duration'], 'La durée au niveau racine devrait être 45');
        
        // Vérifier les propriétés étendues
        $this->assertArrayHasKey('extendedProps', $appointment, 'Les propriétés étendues devraient exister');
        $this->assertEquals(45, $appointment['extendedProps']['duration'], 'La durée dans les propriétés étendues devrait être 45');
    }
}
