<?php
/**
 * Page d'exportation des données
 *
 * @package Calendrier_RDV
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Affiche la page d'exportation des données
 */
function calendrier_rdv_export_page() {
    // Vérifier les permissions
    if (!current_user_can('edit_appointments')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.', 'calendrier-rdv'));
    }
    
    // Récupérer les formats d'exportation disponibles
    $export_manager = calendrier_rdv_export_manager();
    $formats = $export_manager->get_formats();
    
    // Récupérer les prestataires pour le filtre
    $providers = [];
    
    global $wpdb;
    $providers_table = $wpdb->prefix . 'cal_rdv_providers';
    $results = $wpdb->get_results("SELECT ID, name FROM $providers_table WHERE active = 1 ORDER BY name ASC");
    
    foreach ($results as $provider) {
        $providers[$provider->ID] = $provider->name;
    }
    
    // Récupérer les services pour le filtre
    $services = [];
    
    $services_table = $wpdb->prefix . 'cal_rdv_services';
    $results = $wpdb->get_results("SELECT ID, name FROM $services_table WHERE status = 'publish' ORDER BY name ASC");
    
    foreach ($results as $service) {
        $services[$service->ID] = $service->name;
    }
    
    // Date par défaut pour les filtres
    $today = date('Y-m-d');
    $month_ago = date('Y-m-d', strtotime('-1 month'));
    
    // Statuts des rendez-vous pour le filtre
    $appointment_statuses = [
        'all' => __('Tous', 'calendrier-rdv'),
        'pending' => __('En attente', 'calendrier-rdv'),
        'confirmed' => __('Confirmés', 'calendrier-rdv'),
        'completed' => __('Terminés', 'calendrier-rdv'),
        'cancelled' => __('Annulés', 'calendrier-rdv'),
        'no-show' => __('Absents', 'calendrier-rdv'),
    ];
    
    // Types de données exportables
    $data_types = [
        'appointments' => __('Rendez-vous', 'calendrier-rdv'),
        'services' => __('Services', 'calendrier-rdv'),
        'providers' => __('Prestataires', 'calendrier-rdv'),
        'customers' => __('Clients', 'calendrier-rdv'),
    ];
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui', CAL_RDV_PLUGIN_URL . 'assets/css/jquery-ui.min.css');
    
    // Créer le nonce pour les requêtes AJAX
    $export_nonce = wp_create_nonce('calendrier_rdv_export_nonce');
    ?>
    
    <div class="wrap">
        <h1><?php echo esc_html__('Exportation des données', 'calendrier-rdv'); ?></h1>
        
        <div class="notice notice-info">
            <p><?php echo esc_html__('Utilisez ce formulaire pour exporter vos données dans différents formats. Vous pouvez filtrer les données à exporter selon vos besoins.', 'calendrier-rdv'); ?></p>
        </div>
        
        <div class="card" style="max-width: 1000px; margin-top: 20px;">
            <form id="export-form" method="post">
                <input type="hidden" name="action" value="calendrier_rdv_export_data">
                <input type="hidden" name="nonce" value="<?php echo esc_attr($export_nonce); ?>">
                
                <h3><?php echo esc_html__('Type de données à exporter', 'calendrier-rdv'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="data_type"><?php echo esc_html__('Type de données', 'calendrier-rdv'); ?></label></th>
                        <td>
                            <select name="data_type" id="data_type" class="regular-text">
                                <?php foreach ($data_types as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="format"><?php echo esc_html__('Format d\'exportation', 'calendrier-rdv'); ?></label></th>
                        <td>
                            <select name="format" id="format" class="regular-text">
                                <?php foreach ($formats as $format_id => $format) : ?>
                                    <option value="<?php echo esc_attr($format_id); ?>"><?php echo esc_html($format['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description" id="format-description"></p>
                        </td>
                    </tr>
                </table>
                
                <div id="appointments-filters" class="data-type-filters">
                    <h3><?php echo esc_html__('Filtres pour les rendez-vous', 'calendrier-rdv'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="date_from"><?php echo esc_html__('Date de début', 'calendrier-rdv'); ?></label></th>
                            <td>
                                <input type="text" name="filters[date_from]" id="date_from" class="regular-text datepicker" value="<?php echo esc_attr($month_ago); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="date_to"><?php echo esc_html__('Date de fin', 'calendrier-rdv'); ?></label></th>
                            <td>
                                <input type="text" name="filters[date_to]" id="date_to" class="regular-text datepicker" value="<?php echo esc_attr($today); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="status"><?php echo esc_html__('Statut', 'calendrier-rdv'); ?></label></th>
                            <td>
                                <select name="filters[status]" id="status" class="regular-text">
                                    <?php foreach ($appointment_statuses as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="provider_id"><?php echo esc_html__('Prestataire', 'calendrier-rdv'); ?></label></th>
                            <td>
                                <select name="filters[provider_id]" id="provider_id" class="regular-text">
                                    <option value=""><?php echo esc_html__('Tous les prestataires', 'calendrier-rdv'); ?></option>
                                    <?php foreach ($providers as $id => $name) : ?>
                                        <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="service_id"><?php echo esc_html__('Service', 'calendrier-rdv'); ?></label></th>
                            <td>
                                <select name="filters[service_id]" id="service_id" class="regular-text">
                                    <option value=""><?php echo esc_html__('Tous les services', 'calendrier-rdv'); ?></option>
                                    <?php foreach ($services as $id => $name) : ?>
                                        <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="services-filters" class="data-type-filters" style="display: none;">
                    <h3><?php echo esc_html__('Filtres pour les services', 'calendrier-rdv'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="service_status"><?php echo esc_html__('Statut', 'calendrier-rdv'); ?></label></th>
                            <td>
                                <select name="filters[status]" id="service_status" class="regular-text">
                                    <option value=""><?php echo esc_html__('Tous les statuts', 'calendrier-rdv'); ?></option>
                                    <option value="publish"><?php echo esc_html__('Publié', 'calendrier-rdv'); ?></option>
                                    <option value="draft"><?php echo esc_html__('Brouillon', 'calendrier-rdv'); ?></option>
                                    <option value="trash"><?php echo esc_html__('Corbeille', 'calendrier-rdv'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="providers-filters" class="data-type-filters" style="display: none;">
                    <h3><?php echo esc_html__('Filtres pour les prestataires', 'calendrier-rdv'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="provider_active"><?php echo esc_html__('Statut', 'calendrier-rdv'); ?></label></th>
                            <td>
                                <select name="filters[active]" id="provider_active" class="regular-text">
                                    <option value=""><?php echo esc_html__('Tous', 'calendrier-rdv'); ?></option>
                                    <option value="1"><?php echo esc_html__('Actifs uniquement', 'calendrier-rdv'); ?></option>
                                    <option value="0"><?php echo esc_html__('Inactifs uniquement', 'calendrier-rdv'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="customers-filters" class="data-type-filters" style="display: none;">
                    <h3><?php echo esc_html__('Filtres pour les clients', 'calendrier-rdv'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="with_appointments"><?php echo esc_html__('Avec rendez-vous', 'calendrier-rdv'); ?></label></th>
                            <td>
                                <select name="filters[with_appointments]" id="with_appointments" class="regular-text">
                                    <option value=""><?php echo esc_html__('Tous', 'calendrier-rdv'); ?></option>
                                    <option value="1"><?php echo esc_html__('Avec rendez-vous uniquement', 'calendrier-rdv'); ?></option>
                                    <option value="0"><?php echo esc_html__('Sans rendez-vous uniquement', 'calendrier-rdv'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" id="export-button" class="button button-primary">
                        <?php echo esc_html__('Exporter les données', 'calendrier-rdv'); ?>
                    </button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                </p>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Initialiser les datepickers
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
        
        // Gérer l'affichage des filtres en fonction du type de données
        $('#data_type').on('change', function() {
            var dataType = $(this).val();
            $('.data-type-filters').hide();
            $('#' + dataType + '-filters').show();
        }).trigger('change');
        
        // Afficher la description du format sélectionné
        $('#format').on('change', function() {
            var format = $(this).val();
            var descriptions = {
                'csv': '<?php echo esc_js(__('Format CSV (valeurs séparées par des virgules)', 'calendrier-rdv')); ?>',
                'excel': '<?php echo esc_js(__('Format Excel (XLSX) avec mise en forme', 'calendrier-rdv')); ?>'
            };
            
            $('#format-description').text(descriptions[format] || '');
        }).trigger('change');
        
        // Gérer le formulaire d'exportation
        $('#export-form').on('submit', function(e) {
            e.preventDefault();
            
            // Afficher le spinner
            $('#export-button').prop('disabled', true);
            $('#export-form .spinner').addClass('is-active');
            
            // Récupérer les données du formulaire
            var formData = $(this).serialize();
            
            // Envoyer la requête AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Rediriger vers l'URL d'exportation
                        window.location.href = response.data.export_url;
                    } else {
                        alert(response.data.message || '<?php echo esc_js(__('Une erreur est survenue lors de l\'exportation.', 'calendrier-rdv')); ?>');
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('Une erreur est survenue lors de la communication avec le serveur.', 'calendrier-rdv')); ?>');
                },
                complete: function() {
                    // Masquer le spinner
                    $('#export-button').prop('disabled', false);
                    $('#export-form .spinner').removeClass('is-active');
                }
            });
        });
    });
    </script>
    <?php
}
