<?php
/**
 * Class AppointmentsTest
 *
 * @package Calendrier_Rdv\Tests\Unit
 */

namespace CalendrierRdv\Tests\Unit;

use CalendrierRdv\Core\Database\Appointments;
use WP_Mock\Tools\TestCase;
use WP_Mock;

/**
 * Test case for the Appointments class.
 */
class AppointmentsTest extends TestCase {

    /**
     * @var Appointments
     */
    private $appointments;

    /**
     * Set up the test fixture.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize the Appointments class
        $this->appointments = new Appointments();
        
        // Mock WordPress functions
        WP_Mock::setUp();
    }

    /**
     * Tear down the test fixture.
     */
    public function tearDown(): void {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test creating a new appointment.
     */
    public function testCreateAppointment() {
        // Mock global $wpdb
        global $wpdb;
        $wpdb = $this->getMockBuilder('wpdb')
            ->setMethods(['insert', 'insert_id', 'prepare'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $wpdb->insert_id = 123;
        
        // Mock wp_mail function
        WP_Mock::userFunction('wp_mail', [
            'return' => true,
        ]);
        
        // Mock wp_generate_password function
        WP_Mock::userFunction('wp_generate_password', [
            'return' => 'test-token',
        ]);
        
        // Test data
        $appointment_data = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'service_id' => 1,
            'provider_id' => 1,
            'date' => '2025-01-01',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
            'notes' => 'Test appointment',
        ];
        
        // Call the method
        $result = $this->appointments->createAppointment($appointment_data);
        
        // Assert the result
        $this->assertIsInt($result);
        $this->assertEquals(123, $result);
    }

    /**
     * Test getting appointments by date range.
     */
    public function testGetAppointmentsByDateRange() {
        // Mock global $wpdb
        global $wpdb;
        $wpdb = $this->getMockBuilder('wpdb')
            ->setMethods(['get_results', 'prepare'])
            ->disableOriginalConstructor()
            ->getMock();
            
        // Mock the database results
        $expected_results = [
            (object) [
                'id' => 1,
                'customer_name' => 'John Doe',
                'date' => '2025-01-01',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'status' => 'confirmed',
            ],
        ];
        
        $wpdb->expects($this->once())
            ->method('get_results')
            ->willReturn($expected_results);
        
        // Call the method
        $start_date = '2025-01-01';
        $end_date = '2025-01-31';
        $results = $this->appointments->getAppointmentsByDateRange($start_date, $end_date);
        
        // Assert the results
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]->customer_name);
    }

    /**
     * Test updating an appointment status.
     */
    public function testUpdateAppointmentStatus() {
        // Mock global $wpdb
        global $wpdb;
        $wpdb = $this->getMockBuilder('wpdb')
            ->setMethods(['update', 'prepare'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $wpdb->expects($this->once())
            ->method('update')
            ->willReturn(1);
        
        // Mock wp_mail function
        WP_Mock::userFunction('wp_mail', [
            'return' => true,
        ]);
        
        // Call the method
        $appointment_id = 1;
        $status = 'confirmed';
        $result = $this->appointments->updateAppointmentStatus($appointment_id, $status);
        
        // Assert the result
        $this->assertTrue($result);
    }

    /**
     * Test getting appointment by ID.
     */
    public function testGetAppointmentById() {
        // Mock global $wpdb
        global $wpdb;
        $wpdb = $this->getMockBuilder('wpdb')
            ->setMethods(['get_row', 'prepare'])
            ->disableOriginalConstructor()
            ->getMock();
            
        // Mock the database result
        $expected_result = (object) [
            'id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'service_id' => 1,
            'provider_id' => 1,
            'date' => '2025-01-01',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'confirmed',
            'notes' => 'Test appointment',
        ];
        
        $wpdb->expects($this->once())
            ->method('get_row')
            ->willReturn($expected_result);
        
        // Call the method
        $appointment_id = 1;
        $result = $this->appointments->getAppointmentById($appointment_id);
        
        // Assert the result
        $this->assertIsObject($result);
        $this->assertEquals('John Doe', $result->customer_name);
        $this->assertEquals('confirmed', $result->status);
    }

    /**
     * Test checking time slot availability.
     */
    public function testIsTimeSlotAvailable() {
        // Mock global $wpdb
        global $wpdb;
        $wpdb = $this->getMockBuilder('wpdb')
            ->setMethods(['get_var', 'prepare'])
            ->disableOriginalConstructor()
            ->getMock();
            
        // Mock the database result (no conflicting appointments)
        $wpdb->expects($this->once())
            ->method('get_var')
            ->willReturn(0);
        
        // Test data
        $provider_id = 1;
        $date = '2025-01-01';
        $start_time = '09:00:00';
        $end_time = '10:00:00';
        $exclude_id = null;
        
        // Call the method
        $result = $this->appointments->isTimeSlotAvailable($provider_id, $date, $start_time, $end_time, $exclude_id);
        
        // Assert the result
        $this->assertTrue($result);
    }
}
