<?php
/**
 * Class AccessibilityTest
 * 
 * @package CalendrierRdv\Tests\Accessibility
 */

namespace CalendrierRdv\Tests\Accessibility;

use WP_UnitTestCase;
use WP_REST_Request;

class AccessibilityTest extends WP_UnitTestCase {
    private $service_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Créer un service de test
        $this->service_id = $this->factory->post->create([
            'post_type' => 'cal_rdv_service',
            'post_title' => 'Service de test accessibilité',
            'post_status' => 'publish'
        ]);
        
        update_post_meta($this->service_id, '_service_duration', 45);
        update_post_meta($this->service_id, '_service_price', 75);
    }
    
    /**
     * Teste la structure sémantique HTML du formulaire de réservation
     */
    public function testBookingFormSemanticStructure() {
        // Rendre le shortcode
        $output = do_shortcode('[calendrier_booking service="' . $this->service_id . '"]');
        
        // Vérifier que la sortie contient les balises sémantiques attendues
        $this->assertStringContainsString('<form', $output, 'Le formulaire devrait utiliser la balise <form>');
        $this->assertStringContainsString('role="form"', $output, 'Le formulaire devrait avoir un rôle ARIA "form"');
        $this->assertStringContainsString('aria-labelledby', $output, 'Le formulaire devrait avoir un attribut aria-labelledby');
        
        // Vérifier la présence de labels pour chaque champ
        $this->assertStringContainsString('<label', $output, 'Le formulaire devrait contenir des étiquettes (labels)');
        $this->assertStringContainsString('for=', $output, 'Les labels devraient avoir un attribut for');
        
        // Vérifier les attributs ARIA pour les champs obligatoires
        $this->assertStringContainsString('required', $output, 'Les champs obligatoires devraient avoir l\'attribut required');
        $this->assertStringContainsString('aria-required="true"', $output, 'Les champs obligatoires devraient avoir aria-required="true"');
    }
    
    /**
     * Teste le contraste des couleurs
     */
    public function testColorContrast() {
        // Récupérer le contenu de la feuille de style
        $css_file = file_get_contents(plugin_dir_path(dirname(__DIR__)) . 'assets/css/calendrier-rdv.css');
        
        // Vérifier que les couleurs de texte ont un contraste suffisant
        // Cette vérification est basique et devrait être complétée par des outils comme axe ou pa11y
        $this->assertNotRegExp('/color:\s*#[0-9a-f]{3,6}\s*;\s*background-color:\s*#[0-9a-f]{3,6}\s*;/i', $css_file, 
            'Les couleurs de texte et d\'arrière-plan devraient avoir un contraste suffisant');
    }
    
    /**
     * Teste la navigation au clavier
     */
    public function testKeyboardNavigation() {
        // Rendre le shortcode
        $output = do_shortcode('[calendrier_booking service="' . $this->service_id . '"]');
        
        // Vérifier que les éléments interactifs sont focusables
        $this->assertStringContainsString('tabindex="0"', $output, 'Les éléments interactifs devraient être focusables');
        
        // Vérifier que l'ordre de tabulation est logique
        $this->assertGreaterThan(
            strpos($output, 'name="customer_name"'),
            strpos($output, 'name="customer_email"'),
            'L\'ordre de tabulation devrait être logique (nom avant email)'
        );
    }
    
    /**
     * Teste les alternatives textuelles pour les images
     */
    public function testImageAltText() {
        // Rendre le shortcode
        $output = do_shortcode('[calendrier_booking service="' . $this->service_id . '"]');
        
        // Vérifier que les images ont des attributs alt
        if (preg_match_all('/<img[^>]+>/i', $output, $images)) {
            foreach ($images[0] as $img) {
                $this->assertStringContainsString('alt=', $img, 'Toutes les images devraient avoir un attribut alt');
                $this->assertNotRegExp('/<img[^>]*alt=[\'"][\s\'"][^>]*>/i', $img, 'Les attributs alt ne devraient pas être vides');
            }
        }
    }
    
    /**
     * Teste les messages d'erreur accessibles
     */
    public function testAccessibleErrorMessages() {
        // Simuler une soumission de formulaire invalide
        $_POST = [
            'action' => 'cal_rdv_submit_appointment',
            'service_id' => $this->service_id,
            'nonce' => wp_create_nonce('cal_rdv_booking_nonce')
        ];
        
        // Capturer la sortie
        ob_start();
        do_action('admin_post_nopriv_cal_rdv_submit_appointment');
        $output = ob_get_clean();
        
        // Vérifier que les messages d'erreur sont correctement associés aux champs
        $this->assertStringContainsString('aria-invalid="true"', $output, 'Les champs invalides devraient avoir aria-invalid="true"');
        $this->assertStringContainsString('aria-describedby', $output, 'Les champs avec erreur devraient avoir un aria-describedby pointant vers le message d\'erreur');
        $this->assertStringContainsString('role="alert"', $output, 'Les messages d\'erreur devraient avoir role="alert"');
    }
    
    /**
     * Teste la navigation par en-têtes
     */
    public function testHeadingStructure() {
        // Rendre le shortcode
        $output = do_shortcode('[calendrier_booking service="' . $this->service_id . '"]');
        
        // Vérifier la présence d'un titre principal h1
        $this->assertStringContainsString('<h1', $output, 'La page devrait avoir un titre principal h1');
        
        // Vérifier que la hiérarchie des titres est correcte
        $h1_count = substr_count($output, '<h1');
        $h2_count = substr_count($output, '<h2');
        
        $this->assertGreaterThan(0, $h1_count, 'La page devrait avoir au moins un titre h1');
        $this->assertGreaterThanOrEqual($h1_count, $h2_count, 'La hiérarchie des titres devrait être logique (h1 avant h2)');
    }
    
    /**
     * Teste les alternatives pour le contenu non textuel
     */
    public function testNonTextContentAlternatives() {
        // Rendre le shortcode
        $output = do_shortcode('[calendrier_booking service="' . $this->service_id . '"]');
        
        // Vérifier les icônes Font Awesome (si utilisées)
        if (strpos($output, 'fa-') !== false) {
            $this->assertStringContainsString('aria-hidden="true"', $output, 'Les icônes décoratives devraient avoir aria-hidden="true"');
        }
        
        // Vérifier les éléments décoratifs
        $this->assertNotRegExp('/<div[^>]*role="presentation"[^>]*>\s*<\/div>/i', $output, 'Les éléments décoratifs ne devraient pas être dans l\'arbre d\'accessibilité');
    }
    
    public function tearDown(): void {
        // Nettoyer
        if ($this->service_id) {
            wp_delete_post($this->service_id, true);
        }
        
        parent::tearDown();
    }
}
