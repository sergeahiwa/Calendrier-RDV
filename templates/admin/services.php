<?php
/**
 * Template pour la gestion des services
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

// Ici, vous récupérerez les services depuis votre modèle de données
$services = []; // Remplacer par l'appel à votre modèle

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
        <a href="#" class="page-title-action" id="cal-rdv-add-service">
            <span class="dashicons dashicons-plus"></span>
            <?php esc_html_e('Ajouter un service', 'calendrier-rdv'); ?>
        </a>
    </div>

    <!-- Filtres -->
    <div class="cal-rdv-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="calendrier-rdv-services">
            
            <div class="cal-rdv-filter-row">
                <div class="cal-rdv-filter-col">
                    <label for="cal-rdv-search" class="screen-reader-text">
                        <?php esc_html_e('Rechercher', 'calendrier-rdv'); ?>
                    </label>
                    <input type="search" 
                           id="cal-rdv-search" 
                           name="s" 
                           value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php esc_attr_e('Rechercher un service...', 'calendrier-rdv'); ?>">
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
                    <a href="?page=calendrier-rdv-services" class="button button-secondary">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Réinitialiser', 'calendrier-rdv'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Liste des services -->
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
                    <th><?php esc_html_e('Durée', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Prix', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Prestataires', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Statut', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Actions', 'calendrier-rdv'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($services)) : ?>
                    <?php foreach ($services as $service) : ?>
                        <tr>
                            <td><input type="checkbox" class="cal-rdv-select-row" value="<?php echo esc_attr($service->id); ?>"></td>
                            <td>
                                <strong>
                                    <a href="#" class="cal-rdv-edit-service" data-id="<?php echo esc_attr($service->id); ?>">
                                        <?php echo esc_html($service->name); ?>
                                    </a>
                                </strong>
                                <?php if (!empty($service->description)) : ?>
                                    <div class="row-actions">
                                        <span class="description"><?php echo esc_html(wp_trim_words($service->description, 10, '...')); ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $hours = floor($service->duration / 60);
                                $minutes = $service->duration % 60;
                                $duration = [];
                                
                                if ($hours > 0) {
                                    $duration[] = sprintf(_n('%d heure', '%d heures', $hours, 'calendrier-rdv'), $hours);
                                }
                                
                                if ($minutes > 0) {
                                    $duration[] = sprintf(_n('%d minute', '%d minutes', $minutes, 'calendrier-rdv'), $minutes);
                                }
                                
                                echo esc_html(implode(' ', $duration));
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($service->price > 0) {
                                    echo esc_html(number_format($service->price, 2, ',', ' ') . ' ' . get_woocommerce_currency_symbol());
                                } else {
                                    echo '<span class="description">' . esc_html__('Gratuit', 'calendrier-rdv') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($service->providers)) {
                                    echo esc_html(implode(', ', wp_list_pluck($service->providers, 'display_name')));
                                } else {
                                    echo '<span class="description">' . esc_html__('Aucun prestataire', 'calendrier-rdv') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="cal-rdv-status cal-rdv-status-<?php echo esc_attr($service->status); ?>">
                                    <?php echo 'active' === $service->status ? esc_html__('Actif', 'calendrier-rdv') : esc_html__('Inactif', 'calendrier-rdv'); ?>
                                </span>
                            </td>
                            <td class="cal-rdv-actions">
                                <a href="#" 
                                   class="cal-rdv-action cal-rdv-edit" 
                                   data-id="<?php echo esc_attr($service->id); ?>"
                                   title="<?php esc_attr_e('Modifier', 'calendrier-rdv'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="#" 
                                   class="cal-rdv-action cal-rdv-delete" 
                                   data-id="<?php echo esc_attr($service->id); ?>"
                                   title="<?php esc_attr_e('Supprimer', 'calendrier-rdv'); ?>"
                                   data-confirm="<?php esc_attr_e('Êtes-vous sûr de vouloir supprimer ce service ? Cette action est irréversible.', 'calendrier-rdv'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                                <?php if ('active' === $service->status) : ?>
                                    <a href="#" 
                                       class="cal-rdv-action cal-rdv-deactivate" 
                                       data-id="<?php echo esc_attr($service->id); ?>"
                                       title="<?php esc_attr_e('Désactiver', 'calendrier-rdv'); ?>">
                                        <span class="dashicons dashicons-hidden"></span>
                                    </a>
                                <?php else : ?>
                                    <a href="#" 
                                       class="cal-rdv-action cal-rdv-activate" 
                                       data-id="<?php echo esc_attr($service->id); ?>"
                                       title="<?php esc_attr_e('Activer', 'calendrier-rdv'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                <?php endif; ?>
                                <a href="#" 
                                   class="cal-rdv-action cal-rdv-duplicate" 
                                   data-id="<?php echo esc_attr($service->id); ?>"
                                   title="<?php esc_attr_e('Dupliquer', 'calendrier-rdv'); ?>">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <?php esc_html_e('Aucun service trouvé.', 'calendrier-rdv'); ?>
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

<!-- Modale d'ajout/édition de service -->
<div id="cal-rdv-service-form-modal" class="cal-rdv-modal" style="display: none;">
    <div class="cal-rdv-modal-content" style="max-width: 700px;">
        <div class="cal-rdv-modal-header">
            <h3 id="cal-rdv-modal-title"><?php esc_html_e('Ajouter un service', 'calendrier-rdv'); ?></h3>
            <button type="button" class="cal-rdv-modal-close">&times;</button>
        </div>
        <form id="cal-rdv-service-form" class="cal-rdv-ajax-form">
            <div class="cal-rdv-modal-body">
                <input type="hidden" name="action" value="cal_rdv_save_service">
                <input type="hidden" name="service_id" value="0">
                <?php wp_nonce_field('cal_rdv_save_service_nonce', 'cal_rdv_nonce'); ?>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-name">
                        <?php esc_html_e('Nom du service', 'calendrier-rdv'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="cal-rdv-name" 
                           name="name" 
                           required 
                           class="regular-text">
                </div>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-description">
                        <?php esc_html_e('Description', 'calendrier-rdv'); ?>
                    </label>
                    <textarea id="cal-rdv-description" 
                              name="description" 
                              rows="3" 
                              class="large-text"></textarea>
                </div>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-duration">
                            <?php esc_html_e('Durée', 'calendrier-rdv'); ?>
                            <span class="required">*</span>
                        </label>
                        <div class="cal-rdv-duration-selector">
                            <select id="cal-rdv-duration-hours" name="duration_hours" class="cal-rdv-select-small">
                                <?php for ($i = 0; $i <= 8; $i++) : ?>
                                    <option value="<?php echo esc_attr($i); ?>">
                                        <?php echo esc_html(sprintf(_n('%d heure', '%d heures', $i, 'calendrier-rdv'), $i)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select id="cal-rdv-duration-minutes" name="duration_minutes" class="cal-rdv-select-small">
                                <option value="0"><?php esc_html_e('0 minutes', 'calendrier-rdv'); ?></option>
                                <option value="15"><?php esc_html_e('15 minutes', 'calendrier-rdv'); ?></option>
                                <option value="30"><?php esc_html_e('30 minutes', 'calendrier-rdv'); ?></option>
                                <option value="45"><?php esc_html_e('45 minutes', 'calendrier-rdv'); ?></option>
                            </select>
                            <input type="hidden" id="cal-rdv-duration" name="duration" value="30">
                        </div>
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-buffer-before">
                            <?php esc_html_e('Tampon avant (minutes)', 'calendrier-rdv'); ?>
                        </label>
                        <input type="number" 
                               id="cal-rdv-buffer-before" 
                               name="buffer_before" 
                               min="0" 
                               step="5" 
                               value="0" 
                               class="small-text">
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-buffer-after">
                            <?php esc_html_e('Tampon après (minutes)', 'calendrier-rdv'); ?>
                        </label>
                        <input type="number" 
                               id="cal-rdv-buffer-after" 
                               name="buffer_after" 
                               min="0" 
                               step="5" 
                               value="0" 
                               class="small-text">
                    </div>
                </div>
                
                <div class="cal-rdv-form-row">
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-price">
                            <?php esc_html_e('Prix', 'calendrier-rdv'); ?>
                        </label>
                        <input type="number" 
                               id="cal-rdv-price" 
                               name="price" 
                               min="0" 
                               step="0.01" 
                               value="0" 
                               class="small-text">
                        <span class="description"><?php echo esc_html(get_woocommerce_currency_symbol()); ?></span>
                    </div>
                    
                    <div class="cal-rdv-form-col">
                        <label for="cal-rdv-capacity">
                            <?php esc_html_e('Capacité', 'calendrier-rdv'); ?>
                        </label>
                        <input type="number" 
                               id="cal-rdv-capacity" 
                               name="capacity" 
                               min="1" 
                               value="1" 
                               class="small-text">
                        <span class="description"><?php esc_html_e('Nombre maximum de clients', 'calendrier-rdv'); ?></span>
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
                    <label for="cal-rdv-providers">
                        <?php esc_html_e('Prestataires', 'calendrier-rdv'); ?>
                    </label>
                    <select id="cal-rdv-providers" 
                            name="providers[]" 
                            class="cal-rdv-select2" 
                            multiple="multiple" 
                            style="width: 100%;">
                        <!-- Les prestataires seront chargés dynamiquement -->
                    </select>
                    <p class="description">
                        <?php esc_html_e('Sélectionnez les prestataires qui peuvent effectuer ce service.', 'calendrier-rdv'); ?>
                    </p>
                </div>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-categories">
                        <?php esc_html_e('Catégories', 'calendrier-rdv'); ?>
                    </label>
                    <select id="cal-rdv-categories" 
                            name="categories[]" 
                            class="cal-rdv-select2" 
                            multiple="multiple" 
                            style="width: 100%;">
                        <!-- Les catégories seront chargées dynamiquement -->
                    </select>
                    <p class="description">
                        <?php esc_html_e('Catégorisez ce service pour faciliter la recherche et l\'affichage.', 'calendrier-rdv'); ?>
                    </p>
                </div>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-color">
                        <?php esc_html_e('Couleur', 'calendrier-rdv'); ?>
                    </label>
                    <input type="text" 
                           id="cal-rdv-color" 
                           name="color" 
                           class="cal-rdv-color-picker" 
                           value="#3498db">
                    <p class="description">
                        <?php esc_html_e('Cette couleur sera utilisée pour afficher ce service dans le calendrier.', 'calendrier-rdv'); ?>
                    </p>
                </div>
                
                <div class="cal-rdv-form-row">
                    <label for="cal-rdv-image">
                        <?php esc_html_e('Image', 'calendrier-rdv'); ?>
                    </label>
                    <div class="cal-rdv-media-upload">
                        <input type="hidden" id="cal-rdv-image-id" name="image_id">
                        <div id="cal-rdv-image-preview" class="cal-rdv-media-preview">
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
