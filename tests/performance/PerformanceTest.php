<?php
/**
 * Class PerformanceTest
 * 
 * @package CalendrierRdv\Tests\Performance
 */

namespace CalendrierRdv\Tests\Performance;

use WP_UnitTestCase;

class PerformanceTest extends WP_UnitTestCase {
    private $serviceId;
    private $providerId;
    private $appointmentIds = [];
    
    // Seuil d'avertissement en secondes
    private const WARNING_THRESHOLD = 2.0;
    private const CRITICAL_THRESHOLD = 5.0;
    
    public function setUp(): void {
        parent::setUp();
        
        // Créer un service de test
        $this->serviceId = $this->factory->post->create([
            'post_type' => 'cal_rdv_service',
            'post_title' => 'Test de Performance',
            'post_status' => 'publish'
        ]);
        
        update_post_meta($this->serviceId, '_service_duration', 30);
        update_post_meta($this->serviceId, '_service_price', 50);
        
        // Créer un prestataire de test
        $this->providerId = $this->factory->user->create([
            'role' => 'cal_rdv_provider',
            'user_login' => 'perf_provider',
            'user_email' => 'perf@example.com',
            'first_name' => 'Performance',
            'last_name' => 'Tester'
        ]);
        
        update_user_meta($this->providerId, '_provider_services', [$this->serviceId]);
        
        // Créer plusieurs rendez-vous pour les tests de charge
        for ($i = 0; $i < 100; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $time = '10:00:00';
            
            $appointmentId = $this->factory->post->create([
                'post_type' => 'cal_rdv_appointment',
                'post_status' => 'publish',
                'post_title' => 'Rendez-vous de test ' . $i
            ]);
            
            update_post_meta($appointmentId, '_appointment_service_id', $this->serviceId);
            update_post_meta($appointmentId, '_appointment_provider_id', $this->providerId);
            update_post_meta($appointmentId, '_appointment_date', $date);
            update_post_meta($appointmentId, '_appointment_start_time', $time);
            update_post_meta($appointmentId, '_appointment_status', 'confirmed');
            
            $this->appointmentIds[] = $appointmentId;
        }
    }
    
    /**
     * Teste le temps de chargement du calendrier avec beaucoup de rendez-vous
     */
    public function testCalendarLoadTimeWithManyAppointments() {
        // Démarrer le chrono
        $startTime = microtime(true);
        
        // Exécuter la requête pour récupérer les rendez-vous
        $appointments = new \WP_Query([
            'post_type' => 'cal_rdv_appointment',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_appointment_service_id',
                    'value' => $this->serviceId,
                    'compare' => '='
                ]
            ]
        ]);
        
        // Arrêter le chrono
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Vérifier que la requête a réussi
        $this->assertTrue($appointments->have_posts());
        
        // Vérifier le temps d'exécution
        $this->assertLessThan(
            self::CRITICAL_THRESHOLD,
            $executionTime,
            "Le chargement du calendrier avec de nombreux rendez-vous a pris trop de temps: {$executionTime}s"
        );
        
        if ($executionTime > self::WARNING_THRESHOLD) {
            fwrite(STDERR, "[WARNING] Le test de performance a pris {$executionTime}s (seuil d'avertissement: " . self::WARNING_THRESHOLD . "s)\n");
        }
    }
    
    /**
     * Teste le temps de rendu du shortcode avec beaucoup de rendez-vous
     */
    public function testShortcodeRenderTimeWithManyAppointments() {
        // Démarrer le chrono
        $startTime = microtime(true);
        
        // Rendre le shortcode
        ob_start();
        echo do_shortcode('[calendrier_booking service="' . $this->serviceId . '"]');
        $output = ob_get_clean();
        
        // Arrêter le chrono
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Vérifier que le rendu contient le formulaire
        $this->assertStringContainsString('id="calendrier-booking-form"', $output);
        
        // Vérifier le temps d'exécution
        $this->assertLessThan(
            self::CRITICAL_THRESHOLD,
            $executionTime,
            "Le rendu du shortcode a pris trop de temps: {$executionTime}s"
        );
        
        if ($executionTime > self::WARNING_THRESHOLD) {
            fwrite(STDERR, "[WARNING] Le rendu du shortcode a pris {$executionTime}s (seuil d'avertissement: " . self::WARNING_THRESHOLD . "s)\n");
        }
    }
    
    /**
     * Teste les performances de la requête de disponibilité
     */
    public function testAvailabilityQueryPerformance() {
        // Démarrer le chrono
        $startTime = microtime(true);
        
        // Exécuter la requête de disponibilité
        $available_slots = [];
        $start_date = current_time('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+1 month'));
        
        $current_date = new \DateTime($start_date);
        $end_date_obj = new \DateTime($end_date);
        
        while ($current_date <= $end_date_obj) {
            $date = $current_date->format('Y-m-d');
            $day_of_week = strtolower($current_date->format('l'));
            
            // Simuler la vérification de disponibilité pour chaque créneau
            for ($hour = 9; $hour < 18; $hour++) {
                $time = sprintf('%02d:00:00', $hour);
                $available_slots[] = [
                    'date' => $date,
                    'time' => $time,
                    'available' => true
                ];
            }
            
            $current_date->modify('+1 day');
        }
        
        // Arrêter le chrono
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Vérifier que des créneaux ont été trouvés
        $this->assertNotEmpty($available_slots);
        
        // Vérifier le temps d'exécution
        $this->assertLessThan(
            0.5, // Seuil plus strict pour cette opération
            $executionTime,
            "La vérification de disponibilité a pris trop de temps: {$executionTime}s"
        );
    }
    
    /**
     * Teste la consommation mémoire lors du chargement de nombreux rendez-vous
     */
    public function testMemoryUsageWithManyAppointments() {
        // Mémoire avant le test
        $startMemory = memory_get_usage(true);
        
        // Charger tous les rendez-vous
        $appointments = new \WP_Query([
            'post_type' => 'cal_rdv_appointment',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_appointment_service_id',
                    'value' => $this->serviceId,
                    'compare' => '='
                ]
            ]
        ]);
        
        // Mémoire après le chargement
        $endMemory = memory_get_usage(true);
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convertir en Mo
        
        // Vérifier que la consommation mémoire est raisonnable
        $maxMemoryMb = 50; // 50 Mo maximum
        $this->assertLessThan(
            $maxMemoryMb,
            $memoryUsed,
            "La consommation mémoire est trop élevée: {$memoryUsed}MB (max: {$maxMemoryMb}MB)"
        );
        
        if ($memoryUsed > ($maxMemoryMb * 0.8)) {
            fwrite(STDERR, "[WARNING] Consommation mémoire élevée: {$memoryUsed}MB (max: {$maxMemoryMb}MB)\n");
        }
    }
    
    public function tearDown(): void {
        // Nettoyer les rendez-vous
        foreach ($this->appointmentIds as $appointmentId) {
            wp_delete_post($appointmentId, true);
        }
        
        // Nettoyer le service
        if ($this->serviceId) {
            wp_delete_post($this->serviceId, true);
        }
        
        // Nettoyer le prestataire
        if ($this->providerId) {
            wp_delete_user($this->providerId, true);
        }
        
        parent::tearDown();
    }
}
