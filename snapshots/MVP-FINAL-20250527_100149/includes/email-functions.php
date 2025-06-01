<?php
if (!defined('ABSPATH')) exit;

function calendrier_rdv_envoyer_email($to, $subject, $message, $headers = [], $attachments = []) {
    if (defined('CALENDRIER_RDV_DEBUG') && CALENDRIER_RDV_DEBUG) {
        error_log("[calendrier-rdv] Email envoyé à : $to | Sujet : $subject");
    }
    // Option : copie à l’admin si debug
    if (defined('CALENDRIER_RDV_DEBUG') && CALENDRIER_RDV_DEBUG && get_option('admin_email')) {
        $headers[] = 'Bcc: ' . get_option('admin_email');
    }
    return wp_mail($to, $subject, $message, $headers, $attachments);
}

function calendrier_rdv_envoyer_email($to, $subject, $message, $headers = [], $attachments = []) {
    if (defined('CALENDRIER_RDV_DEBUG') && CALENDRIER_RDV_DEBUG) {
        error_log("[calendrier-rdv] Email envoyé à : $to | Sujet : $subject");
    }
    // Option : copie à l’admin si debug
    if (defined('CALENDRIER_RDV_DEBUG') && CALENDRIER_RDV_DEBUG && get_option('admin_email')) {
        $headers[] = 'Bcc: ' . get_option('admin_email');
    }
    return wp_mail($to, $subject, $message, $headers, $attachments);
}

