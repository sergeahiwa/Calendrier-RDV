<?php
/**
 * Tests d'intégration pour la file d'attente des emails
 */

class CalRdv_Email_Queue_Integration_Test extends WP_UnitTestCase {
    private $email_queue;
    private $table_name;
    
    public function setUp(): void {
        parent::setUp();
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rdv_email_failures';
        $this->email_queue = CalRdv_Email_Queue::get_instance();
        
        // Réinitialiser la table avant chaque test
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
    
    public function tearDown(): void {
        parent::tearDown();
    }
    
    /**
     * Test d'intégration complet avec simulation d'échec SMTP
     */
    public function test_complete_workflow() {
        // 1. Simuler un échec d'envoi d'email
        $email_data = [
            'date_rdv' => '2025-06-01 14:30',
            'service' => 'Consultation initiale',
            'client' => [
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'jean.dupont@example.com'
            ]
        ];
        
        // 2. Ajouter à la file d'attente
        $id = $this->email_queue->add_failed_email([
            'recipient_email' => $email_data['client']['email'],
            'recipient_name' => $email_data['client']['prenom'] . ' ' . $email_data['client']['nom'],
            'subject' => 'Échec d\'envoi - Confirmation de rendez-vous',
            'error_code' => 'smtp_error',
            'error_message' => 'Erreur de connexion au serveur SMTP',
            'email_data' => $email_data,
            'max_retries' => 2
        ]);
        
        // 3. Vérifier l'ajout dans la table
        global $wpdb;
        $saved = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );
        
        $this->assertEquals('pending', $saved['status']);
        $this->assertEquals(0, $saved['retry_count']);
        
        // 4. Première tentative d'envoi (simuler un échec)
        add_filter('pre_http_request', [$this, 'mock_smtp_failure'], 10, 3);
        $results = $this->email_queue->process_queue();
        remove_filter('pre_http_request', [$this, 'mock_smtp_failure']);
        
        // 5. Vérifier le statut après échec
        $after_first_attempt = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );
        
        $this->assertEquals(1, $after_first_attempt['retry_count']);
        $this->assertEquals('pending', $after_first_attempt['status']);
        
        // 6. Deuxième tentative (simuler un succès)
        add_filter('pre_http_request', [$this, 'mock_smtp_success'], 10, 3);
        $results = $this->email_queue->process_queue();
        remove_filter('pre_http_request', [$this, 'mock_smtp_success']);
        
        // 7. Vérifier le statut final
        $final = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );
        
        $this->assertEquals('sent', $final['status']);
        $this->assertNotNull($final['updated_at']);
    }
    
    /**
     * Simule un échec SMTP
     */
    public function mock_smtp_failure($preempt, $args, $url) {
        return [
            'response' => ['code' => 500],
            'body' => json_encode([
                'message' => 'SMTP server unavailable',
                'code' => 'smtp_error'
            ])
        ];
    }
    
    /**
     * Simule un envoi réussi
     */
    public function mock_smtp_success() {
        return [
            'response' => ['code' => 200],
            'body' => json_encode([
                'message' => 'Email sent',
                'id' => 'msg_' . uniqid()
            ])
        ];
    }
}
