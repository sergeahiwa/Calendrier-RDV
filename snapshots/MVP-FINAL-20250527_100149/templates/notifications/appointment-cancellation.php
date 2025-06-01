<?php
/**
 * Template pour l'annulation de rendez-vous
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
 * - $cancellation_reason : Raison de l'annulation
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
    <title>Annulation de votre rendez-vous</title>
    <style type="text/css">
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f8f8; padding: 20px; text-align: center; border-bottom: 1px solid #e7e7e7; }
        .content { padding: 20px; background-color: #fff; }
        .footer { margin-top: 20px; padding: 20px; text-align: center; font-size: 12px; color: #777; }
        .button { 
            display: inline-block; 
            padding: 10px 20px; 
            background-color: #f44336; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin: 10px 0; 
        }
        .appointment-details { 
            background-color: #ffebee; 
            padding: 15px; 
            border-left: 4px solid #f44336; 
            margin: 20px 0; 
        }
        .cancellation-reason {
            background-color: #fff3e0;
            padding: 15px;
            border-left: 4px solid #ff9800;
            margin: 15px 0;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Annulation de votre rendez-vous</h1>
        </div>
        
        <div class="content">
            <p>Bonjour <?php echo esc_html($customer_name); ?>,</p>
            
            <p>Nous vous informons que votre rendez-vous prévu le <strong><?php echo $start_date->format('l d F Y à H:i'); ?></strong> a été annulé.</p>
            
            <?php if (!empty($cancellation_reason)): ?>
            <div class="cancellation-reason">
                <p><strong>Raison de l'annulation :</strong><br><?php echo nl2br(esc_html($cancellation_reason)); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="appointment-details">
                <h3>Détails du rendez-vous annulé</h3>
                <p><strong>Service :</strong> <?php echo esc_html($service->getName()); ?></p>
                <p><strong>Prestataire :</strong> <?php echo esc_html($provider->getFullName()); ?></p>
                <p><strong>Date et heure :</strong> <?php echo $start_date->format('l d F Y à H:i'); ?></p>
                <p><strong>Référence :</strong> #<?php echo $appointment->getId(); ?></p>
            </div>
            
            <p>Si vous souhaitez prendre un nouveau rendez-vous, vous pouvez cliquer sur le bouton ci-dessous :</p>
            
            <p>
                <a href="<?php echo esc_url($site_url); ?>" class="button">Prendre un nouveau rendez-vous</a>
            </p>
            
            <p>Si vous n'êtes pas à l'origine de cette annulation ou si vous avez des questions, n'hésitez pas à nous contacter.</p>
            
            <p>Nous nous excusons pour la gêne occasionnée et espérons vous revoir bientôt.</p>
            
            <p>Cordialement,<br>L'équipe <?php echo esc_html($site_name); ?></p>
        </div>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. Tous droits réservés.</p>
            <p>Si vous n'êtes pas concerné par cette annulation, veuillez ignorer cet email.</p>
        </div>
    </div>
</body>
</html>
