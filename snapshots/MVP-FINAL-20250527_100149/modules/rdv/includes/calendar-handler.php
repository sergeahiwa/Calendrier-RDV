<?php
// calendar-handler.php : Handlers AJAX robustes pour slots et réservation
add_action('wp_ajax_get_slots', 'rdv_get_slots_callback');
add_action('wp_ajax_nopriv_get_slots', 'rdv_get_slots_callback');
add_action('wp_ajax_book_slot', 'rdv_book_slot_callback');
add_action('wp_ajax_nopriv_book_slot', 'rdv_book_slot_callback');

function rdv_get_slots_callback() {
    check_ajax_referer('calendar_nonce', 'nonce');
    global $wpdb;
    $date = sanitize_text_field($_GET['date'] ?? '');
    $prestataire_id = intval($_GET['prestataire_id'] ?? 1);
    $table = $wpdb->prefix . 'rdv_events';
    if (!$date) {
        wp_send_json_error(['message' => 'Date manquante.']);
    }
    // Créneaux de 15:30 à 23:30 toutes les 30min
    $slots = [];
    for ($h = 15; $h <= 23; $h++) {
        $slots[] = sprintf('%02d:30', $h);
        if ($h < 23) $slots[] = sprintf('%02d:00', $h+1);
    }
    // Exclure les créneaux déjà réservés
    $query = $wpdb->prepare("SELECT start FROM $table WHERE DATE(start) = %s AND prestataire_id = %d", $date, $prestataire_id);
    $results = $wpdb->get_col($query);
    $reserved = array_map(function($dt) { return date('H:i', strtotime($dt)); }, $results);
    $available = array_values(array_diff($slots, $reserved));
    wp_send_json_success($available);
}

function rdv_book_slot_callback() {
    check_ajax_referer('calendar_nonce', 'nonce');
    global $wpdb;
    $date = sanitize_text_field($_POST['date'] ?? '');
    $time = sanitize_text_field($_POST['time'] ?? '');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $telephone = sanitize_text_field($_POST['telephone'] ?? '');
    $prestataire_id = intval($_POST['prestataire_id'] ?? 1);
    $table = $wpdb->prefix . 'rdv_events';
    if (!$date || !$time || !$title || !$email || !$telephone) {
        wp_send_json_error(['message' => 'Champs obligatoires manquants.']);
    }
    $start = "$date $time";
    // Vérifier si le créneau est déjà pris
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE start = %s AND prestataire_id = %d", $start, $prestataire_id));
    if ($exists) {
        wp_send_json_error(['message' => 'Créneau déjà réservé.']);
    }
    $inserted = $wpdb->insert($table, [
        'title' => $title,
        'start' => $start,
        'end' => $start,
        'prestataire_id' => $prestataire_id,
        'statut' => 'confirmé',
        'email' => $email,
        'telephone' => $telephone,
        'created_at' => current_time('mysql')
    ]);
    if ($inserted) {
        wp_send_json_success(['message' => 'Réservation enregistrée !']);
    } else {
        wp_send_json_error(['message' => 'Erreur lors de l\'enregistrement.']);
    }
}
