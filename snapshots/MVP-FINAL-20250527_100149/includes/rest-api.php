<?php
// includes/rest-api.php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    // 8. POST /edit : édition inline
    register_rest_route('calrdv/v1', '/edit', [
        'methods'  => 'POST',
        'callback' => 'calrdv_rest_edit_rdv',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => [
            'reservation_id' => ['required' => true, 'sanitize_callback' => 'absint'],
            'date_rdv' => ['sanitize_callback' => 'sanitize_text_field'],
            'heure_rdv' => ['sanitize_callback' => 'sanitize_text_field'],
            'prestataire' => ['sanitize_callback' => 'absint'],
            'statut' => ['sanitize_callback' => 'sanitize_text_field'],
            'email' => ['sanitize_callback' => 'sanitize_email'],
            'nom' => ['sanitize_callback' => 'sanitize_text_field'],
            'token' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
        ]
    ]);

    // 7. GET /logs : historique des actions
    register_rest_route('calrdv/v1', '/logs', [
        'methods'  => 'GET',
        'callback' => 'calrdv_rest_get_logs',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => [
            'reservation_id' => ['required' => true, 'sanitize_callback' => 'absint'],
        ]
    ]);

    // 6. POST /confirm : confirme un RDV
    register_rest_route('calrdv/v1', '/confirm', [
        'methods'  => 'POST',
        'callback' => 'calrdv_rest_confirm_rdv',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => [
            'reservation_id' => ['required' => true, 'sanitize_callback' => 'absint'],
            'token' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
        ]
    ]);

    // 4. POST /cancel : annule un RDV
    register_rest_route('calrdv/v1', '/cancel', [
        'methods'  => 'POST',
        'callback' => 'calrdv_rest_cancel_rdv',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
        'args' => [
            'reservation_id' => ['required' => true, 'sanitize_callback' => 'absint'],
            'motif' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'token' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
        ]
    ]);

    // 5. GET /slots accepte prestataire_id
    // (pas besoin de changer le register_rest_route, juste la fonction callback)

    // 1. GET /slots : retourne les créneaux disponibles (public, lecture seule)
    register_rest_route('calrdv/v1', '/slots', [
        'methods'  => 'GET',
        'callback' => 'calrdv_rest_get_slots',
        // Public : lecture seule, pas de données sensibles
        'permission_callback' => '__return_true',
    ]);

    // 2. POST /book : réserve un RDV (protégé contre abus)
    register_rest_route('calrdv/v1', '/book', [
        'methods'  => 'POST',
        'callback' => 'calrdv_rest_book_rdv',
        // Permission : seuls les utilisateurs authentifiés peuvent réserver
        'permission_callback' => function() {
            return is_user_logged_in();
        },
        'args' => [
            'nom'   => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'email' => ['required' => true, 'sanitize_callback' => 'sanitize_email'],
            'prestation' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'date_rdv' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'heure_rdv' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
            'prestataire' => ['required' => true, 'sanitize_callback' => 'absint'],
        ]
    ]);

    // 3. GET /prestataires : liste des prestataires (public, lecture seule)
    register_rest_route('calrdv/v1', '/prestataires', [
        'methods'  => 'GET',
        'callback' => 'calrdv_rest_get_prestataires',
        // Public : lecture seule, pas de données sensibles
        'permission_callback' => '__return_true',
    ]);
});

function calrdv_rest_get_slots($request) {
    // Sanitize
    $prestataire_id = isset($request['prestataire_id']) ? absint($request['prestataire_id']) : 0;
    global $wpdb;
    $table = $wpdb->prefix . 'reservations';
    $today = date('Y-m-d');
    $prestataire_id = isset($request['prestataire_id']) ? absint($request['prestataire_id']) : 0;
    $sql = "SELECT id, nom, email, prestation, date_rdv, heure_rdv, prestataire, statut FROM $table WHERE date_rdv >= %s";
    $args = [$today];
    if ($prestataire_id) {
        $sql .= " AND prestataire = %d";
        $args[] = $prestataire_id;
    }
    $sql .= " ORDER BY date_rdv, heure_rdv ASC";
    $results = $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A);
    return rest_ensure_response(['success' => true, 'slots' => $results]);
}

