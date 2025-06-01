<?php
/**
 * Gestion du menu d'administration du plugin Calendrier RDV
 *
 * @package     CalendrierRdv\Includes\Admin
 * @since       1.0.0
 * @author      Votre Nom <votre.email@example.com>
 */

namespace CalendrierRdv\Includes\Admin;

// Si ce fichier est appelé directement, on sort immédiatement.
if (!defined('ABSPATH')) {
    exit;
}

class AdminMenu {
    /**
     * Initialise les hooks pour le menu d'administration.
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    /**
     * Ajoute les éléments au menu d'administration de WordPress.
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            __('Calendrier RDV', 'calendrier-rdv'), // Titre de la page
            __('Calendrier RDV', 'calendrier-rdv'), // Texte du menu
            'manage_options', // Capacité requise
            'calendrier-rdv', // Slug du menu
            [$this, 'render_dashboard'], // Fonction de rendu
            'dashicons-calendar-alt', // Icône
            30 // Position
        );

        // Sous-menu Tableau de bord
        add_submenu_page(
            'calendrier-rdv',
            __('Tableau de bord', 'calendrier-rdv'),
            __('Tableau de bord', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv',
            [$this, 'render_dashboard']
        );

        // Sous-menu Rendez-vous
        add_submenu_page(
            'calendrier-rdv',
            __('Rendez-vous', 'calendrier-rdv'),
            __('Rendez-vous', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv-appointments',
            [$this, 'render_appointments']
        );

        // Sous-menu Prestataires
        add_submenu_page(
            'calendrier-rdv',
            __('Prestataires', 'calendrier-rdv'),
            __('Prestataires', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv-providers',
            [$this, 'render_providers']
        );

        // Sous-menu Services
        add_submenu_page(
            'calendrier-rdv',
            __('Services', 'calendrier-rdv'),
            __('Services', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv-services',
            [$this, 'render_services']
        );

        // Sous-menu Paramètres
        add_submenu_page(
            'calendrier-rdv',
            __('Paramètres', 'calendrier-rdv'),
            __('Paramètres', 'calendrier-rdv'),
            'manage_options',
            'calendrier-rdv-settings',
            [$this, 'render_settings']
        );
    }


    /**
     * Affiche la page du tableau de bord.
     */
    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Inclure le template du tableau de bord
        include_once CAL_RDV_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Affiche la page des rendez-vous.
     */
    public function render_appointments() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Inclure le template des rendez-vous
        include_once CAL_RDV_PLUGIN_DIR . 'templates/admin/appointments.php';
    }

    /**
     * Affiche la page des prestataires.
     */
    public function render_providers() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Inclure le template des prestataires
        include_once CAL_RDV_PLUGIN_DIR . 'templates/admin/providers.php';
    }

    /**
     * Affiche la page des services.
     */
    public function render_services() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Inclure le template des services
        include_once CAL_RDV_PLUGIN_DIR . 'templates/admin/services.php';
    }

    /**
     * Affiche la page des paramètres.
     */
    public function render_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les droits nécessaires pour accéder à cette page.', 'calendrier-rdv'));
        }
        
        // Enregistrer les paramètres si le formulaire est soumis
        if (isset($_POST['cal_rdv_settings_nonce']) && wp_verify_nonce($_POST['cal_rdv_settings_nonce'], 'cal_rdv_save_settings')) {
            $this->save_settings();
        }
        
        // Inclure le template des paramètres
        include_once CAL_RDV_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Enregistre les paramètres du plugin.
     */
    private function save_settings() {
        // Vérifier le nonce et les capacités
        if (!current_user_can('manage_options')) {
            return;
        }

        // Récupérer les options actuelles
        $options = get_option('cal_rdv_settings', []);

        // Mettre à jour les options
        if (isset($_POST['cal_rdv_company_name'])) {
            $options['company_name'] = sanitize_text_field($_POST['cal_rdv_company_name']);
        }

        if (isset($_POST['cal_rdv_company_email'])) {
            $options['company_email'] = sanitize_email($_POST['cal_rdv_company_email']);
        }

        if (isset($_POST['cal_rdv_time_slot'])) {
            $options['time_slot'] = absint($_POST['cal_rdv_time_slot']);
        }

        // Enregistrer les options
        update_option('cal_rdv_settings', $options);

        // Afficher un message de confirmation
        add_settings_error(
            'cal_rdv_messages',
            'cal_rdv_message',
            __('Paramètres enregistrés avec succès.', 'calendrier-rdv'),
            'updated'
        );
    }
}
