<?php
/**
 * Template pour la page des paramètres
 *
 * @package     CalendrierRdv\Templates\Admin
 * @since       1.0.0
 */

// Vérification de sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les onglets
$tabs = [
    'general'    => __('Général', 'calendrier-rdv'),
    'appearance' => __('Apparence', 'calendrier-rdv'),
    'notifications' => __('Notifications', 'calendrier-rdv'),
    'payments'   => __('Paiements', 'calendrier-rdv'),
    'integrations' => __('Intégrations', 'calendrier-rdv'),
    'advanced'    => __('Avancé', 'calendrier-rdv'),
];

// Onglet actif
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Vérifier si l'onglet existe, sinon utiliser le premier onglet
if (!array_key_exists($current_tab, $tabs)) {
    $current_tab = key($tabs);
}

// Enregistrer les paramètres si le formulaire est soumis
if (isset($_POST['cal_rdv_settings_nonce']) && wp_verify_nonce($_POST['cal_rdv_settings_nonce'], 'cal_rdv_save_settings')) {
    // Traitement des paramètres
    // Ici, vous ajouterez la logique pour enregistrer les paramètres
    
    // Exemple :
    // $options = [];
    // $options['time_slot_step'] = isset($_POST['time_slot_step']) ? absint($_POST['time_slot_step']) : 30;
    // update_option('cal_rdv_settings', $options);
    
    // Afficher un message de succès
    echo '<div class="notice notice-success"><p>' . esc_html__('Paramètres enregistrés avec succès.', 'calendrier-rdv') . '</p></div>';
}

// Récupérer les options
$options = get_option('cal_rdv_settings', []);
?>

<div class="wrap cal-rdv-wrap">
    <div class="cal-rdv-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>
    
    <!-- Navigation par onglets -->
    <nav class="nav-tab-wrapper cal-rdv-nav-tabs">
        <?php foreach ($tabs as $tab_id => $tab_name) : ?>
            <a href="?page=calendrier-rdv-settings&tab=<?php echo esc_attr($tab_id); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Contenu des onglets -->
    <div class="cal-rdv-tab-content">
        <form method="post" action="" class="cal-rdv-settings-form">
            <?php wp_nonce_field('cal_rdv_save_settings', 'cal_rdv_settings_nonce'); ?>
            
            <?php 
            // Inclure le contenu de l'onglet actif
            $template_path = plugin_dir_path(__FILE__) . 'settings/tabs/' . $current_tab . '.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                echo '<div class="notice notice-error"><p>' . 
                     sprintf(
                         /* translators: %s: Nom de l'onglet */
                         esc_html__('Le template pour l\'onglet "%s" est introuvable.', 'calendrier-rdv'),
                         esc_html($tabs[$current_tab])
                     ) . 
                     '</p></div>';
            }
            ?>
            
            <div class="cal-rdv-settings-footer">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Enregistrer les modifications', 'calendrier-rdv'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modale d'aide -->
<div id="cal-rdv-help-modal" class="cal-rdv-modal" style="display: none;">
    <div class="cal-rdv-modal-content">
        <div class="cal-rdv-modal-header">
            <h3 id="cal-rdv-help-modal-title"></h3>
            <button type="button" class="cal-rdv-modal-close">&times;</button>
        </div>
        <div class="cal-rdv-modal-body" id="cal-rdv-help-modal-content">
            <!-- Le contenu d'aide sera chargé ici dynamiquement -->
        </div>
        <div class="cal-rdv-modal-footer">
            <button type="button" class="button button-secondary cal-rdv-modal-close">
                <?php esc_html_e('Fermer', 'calendrier-rdv'); ?>
            </button>
        </div>
    </div>
</div>
