<?php
/**
 * Tests pour les shortcodes du plugin
 */
class ShortcodesTest extends WP_UnitTestCase {
    
    /**
     * Teste le shortcode d'affichage du formulaire de réservation
     */
    public function test_booking_form_shortcode() {
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
        
        // Tester le shortcode sans paramètres
        $output = do_shortcode('[calendrier_rdv_booking]');
        $this->assertStringContainsString('Prendre un rendez-vous', $output);
        $this->assertStringContainsString('Sélectionnez un prestataire', $output);
        
        // Tester avec un prestataire spécifique
        $output = do_shortcode('[calendrier_rdv_booking prestataire_id="' . $prestataire_id . '"]');
        $this->assertStringContainsString('Dr. Test', $output);
        
        // Tester avec un service spécifique
        $output = do_shortcode('[calendrier_rdv_booking service_id="' . $service_id . '"]');
        $this->assertStringContainsString('Consultation', $output);
    }
    
    /**
     * Teste le shortcode d'affichage des rendez-vous à venir
     */
    public function test_upcoming_bookings_shortcode() {
        // Créer un utilisateur de test
        $user_id = $this->factory->user->create([
            'role' => 'subscriber',
            'user_email' => 'test@example.com'
        ]);
        
        // Créer des rendez-vous de test
        $booking1 = $this->factory->post->create([
            'post_type' => 'rdv_booking',
            'post_author' => $user_id,
            'post_status' => 'publish',
            'post_title' => 'Réservation #1',
            'meta_input' => [
                '_client_email' => 'test@example.com',
                '_date_rdv' => date('Y-m-d', strtotime('+1 day')),
                '_heure_debut' => '14:00',
                '_statut' => 'confirme',
                '_reference' => 'TEST-12345678'
            ]
        ]);
        
        // Tester le shortcode avec l'utilisateur connecté
        wp_set_current_user($user_id);
        $output = do_shortcode('[calendrier_rdv_mes_rendezvous]');
        $this->assertStringContainsString('Mes rendez-vous à venir', $output);
        $this->assertStringContainsString('TEST-12345678', $output);
        
        // Tester avec un email spécifique
        $output = do_shortcode('[calendrier_rdv_mes_rendezvous email="test@example.com"]');
        $this->assertStringContainsString('TEST-12345678', $output);
    }
    
    /**
     * Teste le shortcode d'affichage du calendrier
     */
    public function test_calendar_shortcode() {
        // Créer un prestataire de test
        $prestataire_id = $this->factory->post->create([
            'post_type' => 'prestataire',
            'post_title' => 'Dr. Test',
            'post_status' => 'publish'
        ]);
        
        // Tester le shortcode avec un prestataire spécifique
        $output = do_shortcode('[calendrier_rdv_calendrier prestataire_id="' . $prestataire_id . '"]');
        $this->assertStringContainsString('calendrier-rdv-calendar', $output);
        
        // Tester avec des options supplémentaires
        $output = do_shortcode('[calendrier_rdv_calendrier prestataire_id="' . $prestataire_id . '" show_legend="yes" show_navigation="yes"]');
        $this->assertStringContainsString('calendrier-rdv-calendar', $output);
    }
    
    /**
     * Teste le shortcode d'affichage des disponibilités
     */
    public function test_availability_shortcode() {
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
        
        // Tester le shortcode avec des paramètres
        $output = do_shortcode('[calendrier_rdv_disponibilites prestataire_id="' . $prestataire_id . '" service_id="' . $service_id . '" jours="7"]');
        $this->assertStringContainsString('disponibilites-container', $output);
    }
}
