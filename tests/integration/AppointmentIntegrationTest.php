<?php

namespace CalendrierRdv\Tests\Integration;

use CalendrierRdv\Core\Models\Appointment;
use CalendrierRdv\Core\Repositories\AppointmentRepository;
use CalendrierRdv\Core\Services\AppointmentService;
use CalendrierRdv\Infrastructure\Payment\CardPaymentService;
use CalendrierRdv\Infrastructure\Payment\MobileMoneyService;
use CalendrierRdv\Tests\Integration\TestCase;
use WP_REST_Request;

class AppointmentIntegrationTest extends TestCase
{
    private $appointmentService;
    private $appointmentRepository;
    private $cardPaymentService;
    private $mobileMoneyService;
    private $serviceId;
    private $providerId;
    private $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialiser les services
        $this->cardPaymentService = new CardPaymentService();
        $this->mobileMoneyService = new MobileMoneyService();
        $this->appointmentRepository = new AppointmentRepository();
        $this->appointmentService = new AppointmentService(
            $this->appointmentRepository,
            $this->cardPaymentService,
            $this->mobileMoneyService
        );
        
        // Créer des données de test
        $this->serviceId = $this->factory->post->create([
            'post_type' => 'service',
            'post_title' => 'Consultation de test',
            'meta_input' => [
                '_duree' => 60,
                '_prix' => 50.00,
                '_active' => 1
            ]
        ]);
        
        $this->providerId = $this->factory->post->create([
            'post_type' => 'prestataire',
            'post_title' => 'Dr. Test',
            'meta_input' => [
                '_disponibilites' => 'monday,tuesday,wednesday,thursday,friday',
                '_duree_rdv' => 30,
                '_pauses' => [
                    'monday' => ['12:00-13:00'],
                    'tuesday' => ['12:00-13:00']
                ]
            ]
        ]);
        
        $this->customerId = $this->factory->user->create([
            'user_login' => 'testcustomer',
            'user_email' => 'test@example.com',
            'role' => 'customer'
        ]);
    }

    public function testCreateAppointmentWithCardPayment()
    {
        $appointmentData = [
            'service_id' => $this->serviceId,
            'provider_id' => $this->providerId,
            'customer_id' => $this->customerId,
            'appointment_date' => date('Y-m-d', strtotime('next monday')),
            'appointment_time' => '14:00:00',
            'duration' => 60,
            'status' => 'pending_payment',
            'payment_method' => 'card',
            'payment_data' => [
                'card_number' => '4242424242424242',
                'expiry_date' => '12/25',
                'cvv' => '123'
            ]
        ];

        $appointment = $this->appointmentService->createAppointment($appointmentData);
        
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals('confirmed', $appointment->getStatus());
        $this->assertNotEmpty($appointment->getPaymentReference());
    }

    public function testCreateAppointmentWithMobileMoney()
    {
        $appointmentData = [
            'service_id' => $this->serviceId,
            'provider_id' => $this->providerId,
            'customer_id' => $this->customerId,
            'appointment_date' => date('Y-m-d', strtotime('next tuesday')),
            'appointment_time' => '15:30:00',
            'duration' => 60,
            'status' => 'pending_payment',
            'payment_method' => 'mobile_money',
            'payment_data' => [
                'phone' => '+221701234567',
                'operator' => 'orange'
            ]
        ];

        $appointment = $this->appointmentService->createAppointment($appointmentData);
        
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals('pending', $appointment->getStatus());
    }

    public function testCancelAppointment()
    {
        // Créer un rendez-vous
        $appointmentData = [
            'service_id' => $this->serviceId,
            'provider_id' => $this->providerId,
            'customer_id' => $this->customerId,
            'appointment_date' => date('Y-m-d', strtotime('next wednesday')),
            'appointment_time' => '16:00:00',
            'duration' => 60,
            'status' => 'confirmed'
        ];
        
        $appointment = $this->appointmentService->createAppointment($appointmentData);
        
        // Annuler le rendez-vous
        $result = $this->appointmentService->cancelAppointment($appointment->getId(), 'Test d\'annulation');
        
        $this->assertTrue($result);
        
        // Vérifier que le statut a été mis à jour
        $updatedAppointment = $this->appointmentRepository->find($appointment->getId());
        $this->assertEquals('cancelled', $updatedAppointment->getStatus());
    }

    public function testRescheduleAppointment()
    {
        // Créer un rendez-vous
        $appointmentData = [
            'service_id' => $this->serviceId,
            'provider_id' => $this->providerId,
            'customer_id' => $this->customerId,
            'appointment_date' => date('Y-m-d', strtotime('next thursday')),
            'appointment_time' => '10:00:00',
            'duration' => 60,
            'status' => 'confirmed'
        ];
        
        $appointment = $this->appointmentService->createAppointment($appointmentData);
        
        // Reporter le rendez-vous
        $newDate = date('Y-m-d', strtotime('next friday'));
        $newTime = '14:30:00';
        
        $result = $this->appointmentService->rescheduleAppointment(
            $appointment->getId(),
            $newDate,
            $newTime,
            'Test de report'
        );
        
        $this->assertTrue($result);
        
        // Vérifier que la date a été mise à jour
        $updatedAppointment = $this->appointmentRepository->find($appointment->getId());
        $this->assertEquals($newDate, $updatedAppointment->getAppointmentDate()->format('Y-m-d'));
        $this->assertEquals($newTime, $updatedAppointment->getAppointmentTime()->format('H:i:s'));
    }
}
