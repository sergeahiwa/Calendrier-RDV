<?php
/**
 * Template pour la gestion des rendez-vous
 *
 * @package     CalendrierRdv\Templates\Admin
 * @since       1.0.0
 */

// Vérification de sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les paramètres de filtrage
$current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$provider_id = isset($_GET['provider_id']) ? absint($_GET['provider_id']) : 0;

// Ici, vous récupérerez les rendez-vous depuis votre modèle de données
$appointments = []; // Remplacer par l'appel à votre modèle

// Options de statut
$statuses = [
    ''           => __('Tous les statuts', 'calendrier-rdv'),
    'scheduled'  => __('Planifié', 'calendrier-rdv'),
    'confirmed'   => __('Confirmé', 'calendrier-rdv'),
    'completed'   => __('Terminé', 'calendrier-rdv'),
    'cancelled'   => __('Annulé', 'calendrier-rdv'),
    'no-show'     => __('Non honoré', 'calendrier-rdv'),
];
?>

<div class="wrap cal-rdv-wrap">
    <div class="cal-rdv-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <a href="#" class="page-title-action" id="cal-rdv-add-appointment">
            <span class="dashicons dashicons-plus"></span>
            <?php esc_html_e('Ajouter un rendez-vous', 'calendrier-rdv'); ?>
        </a>
    </div>

    <!-- Filtres -->
    <div class="cal-rdv-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="calendrier-rdv-appointments">
            
            <div class="cal-rdv-filter-row">
                <div class="cal-rdv-filter-col">
                    <label for="cal-rdv-date-filter"><?php esc_html_e('Date', 'calendrier-rdv'); ?></label>
                    <input type="date" 
                           id="cal-rdv-date-filter" 
                           name="date" 
                           class="cal-rdv-datepicker" 
                           value="<?php echo esc_attr($current_date); ?>">
                </div>
                
                <div class="cal-rdv-filter-col">
                    <label for="cal-rdv-status-filter"><?php esc_html_e('Statut', 'calendrier-rdv'); ?></label>
                    <select id="cal-rdv-status-filter" name="status" class="cal-rdv-select">
                        <?php foreach ($statuses as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="cal-rdv-filter-col">
                    <label for="cal-rdv-provider-filter"><?php esc_html_e('Prestataire', 'calendrier-rdv'); ?></label>
                    <select id="cal-rdv-provider-filter" name="provider_id" class="cal-rdv-select">
                        <option value=""><?php esc_html_e('Tous les prestataires', 'calendrier-rdv'); ?></option>
                        <!-- Les options seront chargées dynamiquement -->
                    </select>
                </div>
                
                <div class="cal-rdv-filter-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-filter"></span>
                        <?php esc_html_e('Filtrer', 'calendrier-rdv'); ?>
                    </button>
                    <a href="?page=calendrier-rdv-appointments" class="button button-secondary">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Réinitialiser', 'calendrier-rdv'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des rendez-vous -->
    <div class="cal-rdv-card">
        <div class="cal-rdv-table-actions">
            <div class="cal-rdv-bulk-actions">
                <select id="cal-rdv-bulk-action" class="cal-rdv-select">
                    <option value="-1"><?php esc_html_e('Actions groupées', 'calendrier-rdv'); ?></option>
                    <option value="confirm"><?php esc_html_e('Confirmer', 'calendrier-rdv'); ?></option>
                    <option value="cancel"><?php esc_html_e('Annuler', 'calendrier-rdv'); ?></option>
                    <option value="delete"><?php esc_html_e('Supprimer', 'calendrier-rdv'); ?></option>
                </select>
                <button type="button" id="cal-rdv-do-bulk-action" class="button">
                    <?php esc_html_e('Appliquer', 'calendrier-rdv'); ?>
                </button>
            </div>
            
            <div class="cal-rdv-export-actions">
                <button type="button" class="button" id="cal-rdv-export-csv">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Exporter en CSV', 'calendrier-rdv'); ?>
                </button>
                <button type="button" class="button" id="cal-rdv-print">
                    <span class="dashicons dashicons-printer"></span>
                    <?php esc_html_e('Imprimer', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
        
        <table class="cal-rdv-table widefat striped">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="cal-rdv-select-all">
                    </th>
                    <th><?php esc_html_e('ID', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Date et heure', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Client', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Prestataire', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Service', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Statut', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Actions', 'calendrier-rdv'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($appointments)) : ?>
                    <?php foreach ($appointments as $appointment) : ?>
                        <tr>
                            <td><input type="checkbox" class="cal-rdv-select-row" value="<?php echo esc_attr($appointment->id); ?>"></td>
                            <td>#<?php echo esc_html($appointment->id); ?></td>
                            <td>
                                <?php 
                                echo esc_html(
                                    date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'),
                                        strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time)
                                    )
                                );
                                ?>
                            </td>
                            <td>
                                <?php 
                                echo esc_html($appointment->customer_name); 
                                if (!empty($appointment->customer_phone)) {
                                    echo '<br><small>' . esc_html($appointment->customer_phone) . '</small>';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($appointment->provider_name); ?></td>
                            <td><?php echo esc_html($appointment->service_name); ?></td>
                            <td>
                                <span class="cal-rdv-status cal-rdv-status-<?php echo esc_attr($appointment->status); ?>">
                                    <?php echo esc_html(ucfirst($appointment->status)); ?>
                                </span>
                            </td>
                            <td class="cal-rdv-actions">
                                <a href="#" 
                                   class="cal-rdv-action cal-rdv-edit" 
                                   data-id="<?php echo esc_attr($appointment->id); ?>"
                                   title="<?php esc_attr_e('Modifier', 'calendrier-rdv'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="#" 
                                   class="cal-rdv-action cal-rdv-delete" 
                                   data-id="<?php echo esc_attr($appointment->id); ?>"
                                   title="<?php esc_attr_e('Supprimer', 'calendrier-rdv'); ?>"
                                   data-confirm="<?php esc_attr_e('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?', 'calendrier-rdv'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <?php esc_html_e('Aucun rendez-vous trouvé.', 'calendrier-rdv'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="cal-rdv-pagination">
            <!-- La pagination sera ajoutée ici dynamiquement -->
        </div>
    </div>
</div>

<!-- Modale d'ajout/édition de rendez-vous -->
<div id="cal-rdv-appointment-form-modal" class="cal-rdv-modal" style="display: none;">
    <div class="cal-rdv-modal-content" style="max-width: 800px;">
        <div class="cal-rdv-modal-header">
            <h3 id="cal-rdv-modal-title"><?php esc_html_e('Ajouter un rendez-vous', 'calendrier-rdv'); ?></h3>
            <button type="button" class="cal-rdv-modal-close">&times;</button>
        </div>
        <form id="cal-rdv-appointment-form" class="cal-rdv-ajax-form">
            <div class="cal-rdv-modal-body">
                <input type="hidden" name="action" value="cal_rdv_save_appointment">
                <input type="hidden" name="appointment_id" value="0">
                <?php wp_nonce_field('cal_rdv_save_appointment_nonce', 'cal_rdv_nonce'); ?>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-customer-name">
                            <?php esc_html_e('Nom du client', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="cal-rdv-customer-name" 
                               name="customer_name" 
                               required 
                               class="regular-text">
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-customer-email">
                            <?php esc_html_e('Email', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="email" 
                               id="cal-rdv-customer-email" 
                               name="customer_email" 
                               required 
                               class="regular-text">
                    </div>
                </div>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-customer-phone">
                            <?php esc_html_e('Téléphone', 'calendrier-rdv'); ?>
                        </label>
                        <input type="tel" 
                               id="cal-rdv-customer-phone" 
                               name="customer_phone" 
                               class="regular-text">
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-status">
                            <?php esc_html_e('Statut', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <select id="cal-rdv-status" name="status" required>
                            <?php foreach ($statuses as $value => $label) : ?>
                                <?php if ($value !== '') : ?>
                                    <option value="<?php echo esc_attr($value); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-provider">
                            <?php esc_html_e('Prestataire', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <select id="cal-rdv-provider" name="provider_id" required>
                            <option value=""><?php esc_html_e('Sélectionner un prestataire', 'calendrier-rdv'); ?></option>
                            <!-- Les options seront chargées dynamiquement -->
                        </select>
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-service">
                            <?php esc_html_e('Service', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <select id="cal-rdv-service" name="service_id" required>
                            <option value=""><?php esc_html_e('Sélectionner un service', 'calendrier-rdv'); ?></option>
                            <!-- Les options seront chargées dynamiquement -->
                        </select>
                    </div>
                </div>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-date">
                            <?php esc_html_e('Date', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="date" 
                               id="cal-rdv-date" 
                               name="appointment_date" 
                               required 
                               class="cal-rdv-datepicker">
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-time">
                            <?php esc_html_e('Heure', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <select id="cal-rdv-time" name="appointment_time" required>
                            <option value=""><?php esc_html_e('Sélectionner une heure', 'calendrier-rdv'); ?></option>
                            <!-- Les créneaux horaires seront chargés dynamiquement -->
                        </select>
                    </div>
                </div>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-notes">
                        <?php esc_html_e('Notes', 'calendrier-rdv'); ?>
                    </label>
                    <textarea id="cal-rdv-notes" 
                              name="notes" 
                              rows="3" 
                              class="large-text"></textarea>
                </div>
            </div>
            <div class="cal-rdv-modal-footer">
                <button type="button" class="button button-secondary cal-rdv-modal-close">
                    <?php esc_html_e('Annuler', 'calendrier-rdv'); ?>
                </button>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Enregistrer', 'calendrier-rdv'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
