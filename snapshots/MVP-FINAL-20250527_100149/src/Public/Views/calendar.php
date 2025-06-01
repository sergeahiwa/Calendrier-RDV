<?php
/**
 * Template du calendrier public
 *
 * @package CalendrierRdv\Public\Views
 * @var array $atts Attributs du shortcode
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupération du nonce pour la sécurité
$nonce = wp_create_nonce('cal_rdv_booking');
?>

<div class="calendrier-rdv-container" id="calendrier-rdv">
    <div class="calendrier-rdv-loading">
        <span class="spinner is-active"></span>
        <?php esc_html_e('Chargement du calendrier...', 'calendrier-rdv'); ?>
    </div>
    
    <div class="calendrier-rdv-content" style="display: none;">
        <!-- En-tête du calendrier -->
        <div class="calendrier-rdv-header">
            <h2><?php esc_html_e('Prendre rendez-vous', 'calendrier-rdv'); ?></h2>
            <div class="calendrier-rdv-steps">
                <div class="calendrier-rdv-step active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-label"><?php esc_html_e('Choisir une date', 'calendrier-rdv'); ?></span>
                </div>
                <div class="calendrier-rdv-step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-label"><?php esc_html_e('Choisir un créneau', 'calendrier-rdv'); ?></span>
                </div>
                <div class="calendrier-rdv-step" data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-label"><?php esc_html_e('Confirmer', 'calendrier-rdv'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Étape 1 : Sélection de la date -->
        <div class="calendrier-rdv-step-content" data-step="1">
            <div class="calendrier-rdv-month-nav">
                <button type="button" class="calendrier-rdv-prev-month" aria-label="<?php esc_attr_e('Mois précédent', 'calendrier-rdv'); ?>">
                    &larr;
                </button>
                <h3 class="calendrier-rdv-current-month"></h3>
                <button type="button" class="calendrier-rdv-next-month" aria-label="<?php esc_attr_e('Mois suivant', 'calendrier-rdv'); ?>">
                    &rarr;
                </button>
            </div>
            
            <div class="calendrier-rdv-calendar">
                <!-- Le calendrier sera généré par JavaScript -->
            </div>
            
            <div class="calendrier-rdv-actions">
                <button type="button" class="button calendrier-rdv-next-step" disabled>
                    <?php esc_html_e('Suivant', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
        
        <!-- Étape 2 : Sélection du créneau horaire -->
        <div class="calendrier-rdv-step-content" data-step="2" style="display: none;">
            <h3 class="calendrier-rdv-selected-date"></h3>
            
            <div class="calendrier-rdv-time-slots">
                <!-- Les créneaux seront chargés par JavaScript -->
                <p class="calendrier-rdv-loading-slots">
                    <span class="spinner is-active"></span>
                    <?php esc_html_e('Chargement des créneaux disponibles...', 'calendrier-rdv'); ?>
                </p>
            </div>
            
            <div class="calendrier-rdv-actions">
                <button type="button" class="button calendrier-rdv-prev-step">
                    <?php esc_html_e('Retour', 'calendrier-rdv'); ?>
                </button>
                <button type="button" class="button button-primary calendrier-rdv-next-step" disabled>
                    <?php esc_html_e('Suivant', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
        
        <!-- Étape 3 : Formulaire de confirmation -->
        <div class="calendrier-rdv-step-content" data-step="3" style="display: none;">
            <h3><?php esc_html_e('Confirmer votre rendez-vous', 'calendrier-rdv'); ?></h3>
            
            <div class="calendrier-rdv-booking-summary">
                <p class="calendrier-rdv-booking-date"></p>
                <p class="calendrier-rdv-booking-time"></p>
                <p class="calendrier-rdv-booking-service"></p>
                <p class="calendrier-rdv-booking-provider"></p>
            </div>
            
            <form id="calendrier-rdv-booking-form" class="calendrier-rdv-form">
                <input type="hidden" name="action" value="cal_rdv_submit_booking">
                <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="hidden" name="date" id="calendrier-rdv-booking-date">
                <input type="hidden" name="time" id="calendrier-rdv-booking-time">
                <input type="hidden" name="service_id" id="calendrier-rdv-booking-service-id">
                <input type="hidden" name="provider_id" id="calendrier-rdv-booking-provider-id">
                
                <div class="calendrier-rdv-form-group">
                    <label for="calendrier-rdv-booking-name">
                        <?php esc_html_e('Nom complet', 'calendrier-rdv'); ?> *
                    </label>
                    <input type="text" 
                           id="calendrier-rdv-booking-name" 
                           name="name" 
                           required 
                           class="regular-text">
                </div>
                
                <div class="calendrier-rdv-form-group">
                    <label for="calendrier-rdv-booking-email">
                        <?php esc_html_e('Adresse email', 'calendrier-rdv'); ?> *
                    </label>
                    <input type="email" 
                           id="calendrier-rdv-booking-email" 
                           name="email" 
                           required 
                           class="regular-text">
                </div>
                
                <div class="calendrier-rdv-form-group">
                    <label for="calendrier-rdv-booking-phone">
                        <?php esc_html_e('Téléphone', 'calendrier-rdv'); ?> *
                    </label>
                    <input type="tel" 
                           id="calendrier-rdv-booking-phone" 
                           name="phone" 
                           required 
                           class="regular-text">
                </div>
                
                <div class="calendrier-rdv-form-group">
                    <label for="calendrier-rdv-booking-notes">
                        <?php esc_html_e('Notes supplémentaires', 'calendrier-rdv'); ?>
                    </label>
                    <textarea id="calendrier-rdv-booking-notes" 
                              name="notes" 
                              rows="3" 
                              class="large-text"></textarea>
                </div>
                
                <div class="calendrier-rdv-form-actions">
                    <button type="button" class="button calendrier-rdv-prev-step">
                        <?php esc_html_e('Retour', 'calendrier-rdv'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Confirmer le rendez-vous', 'calendrier-rdv'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Message de confirmation -->
        <div class="calendrier-rdv-booking-confirmation" style="display: none;">
            <div class="calendrier-rdv-booking-success">
                <span class="dashicons dashicons-yes"></span>
                <h3><?php esc_html_e('Votre rendez-vous a été enregistré !', 'calendrier-rdv'); ?></h3>
                <p><?php esc_html_e('Un email de confirmation vous a été envoyé.', 'calendrier-rdv'); ?></p>
                <div class="calendrier-rdv-booking-details"></div>
                <button type="button" class="button button-primary calendrier-rdv-new-booking">
                    <?php esc_html_e('Prendre un nouveau rendez-vous', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
