<?php
/**
 * Traitement des rendez-vous avec protection CSRF et validation renforcée
 * 
 * @package CalendrierRdv
 * @version 2.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}

// Désactiver l'affichage des erreurs en production
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    error_reporting(0);
    @ini_set('display_errors', 0);
}

/*
 * 1️⃣ Structure SQL à exécuter dans phpMyAdmin :
 *
 * CREATE TABLE IF NOT EXISTS wp_rdv_events (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     title VARCHAR(255) NOT NULL,
 *     start DATETIME NOT NULL,
 *     end DATETIME DEFAULT NULL,
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 *
 * Adapter le préfixe si besoin.
 */

// 2️⃣ Endpoints AJAX WordPress (à placer dans functions.php ou plugin)
if (defined('ABSPATH')) {
    // 2.1 Charger FullCalendar et JS personnalisé
    add_action('wp_enqueue_scripts', function() {
        wp_enqueue_script('fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', [], null, true);
        wp_enqueue_script('mon-calendar-js', get_stylesheet_directory_uri() . '/js/mon-calendar.js', ['fullcalendar-js'], '1.0', true);
        wp_localize_script('mon-calendar-js', 'calendarAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('calendar_nonce')
        ]);
    });

    // 2.2 Endpoint AJAX pour ajouter un événement
    add_action('wp_ajax_add_rdv_event', 'add_rdv_event_callback');
    add_action('wp_ajax_nopriv_add_rdv_event', 'add_rdv_event_callback');
    function add_rdv_event_callback() {
        // Vérifier le nonce CSRF
        if (!check_ajax_referer('calendar_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Erreur de sécurité. Veuillez rafraîchir la page.', 'calendrier-rdv')]);
        }
        
        // Vérifier le rate limiting
        $rate_limiter = calrdv_get_rate_limiter();
        $rate_limit = $rate_limiter->is_allowed('add_rdv_event');
        
        if ($rate_limit !== true) {
            wp_send_json_error([
                'message' => $rate_limit['message'],
                'code' => $rate_limit['code'],
                'retry_after' => $rate_limit['retry_after'] ?? 0
            ]);
        }

        // Vérifier les permissions utilisateur si nécessaire
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission refusée.', 'calendrier-rdv')]);
        }

        // Nettoyer et valider les entrées
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $start = isset($_POST['start']) ? sanitize_text_field(wp_unslash($_POST['start'])) : '';
        $end = isset($_POST['end']) ? sanitize_text_field(wp_unslash($_POST['end'])) : '';

        // Validation des données
        $errors = [];
        
        // Validation du titre
        if (empty($title)) {
            $errors[] = __('Le titre est requis.', 'calendrier-rdv');
        } elseif (strlen($title) > 255) {
            $errors[] = __('Le titre ne doit pas dépasser 255 caractères.', 'calendrier-rdv');
        }

        // Validation des dates
        if (empty($start) || !preg_match('/^\d{4}-\d{2}-\d{2}/', $start)) {
            $errors[] = __('Une date de début valide est requise.', 'calendrier-rdv');
        }

        if (!empty($end) && !preg_match('/^\d{4}-\d{2}-\d{2}/', $end)) {
            $errors[] = __('Format de date de fin invalide.', 'calendrier-rdv');
        }

        // Vérifier les erreurs
        if (!empty($errors)) {
            wp_send_json_error([
                'message' => __('Erreur de validation :', 'calendrier-rdv'),
                'errors' => $errors
            ]);
        }
        // Validation et formatage des dates
        $start_dt = date('Y-m-d H:i:s', strtotime($start));
        $end_dt = !empty($end) ? date('Y-m-d H:i:s', strtotime($end)) : null;

        // Vérifier que la date de fin est postérieure à la date de début
        if ($end_dt && strtotime($end_dt) <= strtotime($start_dt)) {
            wp_send_json_error([
                'message' => __('La date de fin doit être postérieure à la date de début.', 'calendrier-rdv')
            ]);
        }
        global $wpdb;
        $table = $wpdb->prefix . 'rdv_events';
        $result = $wpdb->insert($table, [
            'title' => $title,
            'start' => $start_dt,
            'end'   => $end_dt
        ], ['%s', '%s', '%s']);
        if ($result) {
            // Journaliser l'action
            if (function_exists('calrdv_log_action')) {
                calrdv_log_action('appointment_created', [
                    'appointment_id' => $wpdb->insert_id,
                    'title' => $title,
                    'start' => $start_dt,
                    'end' => $end_dt
                ]);
            }
            
            wp_send_json_success([
                'message' => __('Rendez-vous ajouté avec succès !', 'calendrier-rdv'),
                'data' => [
                    'id' => $wpdb->insert_id,
                    'title' => $title,
                    'start' => $start_dt,
                    'end' => $end_dt
                ]
            ]);
        } else {
            // Journaliser l'erreur
            if (function_exists('calrdv_log_error')) {
                calrdv_log_error('appointment_creation_failed', [
                    'error' => $wpdb->last_error,
                    'data' => [
                        'title' => $title,
                        'start' => $start_dt,
                        'end' => $end_dt
                    ]
                ]);
            }
            
            wp_send_json_error([
                'message' => __("Une erreur est survenue lors de l'ajout du rendez-vous.", 'calendrier-rdv'),
                'error' => $wpdb->last_error
            ]);
        }
    }

    // 2.3 Endpoint AJAX pour charger les événements existants
    add_action('wp_ajax_get_rdv_events', 'get_rdv_events_callback');
    add_action('wp_ajax_nopriv_get_rdv_events', 'get_rdv_events_callback');
    function get_rdv_events_callback() {
        global $wpdb;
        $table = $wpdb->prefix . 'rdv_events';
        $rows = $wpdb->get_results("SELECT id, title, start, end FROM $table", ARRAY_A);
        $events = [];
        foreach ($rows as $row) {
            $events[] = [
                'id'    => $row['id'],
                'title' => $row['title'],
                'start' => $row['start'],
                'end'   => $row['end']
            ];
        }
        wp_send_json($events);
    }
}

// 3️⃣ Exemple de code JS FullCalendar à placer dans /js/mon-calendar.js
/*
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            selectable: true,
            events: function(fetchInfo, successCallback, failureCallback) {
                fetch(calendarAjax.ajax_url + '?action=get_rdv_events')
                    .then(response => response.json())
                    .then(events => successCallback(events))
                    .catch(error => failureCallback(error));
            },
            select: function(info) {
                const title = prompt('Titre du rendez-vous :');
                if (title) {
                    calendarEl.style.pointerEvents = 'none';
                    fetch(calendarAjax.ajax_url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'add_rdv_event',
                            nonce: calendarAjax.nonce,
                            title: title,
                            start: info.startStr,
                            end: info.endStr
                        })
                    })
                    .then(r => r.json())
                    .then(result => {
                        if (result.success) {
                            alert(result.data.message);
                            calendar.refetchEvents();
                        } else {
                            alert(result.data.message || "Erreur lors de l'ajout.");
                        }
                    })
                    .catch(() => alert('Erreur réseau.'))
                    .finally(() => {
                        calendarEl.style.pointerEvents = '';
                    });
                }
                calendar.unselect();
            }
        });
        calendar.render();
    });
*/

// 4️⃣ Pour tester en local sans WordPress, tu peux adapter ce code PHP pour simuler l'endpoint AJAX, mais pour production, place tout dans functions.php/plugin comme ci-dessus.
