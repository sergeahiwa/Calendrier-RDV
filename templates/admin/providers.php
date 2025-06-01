<?php
/**
 * Template pour la gestion des prestataires
 *
 * @package     CalendrierRdv\Templates\Admin
 * @since       1.0.0
 */

// Vérification de sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les paramètres de filtrage
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Ici, vous récupérerez les prestataires depuis votre modèle de données
$providers = []; // Remplacer par l'appel à votre modèle

// Options de statut
$statuses = [
    ''      => __('Tous les statuts', 'calendrier-rdv'),
    'active' => __('Actif', 'calendrier-rdv'),
    'inactive' => __('Inactif', 'calendrier-rdv'),
];
?>

<div class="wrap cal-rdv-wrap">
    <div class="cal-rdv-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <a href="#" class="page-title-action" id="cal-rdv-add-provider">
            <span class="dashicons dashicons-plus"></span>
            <?php esc_html_e('Ajouter un prestataire', 'calendrier-rdv'); ?>
        </a>
    </div>

    <!-- Filtres -->
    <div class="cal-rdv-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="calendrier-rdv-providers">
            
            <div class="cal-rdv-filter-row">
                <div class="cal-rdv-filter-col">
                    <label for="cal-rdv-search" class="screen-reader-text">
                        <?php esc_html_e('Rechercher', 'calendrier-rdv'); ?>
                    </label>
                    <input type="search" 
                           id="cal-rdv-search" 
                           name="s" 
                           value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php esc_attr_e('Rechercher un prestataire...', 'calendrier-rdv'); ?>">
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
                
                <div class="cal-rdv-filter-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-filter"></span>
                        <?php esc_html_e('Filtrer', 'calendrier-rdv'); ?>
                    </button>
                    <a href="?page=calendrier-rdv-providers" class="button button-secondary">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Réinitialiser', 'calendrier-rdv'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des prestataires -->
    <div class="cal-rdv-card">
        <div class="cal-rdv-table-actions">
            <div class="cal-rdv-bulk-actions">
                <select id="cal-rdv-bulk-action" class="cal-rdv-select">
                    <option value="-1"><?php esc_html_e('Actions groupées', 'calendrier-rdv'); ?></option>
                    <option value="activate"><?php esc_html_e('Activer', 'calendrier-rdv'); ?></option>
                    <option value="deactivate"><?php esc_html_e('Désactiver', 'calendrier-rdv'); ?></option>
                    <option value="delete"><?php esc_html_e('Supprimer', 'calendrier-rdv'); ?></option>
                </select>
                <button type="button" id="cal-rdv-do-bulk-action" class="button">
                    <?php esc_html_e('Appliquer', 'calendrier-rdv'); ?>
                </button>
            </div>
        </div>
        
        <table class="cal-rdv-table widefat striped">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="cal-rdv-select-all">
                    </th>
                    <th><?php esc_html_e('Nom', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Email', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Téléphone', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Services', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Statut', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Actions', 'calendrier-rdv'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($providers)) : ?>
                    <?php foreach ($providers as $provider) : ?>
                        <tr>
                            <td><input type="checkbox" class="cal-rdv-select-row" value="<?php echo esc_attr($provider->id); ?>"></td>
                            <td>
                                <strong>
                                    <a href="#" class="cal-rdv-edit-provider" data-id="<?php echo esc_attr($provider->id); ?>">
                                        <?php echo esc_html($provider->display_name); ?>
                                    </a>
                                </strong>
                                <?php if (!empty($provider->job_title)) : ?>
                                    <div class="row-actions">
                                        <span class="description"><?php echo esc_html($provider->job_title); ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($provider->email)) : ?>
                                    <a href="mailto:<?php echo esc_attr($provider->email); ?>">
                                        <?php echo esc_html($provider->email); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="description"><?php esc_html_e('Non défini', 'calendrier-rdv'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo !empty($provider->phone) ? esc_html($provider->phone) : '<span class="description">' . esc_html__('Non défini', 'calendrier-rdv') . '</span>'; ?>
                            </td>
                            <td>
                                <?php if (!empty($provider->services)) : ?>
                                    <?php echo esc_html(implode(', ', wp_list_pluck($provider->services, 'name'))); ?>
                                <?php else : ?>
                                    <span class="description"><?php esc_html_e('Aucun service', 'calendrier-rdv'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="cal-rdv-status cal-rdv-status-<?php echo esc_attr($provider->status); ?>">
                                    <?php echo 'active' === $provider->status ? esc_html__('Actif', 'calendrier-rdv') : esc_html__('Inactif', 'calendrier-rdv'); ?>
                                </span>
                            </td>
                            <td class="cal-rdv-actions">
                                <a href="#" 
                                   class="cal-rdv-action cal-rdv-edit" 
                                   data-id="<?php echo esc_attr($provider->id); ?>"
                                   title="<?php esc_attr_e('Modifier', 'calendrier-rdv'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="#" 
                                   class="cal-rdv-action cal-rdv-delete" 
                                   data-id="<?php echo esc_attr($provider->id); ?>"
                                   title="<?php esc_attr_e('Supprimer', 'calendrier-rdv'); ?>"
                                   data-confirm="<?php esc_attr_e('Êtes-vous sûr de vouloir supprimer ce prestataire ? Cette action est irréversible.', 'calendrier-rdv'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                                <?php if ('active' === $provider->status) : ?>
                                    <a href="#" 
                                       class="cal-rdv-action cal-rdv-deactivate" 
                                       data-id="<?php echo esc_attr($provider->id); ?>"
                                       title="<?php esc_attr_e('Désactiver', 'calendrier-rdv'); ?>">
                                        <span class="dashicons dashicons-hidden"></span>
                                    </a>
                                <?php else : ?>
                                    <a href="#" 
                                       class="cal-rdv-action cal-rdv-activate" 
                                       data-id="<?php echo esc_attr($provider->id); ?>"
                                       title="<?php esc_attr_e('Activer', 'calendrier-rdv'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                <?php endif; ?>
                                <a href="?page=calendrier-rdv-schedule&provider_id=<?php echo esc_attr($provider->id); ?>" 
                                   class="cal-rdv-action" 
                                   title="<?php esc_attr_e('Gérer l\'emploi du temps', 'calendrier-rdv'); ?>">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <?php esc_html_e('Aucun prestataire trouvé.', 'calendrier-rdv'); ?>
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

<!-- Modale d'ajout/édition de prestataire -->
<div id="cal-rdv-provider-form-modal" class="cal-rdv-modal" style="display: none;">
    <div class="cal-rdv-modal-content" style="max-width: 700px;">
        <div class="cal-rdv-modal-header">
            <h3 id="cal-rdv-modal-title"><?php esc_html_e('Ajouter un prestataire', 'calendrier-rdv'); ?></h3>
            <button type="button" class="cal-rdv-modal-close">&times;</button>
        </div>
        <form id="cal-rdv-provider-form" class="cal-rdv-ajax-form">
            <div class="cal-rdv-modal-body">
                <input type="hidden" name="action" value="cal_rdv_save_provider">
                <input type="hidden" name="provider_id" value="0">
                <?php wp_nonce_field('cal_rdv_save_provider_nonce', 'cal_rdv_nonce'); ?>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-first-name">
                            <?php esc_html_e('Prénom', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="cal-rdv-first-name" 
                               name="first_name" 
                               required 
                               class="regular-text">
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-last-name">
                            <?php esc_html_e('Nom', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="cal-rdv-last-name" 
                               name="last_name" 
                               required 
                               class="regular-text">
                    </div>
                </div>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-email">
                            <?php esc_html_e('Email', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="email" 
                               id="cal-rdv-email" 
                               name="email" 
                               required 
                               class="regular-text">
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-phone">
                            <?php esc_html_e('Téléphone', 'calendrier-rdv'); ?>
                        </label>
                        <input type="tel" 
                               id="cal-rdv-phone" 
                               name="phone" 
                               class="regular-text">
                    </div>
                </div>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-job-title">
                            <?php esc_html_e('Poste', 'calendrier-rdv'); ?>
                        </label>
                        <input type="text" 
                               id="cal-rdv-job-title" 
                               name="job_title" 
                               class="regular-text">
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-status">
                            <?php esc_html_e('Statut', 'calendrier-rdv'); ?>
                        </label>
                        <select id="cal-rdv-status" name="status">
                            <option value="active"><?php esc_html_e('Actif', 'calendrier-rdv'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactif', 'calendrier-rdv'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-services">
                        <?php esc_html_e('Services proposés', 'calendrier-rdv'); ?>
                    </label>
                    <select id="cal-rdv-services" 
                            name="services[]" 
                            class="cal-rdv-select2" 
                            multiple="multiple" 
                            style="width: 100%;">
                        <!-- Les services seront chargés dynamiquement -->
                    </select>
                    <p class="description">
                        <?php esc_html_e('Sélectionnez les services que ce prestataire peut effectuer.', 'calendrier-rdv'); ?>
                    </p>
                </div>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-bio">
                        <?php esc_html_e('Biographie', 'calendrier-rdv'); ?>
                    </label>
                    <textarea id="cal-rdv-bio" 
                              name="bio" 
                              rows="5" 
                              class="large-text"></textarea>
                </div>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-photo">
                        <?php esc_html_e('Photo', 'calendrier-rdv'); ?>
                    </label>
                    <div class="cal-rdv-media-upload">
                        <input type="hidden" id="cal-rdv-photo-id" name="photo_id">
                        <div id="cal-rdv-photo-preview" class="cal-rdv-media-preview">
                            <span class="dashicons dashicons-format-image"></span>
                            <p><?php esc_html_e('Aucun fichier sélectionné', 'calendrier-rdv'); ?></p>
                        </div>
                        <button type="button" class="button cal-rdv-upload-button">
                            <?php esc_html_e('Sélectionner une image', 'calendrier-rdv'); ?>
                        </button>
                        <button type="button" class="button button-link-delete cal-rdv-remove-button" style="display: none;">
                            <?php esc_html_e('Supprimer', 'calendrier-rdv'); ?>
                        </button>
                    </div>
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
