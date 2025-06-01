<?php
/**
 * Template d'email de notification pour le prestataire
 * 
 * @package CalendrierRDV
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo esc_html__('Nouveau rendez-vous', 'calendrier-rdv'); ?></title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 5px; border: 1px solid #e5e5e5;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2c3e50; margin: 0 0 20px 0;"><?php echo esc_html__('Nouveau rendez-vous', 'calendrier-rdv'); ?></h1>
        </div>
        
        <div style="background-color: #fff; padding: 25px; border-radius: 5px; border: 1px solid #e5e5e5; margin-bottom: 25px;">
            <p style="margin-top: 0;">
                <?php 
                printf(
                    esc_html__('Bonjour %s,', 'calendrier-rdv'),
                    esc_html($prestataire_name)
                );
                ?>
            </p>
            
            <p><?php echo esc_html__('Un nouveau rendez-vous a été réservé. Voici les détails :', 'calendrier-rdv'); ?></p>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; width: 120px;">
                        <?php echo esc_html__('Référence', 'calendrier-rdv'); ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        RDV-<?php echo esc_html($appointment_id); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;">
                        <?php echo esc_html__('Client', 'calendrier-rdv'); ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <?php echo esc_html($client_name); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;">
                        <?php echo esc_html__('Date', 'calendrier-rdv'); ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <?php echo esc_html($appointment_date); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;">
                        <?php echo esc_html__('Heure', 'calendrier-rdv'); ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <?php echo esc_html($appointment_time); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;">
                        <?php echo esc_html__('Email', 'calendrier-rdv'); ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <?php echo esc_html($client_email); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;">
                        <?php echo esc_html__('Téléphone', 'calendrier-rdv'); ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <?php echo esc_html($client_phone); ?>
                    </td>
                </tr>
                <?php if (!empty($notes)) : ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; vertical-align: top;">
                        <?php echo esc_html__('Notes', 'calendrier-rdv'); ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <?php echo nl2br(esc_html($notes)); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            
            <p>
                <?php echo esc_html__('Vous pouvez consulter vos rendez-vous dans votre espace d\'administration.', 'calendrier-rdv'); ?>
            </p>
        </div>
        
        <div style="text-align: center; color: #777; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <p style="margin: 0;">
                <?php echo esc_html__('Ceci est un message automatique, merde de ne pas y répondre.', 'calendrier-rdv'); ?>
            </p>
            <p style="margin: 10px 0 0 0;">
                &copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>. Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
