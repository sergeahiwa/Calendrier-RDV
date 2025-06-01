<?php

namespace CalendrierRdv\Tests\Unit\Infrastructure;

use CalendrierRdv\Infrastructure\Export\ExportService;
use PHPUnit\Framework\TestCase;

class ExportServiceTest extends TestCase
{
    private $exportService;

    protected function setUp(): void
    {
        $this->exportService = new ExportService();
    }

    public function testExportCsv(): void
    {
        $appointments = [
            [
                'date' => '2025-06-01 10:00',
                'client' => 'Dupont Jean',
                'email' => 'jean@example.com',
                'service' => 'Consultation',
                'provider' => 'Dr. Martin',
                'status' => 'confirmé'
            ]
        ];

        $result = $this->exportService->exportCsv($appointments);
        $this->assertStringContainsString('2025-06-01 10:00', $result);
        $this->assertStringContainsString('Dupont Jean', $result);
    }

    public function testExportExcel(): void
    {
        $appointments = [
            [
                'date' => '2025-06-01 10:00',
                'client' => 'Dupont Jean',
                'email' => 'jean@example.com',
                'service' => 'Consultation',
                'provider' => 'Dr. Martin',
                'status' => 'confirmé'
            ]
        ];

        $result = $this->exportService->exportExcel($appointments);
        $this->assertStringContainsString('2025-06-01 10:00', $result);
        $this->assertStringContainsString('Dupont Jean', $result);
    }
}
