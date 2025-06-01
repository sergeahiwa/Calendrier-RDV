<?php
/**
 * Template d'email de confirmation de rendez-vous
 * 
 * Variables disponibles :
 * - $client_nom (string) : Nom complet du client
 * - $client_email (string) : Email du client
 * - $date_rdv (string) : Date du rendez-vous formatée
 * - $heure_rdv (string) : Heure du rendez-vous formatée
 * - $service_nom (string) : Nom du service réservé
 * - $duree (int) : Durée du rendez-vous en minutes
 * - $prestataire_nom (string) : Nom du prestataire
 * - $prestataire_email (string) : Email du prestataire
 * - $prestataire_telephone (string) : Téléphone du prestataire
 * - $lien_annulation (string) : Lien pour annuler le rendez-vous
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo esc_html__('Confirmation de votre rendez-vous', 'calendrier-rdv'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
        .button { display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
        .details { margin: 15px 0; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
        .detail-row { margin-bottom: 10px; }
        .detail-label { font-weight: bold; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo esc_html__('Confirmation de rendez-vous', 'calendrier-rdv'); ?></h1>
    </div>
    
    <div class="content">
        <p><?php 
            echo sprintf(
                esc_html__('Bonjour %s,', 'calendrier-rdv'),
                esc_html($client_nom)
            ); 
        ?></p>
        
        <p><?php echo esc_html__('Votre rendez-vous a été confirmé avec succès. Voici les détails :', 'calendrier-rdv'); ?></p>
        
        <div class="details">
            <div class="detail-row">
                <span class="detail-label"><?php echo esc_html__('Service :', 'calendrier-rdv'); ?></span>
                <span><?php echo esc_html($service_nom); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><?php echo esc_html__('Date :', 'calendrier-rdv'); ?></span>
                <span><?php echo esc_html($date_rdv); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><?php echo esc_html__('Heure :', 'calendrier-rdv'); ?></span>
                <span><?php echo esc_html($heure_rdv); ?></span>
                <span>(<?php echo sprintf(esc_html__('%d minutes', 'calendrier-rdv'), $duree); ?>)</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><?php echo esc_html__('Avec :', 'calendrier-rdv'); ?></span>
                <span><?php echo esc_html($prestataire_nom); ?></span>
            </div>
        </div>
        
        <p><?php echo esc_html__('Vous recevrez un rappel 24h avant votre rendez-vous.', 'calendrier-rdv'); ?></p>
        
        <?php if (!empty($lien_annulation)) : ?>
            <p>
                <a href="<?php echo esc_url($lien_annulation); ?>" class="button">
                    <?php echo esc_html__('Annuler le rendez-vous', 'calendrier-rdv'); ?>
                </a>
            </p>
            <p class="small">
                <?php echo esc_html__('Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :', 'calendrier-rdv'); ?><br>
                <?php echo esc_url($lien_annulation); ?>
            </p>
        <?php endif; ?>
        
        <div class="footer">
            <p><?php 
                echo sprintf(
                    esc_html__('Ceci est un email automatique, merci de ne pas y répondre. Pour toute question, contactez %s à %s ou au %s.', 'calendrier-rdv'),
                    esc_html($prestataire_nom),
                    '<a href="mailto:' . esc_attr($prestataire_email) . '">' . esc_html($prestataire_email) . '</a>',
                    esc_html($prestataire_telephone)
                );
            ?></p>
            <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>. <?php echo esc_html__('Tous droits réservés.', 'calendrier-rdv'); ?></p>
        </div>
    </div>
</body>
</html>
