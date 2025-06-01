<?php
/**
 * Tests pour les fonctions utilitaires du plugin
 */
class UtilsTest extends WP_UnitTestCase {
    
    /**
     * Teste la fonction de génération de référence
     */
    public function test_generate_reference() {
        $reference = generate_booking_reference();
        
        // La référence doit commencer par RDV- suivie de 8 caractères alphanumériques
        $this->assertMatchesRegularExpression('/^RDV-[A-Z0-9]{8}$/', $reference);
        
        // Deux références générées ne doivent pas être identiques
        $another_reference = generate_booking_reference();
        $this->assertNotEquals($reference, $another_reference);
    }
    
    /**
     * Teste la fonction de vérification de disponibilité
     */
    public function test_check_availability() {
        // Créer un prestataire et un service de test
        $prestataire_id = $this->factory->post->create([
            'post_type' => 'prestataire',
            'post_title' => 'Dr. Test',
            'post_status' => 'publish'
        ]);
        
        $service_id = $this->factory->post->create([
            'post_type' => 'service',
            'post_title' => 'Consultation',
            'post_status' => 'publish'
        ]);
        
        // Tester la disponibilité pour une date future
        $date = date('Y-m-d', strtotime('+1 day'));
        $heure = '14:00';
        
        // Par défaut, le créneau devrait être disponible
        $is_available = check_availability($prestataire_id, $service_id, $date, $heure);
        $this->assertTrue($is_available);
        
        // Créer un rendez-vous pour ce créneau
        $booking_id = $this->factory->post->create([
            'post_type' => 'rdv_booking',
            'post_status' => 'publish',
            'meta_input' => [
                '_prestataire_id' => $prestataire_id,
                '_service_id' => $service_id,
                '_date_rdv' => $date,
                '_heure_debut' => $heure,
                '_statut' => 'confirme'
            ]
        ]);
        
        // Le créneau ne devrait plus être disponible
        $is_available = check_availability($prestataire_id, $service_id, $date, $heure);
        $this->assertFalse($is_available);
    }
    
    /**
     * Teste la fonction d'envoi d'email de confirmation
     */
    public function test_send_confirmation_email() {
        // Désactiver l'envoi réel d'emails
        add_filter('wp_mail', function($args) {
            $this->assertArrayHasKey('to', $args);
            $this->assertArrayHasKey('subject', $args);
            $this->assertArrayHasKey('message', $args);
            $this->assertArrayHasKey('headers', $args);
            
            // Retourner false pour empêcher l'envoi réel
            return false;
        });
        
        // Données de test
        $booking_data = [
            'client_nom' => 'Test User',
            'client_email' => 'test@example.com',
            'date_rdv' => date('Y-m-d', strtotime('+1 day')),
            'heure_debut' => '14:00',
            'reference' => 'TEST-12345678',
            'prestataire_nom' => 'Dr. Test',
            'service_nom' => 'Consultation',
            'duree' => 30,
            'prix' => 50
        ];
        
        // Tester l'envoi d'email
        $result = send_confirmation_email($booking_data);
        $this->assertTrue($result);
    }
    
    /**
     * Teste la fonction de formatage de la durée
     */
    public function test_format_duration() {
        $this->assertEquals('30 min', format_duration(30));
        $this->assertEquals('1h', format_duration(60));
        $this->assertEquals('1h 30min', format_duration(90));
        $this->assertEquals('2h', format_duration(120));
    }
    
    /**
     * Teste la fonction de validation d'email
     */
    public function test_is_valid_email() {
        $this->assertTrue(is_valid_email('test@example.com'));
        $this->assertFalse(is_valid_email('invalid-email'));
        $this->assertFalse(is_valid_email('test@'));
        $this->assertFalse(is_valid_email('@example.com'));
    }
}
