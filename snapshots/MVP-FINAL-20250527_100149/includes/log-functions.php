<?php
if (!function_exists('calrdv_log')) {
    function calrdv_log($msg) {
        if (defined('CALENDRIER_RDV_DEBUG') && CALENDRIER_RDV_DEBUG) {
            error_log('[calendrier-rdv] ' . $msg);
        }
    }
}

if (!defined('ABSPATH')) exit;

/**
 * Logue une action sur une réservation
 * @param int $reservation_id
 * @param string $action
 * @param string $details
 * @return void
 */
function calrdv_log_action($reservation_id, $action, $details = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'calrdv_logs';
    $user_id = get_current_user_id();
    $wpdb->insert($table, [
        'reservation_id' => $reservation_id,
        'action' => $action,
        'user_id' => $user_id,
        'details' => $details,
        'timestamp' => current_time('mysql')
    ], [ '%d', '%s', '%d', '%s', '%s' ]);
}
