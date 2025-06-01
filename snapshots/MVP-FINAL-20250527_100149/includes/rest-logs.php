<?php
if (!defined('ABSPATH')) exit;

function calrdv_rest_get_logs($request) {
    global $wpdb;
    $reservation_id = absint($request->get_param('reservation_id'));
    $table = $wpdb->prefix . 'calrdv_logs';
    $users_table = $wpdb->prefix . 'users';
    $logs = $wpdb->get_results($wpdb->prepare("SELECT l.*, u.display_name as user_name FROM $table l LEFT JOIN $users_table u ON l.user_id = u.ID WHERE l.reservation_id = %d ORDER BY l.timestamp DESC LIMIT 20", $reservation_id), ARRAY_A);
    return rest_ensure_response(['success' => true, 'logs' => $logs]);
}