function calrdv_rest_book_rdv($request) {
    // Sanitize & validate
    $data = $request->get_json_params();
    $nom   = sanitize_text_field($data['nom'] ?? '');
    $email = sanitize_email($data['email'] ?? '');
    $prestation = sanitize_text_field($data['prestation'] ?? '');
    $date  = sanitize_text_field($data['date_rdv'] ?? '');
    $heure = sanitize_text_field($data['heure_rdv'] ?? '');
    $id_prestataire = absint($data['prestataire'] ?? 0);

    // Validation stricte
    if (empty($nom) || empty($prestation) || empty($date) || empty($heure) || $id_prestataire <= 0 || !is_email($email)) {
        return new WP_Error('invalid_data', __('Données invalides ou incomplètes.', 'calendrier-rdv'), array('status' => 400));
    }

    require_once dirname(__FILE__) . '/../includes/log-functions.php';
    global $wpdb;
    $params = $request->get_params();
    $table = $wpdb->prefix . 'reservations';

    // Vérification simple doublon (même prestataire, date, heure)
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE prestataire = %d AND date_rdv = %s AND heure_rdv = %s", $params['prestataire'], $params['date_rdv'], $params['heure_rdv']));
    if ($exists) {
        return new WP_Error('rdv_exists', __('Un rendez-vous existe déjà pour ce créneau.', 'calendrier-rdv'), ['status' => 409]);
    }

    // Insertion
    $inserted = $wpdb->insert($table, [
        'nom' => $params['nom'],
        'email' => $params['email'],
        'prestation' => $params['prestation'],
        'date_rdv' => $params['date_rdv'],
        'heure_rdv' => $params['heure_rdv'],
        'prestataire' => $params['prestataire'],
        'statut' => 'en_attente',
        'date_creation' => current_time('mysql'),
    ], [
        '%s','%s','%s','%s','%s','%d','%s','%s'
    ]);

    if ($inserted) {
        calrdv_log_action($wpdb->insert_id, 'creation', 'Nouvelle réservation via REST');
        // Envoi email de confirmation via MailerSend
        require_once dirname(__FILE__) . '/mailersend-functions.php';
        // Récupère le nom du prestataire
        $presta_nom = $wpdb->get_var($wpdb->prepare("SELECT nom FROM {$wpdb->prefix}prestataires WHERE id = %d", $params['prestataire']));
        $email_result = calrdv_envoyer_email_confirmation(
            $params['email'],
            $params['nom'],
            [
                'prestation' => $params['prestation'],
                'date_rdv' => $params['date_rdv'],
                'heure_rdv' => $params['heure_rdv'],
                'prestataire_nom' => $presta_nom
            ]
        );
        if (!$email_result['success']) {
            error_log('[calrdv] Echec envoi email confirmation: ' . $email_result['message']);
            return new WP_Error('email_error', __('Erreur lors de l’envoi de l’email de confirmation.', 'calendrier-rdv') . ' ' . $email_result['message'], ['status' => 500]);
        }
        return rest_ensure_response(['success' => true, 'message' => __('Réservation enregistrée et email envoyé.', 'calendrier-rdv')]);
    } else {
        return new WP_Error('rdv_insert_error', __('Erreur lors de l’enregistrement.', 'calendrier-rdv'), ['status' => 500]);
    }
}

function calrdv_rest_get_prestataires($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'prestataires';
    $results = $wpdb->get_results("SELECT id, nom, email, telephone, actif FROM $table WHERE actif = 1 ORDER BY nom ASC", ARRAY_A);
    return rest_ensure_response(['success' => true, 'prestataires' => $results]);
}
