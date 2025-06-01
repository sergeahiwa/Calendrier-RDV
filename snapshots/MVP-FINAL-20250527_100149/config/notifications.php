<?php
/**
 * Configuration des notifications pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Envoie une notification par e-mail
 *
 * @param string $to Adresse e-mail du destinataire
 * @param string $subject Sujet de l'e-mail
 * @param string $message Corps du message (peut inclure du HTML)
 * @param array $headers En-têtes supplémentaires
 * @param array $attachments Pièces jointes
 * @return bool True si l'e-mail a été envoyé avec succès, false sinon
 */
function cal_rdv_send_email($to, $subject, $message, $headers = [], $attachments = []) {
    // Vérifier si l'envoi d'e-mails est désactivé
    if (defined('CAL_RDV_DISABLE_EMAILS') && CAL_RDV_DISABLE_EMAILS) {
        return true; // Simuler un envoi réussi
    }
    
    // Définir le type de contenu par défaut
    $content_type = 'text/html';
    
    // Définir les en-têtes par défaut
    $default_headers = [
        'Content-Type: ' . $content_type . '; charset=UTF-8',
        'From: ' . cal_rdv_get_email_from_header(),
        'Reply-To: ' . cal_rdv_get_option('notifications.reply_to', get_bloginfo('admin_email')),
    ];
    
    // Fusionner avec les en-têtes personnalisés
    $headers = array_unique(array_merge($default_headers, (array) $headers));
    
    // Filtrer les valeurs
    $to = apply_filters('cal_rdv_email_to', $to);
    $subject = apply_filters('cal_rdv_email_subject', $subject);
    $message = apply_filters('cal_rdv_email_message', $message);
    $headers = apply_filters('cal_rdv_email_headers', $headers);
    $attachments = apply_filters('cal_rdv_email_attachments', $attachments);
    
    // Désactiver les filtres de contenu qui pourraient interférer
    remove_all_filters('wp_mail_content_type');
    
    // Forcer le type de contenu
    add_filter('wp_mail_content_type', function() use ($content_type) {
        return $content_type;
    });
    
    // Envoyer l'e-mail
    $result = wp_mail($to, $subject, $message, $headers, $attachments);
    
    // Réinitialiser le filtre de type de contenu
    remove_filter('wp_mail_content_type', 'set_html_content_type');
    
    // Journaliser le résultat pour le débogage
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'Calendrier RDV - E-mail envoyé à %1$s: %2$s',
            $to,
            $result ? 'Succès' : 'Échec'
        ));
    }
    
    return $result;
}

/**
 * Récupère l'en-tête From pour les e-mails
 * 
 * @return string
 */
function cal_rdv_get_email_from_header() {
    $from_name = cal_rdv_get_option('notifications.from_name', get_bloginfo('name'));
    $from_email = cal_rdv_get_option('notifications.from_email', get_bloginfo('admin_email'));
    
    return sprintf('%1$s <%2$s>', $from_name, $from_email);
}

/**
 * Envoie une notification de confirmation de rendez-vous
 * 
 * @param int $appointment_id ID du rendez-vous
 * @return bool
 */
function cal_rdv_send_appointment_confirmation($appointment_id) {
    // Vérifier si la notification est désactivée
    if (!cal_rdv_get_option('notifications.customer_notification', true)) {
        return true;
    }
    
    // Récupérer les informations du rendez-vous
    $appointment = cal_rdv_get_appointment($appointment_id);
    if (!$appointment) {
        return false;
    }
    
    // Récupérer les informations du service
    $service = cal_rdv_get_service($appointment->service_id);
    
    // Récupérer les informations du prestataire
    $provider = cal_rdv_get_provider($appointment->provider_id);
    
    // Préparer les variables de remplacement
    $replacements = [
        '{customer_name}' => $appointment->customer_name,
        '{service_name}' => $service ? $service->name : '',
        '{provider_name}' => $provider ? $provider->display_name : '',
        '{appointment_date}' => cal_rdv_format_date($appointment->start_date, 'l j F Y'),
        '{appointment_time}' => cal_rdv_format_date($appointment->start_date, 'H:i'),
        '{appointment_duration}' => $service ? $service->duration : 30,
        '{location}' => $provider ? $provider->location : '',
        '{site_name}' => get_bloginfo('name'),
        '{site_url}' => home_url(),
        '{admin_email}' => get_bloginfo('admin_email'),
    ];
    
    // Récupérer le sujet et le message du modèle
    $subject = cal_rdv_get_option(
        'notifications.confirmation_subject', 
        __('Confirmation de votre rendez-vous du {appointment_date}', 'calendrier-rdv')
    );
    
    $message = cal_rdv_get_email_template('confirmation', [
        'appointment' => $appointment,
        'service' => $service,
        'provider' => $provider,
    ]);
    
    // Remplacer les variables dans le sujet et le message
    $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
    $message = str_replace(array_keys($replacements), array_values($replacements), $message);
    
    // Envoyer l'e-mail au client
    $sent = cal_rdv_send_email(
        $appointment->customer_email,
        $subject,
        $message
    );
    
    // Envoyer une copie à l'administrateur si nécessaire
    if (cal_rdv_get_option('notifications.admin_notification', true)) {
        $admin_email = cal_rdv_get_option('notifications.admin_email', get_bloginfo('admin_email'));
        
        if ($admin_email && $admin_email !== $appointment->customer_email) {
            $admin_subject = sprintf(
                __('[%1$s] Nouveau rendez-vous: %2$s', 'calendrier-rdv'),
                get_bloginfo('name'),
                $service ? $service->name : ''
            );
            
            cal_rdv_send_email(
                $admin_email,
                $admin_subject,
                $message
            );
        }
    }
    
    return $sent;
}

