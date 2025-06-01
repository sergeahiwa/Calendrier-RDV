<?php
/**
 * Template pour l'affichage du formulaire de réservation
 * 
 * @package CalendrierRDV
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}
?>

<div class="rdv-calendar-container">
    <div class="rdv-calendar-messages"></div>
    
    <form id="rdv-booking-form" class="rdv-booking-form" method="post" novalidate>
        <div class="rdv-form-group">
            <label for="rdv-prestataire"><?php esc_html_e('Prestataire', 'calendrier-rdv'); ?></label>
            <select id="rdv-prestataire" name="prestataire_id" class="rdv-form-control" required>
                <?php foreach ($prestataires as $prestataire) : ?>
                    <option value="<?php echo esc_attr($prestataire->id); ?>">
                        <?php echo esc_html($prestataire->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="rdv-form-group">
            <label for="rdv-date"><?php esc_html_e('Date', 'calendrier-rdv'); ?></label>
            <input type="text" id="rdv-date" name="date" class="rdv-form-control rdv-datepicker" required 
                   placeholder="<?php esc_attr_e('Sélectionnez une date', 'calendrier-rdv'); ?>" 
                   readonly="readonly" autocomplete="off">
        </div>
        
        <div class="rdv-form-group rdv-time-slots" style="display: none;">
            <label><?php esc_html_e('Créneaux disponibles', 'calendrier-rdv'); ?></label>
            <div class="rdv-time-slots-container"></div>
        </div>
        
        <div class="rdv-booking-details" style="display: none;">
            <h3><?php esc_html_e('Vos coordonnées', 'calendrier-rdv'); ?></h3>
            
            <div class="rdv-form-group">
                <label for="rdv-name"><?php esc_html_e('Nom complet', 'calendrier-rdv'); ?> *</label>
                <input type="text" id="rdv-name" name="title" class="rdv-form-control" required>
            </div>
            
            <div class="rdv-form-group">
                <label for="rdv-email"><?php esc_html_e('Email', 'calendrier-rdv'); ?> *</label>
                <input type="email" id="rdv-email" name="email" class="rdv-form-control" required>
            </div>
            
            <div class="rdv-form-group">
                <label for="rdv-phone"><?php esc_html_e('Téléphone', 'calendrier-rdv'); ?> *</label>
                <input type="tel" id="rdv-phone" name="telephone" class="rdv-form-control" required>
            </div>
            
            <div class="rdv-form-group">
                <label for="rdv-notes"><?php esc_html_e('Notes (optionnel)', 'calendrier-rdv'); ?></label>
                <textarea id="rdv-notes" name="notes" class="rdv-form-control" rows="3"></textarea>
            </div>
            
            <div class="rdv-form-actions">
                <button type="button" class="rdv-btn rdv-btn-secondary rdv-btn-cancel">
                    <?php esc_html_e('Annuler', 'calendrier-rdv'); ?>
                </button>
                <button type="submit" class="rdv-btn rdv-btn-primary rdv-btn-submit">
                    <?php esc_html_e('Confirmer le rendez-vous', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
        
        <div class="rdv-booking-confirmation" style="display: none;">
            <div class="rdv-alert rdv-alert-success">
                <h3><?php esc_html_e('Rendez-vous confirmé !', 'calendrier-rdv'); ?></h3>
                <p><?php esc_html_e('Merci, votre rendez-vous a été enregistré avec succès. Vous allez recevoir une confirmation par email.', 'calendrier-rdv'); ?></p>
                <p class="rdv-booking-reference">
                    <strong><?php esc_html_e('Référence :', 'calendrier-rdv'); ?></strong> 
                    <span class="rdv-reference"></span>
                </p>
                <button type="button" class="rdv-btn rdv-btn-primary rdv-btn-new-booking">
                    <?php esc_html_e('Prendre un nouveau rendez-vous', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
    </form>
</div>
