<?php
// =============================================
// Fichier : includes/functions.php
// Description : Fonctions utilitaires pour le plugin
// Auteur : SAN Digital Solutions
// =============================================

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Génère une référence unique pour un rendez-vous
 * 
 * @param int $id ID du rendez-vous
 * @return string
 */
function calendrier_rdv_generate_reference($id) {
    $prefix = 'RDV';
    $date = date('Ymd');
    $unique = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    return sprintf('%1$s-%2$s-%3$s-%4$s', $prefix, $date, $id, $unique);
}

/**
 * Vérifie si un créneau est disponible
 * 
 * @param int $prestataire_id ID du prestataire
 * @param string $date Date au format Y-m-d
 * @param string $heure_debut Heure de début au format H:i:s
 * @param string $heure_fin Heure de fin au format H:i:s
 * @param int $exclude_rdv_id ID du rendez-vous à exclure (pour la mise à jour)
 * @return bool
 */
function calendrier_rdv_is_creneau_disponible($prestataire_id, $date, $heure_debut, $heure_fin, $exclude_rdv_id = 0) {
    global $wpdb;
    
    $table_rdv = $wpdb->prefix . 'calrdv_reservations';
    
    $query = $wpdb->prepare(
        "SELECT COUNT(*) 
        FROM $table_rdv 
        WHERE prestataire_id = %d 
        AND date_rdv = %s 
        AND statut NOT IN ('annule', 'refuse') 
        AND id != %d 
        AND (
            (heure_debut < %s AND heure_fin > %s) OR
            (heure_debut < %s AND heure_fin > %s) OR
            (heure_debut >= %s AND heure_fin <= %s)
        )",
        $prestataire_id,
        $date,
        $exclude_rdv_id,
        $heure_debut,
        $heure_debut,
        $heure_fin,
        $heure_fin,
        $heure_debut,
        $heure_fin
    );
    
    $count = (int) $wpdb->get_var($query);
    
    return $count === 0;
}

/**
 * Envoie un email de confirmation de rendez-vous
 * 
 * @param int $rdv_id ID du rendez-vous
 * @return bool
 */
function calendrier_rdv_send_confirmation_email($rdv_id) {
    global $wpdb;
    
    $table_rdv = $wpdb->prefix . 'calrdv_reservations';
    $table_prestataires = $wpdb->prefix . 'calrdv_prestataires';
    
    // Récupérer les détails du rendez-vous
    $rdv = $wpdb->get_row($wpdb->prepare(
        "SELECT r.*, p.nom as prestataire_nom, p.email as prestataire_email 
        FROM $table_rdv r 
        LEFT JOIN $table_prestataires p ON r.prestataire_id = p.id 
        WHERE r.id = %d",
        $rdv_id
    ));
    
    if (!$rdv) {
        return false;
    }
    
    // Préparer l'email
    $to = $rdv->client_email;
    $subject = sprintf(__('Confirmation de votre rendez-vous du %s', 'calendrier-rdv'), 
                     date_i18n(get_option('date_format') . ' ' . get_option('time_format'), 
                     strtotime($rdv->date_rdv . ' ' . $rdv->heure_debut)));
    
    $message = sprintf(
        __("Bonjour %1$s,\n\nVotre rendez-vous a bien été enregistré.\n\nDétails :\n- Prestataire : %2$s\n- Date : %3$s\n- Heure : %4$s - %5$s\n\nCordialement,\nL'équipe %6$s", 'calendrier-rdv'),
        $rdv->client_nom,
        $rdv->prestataire_nom,
        date_i18n(get_option('date_format'), strtotime($rdv->date_rdv)),
        date_i18n(get_option('time_format'), strtotime($rdv->heure_debut)),
        date_i18n(get_option('time_format'), strtotime($rdv->heure_fin)),
        get_bloginfo('name')
    );
    
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    // Envoyer l'email
    return wp_mail($to, $subject, $message, $headers);
}

/**
 * Récupère la liste des créneaux disponibles pour un prestataire
 * 
 * @param int $prestataire_id ID du prestataire
 * @param string $date Date au format Y-m-d
 * @param int $duree Durée en minutes
 * @return array
 */
