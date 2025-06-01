<?php
/**
 * Calendrier RDV - Module Divi 5
 * 
 * @package CalendrierRDV
 * @version 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\ModuleRegistration;

class CalendrierRdvModule extends Module implements DependencyInterface {
    /**
     * Get the module's dependencies
     */
    public static function get_dependencies() {
        return [
            'et-builder-packages-module',
            'et-builder-packages-utils',
        ];
    }

    /**
     * Register the module
     */
    public function init() {
        // Register the module
        $module = new ModuleRegistration(
            'calendrier-rdv',  // Module slug
            [
                'name'        => esc_html__('Calendrier RDV', 'calendrier-rdv'),
                'icon'        => 'calendar-alt', // Dashicons icon
                'category'    => 'specialty', // or custom category
                'keywords'    => ['rendez-vous', 'calendrier', 'réservation'],
                'styles'      => $this->get_styles(),
                'attributes'  => $this->get_attributes(),
                'render'      => [$this, 'render'],
                'settings'    => $this->get_settings(),
            ]
        );

        // Register the module in Divi Builder
        $module->register();
    }

    /**
     * Define module attributes
     */
    private function get_attributes() {
        return [
            'style' => [
                'type'    => 'string',
                'default' => 'default',
            ],
            'show_title' => [
                'type'    => 'boolean',
                'default' => true,
            ],
            'primary_color' => [
                'type'    => 'string',
                'default' => '#2ea3f2',
            ],
        ];
    }

    /**
     * Define module styles
     */
    private function get_styles() {
        return [
            'frontend' => [
                'css' => [
                    'url'  => CAL_RDV_PLUGIN_URL . 'assets/css/divi-module.css',
                    'path' => CAL_RDV_PLUGIN_DIR . 'assets/css/divi-module.css',
                ],
            ],
            'builder' => [
                'css' => [
                    'url'  => CAL_RDV_PLUGIN_URL . 'assets/css/divi-module-builder.css',
                    'path' => CAL_RDV_PLUGIN_DIR . 'assets/css/divi-module-builder.css',
                ],
            ],
        ];
    }

    /**
     * Define module settings
     */
    private function get_settings() {
        return [
            'general' => [
                'title'    => esc_html__('Contenu', 'calendrier-rdv'),
                'priority' => 1,
                'sections' => [
                    'main_content' => [
                        'title'  => esc_html__('Contenu', 'calendrier-rdv'),
                        'fields' => [
                            'style' => [
                                'label'       => esc_html__('Style', 'calendrier-rdv'),
                                'type'        => 'select',
                                'options'     => [
                                    'default' => esc_html__('Défaut', 'calendrier-rdv'),
                                    'modern'  => esc_html__('Moderne', 'calendrier-rdv'),
                                    'minimal' => esc_html__('Minimal', 'calendrier-rdv'),
                                ],
                                'default'     => 'default',
                                'description' => esc_html__('Choisissez le style d\'affichage du calendrier.', 'calendrier-rdv'),
                            ],
                            'show_title' => [
                                'label'       => esc_html__('Afficher le titre', 'calendrier-rdv'),
                                'type'        => 'yes_no_button',
                                'options'     => [
                                    'on'  => esc_html__('Oui', 'calendrier-rdv'),
                                    'off' => esc_html__('Non', 'calendrier-rdv'),
                                ],
                                'default'     => 'on',
                                'description' => esc_html__('Afficher le titre du calendrier.', 'calendrier-rdv'),
                            ],
                        ],
                    ],
                ],
            ],
            'design' => [
                'title'    => esc_html__('Design', 'calendrier-rdv'),
                'priority' => 2,
                'sections' => [
                    'colors' => [
                        'title'  => esc_html__('Couleurs', 'calendrier-rdv'),
                        'fields' => [
                            'primary_color' => [
                                'label'   => esc_html__('Couleur principale', 'calendrier-rdv'),
                                'type'    => 'color-alpha',
                                'default' => '#2ea3f2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Render the module
     */
    /**
     * Sanitize color value
     */
    private function sanitize_color($color) {
        // Remove any non-hex characters
        $color = preg_replace('/[^0-9a-fA-F#]/', '', $color);
        
        // Ensure it's a valid hex color
        if (strpos($color, '#') !== 0) {
            $color = '#' . $color;
        }
        
        // Ensure it's 6 characters long (without #)
        if (strlen($color) === 4) {
            $color = '#' . str_repeat(substr($color, 1), 3);
        }
        
        return $color;
    }

    /**
     * Render the module
     */
    public function render($attrs, $content = null, $render_slug) {
        // Validate and sanitize attributes
        $valid_styles = ['default', 'modern', 'minimal'];
        $style = in_array($attrs['style'] ?? 'default', $valid_styles) ? $attrs['style'] : 'default';
        
        $show_title = filter_var($attrs['show_title'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        $primary_color = $this->sanitize_color($attrs['primary_color'] ?? '#2ea3f2');

        // Generate unique ID for this module
        $module_id = 'calendrier-rdv-' . uniqid();

        // Include custom style
        $this->enqueue_assets($module_id, $primary_color);

        // Start output
        ob_start();
        ?>
        <div id="<?php echo esc_attr($module_id); ?>" class="calendrier-rdv-module style-<?php echo esc_attr($style); ?>">
            <?php if ($show_title) : ?>
                <h3 class="calendrier-rdv-title"><?php esc_html_e('Prendre rendez-vous', 'calendrier-rdv'); ?></h3>
            <?php endif; ?>
            
            <div class="calendrier-rdv-container">
                <div class="calendrier-rdv-react-root" 
                     data-style="<?php echo esc_attr($style); ?>"
                     data-primary-color="<?php echo esc_attr($primary_color); ?>">
                    <div class="calendrier-rdv-loading">
                        <?php esc_html_e('Chargement du calendrier...', 'calendrier-rdv'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Enqueue necessary assets
     */
    private function enqueue_assets($module_id, $primary_color) {
        // Register and enqueue scripts
        wp_enqueue_script(
            'calendrier-rdv-divi-module',
            CAL_RDV_PLUGIN_URL . 'assets/js/divi-module.js',
            ['react', 'react-dom', 'wp-element', 'wp-api-fetch'],
            CAL_RDV_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('calendrier-rdv-divi-module', 'calendrierRdvConfig', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('calendrier_rdv_nonce'),
            'colors'   => [
                'primary' => $primary_color,
            ],
            'i18n'     => [
                'loading' => esc_html__('Chargement...', 'calendrier-rdv'),
                'error'   => esc_html__('Une erreur est survenue', 'calendrier-rdv'),
            ],
        ]);

        // Add inline style if necessary
        $custom_css = "
            #{$module_id} .calendrier-rdv-container {
                --calendrier-rdv-primary: {$primary_color};
            }
        ";
        wp_add_inline_style('calendrier-rdv-divi-module', $custom_css);
    }
}

// Initialize the module
add_action('et_builder_ready', function() {
    if (class_exists('ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface')) {
        $module = new CalendrierRdvModule();
        $module->init();
    }
});
