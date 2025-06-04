<?php
/**
 * Tests unitaires pour le gestionnaire de cache
 */

use CalendrierRdv\Core\Cache_Manager;
use PHPUnit\Framework\TestCase;

// Simuler les fonctions WordPress si elles n'existent pas
if (!function_exists('get_transient')) {
    function get_transient($key) {
        global $wp_transients;
        return $wp_transients[$key] ?? false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration) {
        global $wp_transients;
        $wp_transients[$key] = $value;
        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($key) {
        global $wp_transients;
        unset($wp_transients[$key]);
        return true;
    }
}

class CacheManagerTest extends TestCase {
    protected function setUp(): void {
        global $wp_transients;
        $wp_transients = [];
    }
    
    public function testSetAndGetCache() {
        $key = 'test_key';
        $value = ['test' => 'value'];
        
        // Test de récupération d'une valeur non existante
        $this->assertNull(Cache_Manager::get($key));
        
        // Test de mise en cache
        $this->assertTrue(Cache_Manager::set($key, $value, 60));
        
        // Test de récupération
        $cached = Cache_Manager::get($key);
        $this->assertEquals($value, $cached);
    }
    
    public function testDeleteCache() {
        $key = 'test_key_delete';
        $value = 'value_to_delete';
        
        // On met en cache
        Cache_Manager::set($key, $value);
        
        // On vérifie que c'est bien en cache
        $this->assertEquals($value, Cache_Manager::get($key));
        
        // On supprime
        $this->assertTrue(Cache_Manager::delete($key));
        
        // On vérifie que ce n'est plus en cache
        $this->assertNull(Cache_Manager::get($key));
    }
    
    public function testFlushCache() {
        // On ajoute quelques entrées en cache
        $test_data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ];
        
        foreach ($test_data as $key => $value) {
            Cache_Manager::set($key, $value);
        }
        
        // On vérifie que tout est bien en cache
        foreach ($test_data as $key => $value) {
            $this->assertEquals($value, Cache_Manager::get($key));
        }
        
        // On vide le cache
        $this->assertTrue(Cache_Manager::flush());
        
        // On vérifie que plus rien n'est en cache
        foreach (array_keys($test_data) as $key) {
            $this->assertNull(Cache_Manager::get($key));
        }
    }
}
