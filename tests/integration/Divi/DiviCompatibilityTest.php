<?php
/**
 * Class DiviCompatibilityTest
 * 
 * @package CalendrierRdv\Tests\Integration\Divi
 */

namespace CalendrierRdv\Tests\Integration\Divi;

use WP_UnitTestCase;

class DiviCompatibilityTest extends WP_UnitTestCase {
    private $serviceId;
    private $providerId;
    private $diviBuilder;

    public function setUp(): void {
        parent::setUp();
        
        // Vérifier si Divi est actif
        if (!defined('ET_BUILDER_PLUGIN_ACTIVE') && !defined('ET_BUILDER_THEME')) {
            $this->markTestSkipped('Divi n\'est pas installé ou activé');
        }
        
        // Initialiser le builder Divi si nécessaire
        if (class_exists('ET_Builder_Element')) {
            $this->diviBuilder = new \ET_Builder_Element();
        }
        
        // Créer un service de test
        $this->serviceId = $this->factory->post->create([
            'post_type' => 'cal_rdv_service',
            'post_title' => 'Massage Divi Test',
            'post_status' => 'publish'
        ]);
        
        update_post_meta($this->serviceId, '_service_duration', 60);
        update_post_meta($this->serviceId, '_service_price', 80);
        
        // Créer un prestataire de test
        $this->providerId = $this->factory->user->create([
            'role' => 'cal_rdv_provider',
            'user_login' => 'divi_provider',
            'user_email' => 'divi@example.com',
            'first_name' => 'Divi',
            'last_name' => 'Tester'
        ]);
        
        update_user_meta($this->providerId, '_provider_services', [$this->serviceId]);
        update_user_meta($this->providerId, '_provider_schedule', [
            'monday' => ['start' => '09:00', 'end' => '18:00'],
            'tuesday' => ['start' => '09:00', 'end' => '18:00'],
            'wednesday' => ['start' => '09:00', 'end' => '18:00'],
            'thursday' => ['start' => '09:00', 'end' => '18:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
            'saturday' => ['start' => '10:00', 'end' => '14:00'],
            'sunday' => ['start' => '', 'end' => '']
        ]);
    }

    public function testShortcodeInDiviBuilder() {
        // Tester le rendu du shortcode dans le builder Divi
        $output = do_shortcode('[calendrier_booking service="' . $this->serviceId . '"]');
        
        // Vérifier que le shortcode est correctement rendu
        $this->assertStringContainsString('id="calendrier-booking-form"', $output);
        $this->assertStringContainsString('Massage Divi Test', $output);
        
        // Vérifier que les styles et scripts nécessaires sont chargés
        $this->assertTrue(wp_style_is('calendrier-rdv-frontend', 'registered'));
        $this->assertTrue(wp_script_is('calendrier-rdv-frontend', 'registered'));
    }

    public function testDiviModuleRegistration() {
        // Vérifier que le module Divi est correctement enregistré
        $modules = apply_filters('et_builder_get_parent_modules', []);
        $this->assertArrayHasKey('et_pb_calendrier_booking', $modules);
        
        // Vérifier que le module enfant est également enregistré
        $child_modules = apply_filters('et_builder_get_child_modules', []);
        $this->assertArrayHasKey('et_pb_calendrier_booking', $child_modules);
    }

    public function testDiviModuleRendering() {
        // Créer une instance du module
        $module = new \ET_Builder_Module_Calendrier_Booking();
        
        // Définir les propriétés du module
        $props = [
            'service_id' => $this->serviceId,
            'provider_id' => $this->providerId,
            'show_title' => 'on',
            'show_description' => 'on',
            'custom_css' => '.calendrier-module { background: #f5f5f5; }'
        ];
        
        // Rendre le module
        $output = $module->render($props, null, 'et_pb_module');
        
        // Vérifier le rendu
        $this->assertStringContainsString('class="et_pb_module et_pb_calendrier_booking', $output);
        $this->assertStringContainsString('id="calendrier-booking-form"', $output);
        $this->assertStringContainsString('Massage Divi Test', $output);
        
        // Vérifier que le CSS personnalisé est inclus
        $this->assertStringContainsString('.calendrier-module { background: #f5f5f5; }', $output);
    }

    public function testDiviVisualBuilderCompatibility() {
        // Simuler le mode Visual Builder
        define('ET_FB_ENABLED', true);
        $_GET['et_fb'] = '1';
        
        // Tester le rendu en mode Visual Builder
        $output = do_shortcode('[calendrier_booking service="' . $this->serviceId . '"]');
        
        // Vérifier que les wrappers spécifiques à Divi sont présents
        $this->assertStringContainsString('class="et-fb-iframe-cover"', $output);
        $this->assertStringContainsString('data-et-vb-needs-update="1"', $output);
        
        // Vérifier que les scripts du Visual Builder sont chargés
        $this->assertTrue(wp_script_is('et-builder-modules-script', 'enqueued'));
    }

    public function testDiviThemeBuilderCompatibility() {
        // Créer un template de thème Divi
        $template_id = $this->factory->post->create([
            'post_type' => 'et_template',
            'post_title' => 'Calendrier RDV Template',
            'post_status' => 'publish'
        ]);
        
        // Ajouter des métadonnées de template
        $template_data = [
            'enabled' => true,
            'content' => '[calendrier_booking]',
            'use_on' => ['all'],
            'exclude_from' => [],
            'custom' => []
        ];
        
        update_post_meta($template_id, '_et_builder_template_settings', $template_data);
        
        // Simuler le chargement du template
        $template = new \ET_Theme_Builder_Template($template_id);
        $output = $template->get_output();
        
        // Vérifier que le shortcode est correctement rendu
        $this->assertStringContainsString('id="calendrier-booking-form"', $output);
    }

    public function testDiviCustomizerCompatibility() {
        // Simuler le Customizer
        global $wp_customize;
        require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
        $wp_customize = new \WP_Customize_Manager();
        
        // Enregistrer nos paramètres dans le Customizer
        do_action('customize_register', $wp_customize);
        
        // Vérifier que nos paramètres sont bien enregistrés
        $this->assertInstanceOf('WP_Customize_Setting', $wp_customize->get_setting('cal_rdv_primary_color'));
        $this->assertInstanceOf('WP_Customize_Control', $wp_customize->get_control('cal_rdv_primary_color'));
        
        // Tester la prévisualisation en direct
        $wp_customize->set_setting('cal_rdv_primary_color', '#ff0000');
        $wp_customize->get_setting('cal_rdv_primary_color')->preview();
        
        // Vérifier que la couleur est appliquée
        $output = do_shortcode('[calendrier_booking]');
        $this->assertStringContainsString('--cal-rdv-primary: #ff0000', $output);
    }

    public function tearDown(): void {
        // Nettoyer
        if ($this->serviceId) {
            wp_delete_post($this->serviceId, true);
        }
        
        if ($this->providerId) {
            wp_delete_user($this->providerId, true);
        }
        
        parent::tearDown();
    }
}
