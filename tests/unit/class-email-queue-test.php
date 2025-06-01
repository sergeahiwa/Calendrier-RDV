<?php
/**
 * Tests unitaires pour la classe CalRdv_Email_Queue
 * 
 * @package CalendrierRdv\Tests\Unit
 */

// Charger WordPress pour les tests
require_once dirname(dirname(dirname(__FILE__))) . '/wp-load.php';

class CalRdv_Email_Queue_Test extends WP_UnitTestCase {
    /**
     * @var CalRdv_Email_Queue
     */
    private $email_queue;
    
    /**
     * @var string
     */
    private $table_name;
    
    /**
     * Configuration initiale avant chaque test
     */
    public function setUp(): void {
        parent::setUp();
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rdv_email_failures';
        $this->email_queue = CalRdv_Email_Queue::get_instance();
        
        // Créer la table si elle n'existe pas
        $migration = new CalRdv_Migration_1_0_0();
        $migration->run();
    }
    
    /**
     * Nettoyage après chaque test
     */
    public function tearDown(): void {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        parent::tearDown();
    }
    
    /**
     * Teste l'ajout d'un email en échec à la file d'attente
     */
    public function test_add_failed_email() {
        // Données de test
        $data = [
            'recipient_email' => 'test@example.com',
            'recipient_name' => 'Test User',
            'subject' => 'Test d\'envoi d\'email',
            'error_code' => 'smtp_error',
            'error_message' => 'Erreur SMTP',
            'email_data' => ['test' => 'data'],
            'max_retries' => 3,
            'status' => 'pending'
        ];
        
        // Ajouter à la file d'attente
        $id = $this->email_queue->add_failed_email($data);
        
        // Vérifier que l'ID est retourné
        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, $id);
        
        // Vérifier que les données sont correctement enregistrées
        global $wpdb;
        $saved = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );
        
        $this->assertEquals($data['recipient_email'], $saved['recipient_email']);
        $this->assertEquals($data['subject'], $saved['subject']);
        $this->assertEquals($data['error_code'], $saved['error_code']);
        $this->assertEquals('pending', $saved['status']);
        $this->assertEquals(0, $saved['retry_count']);
    }
    
    /**
     * Teste le traitement de la file d'attente
     */
    public function test_process_queue() {
        // Ajouter plusieurs emails en échec
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $this->email_queue->add_failed_email([
                'recipient_email' => "test{$i}@example.com",
                'subject' => "Test {$i}",
                'error_code' => 'temporary_error',
                'email_data' => ['test' => $i]
            ]);
        }
        
        // Traiter la file d'attente
        $results = $this->email_queue->process_queue(3); // Limiter à 3 traitements
        
        // Vérifier les résultats
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('skipped', $results);
        $this->assertArrayHasKey('details', $results);
        
        // Vérifier qu'on a bien traité 3 emails (limite imposée)
        $this->assertLessThanOrEqual(3, count($results['details']));
    }
    
    /**
     * Teste le nettoyage des anciennes entrées
     */
    public function test_cleanup_old_failures() {
        // Ajouter des entrées avec différentes dates
        $this->add_old_entries(10, '-1 day'); // Hier
        $this->add_old_entries(5, '-31 days'); // Il y a 31 jours
        $this->add_old_entries(3, '-60 days'); // Il y a 60 jours
        
        // Vérifier qu'on a bien 18 entrées au total
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $this->assertEquals(18, $count);
        
        // Nettoyer les entrées de plus de 30 jours
        $deleted = $this->email_queue->cleanup_old_failures(30);
        
        // Vérifier qu'on a supprimé les bonnes entrées
        $this->assertEquals(3, $deleted); // Les 3 entrées de 60 jours
        
        $remaining = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $this->assertEquals(15, $remaining);
    }
    
    /**
     * Ajoute des entrées avec une date spécifique
     * 
     * @param int $count Nombre d'entrées à ajouter
     * @param string $date_offset Décalage de date (ex: '-1 day')
     */
    private function add_old_entries($count, $date_offset) {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime($date_offset));
        
        for ($i = 0; $i < $count; $i++) {
            $wpdb->insert(
                $this->table_name,
                [
                    'recipient_email' => "old{$i}@example.com",
                    'subject' => "Old entry {$i}",
                    'error_code' => 'test',
                    'status' => 'failed',
                    'created_at' => $date,
                    'updated_at' => $date
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s']
            );
        }
    }
}
