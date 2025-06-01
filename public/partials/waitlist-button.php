<?php
/**
 * Template partiel pour le bouton de liste d'attente
 *
 * Variables disponibles :
 * - $atts: Attributs du shortcode
 * - $is_on_waitlist: Booléen indiquant si l'utilisateur est déjà en liste d'attente
 */

// Récupérer les paramètres
$service_id = !empty($atts['service_id']) ? intval($atts['service_id']) : 0;
$date = !empty($atts['date']) ? sanitize_text_field($atts['date']) : '';
$start_time = !empty($atts['start_time']) ? sanitize_text_field($atts['start_time']) : '';
$end_time = !empty($atts['end_time']) ? sanitize_text_field($atts['end_time']) : '';
$class = !empty($atts['class']) ? esc_attr($atts['class']) : 'button';

// Vérifier si les paramètres requis sont présents
if (!$service_id || !$date || !$start_time) {
    return;
}

// Récupérer le service
$service = get_post($service_id);
if (!$service) {
    return;
}

// Récupérer l'utilisateur courant
$current_user = wp_get_current_user();
$user_name = $current_user->exists() ? $current_user->display_name : '';
$user_email = $current_user->exists() ? $current_user->user_email : '';
$user_phone = $current_user->exists() ? get_user_meta($current_user->ID, 'billing_phone', true) : '';
?>

<div class="calendrier-rdv-waitlist-button" 
     data-service-id="<?php echo esc_attr($service_id); ?>"
     data-date="<?php echo esc_attr($date); ?>"
     data-start-time="<?php echo esc_attr($start_time); ?>"
     data-end-time="<?php echo esc_attr($end_time); ?>">
    
    <?php if ($is_on_waitlist) : ?>
        <button type="button" class="calendrier-rdv-leave-waitlist <?php echo esc_attr($class); ?>">
            <span class="dashicons dashicons-dismiss" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php echo esc_html__('Quitter la liste d\'attente', 'calendrier-rdv'); ?>
        </button>
        
        <div class="calendrier-rdv-waitlist-status" style="margin-top: 10px; font-size: 0.9em; color: #666;">
            <span class="dashicons dashicons-yes" style="color: #46b450; vertical-align: middle;"></span>
            <?php echo esc_html__('Vous êtes sur la liste d\'attente pour ce créneau.', 'calendrier-rdv'); ?>
        </div>
    <?php else : ?>
        <button type="button" class="calendrier-rdv-join-waitlist <?php echo esc_attr($class); ?>">
            <span class="dashicons dashicons-editor-ol" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php echo esc_html__('Rejoindre la liste d\'attente', 'calendrier-rdv'); ?>
        </button>
    <?php endif; ?>
    
    <!-- Formulaire pour rejoindre la liste d'attente (caché par défaut) -->
    <div id="calendrier-rdv-waitlist-form" style="display: none; margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
        <h4 style="margin-top: 0;">
            <?php echo esc_html__('Rejoindre la liste d\'attente', 'calendrier-rdv'); ?>
        </h4>
        
        <p style="margin-top: 0; margin-bottom: 15px;">
            <?php echo esc_html__('Soyez notifié par email si une place se libère pour ce créneau.', 'calendrier-rdv'); ?>
        </p>
        
        <div class="calendrier-rdv-field" style="margin-bottom: 10px;">
            <label for="calendrier-rdv-waitlist-name" style="display: block; margin-bottom: 5px; font-weight: 600;">
                <?php echo esc_html__('Votre nom', 'calendrier-rdv'); ?>
                <span class="calendrier-rdv-required" style="color: #dc3232;">*</span>
            </label>
            <input type="text" id="calendrier-rdv-waitlist-name" 
                   value="<?php echo esc_attr($user_name); ?>" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div class="calendrier-rdv-field" style="margin-bottom: 10px;">
            <label for="calendrier-rdv-waitlist-email" style="display: block; margin-bottom: 5px; font-weight: 600;">
                <?php echo esc_html__('Votre email', 'calendrier-rdv'); ?>
                <span class="calendrier-rdv-required" style="color: #dc3232;">*</span>
            </label>
            <input type="email" id="calendrier-rdv-waitlist-email" 
                   value="<?php echo esc_attr($user_email); ?>" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div class="calendrier-rdv-field" style="margin-bottom: 15px;">
            <label for="calendrier-rdv-waitlist-phone" style="display: block; margin-bottom: 5px; font-weight: 600;">
                <?php echo esc_html__('Votre téléphone', 'calendrier-rdv'); ?>
                <span class="calendrier-rdv-required" style="color: #dc3232;">*</span>
            </label>
            <input type="tel" id="calendrier-rdv-waitlist-phone" 
                   value="<?php echo esc_attr($user_phone); ?>" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div class="calendrier-rdv-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="button button-secondary calendrier-rdv-cancel-waitlist" style="margin-right: auto;">
                <?php echo esc_html__('Annuler', 'calendrier-rdv'); ?>
            </button>
            
            <button type="button" class="button button-primary calendrier-rdv-submit-waitlist" disabled>
                <span class="spinner" style="margin-top: 4px; float: none; visibility: hidden;"></span>
                <span class="text"><?php echo esc_html__('Confirmer', 'calendrier-rdv'); ?></span>
            </button>
        </div>
    </div>
    
    <!-- Message de confirmation (caché par défaut) -->
    <div id="calendrier-rdv-waitlist-success" style="display: none; margin-top: 15px; padding: 15px; background-color: #edfaef; border-left: 4px solid #46b450; border-radius: 4px;">
        <p style="margin: 0;">
            <span class="dashicons dashicons-yes" style="color: #46b450; vertical-align: middle;"></span>
            <?php echo esc_html__('Vous avez été ajouté à la liste d\'attente avec succès !', 'calendrier-rdv'); ?>
        </p>
    </div>
    
    <!-- Message d'erreur (caché par défaut) -->
    <div id="calendrier-rdv-waitlist-error" style="display: none; margin-top: 15px; padding: 15px; background-color: #fbeaea; border-left: 4px solid #dc3232; border-radius: 4px;">
        <p style="margin: 0; color: #dc3232;">
            <span class="dashicons dashicons-warning" style="vertical-align: middle;"></span>
            <span class="message"></span>
        </p>
    </div>
