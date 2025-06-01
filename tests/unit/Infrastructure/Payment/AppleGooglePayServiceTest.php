<?php

namespace CalendrierRdv\Tests\Unit\Infrastructure\Payment;

use CalendrierRdv\Infrastructure\Payment\AppleGooglePayService;
use PHPUnit\Framework\TestCase;

class AppleGooglePayServiceTest extends TestCase
{
    private $paymentService;

    protected function setUp(): void
    {
        $this->paymentService = new AppleGooglePayService();
    }

    public function testProcessPayment(): void
    {
        $paymentData = [
            'amount' => 100,
            'currency' => 'EUR',
            'token' => 'test_token_123',
            'description' => 'Test payment'
        ];

        $result = $this->paymentService->processPayment($paymentData);
        $this->assertTrue($result);
    }
}
