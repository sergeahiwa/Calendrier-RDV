<?php
/**
 * Test d'exemple pour vérifier la configuration des tests
 */
class ExampleTest extends WP_UnitTestCase {
    /**
     * Vérifie que true est vrai
     */
    public function test_true_is_true() {
        $this->assertTrue(true);
    }

    /**
     * Vérifie que le plugin est chargé
     */
    public function test_plugin_loaded() {
        $this->assertTrue(function_exists('calendrier_rdv_init'));
    }
}
