<?php
/**
 * Template partiel pour le formulaire de réservation
 *
 * Variables disponibles :
 * - $atts: Attributs du shortcode
 * - $services: Tableau des services disponibles
 * - $prestataires: Tableau des prestataires disponibles
 * - $opening_hours: Tableau des heures d'ouverture
 */

// Vérifier si l'utilisateur est connecté
$current_user = wp_get_current_user();
$user_name = $current_user->exists() ? $current_user->display_name : '';
$user_email = $current_user->exists() ? $current_user->user_email : '';
$user_phone = $current_user->exists() ? get_user_meta($current_user->ID, 'billing_phone', true) : '';

// Récupérer les paramètres
$service_id = !empty($atts['service_id']) ? intval($atts['service_id']) : 0;
$prestataire_id = !empty($atts['prestataire_id']) ? intval($atts['prestataire_id']) : 0;
$show_title = $atts['show_title'] !== 'no';
$show_description = $atts['show_description'] !== 'no';

// Récupérer le fuseau horaire du site
$timezone = wp_timezone();
$timezone_string = $timezone->getName();
$timezone_offset = $timezone->getOffset(new DateTime()) / 3600; // en heures
?>

<div class="calendrier-rdv-booking-form" data-timezone="<?php echo esc_attr($timezone_string); ?>">
    <?php if ($show_title) : ?>
        <h2 class="calendrier-rdv-title"><?php echo esc_html__('Prendre rendez-vous', 'calendrier-rdv'); ?></h2>
    <?php endif; ?>
    
    <?php if ($show_description) : ?>
        <p class="calendrier-rdv-description">
            <?php echo esc_html__('Sélectionnez un service, une date et un créneau horaire pour votre rendez-vous.', 'calendrier-rdv'); ?>
        </p>
    <?php endif; ?>
    
    <form id="calendrier-rdv-form" class="calendrier-rdv-form" method="post" novalidate>
        <?php wp_nonce_field('calendrier_rdv_booking', 'calendrier_rdv_nonce'); ?>
        
        <!-- Étape 1 : Sélection du service -->
        <div class="calendrier-rdv-step" data-step="1">
            <h3 class="calendrier-rdv-step-title"><?php echo esc_html__('1. Choisissez un service', 'calendrier-rdv'); ?></h3>
            
            <div class="calendrier-rdv-fields">
                <div class="calendrier-rdv-field">
                    <label for="calendrier-rdv-service">
                        <?php echo esc_html__('Service', 'calendrier-rdv'); ?>
                        <span class="calendrier-rdv-required">*</span>
                    </label>
                    <select id="calendrier-rdv-service" name="service_id" required>
                        <option value=""><?php echo esc_html__('Sélectionnez un service', 'calendrier-rdv'); ?></option>
                        <?php foreach ($services as $service) : ?>
                            <option value="<?php echo esc_attr($service->id); ?>" 
                                <?php selected($service_id, $service->id); ?>>
                                <?php echo esc_html($service->name); ?>
                                <?php if (!empty($service->price)) : ?>
                                    - <?php echo esc_html(number_format($service->price, 2, ',', ' ')); ?> €
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (count($prestataires) > 1) : ?>
                    <div class="calendrier-rdv-field">
                        <label for="calendrier-rdv-prestataire">
                            <?php echo esc_html__('Avec', 'calendrier-rdv'); ?>
                        </label>
                        <select id="calendrier-rdv-prestataire" name="prestataire_id">
                            <option value=""><?php echo esc_html__('Peu importe', 'calendrier-rdv'); ?></option>
                            <?php foreach ($prestataires as $prestataire) : ?>
                                <option value="<?php echo esc_attr($prestataire->id); ?>" 
                                    <?php selected($prestataire_id, $prestataire->id); ?>>
                                    <?php echo esc_html($prestataire->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else : ?>
                    <input type="hidden" name="prestataire_id" value="<?php echo !empty($prestataires[0]->id) ? esc_attr($prestataires[0]->id) : ''; ?>">
                <?php endif; ?>
                
                <div class="calendrier-rdv-field">
                    <label for="calendrier-rdv-timezone">
                        <?php echo esc_html__('Fuseau horaire', 'calendrier-rdv'); ?>
                    </label>
                    <select id="calendrier-rdv-timezone" name="timezone" class="calendrier-rdv-timezone-select">
                        <?php 
                        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                        $timezone_offsets = [];
                        
                        foreach ($timezones as $tz) {
                            $tz_obj = new DateTimeZone($tz);
                            $offset = $tz_obj->getOffset(new DateTime()) / 3600; // en heures
                            $timezone_offsets[$tz] = $offset;
                        }
                        
                        // Trier par décalage
                        asort($timezone_offsets);
                        
                        // Afficher les options
                        foreach ($timezone_offsets as $tz => $offset) {
                            $offset_prefix = $offset < 0 ? '-' : '+';
                            $offset_formatted = $offset_prefix . str_pad(abs($offset), 2, '0', STR_PAD_LEFT) . ':00';
                            $tz_display = str_replace('_', ' ', $tz);
                            $selected = $tz === $timezone_string ? 'selected' : '';
                            
                            echo sprintf(
                                '<option value="%1$s" %2$s data-offset="%3$s">(UTC%4$s) %5$s</option>',
                                esc_attr($tz),
                                $selected,
                                esc_attr($offset),
                                esc_html($offset_formatted),
                                esc_html($tz_display)
                            );
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="calendrier-rdv-actions">
                <button type="button" class="calendrier-rdv-next-step" disabled>
                    <?php echo esc_html__('Suivant', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
        
        <!-- Étape 2 : Sélection de la date et de l'heure -->
        <div class="calendrier-rdv-step" data-step="2" style="display: none;">
            <div class="calendrier-rdv-step-header">
                <button type="button" class="calendrier-rdv-prev-step">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php echo esc_html__('Retour', 'calendrier-rdv'); ?>
                </button>
                <h3 class="calendrier-rdv-step-title"><?php echo esc_html__('2. Choisissez une date et une heure', 'calendrier-rdv'); ?></h3>
            </div>
            
            <div class="calendrier-rdv-fields">
                <div class="calendrier-rdv-field">
                    <label for="calendrier-rdv-date">
                        <?php echo esc_html__('Date', 'calendrier-rdv'); ?>
                        <span class="calendrier-rdv-required">*</span>
                    </label>
                    <input type="text" id="calendrier-rdv-date" name="date" class="calendrier-rdv-datepicker" 
                           placeholder="<?php echo esc_attr__('Sélectionnez une date', 'calendrier-rdv'); ?>" 
                           autocomplete="off" readonly>
                </div>
                
                <div class="calendrier-rdv-field">
                    <label for="calendrier-rdv-time">
                        <?php echo esc_html__('Horaire', 'calendrier-rdv'); ?>
                        <span class="calendrier-rdv-required">*</span>
                    </label>
                    <select id="calendrier-rdv-time" name="time" disabled>
                        <option value=""><?php echo esc_html__('Sélectionnez d\'abord une date', 'calendrier-rdv'); ?></option>
                    </select>
                    <div id="calendrier-rdv-slots-loading" style="display: none; margin-top: 10px;">
                        <span class="spinner is-active" style="float: none; margin-top: 0;"></span>
                        <?php echo esc_html__('Chargement des créneaux...', 'calendrier-rdv'); ?>
                    </div>
                    <div id="calendrier-rdv-no-slots" style="display: none; margin-top: 10px; color: #d63638;">
                        <?php echo esc_html__('Aucun créneau disponible pour cette date. Veuillez choisir une autre date.', 'calendrier-rdv'); ?>
                    </div>
                </div>
                
                <div id="calendrier-rdv-waitlist-container" style="display: none; margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">
                    <p style="margin-top: 0; margin-bottom: 10px;">
                        <span class="dashicons dashicons-info" style="color: #f0ad4e; margin-right: 5px;"></span>
                        <?php echo esc_html__('Ce créneau est complet. Souhaitez-vous être ajouté à la liste d\'attente ?', 'calendrier-rdv'); ?>
                    </p>
                    <button type="button" id="calendrier-rdv-join-waitlist" class="button button-secondary">
                        <?php echo esc_html__('Rejoindre la liste d\'attente', 'calendrier-rdv'); ?>
                    </button>
                </div>
            </div>
            
            <div class="calendrier-rdv-actions">
                <button type="button" class="calendrier-rdv-prev-step">
                    <?php echo esc_html__('Précédent', 'calendrier-rdv'); ?>
                </button>
                <button type="button" class="calendrier-rdv-next-step" disabled>
                    <?php echo esc_html__('Suivant', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
        
        <!-- Étape 3 : Informations personnelles -->
        <div class="calendrier-rdv-step" data-step="3" style="display: none;">
            <div class="calendrier-rdv-step-header">
                <button type="button" class="calendrier-rdv-prev-step">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php echo esc_html__('Retour', 'calendrier-rdv'); ?>
                </button>
                <h3 class="calendrier-rdv-step-title"><?php echo esc_html__('3. Vos coordonnées', 'calendrier-rdv'); ?></h3>
            </div>
            
            <div class="calendrier-rdv-fields">
                <div class="calendrier-rdv-field">
                    <label for="calendrier-rdv-name">
                        <?php echo esc_html__('Nom complet', 'calendrier-rdv'); ?>
                        <span class="calendrier-rdv-required">*</span>
                    </label>
                    <input type="text" id="calendrier-rdv-name" name="name" 
                           value="<?php echo esc_attr($user_name); ?>" required>
                </div>
                
                <div class="calendrier-rdv-field">
                    <label for="calendrier-rdv-email">
                        <?php echo esc_html__('Adresse email', 'calendrier-rdv'); ?>
                        <span class="calendrier-rdv-required">*</span>
                    </label>
                    <input type="email" id="calendrier-rdv-email" name="email" 
                           value="<?php echo esc_attr($user_email); ?>" required>
                </div>
                
                <div class="calendrier-rdv-field">
                    <label for="calendrier-rdv-phone">
                        <?php echo esc_html__('Téléphone', 'calendrier-rdv'); ?>
                        <span class="calendrier-rdv-required">*</span>
                    </label>
                    <input type="tel" id="calendrier-rdv-phone" name="phone" 
                           value="<?php echo esc_attr($user_phone); ?>" required>
                </div>
                
                <div class="calendrier-rdv-field">
                    <label for="calendrier-rdv-notes">
                        <?php echo esc_html__('Notes ou commentaires (optionnel)', 'calendrier-rdv'); ?>
                    </label>
                    <textarea id="calendrier-rdv-notes" name="notes" rows="3"></textarea>
                </div>
                
                <?php if (!is_user_logged_in()) : ?>
                    <div class="calendrier-rdv-field">
                        <label>
                            <input type="checkbox" name="create_account" id="calendrier-rdv-create-account" value="1">
                            <?php echo esc_html__('Créer un compte pour gérer mes rendez-vous', 'calendrier-rdv'); ?>
                        </label>
                    </div>
                    
                    <div id="calendrier-rdv-account-fields" style="display: none; margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
                        <div class="calendrier-rdv-field">
                            <label for="calendrier-rdv-password">
                                <?php echo esc_html__('Mot de passe', 'calendrier-rdv'); ?>
                                <span class="calendrier-rdv-required">*</span>
                            </label>
                            <input type="password" id="calendrier-rdv-password" name="password">
                            <p class="description">
                                <?php echo esc_html__('Créez un mot de passe pour votre compte.', 'calendrier-rdv'); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="calendrier-rdv-field">
                    <div class="calendrier-rdv-consent">
                        <label>
                            <input type="checkbox" name="privacy_policy" id="calendrier-rdv-privacy-policy" required>
                            <?php 
                            printf(
                                /* translators: %s: URL de la politique de confidentialité */
                                esc_html__('J\'accepte la %s de ce site.', 'calendrier-rdv'),
                                '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">' . esc_html__('politique de confidentialité', 'calendrier-rdv') . '</a>'
                            );
                            ?>
                            <span class="calendrier-rdv-required">*</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="calendrier-rdv-actions">
                <button type="button" class="calendrier-rdv-prev-step">
                    <?php echo esc_html__('Précédent', 'calendrier-rdv'); ?>
                </button>
                <button type="submit" class="calendrier-rdv-submit">
                    <span class="spinner" style="display: none;"></span>
                    <span class="text"><?php echo esc_html__('Confirmer la réservation', 'calendrier-rdv'); ?></span>
                </button>
            </div>
        </div>
        
        <!-- Étape 4 : Confirmation -->
        <div class="calendrier-rdv-step" data-step="4" style="display: none;">
            <div class="calendrier-rdv-confirmation">
                <div class="calendrier-rdv-confirmation-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                
                <h3 class="calendrier-rdv-confirmation-title">
                    <?php echo esc_html__('Votre rendez-vous a été réservé avec succès !', 'calendrier-rdv'); ?>
                </h3>
                
                <div class="calendrier-rdv-confirmation-details">
                    <p id="calendrier-rdv-confirmation-message">
                        <?php echo esc_html__('Un email de confirmation a été envoyé à votre adresse email.', 'calendrier-rdv'); ?>
                    </p>
                    
                    <div class="calendrier-rdv-booking-summary">
                        <h4><?php echo esc_html__('Récapitulatif de votre réservation', 'calendrier-rdv'); ?></h4>
                        <ul>
                            <li>
                                <strong><?php echo esc_html__('Service :', 'calendrier-rdv'); ?></strong>
                                <span id="booking-summary-service"></span>
                            </li>
                            <li>
                                <strong><?php echo esc_html__('Date :', 'calendrier-rdv'); ?></strong>
                                <span id="booking-summary-date"></span>
                            </li>
                            <li>
                                <strong><?php echo esc_html__('Horaire :', 'calendrier-rdv'); ?></strong>
                                <span id="booking-summary-time"></span>
                            </li>
                            <li>
                                <strong><?php echo esc_html__('Avec :', 'calendrier-rdv'); ?></strong>
                                <span id="booking-summary-prestataire"></span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="calendrier-rdv-booking-actions">
                        <a href="<?php echo esc_url(home_url()); ?>" class="button button-primary">
                            <?php echo esc_html__('Retour à l\'accueil', 'calendrier-rdv'); ?>
                        </a>
                        
                        <button type="button" id="calendrier-rdv-add-to-calendar" class="button button-secondary">
                            <span class="dashicons dashicons-calendar-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                            <?php echo esc_html__('Ajouter à mon calendrier', 'calendrier-rdv'); ?>
                        </button>
                        
                        <div id="calendrier-rdv-calendar-options" style="display: none; margin-top: 15px; text-align: left;">
                            <a href="#" class="calendrier-rdv-download-ical" target="_blank" style="display: block; margin: 5px 0;">
                                <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                                <?php echo esc_html__('Télécharger le fichier .ics', 'calendrier-rdv'); ?>
                            </a>
                            
                            <a href="#" class="calendrier-rdv-google-calendar" target="_blank" style="display: block; margin: 5px 0;">
                                <span class="dashicons dashicons-google" style="vertical-align: middle; margin-right: 5px;"></span>
                                <?php echo esc_html__('Ajouter à Google Calendar', 'calendrier-rdv'); ?>
                            </a>
                            
                            <a href="#" class="calendrier-rdv-outlook" target="_blank" style="display: block; margin: 5px 0;">
                                <span class="dashicons dashicons-email" style="vertical-align: middle; margin-right: 5px;"></span>
                                <?php echo esc_html__('Ajouter à Outlook', 'calendrier-rdv'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modale de chargement -->
<div id="calendrier-rdv-loading" style="display: none;">
    <div class="calendrier-rdv-loading-content">
        <div class="calendrier-rdv-spinner"></div>
        <p><?php echo esc_html__('Traitement en cours...', 'calendrier-rdv'); ?></p>
    </div>
</div>
