<?php

namespace CalendrierRdv\Tests\Unit\Infrastructure\Payment;

use CalendrierRdv\Infrastructure\Payment\MobileMoneyService;
use PHPUnit\Framework\TestCase;

class MobileMoneyServiceTest extends TestCase
{
    private $paymentService;

    protected function setUp(): void
    {
        $this->paymentService = new MobileMoneyService();
    }

    public function testProcessPayment(): void
    {
        $paymentData = [
            'amount' => 5000,
            'currency' => 'XOF',
            'phone' => '+221701234567',
            'operator' => 'orange',
            'description' => 'Paiement test'
        ];

        $result = $this->paymentService->processPayment($paymentData);
        $this->assertTrue($result);
    }
}