/**
 * Envoie un rappel de rendez-vous
 * 
 * @param int $appointment_id ID du rendez-vous
 * @return bool
 */
function cal_rdv_send_appointment_reminder($appointment_id) {
    // Vérifier si les rappels sont activés
    if (!cal_rdv_get_option('notifications.reminder_enabled', true)) {
        return true;
    }
    
    // Récupérer les informations du rendez-vous
    $appointment = cal_rdv_get_appointment($appointment_id);
    if (!$appointment) {
        return false;
    }
    
    // Vérifier si le rappel a déjà été envoyé
    if (get_post_meta($appointment_id, '_appointment_reminder_sent', true)) {
        return true;
    }
    
    // Récupérer les informations du service et du prestataire
    $service = cal_rdv_get_service($appointment->service_id);
    $provider = cal_rdv_get_provider($appointment->provider_id);
    
    // Préparer les variables de remplacement
    $replacements = [
        '{customer_name}' => $appointment->customer_name,
        '{service_name}' => $service ? $service->name : '',
        '{provider_name}' => $provider ? $provider->display_name : '',
        '{appointment_date}' => cal_rdv_format_date($appointment->start_date, 'l j F Y'),
        '{appointment_time}' => cal_rdv_format_date($appointment->start_date, 'H:i'),
        '{appointment_duration}' => $service ? $service->duration : 30,
        '{location}' => $provider ? $provider->location : '',
        '{site_name}' => get_bloginfo('name'),
        '{site_url}' => home_url(),
        '{cancel_url}' => add_query_arg([
            'action' => 'cancel',
            'appointment_id' => $appointment_id,
            'token' => wp_create_nonce('cancel_appointment_' . $appointment_id),
        ], home_url('/appointments/')),
    ];
    
    // Récupérer le sujet et le message du modèle
    $subject = cal_rdv_get_option(
        'notifications.reminder_subject', 
        __('Rappel: Votre rendez-vous du {appointment_date}', 'calendrier-rdv')
    );
    
    $message = cal_rdv_get_email_template('reminder', [
        'appointment' => $appointment,
        'service' => $service,
        'provider' => $provider,
    ]);
    
    // Remplacer les variables dans le sujet et le message
    $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
    $message = str_replace(array_keys($replacements), array_values($replacements), $message);
    
    // Envoyer l'e-mail
    $sent = cal_rdv_send_email(
        $appointment->customer_email,
        $subject,
        $message
    );
    
    // Marquer le rappel comme envoyé
    if ($sent) {
        update_post_meta($appointment_id, '_appointment_reminder_sent', current_time('mysql'));
    }
    
    return $sent;
}

/**
 * Envoie une notification d'annulation de rendez-vous
 * 
 * @param int $appointment_id ID du rendez-vous
 * @param string $reason Raison de l'annulation (optionnel)
 * @return bool
 */
