<?php
/**
 * Class SecurityTest
 * 
 * @package CalendrierRdv\Tests\Security
 */

namespace CalendrierRdv\Tests\Security;

use WP_UnitTestCase;
use WP_REST_Request;

class SecurityTest extends WP_UnitTestCase {
    private $admin_id;
    private $subscriber_id;
    private $service_id;
    private $appointment_id;
    
    public function setUp(): void {
        parent::setUp();
        
        // Créer un administrateur pour les tests
        $this->admin_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_login' => 'security_admin',
            'user_pass' => 'testpass123'
        ]);
        
        // Créer un utilisateur standard pour les tests
        $this->subscriber_id = $this->factory->user->create([
            'role' => 'subscriber',
            'user_login' => 'security_user',
            'user_pass' => 'testpass123'
        ]);
        
        // Créer un service de test
        $this->service_id = $this->factory->post->create([
            'post_type' => 'cal_rdv_service',
            'post_title' => 'Service de test de sécurité',
            'post_status' => 'publish'
        ]);
        
        update_post_meta($this->service_id, '_service_duration', 60);
        update_post_meta($this->service_id, '_service_price', 100);
        
        // Créer un rendez-vous de test
        $this->appointment_id = $this->factory->post->create([
            'post_type' => 'cal_rdv_appointment',
            'post_status' => 'publish',
            'post_title' => 'Rendez-vous de test de sécurité'
        ]);
        
        update_post_meta($this->appointment_id, '_appointment_service_id', $this->service_id);
        update_post_meta($this->appointment_id, '_appointment_date', '2025-06-15');
        update_post_meta($this->appointment_id, '_appointment_start_time', '14:00:00');
        update_post_meta($this->appointment_id, '_appointment_status', 'pending');
    }
    
    /**
     * Teste la protection CSRF sur les endpoints d'administration
     */
    public function testCsrfProtection() {
        // Simuler une requête sans nonce
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/admin/appointments');
        $response = rest_do_request($request);
        
        // Vérifier que la requête est rejetée avec une erreur 403
        $this->assertEquals(403, $response->get_status());
        $this->assertEquals('rest_cookie_invalid_nonce', $response->get_data()['code']);
    }
    
    /**
     * Teste les contrôles d'accès basés sur les rôles
     */
    public function testRoleBasedAccessControl() {
        // Tester l'accès administrateur
        wp_set_current_user($this->admin_id);
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/admin/appointments');
        $response = rest_do_request($request);
        
        // Un admin devrait pouvoir accéder à cette route
        $this->assertNotEquals(403, $response->get_status());
        
        // Tester l'accès utilisateur standard
        wp_set_current_user($this->subscriber_id);
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/admin/appointments');
        $response = rest_do_request($request);
        
        // Un utilisateur standard ne devrait pas pouvoir accéder à cette route
        $this->assertEquals(403, $response->get_status());
    }
    
    /**
     * Teste la validation des entrées utilisateur
     */
    public function testInputValidation() {
        wp_set_current_user($this->admin_id);
        
        // Tester une requête avec des données malveillantes
        $malicious_data = [
            'service_id' => $this->service_id,
            'date' => '2025-06-15',
            'time' => '14:00:00',
            'customer_name' => '<script>alert("XSS")</script>',
            'customer_email' => 'not-an-email',
            'customer_phone' => '0123456789',
            'notes' => 'Test de sécurité avec caractères spéciaux: &<>"\''
        ];
        
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/appointments');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode($malicious_data));
        
        $response = rest_do_request($request);
        
        // Vérifier que la validation a échoué
        $this->assertEquals(400, $response->get_status());
        
        // Vérifier que les erreurs de validation sont présentes
        $data = $response->get_data();
        $this->assertArrayHasKey('code', $data);
        $this->assertEquals('rest_invalid_param', $data['code']);
        
        // Vérifier que le script XSS a été nettoyé
        $this->assertNotContains('<script>', $malicious_data['customer_name']);
    }
    
    /**
     * Teste la protection contre les injections SQL
     */
    public function testSqlInjectionProtection() {
        wp_set_current_user($this->admin_id);
        
        // Tenter une injection SQL dans un paramètre de recherche
        $injection = "1' OR '1'='1";
        
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/admin/appointments');
        $request->set_param('search', $injection);
        
        $response = rest_do_request($request);
        
        // La requête ne devrait pas échouer avec une erreur SQL
        $this->assertNotEquals(500, $response->get_status());
        
        // Vérifier que la recherche n'a pas renvoyé tous les résultats (comme le ferait une injection réussie)
        $data = $response->get_data();
        $this->assertArrayHasKey('data', $data);
        $this->assertEmpty($data['data'], 'La recherche a renvoyé des résultats inattendus, possible injection SQL');
    }
    
    /**
     * Teste la protection contre les attaques XSS
     */
    public function testXssProtection() {
        wp_set_current_user($this->admin_id);
        
        // Créer un rendez-vous avec un script malveillant dans les notes
        $xss_payload = '<script>alert("XSS")</script>';
        
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/appointments');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'service_id' => $this->service_id,
            'date' => '2025-06-15',
            'time' => '14:00:00',
            'customer_name' => 'Test XSS',
            'customer_email' => 'test@example.com',
            'customer_phone' => '0123456789',
            'notes' => $xss_payload
        ]));
        
        $response = rest_do_request($request);
        
        // Vérifier que la requête a réussi
        $this->assertEquals(201, $response->get_status());
        
        // Récupérer le rendez-vous créé
        $appointment_id = $response->get_data()['id'];
        $request = new WP_REST_Request('GET', "/calendrier-rdv/v1/appointments/{$appointment_id}");
        $response = rest_do_request($request);
        
        // Vérifier que le script a été échappé
        $data = $response->get_data();
        $this->assertStringNotContainsString('<script>', $data['notes']);
        $this->assertStringContainsString('&lt;script&gt;', $data['notes']);
    }
    
    /**
     * Teste la protection contre les attaques CSRF sur les formulaires
     */
    public function testFormCsrfProtection() {
        // Simuler une soumission de formulaire sans jeton CSRF
        $_POST = [
            'action' => 'cal_rdv_submit_appointment',
            'service_id' => $this->service_id,
            'date' => '2025-06-15',
            'time' => '14:00:00',
            'customer_name' => 'Test CSRF',
            'customer_email' => 'test@example.com',
            'customer_phone' => '0123456789'
        ];
        
        // Capturer la sortie pour vérifier le message d'erreur
        ob_start();
        do_action('admin_post_nopriv_cal_rdv_submit_appointment');
        $output = ob_get_clean();
        
        // Vérifier que la requête a échoué avec une erreur CSRF
        $this->assertStringContainsString('Erreur de sécurité', $output);
    }
    
    public function tearDown(): void {
        // Nettoyer
        if ($this->appointment_id) {
            wp_delete_post($this->appointment_id, true);
        }
        
        if ($this->service_id) {
            wp_delete_post($this->service_id, true);
        }
        
        if ($this->admin_id) {
            wp_delete_user($this->admin_id, true);
        }
        
        if ($this->subscriber_id) {
            wp_delete_user($this->subscriber_id, true);
        }
        
        parent::tearDown();
    }
}