function calendrier_rdv_get_creneaux_disponibles($prestataire_id, $date, $duree) {
    global $wpdb;
    
    $creneaux = array();
    $table_horaires = $wpdb->prefix . 'calrdv_horaires';
    $table_feries = $wpdb->prefix . 'calrdv_jours_feries';
    
    // Vérifier si c'est un jour férié
    $jour_semaine = strtolower(date('l', strtotime($date)));
    $est_ferie = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_feries WHERE date = %s",
        $date
    ));
    
    if ($est_ferie) {
        return $creneaux; // Aucun créneau disponible les jours fériés
    }
    
    // Récupérer les horaires du jour
    $horaire = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_horaires 
        WHERE jour = %s 
        AND (prestataire_id = %d OR prestataire_id IS NULL) 
        ORDER BY prestataire_id DESC 
        LIMIT 1",
        $jour_semaine,
        $prestataire_id
    ));
    
    if (!$horaire || $horaire->est_ferie) {
        return $creneaux; // Aucun horaire défini pour ce jour
    }
    
    // Générer les créneaux disponibles
    $debut_matin = strtotime($horaire->ouverture_matin);
    $fin_matin = strtotime($horaire->fermeture_matin);
    $debut_aprem = strtotime($horaire->ouverture_apres_midi);
    $fin_aprem = strtotime($horaire->fermeture_apres_midi);
    
    $duree_secondes = $duree * 60;
    $intervalle = 15 * 60; // Tous les 15 minutes
    
    // Créneaux du matin
    if ($debut_matin && $fin_matin) {
        $current = $debut_matin;
        while (($current + $duree_secondes) <= $fin_matin) {
            $creneaux[] = date('H:i:s', $current);
            $current += $intervalle;
        }
    }
    
    // Créneaux de l'après-midi
    if ($debut_aprem && $fin_aprem) {
        $current = $debut_aprem;
        while (($current + $duree_secondes) <= $fin_aprem) {
            $creneaux[] = date('H:i:s', $current);
            $current += $intervalle;
        }
    }
    
    // Filtrer les créneaux déjà pris
    $table_rdv = $wpdb->prefix . 'calrdv_reservations';
    $rdvs_du_jour = $wpdb->get_results($wpdb->prepare(
        "SELECT heure_debut, heure_fin 
        FROM $table_rdv 
        WHERE prestataire_id = %d 
        AND date_rdv = %s 
        AND statut NOT IN ('annule', 'refuse')",
        $prestataire_id,
        $date
    ));
    
    foreach ($rdvs_du_jour as $rdv) {
        $debut_rdv = strtotime($rdv->heure_debut);
        $fin_rdv = strtotime($rdv->heure_fin);
        
        foreach ($creneaux as $key => $creneau) {
            $debut_creneau = strtotime($creneau);
            $fin_creneau = $debut_creneau + $duree_secondes;
            
            // Vérifier si les créneaux se chevauchent
            if ($debut_creneau < $fin_rdv && $fin_creneau > $debut_rdv) {
                unset($creneaux[$key]);
            }
        }
    }
    
    // Réindexer le tableau
    return array_values($creneaux);
}

/**
 * Enregistre un message dans les logs
 * 
 * @param string $message Message à enregistrer
 * @param string $niveau Niveau de log (debug, info, warning, error, critical)
 * @param array $contexte Contexte supplémentaire
 * @return bool
 */
function calendrier_rdv_log($message, $niveau = 'info', $contexte = array()) {
    global $wpdb;
    
    $niveaux_autorises = array('debug', 'info', 'warning', 'error', 'critical');
    $niveau = in_array($niveau, $niveaux_autorises) ? $niveau : 'info';
    
    $table_logs = $wpdb->prefix . 'calrdv_logs';
    
    $data = array(
        'niveau' => $niveau,
        'message' => $message,
        'contexte' => !empty($contexte) ? json_encode($contexte) : null,
        'utilisateur_id' => get_current_user_id() ?: null,
        'adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'date_creation' => current_time('mysql')
    );
    
    $format = array('%s', '%s', '%s', '%d', '%s', '%s', '%s');
    
    $result = $wpdb->insert($table_logs, $data, $format);
    
    // En mode débogage, écrire aussi dans le log PHP
    if (CAL_RDV_DEBUG) {
        error_log(sprintf('[Calendrier RDV %1$s] %2$s', strtoupper($niveau), $message));
        if (!empty($contexte)) {
            error_log('Contexte: ' . print_r($contexte, true));
        }
    }
    
    return $result !== false;
}
