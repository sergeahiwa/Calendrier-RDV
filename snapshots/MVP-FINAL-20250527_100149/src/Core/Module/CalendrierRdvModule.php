<?php

namespace CalendrierRdv\Core\Module;

use ET_Builder_Module;
use ET_Builder_Element;

class CalendrierRdvModule extends ET_Builder_Module {
    public $slug = 'et_pb_calendrier_rdv';
    public $vb_support = 'on';
    public $type = 'child';
    public $child_slug = 'et_pb_calendrier_rdv_item';
    public $child_item_text = 'Rendez-vous';

    public function init() {
        $this->name = esc_html__('Calendrier RDV', 'calendrier-rdv');
        $this->icon = 'calendar';
        $this->main_css_element = '%%order_class%%';
        
        $this->settings_modal_toggles = array(
            'general' => array(
                'toggles' => array(
                    'main_content' => esc_html__('Contenu principal', 'calendrier-rdv'),
                    'services' => esc_html__('Services', 'calendrier-rdv'),
                    'providers' => esc_html__('Prestataires', 'calendrier-rdv'),
                    'booking' => esc_html__('Réservation', 'calendrier-rdv'),
                    'messages' => esc_html__('Messages', 'calendrier-rdv'),
                ),
            ),
            'advanced' => array(
                'toggles' => array(
                    'layout' => esc_html__('Layout', 'calendrier-rdv'),
                    'calendar' => esc_html__('Calendrier', 'calendrier-rdv'),
                    'form' => esc_html__('Formulaire', 'calendrier-rdv'),
                ),
            ),
        );
    }

    public function get_fields() {
        return array(
            // Contenu principal
            'title' => array(
                'label' => esc_html__('Titre', 'calendrier-rdv'),
                'type' => 'text',
                'option_category' => 'basic_option',
                'toggle_slug' => 'main_content',
                'default' => esc_html__('Prenez rendez-vous', 'calendrier-rdv'),
            ),
            'description' => array(
                'label' => esc_html__('Description', 'calendrier-rdv'),
                'type' => 'text',
                'option_category' => 'basic_option',
                'toggle_slug' => 'main_content',
                'default' => esc_html__('Choisissez un service et un créneau', 'calendrier-rdv'),
            ),

            // Services
            'service_display' => array(
                'label' => esc_html__('Affichage des services', 'calendrier-rdv'),
                'type' => 'select',
                'option_category' => 'configuration',
                'toggle_slug' => 'services',
                'options' => array(
                    'grid' => esc_html__('Grille', 'calendrier-rdv'),
                    'list' => esc_html__('Liste', 'calendrier-rdv'),
                ),
                'default' => 'grid',
            ),
            'service_columns' => array(
                'label' => esc_html__('Colonnes de services', 'calendrier-rdv'),
                'type' => 'number',
                'option_category' => 'configuration',
                'toggle_slug' => 'services',
                'default' => 3,
                'min' => 1,
                'max' => 6,
            ),

            // Prestataires
            'provider_display' => array(
                'label' => esc_html__('Affichage des prestataires', 'calendrier-rdv'),
                'type' => 'select',
                'option_category' => 'configuration',
                'toggle_slug' => 'providers',
                'options' => array(
                    'grid' => esc_html__('Grille', 'calendrier-rdv'),
                    'list' => esc_html__('Liste', 'calendrier-rdv'),
                    'dropdown' => esc_html__('Liste déroulante', 'calendrier-rdv'),
                ),
                'default' => 'dropdown',
            ),

            // Réservation
            'booking_mode' => array(
                'label' => esc_html__('Mode de réservation', 'calendrier-rdv'),
                'type' => 'select',
                'option_category' => 'configuration',
                'toggle_slug' => 'booking',
                'options' => array(
                    'calendar' => esc_html__('Calendrier', 'calendrier-rdv'),
                    'list' => esc_html__('Liste', 'calendrier-rdv'),
                ),
                'default' => 'calendar',
            ),
            'min_booking_time' => array(
                'label' => esc_html__('Temps minimum de réservation (minutes)', 'calendrier-rdv'),
                'type' => 'number',
                'option_category' => 'configuration',
                'toggle_slug' => 'booking',
                'default' => 30,
                'min' => 15,
            ),

            // Messages
            'success_message' => array(
                'label' => esc_html__('Message de succès', 'calendrier-rdv'),
                'type' => 'text',
                'option_category' => 'configuration',
                'toggle_slug' => 'messages',
                'default' => esc_html__('Votre rendez-vous a été réservé avec succès !', 'calendrier-rdv'),
            ),
            'error_message' => array(
                'label' => esc_html__('Message d\'erreur', 'calendrier-rdv'),
                'type' => 'text',
                'option_category' => 'configuration',
                'toggle_slug' => 'messages',
                'default' => esc_html__('Une erreur est survenue lors de la réservation.', 'calendrier-rdv'),
            ),
        );
    }

    public function render($unprocessed = false) {
        $title = $this->props['title'];
        $description = $this->props['description'];
        
        $module_class = $this->props['module_class'] ? ' ' . $this->props['module_class'] : '';
        
        $output = sprintf(
            '<div class="et_pb_calendrier_rdv%s">',
            esc_attr($module_class)
        );
        
        if (!empty($title)) {
            $output .= sprintf(
                '<h2 class="et_pb_calendrier_rdv_title">%s</h2>',
                esc_html($title)
            );
        }
        
        if (!empty($description)) {
            $output .= sprintf(
                '<div class="et_pb_calendrier_rdv_description">%s</div>',
                esc_html($description)
            );
        }
        
        $output .= '<div class="et_pb_calendrier_rdv_content"></div>';
        $output .= '</div>';
        
        return $output;
    }
}
