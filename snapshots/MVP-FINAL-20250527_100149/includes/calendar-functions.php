<?php
// Fonctions d'intégration (enqueue, nonce, etc.)
add_action('wp_enqueue_scripts', function() {
    if (is_page('Calendrier des Rendez-vous')) {
        // FullCalendar CDN
        wp_enqueue_style('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css', [], null);
        wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js', [], null, true);
        // Script principal
        wp_enqueue_script('divi-calendar-js', get_stylesheet_directory_uri() . '/public/divi-calendar.js', ['fullcalendar'], '1.0', true);
        // Passage des variables JS
        wp_localize_script('divi-calendar-js', 'calendarAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('calendar_nonce'),
            'prestataire_id' => 1 // à adapter si multi-prestataires
        ]);
    }
});
