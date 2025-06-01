<?php

namespace CalendrierRdv\Tests\Integration\Services;

use CalendrierRdv\Infrastructure\Export\ExportService;
use PHPUnit\Framework\TestCase;

class ExportServiceIntegrationTest extends TestCase
{
    private $exportService;

    protected function setUp(): void
    {
        $this->exportService = new ExportService();
    }

    public function testExportWorkflow(): void
    {
        // Données de test
        $appointments = [
            [
                'date' => '2025-06-15 14:00',
                'client' => 'Jean Dupont',
                'email' => 'jean@example.com',
                'service' => 'Consultation',
                'provider' => 'Dr. Martin',
                'status' => 'confirmé'
            ],
            [
                'date' => '2025-06-16 15:30',
                'client' => 'Aminata Diop',
                'email' => 'aminata@example.com',
                'service' => 'Massage',
                'provider' => 'Sophie K.',
                'status' => 'confirmé'
            ]
        ];

        // Test export CSV
        $csvResult = $this->exportService->exportCsv($appointments);
        $this->assertStringContainsString('Jean Dupont', $csvResult);
        $this->assertStringContainsString('Aminata Diop', $csvResult);

        // Test export Excel (TSV)
        $excelResult = $this->exportService->exportExcel($appointments);
        $this->assertStringContainsString('Jean Dupont', $excelResult);
        $this->assertStringContainsString('Aminata Diop', $excelResult);
    }
}
