<?php
// includes/mailersend-functions.php
if (!defined('ABSPATH')) exit;

/**
 * Envoie un email de confirmation via l’API MailerSend
 * @param string $to_email
 * @param string $to_name
 * @param array $data (date, heure, prestation, prestataire, etc.)
 * @return array [success=>bool, message=>string]
 */
function calrdv_envoyer_email_confirmation($to_email, $to_name, $data = []) {
    $api_key = defined('MAILERSEND_API_KEY') ? MAILERSEND_API_KEY : '';
    if (!$api_key) {
        return ['success' => false, 'message' => 'Clé API MailerSend manquante.'];
    }

    // Préparer les données pour le template
    $template_data = wp_parse_args($data, [
        'client_nom' => $to_name,
        'client_email' => $to_email,
        'date_rdv' => '',
        'heure_rdv' => '',
        'service_nom' => '',
        'duree' => 30,
        'prestataire_nom' => '',
        'prestataire_email' => '',
        'prestataire_telephone' => '',
        'lien_annulation' => ''
    ]);
    
    // Générer le contenu HTML à partir du template
    $html_content = calrdv_get_email_template('email-confirmation-rdv', $template_data);
    
    // Générer une version texte à partir du HTML
    $text_content = wp_strip_all_tags($html_content);
    
    // Préparer les en-têtes de l'email
    $from_email = defined('MAILERSEND_FROM_EMAIL') ? MAILERSEND_FROM_EMAIL : get_option('admin_email');
    $from_name = defined('MAILERSEND_FROM_NAME') ? MAILERSEND_FROM_NAME : get_bloginfo('name');
    $subject = sprintf(
        __('Confirmation de votre rendez-vous - %s', 'calendrier-rdv'), 
        $template_data['service_nom']
    );
    
    // Préparer le corps de la requête MailerSend
    $body = [
        'from' => [
            'email' => $from_email,
            'name' => $from_name
        ],
        'to' => [
            [
                'email' => $to_email,
                'name' => $to_name
            ]
        ],
        'subject' => $subject,
        'html' => $html_content,
        'text' => $text_content,
        'tags' => [
            ['name' => 'confirmation_rdv'],
            ['name' => 'service_' . sanitize_title($template_data['service_nom'])]
        ]
    ];

    // Envoyer la requête à l'API MailerSend
    $response = wp_remote_post('https://api.mailersend.com/v1/email', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'CalendrierRDV/' . CALENDRIER_RDV_VERSION . '; ' . home_url()
        ],
        'body' => wp_json_encode($body),
        'timeout' => 15,
        'redirection' => 0,
        'httpversion' => '1.1',
        'blocking' => true,
        'data_format' => 'body'
    ]);

    // Gérer la réponse
    if (is_wp_error($response)) {
        // Journaliser l'erreur
        if (function_exists('calrdv_log_error')) {
            calrdv_log_error('mailersend_api_error', [
                'error' => $response->get_error_message(),
                'to' => $to_email,
                'subject' => $subject
            ]);
        }
        
        return [
            'success' => false, 
            'message' => __('Erreur lors de l\'envoi de l\'email : ', 'calendrier-rdv') . $response->get_error_message(),
            'code' => 'sending_failed'
        ];
    }
    
    // Récupérer le code de statut HTTP
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    // Vérifier si l'envoi a réussi
    if ($code >= 200 && $code < 300) {
        // Journaliser le succès
        if (function_exists('calrdv_log_action')) {
            calrdv_log_action('email_confirmation_sent', [
                'to' => $to_email,
                'subject' => $subject,
                'template' => 'email-confirmation-rdv'
            ]);
        }
        
        return [
            'success' => true, 
            'message' => __('Email de confirmation envoyé avec succès.', 'calendrier-rdv'),
            'code' => 'sent',
            'data' => [
                'to' => $to_email,
                'subject' => $subject
            ]
        ];
    }
    
    // Gérer les erreurs de l'API MailerSend
    $error_message = __('Erreur inconnue de l\'API MailerSend', 'calendrier-rdv');
    $error_code = 'api_error';
    $response_data = json_decode($body, true);
    
    if (!empty($response_data['message'])) {
        $error_message = $response_data['message'];
        $error_code = $response_data['code'] ?? 'api_error';
    }
    
    // Si l'erreur est temporaire, ajouter à la file d'attente
    if ($this->is_temporary_error($code, $error_code)) {
        $this->add_to_retry_queue(
            $to_email, 
            $to_name, 
            $subject, 
            $template_data, 
            $error_code, 
            $error_message
        );
    }
    
    // Journaliser l'erreur
    if (function_exists('calrdv_log_error')) {
        calrdv_log_error('mailersend_api_error', [
            'code' => $code,
            'error' => $error_message,
            'response' => $body,
            'to' => $to_email,
            'subject' => $subject
        ]);
    }
    
    // Préparer la réponse d'erreur
    $error_response = [
        'success' => false, 
        'message' => sprintf(
            __('Erreur MailerSend (%d) : %s', 'calendrier-rdv'),
            $code,
            $error_message
        ),
        'code' => $error_code,
        'http_code' => $code,
        'response' => $response_data
    ];
    
    // Si l'erreur est temporaire, ajouter à la file d'attente
    if ($this->is_temporary_error($code, $error_code)) {
        $this->add_to_retry_queue($to_email, $to_name, $subject, $template_data, $error_code, $error_message);
    }
    
    return $error_response;
}

