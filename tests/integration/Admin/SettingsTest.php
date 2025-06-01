<?php
/**
 * Tests d'intégration pour la classe Settings
 *
 * @package CalendrierRDV\Tests\Integration\Admin
 */

namespace CalendrierRDV\Tests\Integration\Admin;

use CalendrierRDV\Includes\Admin\Settings;
use WP_UnitTestCase;

/**
 * Classe de test pour la gestion des paramètres
 */
class SettingsTest extends WP_UnitTestCase {

    /**
     * Instance de la classe Settings
     *
     * @var Settings
     */
    private $settings;

    /**
     * Configuration initiale pour les tests
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialisation de la classe Settings
        $this->settings = new Settings();
        
        // Réinitialisation des options avant chaque test
        delete_option('cal_rdv_settings');
    }

    /**
     * Teste l'initialisation des paramètres par défaut
     */
    public function test_default_settings_initialization() {
        $defaults = $this->settings->get_default_settings();
        
        // Vérifie que les paramètres par défaut sont bien définis
        $this->assertArrayHasKey('company_name', $defaults);
        $this->assertArrayHasKey('time_slot_duration', $defaults);
        $this->assertArrayHasKey('date_format', $defaults);
    }

    /**
     * Teste la sauvegarde des paramètres
     */
    public function test_saving_settings() {
        // Données de test
        $test_data = [
            'company_name' => 'Test Company',
            'time_slot_duration' => 30,
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        ];

        // Sauvegarde des paramètres
        $this->settings->save_settings($test_data);

        // Récupération des paramètres sauvegardés
        $saved_settings = get_option('cal_rdv_settings');

        // Vérifications
        $this->assertEquals($test_data['company_name'], $saved_settings['company_name']);
        $this->assertEquals($test_data['time_slot_duration'], $saved_settings['time_slot_duration']);
    }

    /**
     * Teste la validation des paramètres
     */
    public function test_settings_validation() {
        // Données avec des valeurs invalides
        $invalid_data = [
            'company_name' => '<script>alert("test")</script>',
            'time_slot_duration' => 'invalid',
            'email' => 'not-an-email'
        ];

        // Validation des paramètres
        $validated = $this->settings->validate_settings($invalid_data);

        // Vérifications
        $this->assertNotEquals($invalid_data['company_name'], $validated['company_name']);
        $this->assertIsInt($validated['time_slot_duration']);
        $this->assertEmpty($validated['email']); // L'email invalide devrait être rejeté
    }

    /**
     * Teste la récupération d'un paramètre spécifique
     */
    public function test_get_setting() {
        // Données de test
        $test_data = [
            'company_name' => 'Test Company',
            'time_slot_duration' => 30
        ];
        
        // Sauvegarde des paramètres
        update_option('cal_rdv_settings', $test_data);

        // Récupération d'un paramètre
        $company_name = $this->settings->get_setting('company_name');
        $non_existent = $this->settings->get_setting('non_existent', 'default');

        // Vérifications
        $this->assertEquals($test_data['company_name'], $company_name);
        $this->assertEquals('default', $non_existent);
    }

    /**
     * Teste la réinitialisation des paramètres
     */
    public function test_reset_settings() {
        // Données de test
        $test_data = [
            'company_name' => 'Test Company',
            'time_slot_duration' => 60
        ];
        
        // Sauvegarde des paramètres
        update_option('cal_rdv_settings', $test_data);

        // Réinitialisation
        $this->settings->reset_settings();

        // Récupération des paramètres réinitialisés
        $defaults = $this->settings->get_default_settings();
        $current = get_option('cal_rdv_settings');

        // Vérification que les paramètres ont été réinitialisés
        $this->assertEquals($defaults, $current);
    }

    /**
     * Nettoyage après les tests
     */
    public function tearDown(): void {
        // Suppression des options créées
        delete_option('cal_rdv_settings');
        delete_option('cal_rdv_settings_backup');
        
        parent::tearDown();
    }
}
