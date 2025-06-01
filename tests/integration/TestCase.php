<?php

namespace CalendrierRdv\Tests\Integration;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Configuration commune pour tous les tests d'intégration
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Nettoyage après les tests
    }
}
