<?php
/**
 * Class TimeSlotValidationTest
 *
 * @package CalendrierRdv\Tests\Unit
 */

namespace CalendrierRdv\Tests\Unit;

use CalendrierRdv\Core\Validators\TimeSlotValidator;
use WP_Mock\Tools\TestCase;
use WP_Mock;

class TimeSlotValidationTest extends TestCase {
    private $validator;

    public function setUp(): void {
        parent::setUp();
        $this->validator = new TimeSlotValidator();
        WP_Mock::setUp();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    public function testValidTimeSlot() {
        // Données de test valides
        $data = [
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'date' => '2025-06-15'
        ];

        $result = $this->validator->validate($data);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testInvalidTimeSlot() {
        // Créneau invalide (fin avant début)
        $data = [
            'start_time' => '15:00:00',
            'end_time' => '14:00:00',
            'date' => '2025-06-15'
        ];

        $result = $this->validator->validate($data);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('time_slot', $result->getErrors());
    }

    public function testPastDate() {
        // Date dans le passé
        $pastDate = date('Y-m-d', strtotime('-1 day'));
        
        $data = [
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'date' => $pastDate
        ];

        $result = $this->validator->validate($data);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
    }

    public function testOutsideBusinessHours() {
        // En dehors des heures d'ouverture (avant 9h)
        $data = [
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'date' => '2025-06-15'
        ];

        $result = $this->validator->validate($data);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('business_hours', $result->getErrors());
    }
}