</div>

<!-- Script pour gérer les interactions de la liste d'attente -->
<script>
jQuery(document).ready(function($) {
    // Afficher le formulaire de liste d'attente
    $('.calendrier-rdv-join-waitlist').on('click', function(e) {
        e.preventDefault();
        var $container = $(this).closest('.calendrier-rdv-waitlist-button');
        $container.find('#calendrier-rdv-waitlist-form').slideDown();
    });
    
    // Annuler le formulaire de liste d'attente
    $('.calendrier-rdv-cancel-waitlist').on('click', function() {
        $(this).closest('#calendrier-rdv-waitlist-form').slideUp();
    });
    
    // Valider le formulaire de liste d'attente
    $('.calendrier-rdv-submit-waitlist').on('click', function() {
        var $button = $(this);
        var $form = $button.closest('#calendrier-rdv-waitlist-form');
        var $container = $button.closest('.calendrier-rdv-waitlist-button');
        
        var name = $('#calendrier-rdv-waitlist-name').val().trim();
        var email = $('#calendrier-rdv-waitlist-email').val().trim();
        var phone = $('#calendrier-rdv-waitlist-phone').val().trim();
        
        // Validation
        if (!name || !email || !phone) {
            showWaitlistError('Veuillez remplir tous les champs obligatoires.');
            return;
        }
        
        if (!isValidEmail(email)) {
            showWaitlistError('Veuillez entrer une adresse email valide.');
            return;
        }
        
        // Désactiver le bouton et afficher le spinner
        $button.prop('disabled', true);
        $button.find('.spinner').css('visibility', 'visible');
        
        // Envoyer la requête AJAX
        $.ajax({
            url: calendrierRdvVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'calendrier_rdv_join_waitlist',
                nonce: calendrierRdvVars.nonce,
                service_id: $container.data('service-id'),
                date: $container.data('date'),
                start_time: $container.data('start-time'),
                end_time: $container.data('end-time'),
                name: name,
                email: email,
                phone: phone
            },
            success: function(response) {
                if (response.success) {
                    // Afficher le message de succès
                    $form.slideUp();
                    $container.find('#calendrier-rdv-waitlist-success').slideDown();
                    
                    // Mettre à jour l'interface
                    $container.find('.calendrier-rdv-join-waitlist')
                        .removeClass('calendrier-rdv-join-waitlist')
                        .addClass('calendrier-rdv-leave-waitlist')
                        .html('<span class="dashicons dashicons-dismiss" style="vertical-align: middle; margin-right: 5px;"></span> <?php echo esc_js(__('Quitter la liste d\'attente', 'calendrier-rdv')); ?>');
                    
                    // Ajouter le statut de liste d'attente
                    if (!$container.find('.calendrier-rdv-waitlist-status').length) {
                        $container.append(
                            '<div class="calendrier-rdv-waitlist-status" style="margin-top: 10px; font-size: 0.9em; color: #666;">' +
                            '<span class="dashicons dashicons-yes" style="color: #46b450; vertical-align: middle;"></span> ' +
                            '<?php echo esc_js(__('Vous êtes sur la liste d\\\'attente pour ce créneau.', 'calendrier-rdv')); ?>' +
                            '</div>'
                        );
                    }
                } else {
                    // Afficher l'erreur
                    var message = response.data && response.data.message ? response.data.message : 'Une erreur est survenue. Veuillez réessayer.';
                    showWaitlistError(message);
                }
            },
            error: function() {
                showWaitlistError('Une erreur est survenue lors de la communication avec le serveur.');
            },
            complete: function() {
                // Réactiver le bouton et masquer le spinner
                $button.prop('disabled', false);
                $button.find('.spinner').css('visibility', 'hidden');
            }
        });
    });
    
    // Quitter la liste d'attente
    $(document).on('click', '.calendrier-rdv-leave-waitlist', function(e) {
        e.preventDefault();
        
        if (!confirm('Êtes-vous sûr de vouloir quitter la liste d\'attente pour ce créneau ?')) {
            return;
        }
        
        var $button = $(this);
        var $container = $button.closest('.calendrier-rdv-waitlist-button');
        
        // Désactiver le bouton
        $button.prop('disabled', true);
        
        // Envoyer la requête AJAX
        $.ajax({
            url: calendrierRdvVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'calendrier_rdv_leave_waitlist',
                nonce: calendrierRdvVars.nonce,
                service_id: $container.data('service-id'),
                date: $container.data('date'),
                start_time: $container.data('start-time')
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour l'interface
                    $container.find('.calendrier-rdv-leave-waitlist')
                        .removeClass('calendrier-rdv-leave-waitlist')
                        .addClass('calendrier-rdv-join-waitlist')
                        .html('<span class="dashicons dashicons-editor-ol" style="vertical-align: middle; margin-right: 5px;"></span> <?php echo esc_js(__('Rejoindre la liste d\\\'attente', 'calendrier-rdv')); ?>');
                    
                    // Supprimer le statut de liste d'attente
                    $container.find('.calendrier-rdv-waitlist-status').remove();
                    
                    // Afficher un message de confirmation
                    alert('Vous avez été retiré de la liste d\'attente avec succès.');
                } else {
                    // Afficher l'erreur
                    var message = response.data && response.data.message ? response.data.message : 'Une erreur est survenue. Veuillez réessayer.';
                    alert(message);
                }
            },
            error: function() {
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            },
            complete: function() {
                // Réactiver le bouton
                $button.prop('disabled', false);
            }
        });
    });
    
    // Valider les champs du formulaire en temps réel
    $('#calendrier-rdv-waitlist-name, #calendrier-rdv-waitlist-email, #calendrier-rdv-waitlist-phone').on('input', function() {
        validateWaitlistForm();
    });
    
    // Fonction pour valider le formulaire de liste d'attente
    function validateWaitlistForm() {
        var name = $('#calendrier-rdv-waitlist-name').val().trim();
        var email = $('#calendrier-rdv-waitlist-email').val().trim();
        var phone = $('#calendrier-rdv-waitlist-phone').val().trim();
        
        var isValid = name && email && phone && isValidEmail(email);
        $('.calendrier-rdv-submit-waitlist').prop('disabled', !isValid);
    }
    
    // Fonction pour afficher une erreur
    function showWaitlistError(message) {
        var $error = $('#calendrier-rdv-waitlist-error');
        $error.find('.message').text(message);
        $error.slideDown();
        
        // Masquer l'erreur après 5 secondes
        setTimeout(function() {
            $error.slideUp();
        }, 5000);
    }
    
    // Fonction pour valider l'email
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
</script>
