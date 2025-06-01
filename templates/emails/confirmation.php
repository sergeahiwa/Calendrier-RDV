<?php
/**
 * Modèle d'email de confirmation de rendez-vous
 *
 * Variables disponibles :
 * - $appointment : Objet contenant les données du rendez-vous
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo esc_html__('Confirmation de votre rendez-vous', 'calendrier-rdv'); ?></title>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid #0073aa;">
            <h1 style="margin: 0; color: #0073aa;"><?php echo esc_html(get_bloginfo('name')); ?></h1>
        </div>
        
        <div style="padding: 20px;">
            <h2 style="color: #0073aa; margin-top: 0;"><?php echo esc_html__('Confirmation de votre rendez-vous', 'calendrier-rdv'); ?></h2>
            
            <p><?php echo esc_html__('Bonjour', 'calendrier-rdv'); ?> <?php echo esc_html($appointment->customer_name); ?>,</p>
            
            <p><?php echo esc_html__('Votre rendez-vous a bien été enregistré. Voici un récapitulatif :', 'calendrier-rdv'); ?></p>
            
            <div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa;">
                <p style="margin: 0 0 10px 0; font-weight: bold;">
                    <?php 
                    echo sprintf(
                        esc_html__('Rendez-vous du %s à %s', 'calendrier-rdv'),
                        date_i18n(get_option('date_format'), strtotime($appointment->date)),
                        date_i18n(get_option('time_format'), strtotime($appointment->time))
                    );
                    ?>
                </p>
                
                <?php if (!empty($appointment->service_name)) : ?>
                <p style="margin: 5px 0;">
                    <strong><?php echo esc_html__('Service :', 'calendrier-rdv'); ?></strong> 
                    <?php echo esc_html($appointment->service_name); ?>
                </p>
                <?php endif; ?>
                
                <?php if (!empty($appointment->provider_name)) : ?>
                <p style="margin: 5px 0;">
                    <strong><?php echo esc_html__('Prestataire :', 'calendrier-rdv'); ?></strong> 
                    <?php echo esc_html($appointment->provider_name); ?>
                </p>
                <?php endif; ?>
            </div>
            
            <p>
                <?php echo esc_html__('Vous recevrez un rappel par email 24 heures avant votre rendez-vous.', 'calendrier-rdv'); ?>
            </p>
            
            <p>
                <?php echo esc_html__('Pour toute modification ou annulation, veuillez nous contacter par téléphone ou par email.', 'calendrier-rdv'); ?>
            </p>
            
            <p>
                <?php echo esc_html__('Cordialement,', 'calendrier-rdv'); ?><br>
                <?php echo esc_html(get_bloginfo('name')); ?>
            </p>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd;">
            <p style="margin: 0;">
                <?php echo esc_html__('Cet email a été envoyé automatiquement, merci de ne pas y répondre.', 'calendrier-rdv'); ?>
            </p>
            <p style="margin: 5px 0 0 0;">
                &copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>. <?php echo esc_html__('Tous droits réservés.', 'calendrier-rdv'); ?>
            </p>
        </div>
    </div>
</body>
</html>
