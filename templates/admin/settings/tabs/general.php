<?php
/**
 * Template pour l'onglet des paramètres généraux
 *
 * @package     CalendrierRdv\Templates\Admin\Settings\Tabs
 * @since       1.0.0
 */

// Vérification de sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les options
$options = get_option('cal_rdv_settings', []);

// Définir les valeurs par défaut
$defaults = [
    'time_slot' => 30,
    'min_booking_notice' => 60,
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
    'start_of_week' => get_option('start_of_week', 1),
    'company_name' => get_bloginfo('name'),
    'company_address' => '',
    'company_phone' => '',
    'company_email' => get_bloginfo('admin_email'),
];

$settings = wp_parse_args($options, $defaults);

// Jours de la semaine
$week_days = [
    0 => __('Dimanche', 'calendrier-rdv'),
    1 => __('Lundi', 'calendrier-rdv'),
    2 => __('Mardi', 'calendrier-rdv'),
    3 => __('Mercredi', 'calendrier-rdv'),
    4 => __('Jeudi', 'calendrier-rdv'),
    5 => __('Vendredi', 'calendrier-rdv'),
    6 => __('Samedi', 'calendrier-rdv'),
];
?>

<div class="cal-rdv-settings-section">
    <h2><?php esc_html_e('Paramètres généraux', 'calendrier-rdv'); ?></h2>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="cal-rdv-company-name">
                    <?php esc_html_e('Nom de l\'entreprise', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <input type="text" 
                       id="cal-rdv-company-name" 
                       name="cal_rdv_settings[company_name]" 
                       value="<?php echo esc_attr($settings['company_name']); ?>" 
                       class="regular-text">
                <p class="description">
                    <?php esc_html_e('Le nom de votre entreprise ou organisation.', 'calendrier-rdv'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cal-rdv-company-address">
                    <?php esc_html_e('Adresse', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <textarea id="cal-rdv-company-address" 
                          name="cal_rdv_settings[company_address]" 
                          rows="3" 
                          class="large-text"><?php echo esc_textarea($settings['company_address']); ?></textarea>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cal-rdv-company-phone">
                    <?php esc_html_e('Téléphone', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <input type="text" 
                       id="cal-rdv-company-phone" 
                       name="cal_rdv_settings[company_phone]" 
                       value="<?php echo esc_attr($settings['company_phone']); ?>" 
                       class="regular-text">
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cal-rdv-company-email">
                    <?php esc_html_e('Email de contact', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <input type="email" 
                       id="cal-rdv-company-email" 
                       name="cal_rdv_settings[company_email]" 
                       value="<?php echo esc_attr($settings['company_email']); ?>" 
                       class="regular-text">
                <p class="description">
                    <?php esc_html_e('Cet email sera utilisé pour les notifications et les confirmations.', 'calendrier-rdv'); ?>
                </p>
            </td>
        </tr>
    </table>
</div>

<div class="cal-rdv-settings-section">
    <h2><?php esc_html_e('Paramètres des créneaux horaires', 'calendrier-rdv'); ?></h2>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="cal-rdv-time-slot">
                    <?php esc_html_e('Durée des créneaux', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <select id="cal-rdv-time-slot" name="cal_rdv_settings[time_slot]" class="regular-text">
                    <option value="15" <?php selected($settings['time_slot'], 15); ?>>15 <?php esc_html_e('minutes', 'calendrier-rdv'); ?></option>
                    <option value="30" <?php selected($settings['time_slot'], 30); ?>>30 <?php esc_html_e('minutes', 'calendrier-rdv'); ?></option>
                    <option value="60" <?php selected($settings['time_slot'], 60); ?>>1 <?php esc_html_e('heure', 'calendrier-rdv'); ?></option>
                    <option value="90" <?php selected($settings['time_slot'], 90); ?>>1.5 <?php esc_html_e('heures', 'calendrier-rdv'); ?></option>
                    <option value="120" <?php selected($settings['time_slot'], 120); ?>>2 <?php esc_html_e('heures', 'calendrier-rdv'); ?></option>
                </select>
                <p class="description">
                    <?php esc_html_e('Définissez la durée par défaut des créneaux de rendez-vous.', 'calendrier-rdv'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cal-rdv-min-booking-notice">
                    <?php esc_html_e('Délai minimum de réservation', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <input type="number" 
                       id="cal-rdv-min-booking-notice" 
                       name="cal_rdv_settings[min_booking_notice]" 
                       value="<?php echo esc_attr($settings['min_booking_notice']); ?>" 
                       min="0" 
                       step="15" 
                       class="small-text">
                <span class="description">
                    <?php esc_html_e('minutes avant le créneau', 'calendrier-rdv'); ?>
                </span>
                <p class="description">
                    <?php esc_html_e('Délai minimum avant le début du créneau pour permettre la réservation.', 'calendrier-rdv'); ?>
                </p>
            </td>
        </tr>
    </table>
</div>

<div class="cal-rdv-settings-section">
    <h2><?php esc_html_e('Format des dates et heures', 'calendrier-rdv'); ?></h2>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="cal-rdv-date-format">
                    <?php esc_html_e('Format de date', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <select id="cal-rdv-date-format" name="cal_rdv_settings[date_format]" class="regular-text">
                    <option value="d/m/Y" <?php selected($settings['date_format'], 'd/m/Y'); ?>>
                        <?php echo esc_html(date('d/m/Y') . ' (d/m/Y)'); ?>
                    </option>
                    <option value="m/d/Y" <?php selected($settings['date_format'], 'm/d/Y'); ?>>
                        <?php echo esc_html(date('m/d/Y') . ' (m/d/Y)'); ?>
                    </option>
                    <option value="Y-m-d" <?php selected($settings['date_format'], 'Y-m-d'); ?>>
                        <?php echo esc_html(date('Y-m-d') . ' (Y-m-d)'); ?>
                    </option>
                    <option value="d-m-Y" <?php selected($settings['date_format'], 'd-m-Y'); ?>>
                        <?php echo esc_html(date('d-m-Y') . ' (d-m-Y)'); ?>
                    </option>
                    <option value="d.m.Y" <?php selected($settings['date_format'], 'd.m.Y'); ?>>
                        <?php echo esc_html(date('d.m.Y') . ' (d.m.Y)'); ?>
                    </option>
                    <option value="d M Y" <?php selected($settings['date_format'], 'd M Y'); ?>>
                        <?php echo esc_html(date('d M Y') . ' (d M Y)'); ?>
                    </option>
                    <option value="d F Y" <?php selected($settings['date_format'], 'd F Y'); ?>>
                        <?php echo esc_html(date('d F Y') . ' (d F Y)'); ?>
                    </option>
                </select>
                <p class="description">
                    <?php esc_html_e('Choisissez le format d\'affichage des dates.', 'calendrier-rdv'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cal-rdv-time-format">
                    <?php esc_html_e('Format d\'heure', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <select id="cal-rdv-time-format" name="cal_rdv_settings[time_format]" class="regular-text">
                    <option value="H:i" <?php selected($settings['time_format'], 'H:i'); ?>>
                        <?php echo esc_html(date('H:i') . ' (24 heures)'); ?>
                    </option>
                    <option value="g:i a" <?php selected($settings['time_format'], 'g:i a'); ?>>
                        <?php echo esc_html(date('g:i a') . ' (12 heures)'); ?>
                    </option>
                    <option value="g:i A" <?php selected($settings['time_format'], 'g:i A'); ?>>
                        <?php echo esc_html(date('g:i A') . ' (12 heures avec AM/PM)'); ?>
                    </option>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="cal-rdv-start-of-week">
                    <?php esc_html_e('Premier jour de la semaine', 'calendrier-rdv'); ?>
                </label>
            </th>
            <td>
                <select id="cal-rdv-start-of-week" name="cal_rdv_settings[start_of_week]" class="regular-text">
                    <?php foreach ($week_days as $day_num => $day_name) : ?>
                        <option value="<?php echo esc_attr($day_num); ?>" <?php selected($settings['start_of_week'], $day_num); ?>>
                            <?php echo esc_html($day_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e('Définissez le premier jour de la semaine pour le calendrier.', 'calendrier-rdv'); ?>
                </p>
            </td>
        </tr>
    </table>
</div>
