<?php
/**
 * Template pour le rappel de rendez-vous
 * 
 * Variables disponibles :
 * - $appointment : Objet Appointment
 * - $service : Objet Service
 * - $provider : Objet Provider
 * - $customer_name : Nom du client
 * - $customer_email : Email du client
 * - $start_date : Date de début du rendez-vous (objet DateTime)
 * - $end_date : Date de fin du rendez-vous (objet DateTime)
 * - $status : Statut du rendez-vous
 * - $payment_status : Statut du paiement
 * - $site_name : Nom du site
 * - $site_url : URL du site
 * - $admin_url : URL de l'administration
 * - $current_time : Date et heure actuelles
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Rappel : Votre rendez-vous approche</title>
    <style type="text/css">
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f8f8; padding: 20px; text-align: center; border-bottom: 1px solid #e7e7e7; }
        .content { padding: 20px; background-color: #fff; }
        .footer { margin-top: 20px; padding: 20px; text-align: center; font-size: 12px; color: #777; }
        .button { 
            display: inline-block; 
            padding: 10px 20px; 
            background-color: #2196F3; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        .appointment-details { 
            background-color: #f0f7ff; 
            padding: 15px; 
            border-left: 4px solid #2196F3; 
            margin: 20px 0; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rappel : Votre rendez-vous approche</h1>
        </div>
        
        <div class="content">
            <p>Bonjour <?php echo esc_html($customer_name); ?>,</p>
            
            <p>Ceci est un rappel pour votre rendez-vous prévu le <strong><?php echo $start_date->format('l d F Y à H:i'); ?></strong>.</p>
            
            <div class="appointment-details">
                <h3>Détails du rendez-vous</h3>
                <p><strong>Service :</strong> <?php echo esc_html($service->getName()); ?></p>
                <p><strong>Prestataire :</strong> <?php echo esc_html($provider->getFullName()); ?></p>
                <p><strong>Date et heure :</strong> <?php echo $start_date->format('l d F Y à H:i'); ?></p>
                <p><strong>Durée :</strong> <?php echo $service->getDuration(); ?> minutes</p>
                <p><strong>Lieu :</strong> En ligne</p>
                
                <p><em>Ce rendez-vous aura lieu dans moins de 24 heures.</em></p>
            </div>
            
            <p>Pour vous connecter à votre rendez-vous, cliquez sur le bouton ci-dessous :</p>
            
            <p>
                <a href="<?php echo esc_url($site_url); ?>" class="button">Accéder au rendez-vous</a>
            </p>
            
            <p>Si vous ne pouvez pas vous présenter, veuillez nous prévenir dès que possible.</p>
            
            <p>Cordialement,<br>L'équipe <?php echo esc_html($site_name); ?></p>
        </div>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. Tous droits réservés.</p>
            <p>Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet email.</p>
        </div>
    </div>
</body>
</html>
