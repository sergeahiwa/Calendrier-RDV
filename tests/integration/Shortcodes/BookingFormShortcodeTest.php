<?php
/**
 * Class BookingFormShortcodeTest
 *
 * @package CalendrierRdv\Tests\Integration\Shortcodes
 */

namespace CalendrierRdv\Tests\Integration\Shortcodes;

use WP_UnitTestCase;

class BookingFormShortcodeTest extends WP_UnitTestCase {
    private $shortcode;
    private $serviceId;
    private $providerId;

    public function setUp(): void {
        parent::setUp();
        
        // Initialiser les shortcodes
        new \CalendrierRdv\Includes\Shortcodes\BookingFormShortcode();
        
        // Créer un service de test
        $this->serviceId = $this->factory->post->create([
            'post_type' => 'cal_rdv_service',
            'post_title' => 'Massage relaxant',
            'post_status' => 'publish'
        ]);
        
        update_post_meta($this->serviceId, '_service_duration', 60);
        update_post_meta($this->serviceId, '_service_price', 70);
        
        // Créer un prestataire de test
        $this->providerId = $this->factory->user->create([
            'role' => 'cal_rdv_provider',
            'user_login' => 'massage_provider',
            'user_email' => 'massage@example.com',
            'first_name' => 'Marie',
            'last_name' => 'Dupont'
        ]);
        
        update_user_meta($this->providerId, '_provider_services', [$this->serviceId]);
        update_user_meta($this->providerId, '_provider_schedule', [
            'monday' => ['start' => '09:00', 'end' => '18:00'],
            'tuesday' => ['start' => '09:00', 'end' => '18:00'],
            'wednesday' => ['start' => '09:00', 'end' => '18:00'],
            'thursday' => ['start' => '09:00', 'end' => '18:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
            'saturday' => ['start' => '10:00', 'end' => '14:00'],
            'sunday' => ['start' => '', 'end' => '']
        ]);
    }

    public function testBookingFormShortcodeRenders() {
        // Tester le rendu du shortcode sans paramètres
        $output = do_shortcode('[calendrier_booking]');
        
        // Vérifier que le contenu de base est présent
        $this->assertStringContainsString('id="calendrier-booking-form"', $output);
        $this->assertStringContainsString('Sélectionnez un service', $output);
        
        // Vérifier que les services sont chargés
        $this->assertStringContainsString('Massage relaxant', $output);
    }

    public function testBookingFormWithServicePreselected() {
        // Tester avec un service présélectionné
        $output = do_shortcode('[calendrier_booking service="' . $this->serviceId . '"]');
        
        // Vérifier que le service est présélectionné
        $this->assertStringContainsString('value="' . $this->serviceId . '" selected', $output);
        
        // Vérifier que le sélecteur de prestataire est affiché
        $this->assertStringContainsString('Sélectionnez un prestataire', $output);
        $this->assertStringContainsString('Marie Dupont', $output);
    }

    public function testBookingFormWithProviderPreselected() {
        // Tester avec un prestataire présélectionné
        $output = do_shortcode('[calendrier_booking service="' . $this->serviceId . '" provider="' . $this->providerId . '"]');
        
        // Vérifier que le calendrier est affiché
        $this->assertStringContainsString('id="calendrier-booking-calendar"', $output);
        
        // Vérifier que le formulaire de réservation est présent mais masqué
        $this->assertStringContainsString('id="calendrier-booking-details"', $output);
        $this->assertStringContainsString('style="display:none;"', $output);
    }

    public function testBookingFormWithInvalidService() {
        // Tester avec un ID de service invalide
        $output = do_shortcode('[calendrier_booking service="99999"]');
        
        // Vérifier le message d'erreur
        $this->assertStringContainsString('Service non trouvé', $output);
    }

    public function testBookingFormWithInvalidProvider() {
        // Tester avec un ID de prestataire invalide
        $output = do_shortcode('[calendrier_booking service="' . $this->serviceId . '" provider="99999"]');
        
        // Vérifier le message d'erreur
        $this->assertStringContainsString('Prestataire non trouvé', $output);
    }

    public function testBookingFormWithMinimalMarkup() {
        // Tester avec l'option de balisage minimal
        $output = do_shortcode('[calendrier_booking minimal="true"]');
        
        // Vérifier que les classes CSS minimales sont présentes
        $this->assertStringContainsString('class="calendrier-minimal"', $output);
    }

    public function tearDown(): void {
        // Nettoyer
        wp_delete_post($this->serviceId, true);
        wp_delete_user($this->providerId, true);
        parent::tearDown();
    }
}
