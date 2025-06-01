<?php

namespace CalendrierRdv\Tests\Integration;

use CalendrierRdv\Tests\Integration\TestCase;
use WP_REST_Request;

class RestPermissionsTest extends TestCase
{
    protected $service_id;
    protected $provider_id;

    protected function setUp(): void 
    {
        parent::setUp();
        
        // Créer des données de test
        $this->provider_id = create_test_prestataire();
        $this->service_id = create_test_service([
            'meta_input' => [
                '_prestataires' => [$this->provider_id]
            ]
        ]);
        
        // Réinitialiser les rôles utilisateur
        $this->reset_roles();
    }

    /**
     * Teste l'accès non autorisé aux endpoints protégés
     */
    public function test_unauthenticated_access()
    {
        // Définir l'utilisateur comme non connecté
        wp_set_current_user(0);
        
        // Tester différents endpoints protégés
        $endpoints = [
            ['method' => 'POST', 'path' => '/calendrier-rdv/v1/services'],
            ['method' => 'PUT', 'path' => '/calendrier-rdv/v1/services/' . $this->service_id],
            ['method' => 'DELETE', 'path' => '/calendrier-rdv/v1/services/' . $this->service_id],
            ['method' => 'POST', 'path' => '/calendrier-rdv/v1/appointments']
        ];
        
        foreach ($endpoints as $endpoint) {
            $request = new WP_REST_Request($endpoint['method'], $endpoint['path']);
            $response = rest_do_request($request);
            
            $this->assertEquals(
                401, 
                $response->get_status(),
                sprintf('Échec pour %1$s %2$s', $endpoint['method'], $endpoint['path'])
            );
        }
    }

    /**
     * Teste les accès en fonction des rôles utilisateurs
     */
    public function test_role_based_access()
    {
        // Tester avec un rôle abonné (accès limité)
        $subscriber_id = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);
        
        $endpoints = [
            ['method' => 'POST', 'path' => '/calendrier-rdv/v1/services', 'expected' => 403],
            ['method' => 'GET', 'path' => '/calendrier-rdv/v1/services', 'expected' => 200]
        ];
        
        foreach ($endpoints as $endpoint) {
            $request = new WP_REST_Request($endpoint['method'], $endpoint['path']);
            $response = rest_do_request($request);
            
            $this->assertEquals(
                $endpoint['expected'],
                $response->get_status(),
                sprintf('Échec pour %1$s %2$s avec rôle abonné', $endpoint['method'], $endpoint['path'])
            );
        }
        
        // Tester avec un administrateur (accès complet)
        $admin_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
        
        $endpoints = [
            ['method' => 'POST', 'path' => '/calendrier-rdv/v1/services', 'expected' => 201],
            ['method' => 'GET', 'path' => '/calendrier-rdv/v1/services', 'expected' => 200]
        ];
        
        foreach ($endpoints as $endpoint) {
            $request = new WP_REST_Request($endpoint['method'], $endpoint['path']);
            $response = rest_do_request($request);
            
            $this->assertEquals(
                $endpoint['expected'],
                $response->get_status(),
                sprintf('Échec pour %1$s %2$s avec rôle administrateur', $endpoint['method'], $endpoint['path'])
            );
        }
    }

    protected function tearDown(): void 
    {
        if (isset($this->service_id)) {
            wp_delete_post($this->service_id, true);
        }
        if (isset($this->provider_id)) {
            wp_delete_post($this->provider_id, true);
        }
        
        // Nettoyer les utilisateurs créés
        $users = get_users(['role__in' => ['subscriber', 'administrator']]);
        foreach ($users as $user) {
            wp_delete_user($user->ID);
        }
        
        parent::tearDown();
    }
}
