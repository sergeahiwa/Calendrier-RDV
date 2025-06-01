<?php
/**
 * Tests d'intégration pour la classe Admin
 *
 * @package CalendrierRDV\Tests\Integration\Admin
 */

namespace CalendrierRDV\Tests\Integration\Admin;

use CalendrierRDV\Includes\Admin\Admin;
use WP_UnitTestCase;

/**
 * Classe de test pour l'administration
 */
class AdminTest extends WP_UnitTestCase {

    /**
     * Instance de la classe Admin
     *
     * @var Admin
     */
    private $admin;

    /**
     * Configuration initiale pour les tests
     */
    public function setUp(): void {
        parent::setUp();
        $this->admin = new Admin('calendrier-rdv', '1.0.0');
    }

    /**
     * Teste l'ajout des menus d'administration
     */
    public function test_admin_menu_initialization() {
        global $menu, $submenu;
        
        // Exécuter la méthode qui ajoute les menus
        $this->admin->add_admin_menu();
        
        // Vérifier que le menu principal a été ajouté
        $this->assertArrayHasKey('calendrier-rdv', $GLOBALS['admin_page_hooks']);
        
        // Vérifier les sous-menus
        $this->assertNotEmpty($submenu['calendrier-rdv']);
        $submenu_items = wp_list_pluck($submenu['calendrier-rdv'], 0);
        $this->assertContains('Tableau de bord', $submenu_items);
        $this->assertContains('Paramètres', $submenu_items);
    }

    /**
     * Teste le chargement des scripts et styles
     */
    public function test_enqueue_scripts() {
        // Créer un faux écran d'administration
        set_current_screen('toplevel_page_calendrier-rdv');
        
        // Exécuter la méthode qui charge les scripts
        do_action('admin_enqueue_scripts', 'toplevel_page_calendrier-rdv');
        
        // Vérifier que les scripts sont enregistrés
        $this->assertTrue(wp_script_is('calendrier-rdv-admin', 'registered'));
        $this->assertTrue(wp_style_is('calendrier-rdv-admin', 'registered'));
    }

    /**
     * Teste la gestion des capacités utilisateur
     */
    public function test_user_capabilities() {
        // Créer un rôle de test
        $role = 'editor';
        
        // Vérifier que le rôle n'a pas les capacités par défaut
        $editor = get_role($role);
        $this->assertFalse($editor->has_cap('manage_calendrier_rdv'));
        
        // Ajouter les capacités
        $this->admin->add_capabilities();
        
        // Vérifier que les capacités ont été ajoutées
        $editor = get_role($role);
        $this->assertTrue($editor->has_cap('manage_calendrier_rdv'));
        
        // Nettoyer
        remove_role($role);
    }

    /**
     * Teste l'affichage de la page d'administration
     */
    public function test_admin_page_display() {
        // Créer un utilisateur administrateur
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        
        // Capturer la sortie de la page
        ob_start();
        $this->admin->display_admin_page();
        $output = ob_get_clean();
        
        // Vérifier que le contenu de base est présent
        $this->assertStringContainsString('Calendrier RDV', $output);
        $this->assertStringContainsString('Tableau de bord', $output);
    }

    /**
     * Teste la sauvegarde des paramètres
     */
    public function test_settings_save() {
        // Données de test
        $_POST = [
            'cal_rdv_nonce' => wp_create_nonce('cal_rdv_save_settings'),
            'cal_rdv_settings' => [
                'company_name' => 'Test Company',
                'time_slot_duration' => '30',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i'
            ]
        ];
        
        // Exécuter la sauvegarde
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->admin->save_settings();
        
        // Vérifier que les options ont été sauvegardées
        $options = get_option('cal_rdv_settings');
        $this->assertEquals('Test Company', $options['company_name']);
        $this->assertEquals('30', $options['time_slot_duration']);
    }

    /**
     * Nettoyage après les tests
     */
    public function tearDown(): void {
        parent::tearDown();
        
        // Supprimer les données de test
        delete_option('cal_rdv_settings');
        
        // Réinitialiser les variables globales
        unset($GLOBALS['menu'], $GLOBALS['submenu']);
    }
}