function calrdv_envoyer_email_rappel($reservation_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'reservations';
    $rdv = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $reservation_id));
    if (!$rdv) return false;

    // Variables dynamiques
    $client_email = $rdv->email;
    $client_nom = isset($rdv->nom) ? $rdv->nom : '';
    $presta_nom = isset($rdv->prestataire) ? $rdv->prestataire : 'notre équipe';
    $presta_tel = isset($rdv->presta_tel) ? $rdv->presta_tel : '';
    $date = isset($rdv->date_rdv) ? $rdv->date_rdv : '';
    $heure = isset($rdv->heure_rdv) ? $rdv->heure_rdv : '';
    $lieu = isset($rdv->lieu) ? $rdv->lieu : '[Lieu à préciser]';
    $modif_url = isset($rdv->modif_url) ? $rdv->modif_url : '#';
    $logo_url = 'https://www.san-digital.com/logo-san-digital.png'; // À adapter
    $qr_url = 'https://chart.googleapis.com/chart?chs=160x160&cht=qr&chl=' . urlencode($modif_url);

    $to = $client_email;
    $subject = "🔔 Rappel : votre rendez-vous de demain avec $presta_nom";
    $headers = [
        "Content-Type: text/html; charset=UTF-8",
        "From: SAN Digital Solutions <no-reply@sandigitalsolutions.com>"
    ];

    $message = "\n<html>\n  <body style=\"font-family: Arial, sans-serif; color: #272f63;\">\n    <div style=\"max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px;\">\n      <img src=\"$logo_url\" alt=\"Logo SAN Digital Solutions\" style=\"max-height: 60px; margin-bottom: 20px;\" />\n\n      <h2 style=\"color: #346fb3;\">Rappel : votre rendez-vous est prévu demain</h2>\n\n      <p>Bonjour <strong>$client_nom</strong>,</p>\n\n      <p>Nous vous rappelons que vous avez un rendez-vous confirmé avec <strong>$presta_nom</strong>.</p>\n\n      <table style=\"width: 100%; margin: 20px 0; font-size: 15px;\">\n        <tr><td><strong>📅 Date :</strong></td><td>$date</td></tr>\n        <tr><td><strong>⏰ Heure :</strong></td><td>$heure</td></tr>\n        <tr><td><strong>👨‍💼 Prestataire :</strong></td><td>$presta_nom ($presta_tel)</td></tr>\n        <tr><td><strong>📍 Lieu :</strong></td><td>$lieu</td></tr>\n      </table>\n\n      <p>🔁 En cas d’imprévu, vous pouvez <a href=\"$modif_url\" style=\"color: #8fad0c;\">modifier ou annuler votre rendez-vous</a> en un clic.</p>\n\n      <p>📱 Scannez ce QR Code pour retrouver toutes les infos sur mobile :</p>\n      <p><img src=\"$qr_url\" alt=\"QR Code RDV\" style=\"max-width: 150px;\" /></p>\n\n      <hr style=\"margin: 30px 0;\" />\n\n      <p style=\"font-size: 13px; color: #666;\">\n        Ce message est envoyé automatiquement depuis le système de réservation SAN Digital Solutions.<br>\n        Pour toute question, contactez-nous à <a href=\"mailto:contact@sandigitalsolutions.com\">contact@sandigitalsolutions.com</a>.\n      </p>\n    </div>\n  </body>\n</html>\n";

    // Génération du fichier ICS (calendrier universel)
    $ics_content = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//SAN Digital Solutions//RDV//FR\r\n";
    $ics_content .= "BEGIN:VEVENT\r\n";
    $ics_content .= "UID:rdv-" . $reservation_id . "@sandigitalsolutions.com\r\n";
    $ics_content .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    // Date et heure au format UTC (adaptation possible)
    $dtstart = date('Ymd\THis', strtotime($date . ' ' . $heure));
    $dtend = date('Ymd\THis', strtotime($date . ' ' . $heure . ' +1 hour'));
    $ics_content .= "DTSTART:$dtstart\r\n";
    $ics_content .= "DTEND:$dtend\r\n";
    $ics_content .= "SUMMARY:Rendez-vous avec $presta_nom\r\n";
    $ics_content .= "DESCRIPTION:RDV confirmé avec $presta_nom ($presta_tel)\r\n";
    $ics_content .= "LOCATION:$lieu\r\n";
    $ics_content .= "URL:$modif_url\r\n";
    $ics_content .= "END:VEVENT\r\nEND:VCALENDAR\r\n";

    // Sauvegarde temporaire du fichier ICS
    $ics_filename = sys_get_temp_dir() . "/rdv-{$reservation_id}.ics";
    file_put_contents($ics_filename, $ics_content);

    // Génération du PDF récapitulatif du RDV
    require_once __DIR__ . '/fpdf.php';
    $pdf_filename = sys_get_temp_dir() . "/rdv-{$reservation_id}.pdf";
    $pdf = new FPDF();
    $pdf->AddPage();
    // Logo
    if ($logo_url) {
        // Télécharge le logo si distant
        $logo_path = $logo_url;
        if (strpos($logo_url, 'http') === 0) {
            $logo_tmp = sys_get_temp_dir() . '/logo-san-digital.png';
            if (!file_exists($logo_tmp)) {
                file_put_contents($logo_tmp, file_get_contents($logo_url));
            }
            $logo_path = $logo_tmp;
        }
        $pdf->Image($logo_path, 10, 10, 40);
        $pdf->Ln(20);
    }
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(52, 111, 179); // Bleu SAN Digital
    $pdf->Cell(0, 15, utf8_decode('Récapitulatif de votre rendez-vous'), 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(39, 47, 99);
    $pdf->Cell(50, 8, utf8_decode('Client :'), 0, 0);
    $pdf->Cell(0, 8, utf8_decode($client_nom), 0, 1);
    $pdf->Cell(50, 8, utf8_decode('Prestataire :'), 0, 0);
    $pdf->Cell(0, 8, utf8_decode($presta_nom . ($presta_tel ? ' (' . $presta_tel . ')' : '')), 0, 1);
    $pdf->Cell(50, 8, utf8_decode('Date :'), 0, 0);
    $pdf->Cell(0, 8, utf8_decode($date), 0, 1);
    $pdf->Cell(50, 8, utf8_decode('Heure :'), 0, 0);
    $pdf->Cell(0, 8, utf8_decode($heure), 0, 1);
    $pdf->Cell(50, 8, utf8_decode('Lieu :'), 0, 0);
    $pdf->Cell(0, 8, utf8_decode($lieu), 0, 1);
    $pdf->Ln(8);
    // QR code
    if ($qr_url) {
        $qr_tmp = sys_get_temp_dir() . "/qr-{$reservation_id}.png";
        file_put_contents($qr_tmp, file_get_contents($qr_url));
        $pdf->Image($qr_tmp, $pdf->GetX(), $pdf->GetY(), 40);
        $pdf->Ln(45);
    }
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(0, 7, utf8_decode("Ce document a été généré automatiquement par SAN Digital Solutions.\nPour toute question : contact@sandigitalsolutions.com"));
    $pdf->Output('F', $pdf_filename);

    // Ajoute ICS + PDF en pièce jointe
    $attachments = [$ics_filename, $pdf_filename];

    // Envoi de l’email avec pièces jointes
    $result = calendrier_rdv_envoyer_email($to, $subject, $message, $headers, $attachments);

    // Nettoyage des fichiers temporaires
    if (file_exists($ics_filename)) unlink($ics_filename);
    if (isset($qr_tmp) && file_exists($qr_tmp)) unlink($qr_tmp);
    if (file_exists($pdf_filename)) unlink($pdf_filename);
    if (isset($logo_tmp) && file_exists($logo_tmp)) unlink($logo_tmp);
    return $result;
}
