<?php
/**
 * Template: Formulaire de réservation
 *
 * @package Calendrier_RDV
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enregistrer et charger les styles et scripts
function calendrier_rdv_enqueue_booking_assets() {
    // Enqueue Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    
    // Enqueue jQuery UI CSS
    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
    
    // Enqueue notre CSS personnalisé
    wp_enqueue_style('calendrier-rdv-booking-form', plugin_dir_url(__FILE__) . 'css/booking-form.css', array(), CALENDRIER_RDV_VERSION);
    
    // Enqueue jQuery et jQuery UI
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    
    // Localiser l'URL du répertoire des thèmes pour les images
    wp_localize_script('jquery', 'calendrierRdvVars', [
        'restUrl' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'pluginUrl' => plugin_dir_url(dirname(__FILE__)),
        'dateFormat' => 'yy-mm-dd',
        'timeFormat' => 'HH:mm',
        'i18n' => [
            'loading' => __('Chargement...', 'calendrier-rdv'),
            'noSlots' => __('Aucun créneau disponible', 'calendrier-rdv'),
            'selectDate' => __('Veuillez sélectionner une date', 'calendrier-rdv'),
            'selectTime' => __('Veuillez sélectionner un créneau horaire', 'calendrier-rdv'),
            'requiredField' => __('Ce champ est obligatoire', 'calendrier-rdv'),
            'invalidEmail' => __('Veuillez entrer une adresse email valide', 'calendrier-rdv'),
            'bookingSuccess' => __('Votre rendez-vous a été enregistré avec succès!', 'calendrier-rdv'),
            'bookingError' => __('Une erreur est survenue. Veuillez réessayer.', 'calendrier-rdv')
        ]
    ]);
    
    // Enqueue notre script JavaScript
    wp_enqueue_script(
        'calendrier-rdv-booking-form',
        plugin_dir_url(__FILE__) . 'js/booking-form.js',
        array('jquery', 'jquery-ui-datepicker'),
        CALENDRIER_RDV_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'calendrier_rdv_enqueue_booking_assets');

// Récupérer les services disponibles
$services = []; // À remplacer par votre logique de récupération des services

?>

<div class="calendrier-rdv-container">
    <div class="calendrier-rdv-header">
        <h1>Prendre un rendez-vous</h1>
        <p class="subtitle">Choisissez un service, un prestataire, une date et un créneau pour votre rendez-vous</p>
    </div>
    
    <form class="booking-form" id="calendrier-rdv-form" method="post">
        <!-- Étape 1 : Sélection du service -->
        <div class="booking-step" id="step-1">
            <div class="step-header">
                <span class="step-number">1</span>
                <h2>Choisissez votre service</h2>
            </div>
            
            <div class="services-grid">
                <?php foreach ($services as $service): ?>
                    <div class="service-card" data-service-id="<?php echo esc_attr($service->id); ?>">
                        <div class="service-icon">
                            <i class="fas fa-<?php echo esc_attr($service->icone ?? 'calendar-alt'); ?>"></i>
                        </div>
                        <h3><?php echo esc_html($service->nom); ?></h3>
                        <?php if ($service->duree): ?>
                            <p class="duration"><?php echo esc_html($service->duree); ?> min</p>
                        <?php endif; ?>
                        <?php if ($service->prix): ?>
                            <p class="price"><?php echo esc_html($service->prix); ?> €</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="step-actions">
                <button type="button" class="btn-next" disabled>Suivant</button>
            </div>
        </div>
        
        <!-- Étape 2 : Sélection du prestataire -->
        <div class="booking-step" id="step-2" style="display: none;">
            <div class="step-header">
                <span class="step-number">2</span>
                <h2>Choisissez votre prestataire</h2>
            </div>
            
            <div class="providers-list" id="providers-container">
                <!-- Les prestataires seront chargés dynamiquement ici -->
                <p class="loading-text">Chargement des prestataires disponibles...</p>
            </div>
            
            <div class="step-actions">
                <button type="button" class="btn-prev">Précédent</button>
                <button type="button" class="btn-next" disabled>Suivant</button>
            </div>
        </div>
        
        <!-- Étape 3 : Sélection de la date -->
        <div class="booking-step" id="step-3" style="display: none;">
            <div class="step-header">
                <span class="step-number">3</span>
                <h2>Choisissez une date</h2>
            </div>
            
            <div class="date-selection">
                <div class="calendar-container">
                    <div id="booking-calendar"></div>
                </div>
                
                <div class="time-slots-container">
                    <h3>Créneaux disponibles</h3>
                    <div class="time-slots" id="available-slots">
                        <p class="info-message">Sélectionnez une date pour voir les créneaux disponibles</p>
                    </div>
                </div>
            </div>
            
            <div class="step-actions">
                <button type="button" class="btn-prev">Précédent</button>
                <button type="button" class="btn-next" disabled>Suivant</button>
            </div>
        </div>
        
        <!-- Étape 4 : Informations personnelles -->
        <div class="booking-step" id="step-4" style="display: none;">
            <div class="step-header">
                <span class="step-number">4</span>
                <h2>Vos informations</h2>
            </div>
            
            <div class="booking-summary">
                <h3>Récapitulatif de votre rendez-vous</h3>
                <div class="summary-details">
                    <p><strong>Service :</strong> <span id="selected-service">-</span></p>
                    <p><strong>Prestataire :</strong> <span id="selected-provider">-</span></p>
                    <p><strong>Date :</strong> <span id="selected-date">-</span></p>
                    <p><strong>Horaire :</strong> <span id="selected-time">-</span></p>
                </div>
            </div>
            
            <div class="form-fields">
                <div class="form-group">
                    <label for="nom">Nom complet *</label>
                    <input type="text" id="nom" name="customer_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Adresse email *</label>
                    <input type="email" id="email" name="customer_email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="telephone">Téléphone *</label>
                    <input type="tel" id="telephone" name="customer_phone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="notes">Informations complémentaires (optionnel)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Précisez ici toute information utile pour votre rendez-vous"></textarea>
                </div>
                
                <div class="form-group terms-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        J'accepte les <a href="<?php echo esc_url(get_permalink(get_option('wp_page_for_privacy_policy'))); ?>" target="_blank">mentions légales</a> et la politique de confidentialité *
                    </label>
                </div>
            </div>
            
            <div class="step-actions">
                <button type="button" class="btn-prev">Précédent</button>
                <button type="submit" class="btn-submit">Confirmer le rendez-vous</button>
            </div>
        </div>
        
        <!-- Étape 5 : Confirmation -->
        <div class="booking-step" id="step-5" style="display: none;">
            <div class="confirmation-message">
                <div class="confirmation-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Votre rendez-vous est confirmé !</h2>
                <p>Un email de confirmation vous a été envoyé à <span id="confirmation-email"></span></p>
                
                <div class="confirmation-details">
                    <h3>Détails du rendez-vous</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Numéro de réservation :</span>
                            <span class="detail-value" id="booking-number">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Service :</span>
                            <span class="detail-value" id="confirmation-service">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Prestataire :</span>
                            <span class="detail-value" id="confirmation-provider">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date :</span>
                            <span class="detail-value" id="confirmation-date">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Horaire :</span>
                            <span class="detail-value" id="confirmation-time">-</span>
                        </div>
                    </div>
                </div>
                
                <div class="confirmation-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-home">Retour à l'accueil</a>
                    <a href="#" class="btn-print" id="print-booking">Imprimer ce rendez-vous</a>
                </div>
                
                <div class="confirmation-note">
                    <p><i class="fas fa-info-circle"></i> Un rappel vous sera envoyé 24h avant votre rendez-vous.</p>
                </div>
            </div>
        </div>
        
        <?php wp_nonce_field('calendrier_rdv_booking', 'calendrier_rdv_nonce'); ?>
    </form>
</div>
