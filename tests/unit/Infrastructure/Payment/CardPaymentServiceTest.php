<?php

namespace CalendrierRdv\Tests\Unit\Infrastructure\Payment;

use CalendrierRdv\Infrastructure\Payment\CardPaymentService;
use PHPUnit\Framework\TestCase;

class CardPaymentServiceTest extends TestCase
{
    private $paymentService;

    protected function setUp(): void
    {
        $this->paymentService = new CardPaymentService();
    }

    public function testProcessPayment(): void
    {
        $paymentData = [
            'amount' => 100,
            'currency' => 'EUR',
            'card_number' => '4242424242424242',
            'expiry_date' => '12/25',
            'cvv' => '123',
            'description' => 'Test payment'
        ];

        $result = $this->paymentService->processPayment($paymentData);
        $this->assertTrue($result);
    }

    public function testProcessPaymentWithInvalidCard(): void
    {
        $paymentData = [
            'amount' => 100,
            'currency' => 'EUR',
            'card_number' => '1234567812345678', // Carte invalide
            'expiry_date' => '12/25',
            'cvv' => '123',
            'description' => 'Test payment'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->paymentService->processPayment($paymentData);
    }

    public function testProcessPaymentWithExpiredCard(): void
    {
        $paymentData = [
            'amount' => 100,
            'currency' => 'EUR',
            'card_number' => '4242424242424242',
            'expiry_date' => '01/20', // Carte expirée
            'cvv' => '123',
            'description' => 'Test payment'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $result = $this->paymentService->processPayment($paymentData);
    }

    public function testProcessPartialRefund(): void
    {
        $transactionId = 'txn_123456';
        $amount = 50;
        
        $result = $this->paymentService->processRefund($transactionId, $amount);
        $this->assertTrue($result);
    }

    public function testProcessFullRefund(): void
    {
        $transactionId = 'txn_123456';
        
        $result = $this->paymentService->processRefund($transactionId);
        $this->assertTrue($result);
    }
}
