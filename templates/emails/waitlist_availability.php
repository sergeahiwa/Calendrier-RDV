<?php
/**
 * Modèle d'email de notification de disponibilité pour la liste d'attente
 *
 * Variables disponibles :
 * - $entry: Objet contenant les détails de l'inscription en liste d'attente
 * - $booking: Objet contenant les détails de la réservation créée
 * - $site_name: Nom du site
 * - $site_url: URL du site
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo esc_html__('Un créneau s\'est libéré !', 'calendrier-rdv'); ?></title>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; color: #333;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2271b1;"><?php echo esc_html($site_name); ?></h1>
        </div>
        
        <div style="background-color: #f0f8ff; padding: 30px; border-radius: 5px; border: 1px solid #b8d8f0;">
            <h2 style="color: #1a5a96; margin-top: 0;"><?php echo esc_html__('Un créneau s\'est libéré !', 'calendrier-rdv'); ?></h2>
            
            <p><?php echo esc_html__('Bonjour', 'calendrier-rdv') . ' ' . esc_html($entry->name); ?>,</p>
            
            <p><?php echo esc_html__('Une place s\'est libérée pour le créneau que vous attendiez ! Nous avons réservé ce créneau pour vous.', 'calendrier-rdv'); ?></p>
            
            <div style="background-color: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #1a5a96; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #1a5a96;"><?php echo esc_html__('Détails de votre réservation', 'calendrier-rdv'); ?></h3>
                
                <table style="width: 100%; border-collapse: collapse; margin: 10px 0;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee; width: 120px;"><strong><?php echo esc_html__('Numéro de réservation :', 'calendrier-rdv'); ?></strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#<?php echo (int) $booking->id; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong><?php echo esc_html__('Service :', 'calendrier-rdv'); ?></strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php echo esc_html(get_the_title($booking->service_id)); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong><?php echo esc_html__('Date :', 'calendrier-rdv'); ?></strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php echo date_i18n(get_option('date_format'), strtotime($booking->date_rdv)); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong><?php echo esc_html__('Horaire :', 'calendrier-rdv'); ?></strong></td>
                        <td style="padding: 8px 0;">
                            <?php echo date_i18n(get_option('time_format'), strtotime($booking->heure_debut)); ?> - 
                            <?php 
                                $end_time = strtotime($booking->heure_debut . ' + ' . $booking->duree . ' minutes');
                                echo date_i18n(get_option('time_format'), $end_time);
                            ?>
                        </td>
                    </tr>
                </table>
                
                <p style="background-color: #e6f2ff; padding: 10px; border-radius: 4px; margin: 15px 0 0 0;">
                    <strong><?php echo esc_html__('Important :', 'calendrier-rdv'); ?></strong> 
                    <?php echo esc_html__('Vous avez 24 heures pour confirmer ou annuler ce rendez-vous. Passé ce délai, le créneau sera à nouveau proposé à d\'autres personnes en liste d\'attente.', 'calendrier-rdv'); ?>
                </p>
            </div>
            
            <div style="margin: 30px 0; text-align: center;">
                <a href="<?php echo esc_url(add_query_arg([
                    'action' => 'confirm_booking',
                    'booking_id' => $booking->id,
                    'token' => $booking->confirmation_token
                ], home_url())); ?>" 
                style="background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; margin-right: 10px; display: inline-block; margin-bottom: 10px;">
                    <?php echo esc_html__('Confirmer ce rendez-vous', 'calendrier-rdv'); ?>
                </a>
                
                <a href="<?php echo esc_url(add_query_arg([
                    'action' => 'cancel_booking',
                    'booking_id' => $booking->id,
                    'token' => $booking->confirmation_token
                ], home_url())); ?>" 
                style="background-color: #f44336; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; display: inline-block;">
                    <?php echo esc_html__('Annuler ce rendez-vous', 'calendrier-rdv'); ?>
                </a>
            </div>
            
            <p><?php echo esc_html__('Si vous rencontrez des difficultés avec les boutons ci-dessus, vous pouvez copier-coller les liens suivants dans votre navigateur :', 'calendrier-rdv'); ?></p>
            
            <p style="word-break: break-all; font-size: 12px; color: #555; background-color: #f5f5f5; padding: 10px; border-radius: 4px;">
                <strong><?php echo esc_html__('Confirmer :', 'calendrier-rdv'); ?></strong> 
                <?php echo esc_url(add_query_arg([
                    'action' => 'confirm_booking',
                    'booking_id' => $booking->id,
                    'token' => $booking->confirmation_token
                ], home_url())); ?>
                <br><br>
                <strong><?php echo esc_html__('Annuler :', 'calendrier-rdv'); ?></strong> 
                <?php echo esc_url(add_query_arg([
                    'action' => 'cancel_booking',
                    'booking_id' => $booking->id,
                    'token' => $booking->confirmation_token
                ], home_url())); ?>
            </p>
            
            <p><?php echo esc_html__('Cordialement,', 'calendrier-rdv'); ?><br>
            <?php echo esc_html($site_name); ?></p>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; text-align: center;">
            <p><?php echo esc_html__('Ceci est un email automatique, merci de ne pas y répondre.', 'calendrier-rdv'); ?></p>
            <p>&copy; <?php echo date('Y') . ' ' . esc_html($site_name); ?>. <?php echo esc_html__('Tous droits réservés.', 'calendrier-rdv'); ?></p>
        </div>
    </div>
</body>
</html>
