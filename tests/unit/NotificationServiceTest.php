<?php
/**
 * Class NotificationServiceTest
 *
 * @package CalendrierRdv\Tests\Unit
 */

namespace CalendrierRdv\Tests\Unit;

use CalendrierRdv\Core\Services\NotificationService;
use WP_Mock\Tools\TestCase;
use WP_Mock;

class NotificationServiceTest extends TestCase {
    private $notificationService;

    public function setUp(): void {
        parent::setUp();
        $this->notificationService = new NotificationService();
        WP_Mock::setUp();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    public function testSendAppointmentConfirmation() {
        // Données de test
        $appointment = (object) [
            'id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'date' => '2025-06-15',
            'start_time' => '14:00:00',
            'service_name' => 'Consultation initiale'
        ];

        // Mock de la fonction wp_mail
        WP_Mock::userFunction('wp_mail', [
            'times' => 1,
            'args' => [
                'john@example.com',
                WP_Mock\Functions::type('string'),
                WP_Mock\Functions::type('string'),
                ['Content-Type: text/html']
            ],
            'return' => true
        ]);

        // Exécution
        $result = $this->notificationService->sendAppointmentConfirmation($appointment);
        
        // Vérification
        $this->assertTrue($result);
    }

    public function testSendReminder() {
        // Données de test
        $appointment = (object) [
            'id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'date' => '2025-06-15',
            'start_time' => '14:00:00',
            'service_name' => 'Consultation initiale'
        ];

        // Mock de la fonction wp_mail
        WP_Mock::userFunction('wp_mail', [
            'times' => 1,
            'args' => [
                'john@example.com',
                WP_Mock\Functions::type('string'),
                WP_Mock\Functions::type('string'),
                ['Content-Type: text/html']
            ],
            'return' => true
        ]);

        // Exécution
        $result = $this->notificationService->sendReminder($appointment);
        
        // Vérification
        $this->assertTrue($result);
    }
}
