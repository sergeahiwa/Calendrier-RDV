<?php
if (!defined('ABSPATH')) exit;

function calrdv_rest_edit_rdv($request) {
    global $wpdb;
    require_once dirname(__FILE__) . '/../includes/log-functions.php';
    $params = $request->get_params();
    $table = $wpdb->prefix . 'reservations';
    $id = $params['reservation_id'];
    $token = $params['token'];
    if (!wp_verify_nonce($token, 'calrdv_edit_rdv')) {
        return new WP_Error('invalid_token', __('Jeton de sécurité invalide.', 'calendrier-rdv'), ['status' => 403]);
    }
    $rdv = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    if (!$rdv) {
        return new WP_Error('not_found', __('Rendez-vous introuvable.', 'calendrier-rdv'), ['status' => 404]);
    }
    $fields = [];
    $details = [];
    foreach(['date_rdv','heure_rdv','prestataire','statut','email','nom'] as $f) {
        if (isset($params[$f]) && $params[$f] !== '' && $params[$f] != $rdv[$f]) {
            $fields[$f] = $params[$f];
            $details[] = "$f: " . $rdv[$f] . " → " . $params[$f];
        }
    }
    if (!$fields) {
        return rest_ensure_response(['success'=>false,'message'=>'Aucune modification.']);
    }
    $fields['date_modification'] = current_time('mysql');
    $wpdb->update($table, $fields, [ 'id' => $id ]);
    calrdv_log_action($id, 'modification', implode(' | ', $details));
    $new_rdv = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    return rest_ensure_response(['success'=>true,'rdv'=>$new_rdv]);
}
