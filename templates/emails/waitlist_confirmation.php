<?php
/**
 * Modèle d'email de confirmation d'inscription en liste d'attente
 *
 * Variables disponibles :
 * - $entry: Objet contenant les détails de l'inscription
 * - $site_name: Nom du site
 * - $site_url: URL du site
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo esc_html__('Confirmation d\'inscription en liste d\'attente', 'calendrier-rdv'); ?></title>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; color: #333;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2271b1;"><?php echo esc_html($site_name); ?></h1>
        </div>
        
        <div style="background-color: #f9f9f9; padding: 30px; border-radius: 5px; border: 1px solid #ddd;">
            <h2 style="color: #2271b1; margin-top: 0;"><?php echo esc_html__('Confirmation d\'inscription en liste d\'attente', 'calendrier-rdv'); ?></h2>
            
            <p><?php echo esc_html__('Bonjour', 'calendrier-rdv') . ' ' . esc_html($entry->name); ?>,</p>
            
            <p><?php echo esc_html__('Nous vous confirmons votre inscription en liste d\'attente pour le créneau suivant :', 'calendrier-rdv'); ?></p>
            
            <div style="background-color: #fff; padding: 15px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <p style="margin: 5px 0;">
                    <strong><?php echo esc_html__('Service :', 'calendrier-rdv'); ?></strong> 
                    <?php echo esc_html(get_the_title($entry->service_id)); ?>
                </p>
                <p style="margin: 5px 0;">
                    <strong><?php echo esc_html__('Date :', 'calendrier-rdv'); ?></strong> 
                    <?php echo date_i18n(get_option('date_format'), strtotime($entry->date)); ?>
                </p>
                <p style="margin: 5px 0;">
                    <strong><?php echo esc_html__('Horaire :', 'calendrier-rdv'); ?></strong> 
                    <?php echo date_i18n(get_option('time_format'), strtotime($entry->start_time)); ?> - 
                    <?php echo date_i18n(get_option('time_format'), strtotime($entry->end_time)); ?>
                </p>
                <p style="margin: 5px 0;">
                    <strong><?php echo esc_html__('Position dans la file d\'attente :', 'calendrier-rdv'); ?></strong> 
                    #<?php echo (int) $entry->position; ?>
                </p>
            </div>
            
            <p><?php echo esc_html__('Vous serez contacté(e) par email si une place se libère pour ce créneau.', 'calendrier-rdv'); ?></p>
            
            <p><?php echo esc_html__('Si vous ne souhaitez plus être sur la liste d\'attente, vous pouvez annuler votre inscription en cliquant sur le lien ci-dessous :', 'calendrier-rdv'); ?></p>
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="<?php echo esc_url(add_query_arg([
                    'action' => 'cancel_waitlist',
                    'waitlist_id' => $entry->id,
                    'token' => wp_create_nonce('cancel_waitlist_' . $entry->id)
                ], home_url())); ?>" 
                style="background-color: #dc3232; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                    <?php echo esc_html__('Annuler mon inscription', 'calendrier-rdv'); ?>
                </a>
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
