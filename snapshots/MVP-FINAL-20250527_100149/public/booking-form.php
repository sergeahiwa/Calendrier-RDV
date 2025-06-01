<?php
/**
 * Template: Formulaire de réservation
 */
?>

<div class="calendrier-rdv-container">
    <div class="calendrier-rdv-header">
        <h1>Prendre un rendez-vous</h1>
        <p>Réservez votre créneau en quelques clics</p>
    </div>
    
    <form class="booking-form" method="post">
        <div class="calendrier-rdv-grid">
            <!-- Section de sélection du prestataire -->
            <div class="selection-section">
                <h2>1. Choisissez votre prestataire</h2>
                <div class="form-group">
                    <label for="prestataire">Prestataire *</label>
                    <select id="prestataire" class="form-control" required>
                        <option value="">Sélectionnez un prestataire</option>
                        <?php foreach ($prestataires as $prestataire): ?>
                            <option value="<?php echo esc_attr($prestataire->id); ?>">
                                <?php echo esc_html($prestataire->nom); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Section de sélection du service -->
            <div class="selection-section">
                <h2>2. Choisissez votre service</h2>
                <div class="form-group">
                    <label for="service">Service *</label>
                    <select id="service" class="form-control" required disabled>
                        <option value="">Sélectionnez d'abord un prestataire</option>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo esc_attr($service->id); ?>">
                                    <?php echo esc_html($service->nom); ?>
                                    <?php if ($service->duree): ?>
                                        (<?php echo esc_html($service->duree); ?> min)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <!-- Calendrier -->
            <div class="calendar-container">
                <h2>3. Choisissez une date</h2>
                <div class="calendar"></div>
                <div class="selected-date-display">
                    <p>Date sélectionnée : <span class="selected-date">Non sélectionnée</span></p>
                </div>
                
                <div class="time-slots-container">
                    <h3>Créneaux disponibles :</h3>
                    <div class="time-slots">
                        <!-- Les créneaux seront chargés dynamiquement ici -->
                        <p>Sélectionnez d'abord une date</p>
                    </div>
                </div>
            </div>
            
            <!-- Informations personnelles -->
            <div class="selection-section">
                <h2>4. Vos informations</h2>
                <div class="form-group">
                    <label for="nom">Nom complet *</label>
                    <input type="text" id="nom" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="telephone">Téléphone *</label>
                    <input type="tel" id="telephone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes (optionnel)</label>
                    <textarea id="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
        
        <!-- Bouton de soumission -->
        <div class="form-actions">
            <button type="submit" class="btn-submit" disabled>Confirmer le rendez-vous</button>
            <div class="form-message"></div>
        </div>
        
        <?php wp_nonce_field('calendrier_rdv_booking', 'calendrier_rdv_nonce'); ?>
    </form>
</div>

<?php
// Localisation des variables pour JavaScript
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
        'selectTime' => __('Veuillez sélectionner un créneau', 'calendrier-rdv'),
    ]
]);
?>

<!-- Intégration des styles et scripts -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<link rel="stylesheet" href="<?php echo esc_url(plugins_url('public/calendrier-rdv.css', dirname(__FILE__))); ?>">

<?php
// Enregistrement des scripts avec dépendances
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('calendrier-rdv-js', 
    plugins_url('public/calendrier-rdv.js', dirname(__FILE__)), 
    ['jquery', 'jquery-ui-datepicker'], 
    filemtime(plugin_dir_path(dirname(__FILE__)) . 'public/calendrier-rdv.js'), 
    true
);

// Inline styles pour les messages
?>
<style>
    .calendrier-rdv-message {
        margin: 15px 0;
        padding: 10px 15px;
        border-radius: 4px;
    }
    .calendrier-rdv-message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .calendrier-rdv-message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .loading {
        display: inline-block;
        margin-left: 10px;
        vertical-align: middle;
    }
</style>
