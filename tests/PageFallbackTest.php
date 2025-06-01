<?php
// tests/PageFallbackTest.php

use PHPUnit\Framework\TestCase;

class Calendrier_RDV_Page_Fallback_Test extends WP_UnitTestCase {
    public function test_calendar_page_fallback() {
        // Suppression de la page calendrier si elle existe
        $page = get_page_by_path('calendrier-rdv');
        if ($page) {
            wp_delete_post($page->ID, true);
        }

        // Appel de la fonction de fallback (à adapter selon ton plugin)
        if (function_exists('calrdv_ensure_calendar_page')) {
            calrdv_ensure_calendar_page();
        }

        // Vérifie que la page a bien été recréée
        $page = get_page_by_path('calendrier-rdv');
        $this->assertNotNull($page, 'La page calendrier doit être recréée si supprimée.');
    }
}