/**
 * Vérifie si une erreur est temporaire et peut être réessayée
 * 
 * @param int $http_code Code HTTP de la réponse
 * @param string $error_code Code d'erreur de l'API
 * @return bool True si l'erreur est temporaire
 */
private function is_temporary_error($http_code, $error_code) {
    // Erreurs HTTP temporaires (5xx, 429, 408, 502, 503, 504)
    if (in_array($http_code, [408, 429, 500, 502, 503, 504])) {
        return true;
    }
    
    // Codes d'erreur spécifiques à MailerSend qui peuvent être temporaires
    $temporary_error_codes = [
        'rate_limit_exceeded',
        'temporary_unavailable',
        'timeout',
        'server_error',
        'too_many_requests'
    ];
    
    return in_array($error_code, $temporary_error_codes);
}

/**
 * Ajoute un email à la file d'attente pour une nouvelle tentative
 * 
 * @param string $to_email Adresse email du destinataire
 * @param string $to_name Nom du destinataire
 * @param string $subject Objet de l'email
 * @param array $template_data Données du template
 * @param string $error_code Code d'erreur
 * @param string $error_message Message d'erreur
 * @return bool|int ID de l'entrée ou false en cas d'échec
 */
private function add_to_retry_queue($to_email, $to_name, $subject, $template_data, $error_code, $error_message) {
    // Vérifier si la classe de file d'attente existe
    if (!class_exists('CalRdv_Email_Queue')) {
        require_once CAL_RDV_PLUGIN_DIR . 'includes/class-email-queue.php';
    }
    
    try {
        $queue = CalRdv_Email_Queue::get_instance();
        
        // Calculer le délai avant la prochaine tentative (en minutes)
        $retry_count = 0; // Première tentative
        $retry_delay = $this->calculate_retry_delay($retry_count);
        $scheduled_at = date('Y-m-d H:i:s', time() + ($retry_delay * 60));
        
        // Ajouter à la file d'attente
        return $queue->add_failed_email([
            'recipient_email' => $to_email,
            'recipient_name' => $to_name,
            'subject' => $subject,
            'error_code' => $error_code,
            'error_message' => $error_message,
            'email_data' => $template_data,
            'status' => 'pending',
            'scheduled_at' => $scheduled_at,
            'next_retry' => $scheduled_at,
            'retry_count' => $retry_count,
            'max_retries' => apply_filters('calrdv_max_email_retries', 3)
        ]);
    } catch (Exception $e) {
        error_log('Erreur lors de l\'ajout à la file d\'attente : ' . $e->getMessage());
        return false;
    }
}

/**
 * Calcule le délai avant une nouvelle tentative (en minutes)
 * 
 * @param int $attempt_num Numéro de la tentative (commence à 0)
 * @return int Délai en minutes
 */
private function calculate_retry_delay($attempt_num) {
    // Stratégie de backoff exponentiel avec facteur aléatoire
    $base_delay = pow(2, $attempt_num) * 5; // 5, 10, 20, 40, ... minutes
    $jitter = rand(1, 10); // Ajouter un peu d'aléatoire
    
    // Ne pas dépasser 1440 minutes (24h)
    return min($base_delay + $jitter, 1440);
}
}
