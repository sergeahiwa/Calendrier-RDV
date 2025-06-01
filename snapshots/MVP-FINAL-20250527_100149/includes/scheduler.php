<?php
if (!defined('ABSPATH')) exit;
require_once dirname(__FILE__).'/email-functions.php';
require_once dirname(__FILE__).'/log-functions.php';

function calrdv_verifier_rappels() {
    global $wpdb;
    $table = $wpdb->prefix . 'reservations';
    $now = current_time('mysql');
    $dans_24h = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $rdvs = $wpdb->get_results("SELECT * FROM $table WHERE statut = 'confirme' AND rappel_envoye = 0 AND CONCAT(date_rdv, ' ', heure_rdv) BETWEEN '$dans_24h' AND DATE_ADD('$dans_24h', INTERVAL 15 MINUTE)");
    foreach ($rdvs as $rdv) {
        calrdv_envoyer_email_rappel($rdv->id);
        $wpdb->update($table, ['rappel_envoye' => 1], ['id' => $rdv->id]);
        calrdv_log_action($rdv->id, 'rappel_envoye', 'Rappel automatique 24h avant envoyé.');
    }
}

function calrdv_register_rappel_cron() {
    if (!wp_next_scheduled('calrdv_verifier_rappels_event')) {
        wp_schedule_event(time(), 'hourly', 'calrdv_verifier_rappels_event');
    }
}
add_action('wp', 'calrdv_register_rappel_cron');
add_action('calrdv_verifier_rappels_event', 'calrdv_verifier_rappels');