function cal_rdv_send_appointment_cancellation($appointment_id, $reason = '') {
    // Récupérer les informations du rendez-vous
    $appointment = cal_rdv_get_appointment($appointment_id);
    if (!$appointment) {
        return false;
    }
    
    // Récupérer les informations du service et du prestataire
    $service = cal_rdv_get_service($appointment->service_id);
    $provider = cal_rdv_get_provider($appointment->provider_id);
    
    // Préparer les variables de remplacement
    $replacements = [
        '{customer_name}' => $appointment->customer_name,
        '{service_name}' => $service ? $service->name : '',
        '{provider_name}' => $provider ? $provider->display_name : '',
        '{appointment_date}' => cal_rdv_format_date($appointment->start_date, 'l j F Y'),
        '{appointment_time}' => cal_rdv_format_date($appointment->start_date, 'H:i'),
        '{reason}' => $reason,
        '{site_name}' => get_bloginfo('name'),
        '{site_url}' => home_url(),
        '{contact_email}' => cal_rdv_get_option('notifications.reply_to', get_bloginfo('admin_email')),
    ];
    
    // Récupérer le sujet et le message du modèle
    $subject = cal_rdv_get_option(
        'notifications.cancellation_subject', 
        __('Annulation de votre rendez-vous du {appointment_date}', 'calendrier-rdv')
    );
    
    $message = cal_rdv_get_email_template('cancellation', [
        'appointment' => $appointment,
        'service' => $service,
        'provider' => $provider,
        'reason' => $reason,
    ]);
    
    // Remplacer les variables dans le sujet et le message
    $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
    $message = str_replace(array_keys($replacements), array_values($replacements), $message);
    
    // Envoyer l'e-mail au client
    $sent = cal_rdv_send_email(
        $appointment->customer_email,
        $subject,
        $message
    );
    
    // Envoyer une copie à l'administrateur si nécessaire
    if (cal_rdv_get_option('notifications.admin_notification', true)) {
        $admin_email = cal_rdv_get_option('notifications.admin_email', get_bloginfo('admin_email'));
        
        if ($admin_email && $admin_email !== $appointment->customer_email) {
            $admin_subject = sprintf(
                __('[%1$s] Rendez-vous annulé: %2$s', 'calendrier-rdv'),
                get_bloginfo('name'),
                $service ? $service->name : ''
            );
            
            cal_rdv_send_email(
                $admin_email,
                $admin_subject,
                $message
            );
        }
    }
    
    return $sent;
}

/**
 * Récupère le contenu d'un modèle d'e-mail
 * 
 * @param string $template Nom du modèle (sans l'extension)
 * @param array $args Variables à passer au modèle
 * @return string Contenu du modèle
 */
function cal_rdv_get_email_template($template, $args = []) {
    // Chemin par défaut dans le plugin
    $default_path = CAL_RDV_PLUGIN_DIR . 'templates/emails/';
    
    // Chercher le modèle dans le thème
    $template_file = locate_template([
        'calendrier-rdv/emails/' . $template . '.php',
        'calendrier-rdv/' . $template . '.php',
    ]);
    
    // Si le modèle n'est pas trouvé dans le thème, utiliser le modèle par défaut
    if (!$template_file || !file_exists($template_file)) {
        $template_file = $default_path . $template . '.php';
        
        // Si le modèle par défaut n'existe pas, retourner une chaîne vide
        if (!file_exists($template_file)) {
            return '';
        }
    }
    
    // Extraire les variables pour les rendre disponibles dans le modèle
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    // Démarrer la mise en mémoire tampon
    ob_start();
    
    // Inclure le modèle
    include $template_file;
    
    // Récupérer le contenu du tampon et le nettoyer
    $content = ob_get_clean();
    
    // Retourner le contenu du modèle
    return $content;
}

/**
 * Planifie les rappels de rendez-vous
 * 
 * @return void
 */
function cal_rdv_schedule_reminders() {
    // Vérifier si les rappels sont activés
    if (!cal_rdv_get_option('notifications.reminder_enabled', true)) {
        return;
    }
    
    // Récupérer l'heure du rappel (en heures avant le rendez-vous)
    $reminder_hours = (int) cal_rdv_get_option('notifications.reminder_time', 24);
    
    // Si le rappel est désactivé ou mal configuré, ne rien faire
    if ($reminder_hours <= 0) {
        return;
    }
    
    // Calculer la date de début et de fin pour les rendez-vous à rappeler
    $now = current_time('mysql');
    $reminder_time = date('Y-m-d H:i:s', strtotime("+{$reminder_hours} hours"));
    
    // Récupérer les rendez-vous à venir qui n'ont pas encore reçu de rappel
    $appointments = cal_rdv_get_appointments([
        'status' => 'confirmed',
        'date_query' => [
            'after' => $now,
            'before' => $reminder_time,
        ],
        'meta_query' => [
            [
                'key' => '_appointment_reminder_sent',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);
    
    // Envoyer les rappels
    foreach ($appointments as $appointment) {
        cal_rdv_send_appointment_reminder($appointment->ID);
    }
}
add_action('cal_rdv_daily_scheduled_events', 'cal_rdv_schedule_reminders');

/**
 * Planifie les événements récurrents
 * 
 * @return void
 */
function cal_rdv_schedule_events() {
    // Planifier l'événement quotidien s'il n'est pas déjà planifié
    if (!wp_next_scheduled('cal_rdv_daily_scheduled_events')) {
        wp_schedule_event(time(), 'daily', 'cal_rdv_daily_scheduled_events');
    }
}
add_action('wp', 'cal_rdv_schedule_events');

/**
 * Nettoie les événements planifiés lors de la désactivation du plugin
 * 
 * @return void
 */
function cal_rdv_clear_scheduled_events() {
    wp_clear_scheduled_hook('cal_rdv_daily_scheduled_events');
}
register_deactivation_hook(CAL_RDV_PLUGIN_FILE, 'cal_rdv_clear_scheduled_events');
