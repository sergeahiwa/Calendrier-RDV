<?php
/**
 * Module Divi pour Calendrier RDV
 */
class CalendrierRdvModule extends ET_Builder_Module {
    
    public $slug = 'calendrier_rdv_module';
    public $vb_support = 'on';
    public $name = 'Calendrier RDV';
    public $icon_path = ''; // Chemin vers une icône personnalisée si nécessaire
    
    protected $module_credits = [
        'module_uri' => 'https://votresite.com/calendrier-rdv',
        'author'     => 'Votre Nom',
        'author_uri' => 'https://votresite.com',
    ];
    
    public function init() {
        $this->name = esc_html__('Calendrier RDV', 'calendrier-rdv');
        $this->icon_path = plugin_dir_url(__FILE__) . 'icon.svg';
        
        // Enregistrer les styles et scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    public function enqueue_assets() {
        // Ne charger les assets que si le module est utilisé sur la page
        if (et_core_is_fb_enabled() || $this->is_module_present()) {
            // Styles
            wp_enqueue_style(
                'calendrier-rdv-divi',
                plugin_dir_url(__FILE__) . 'css/divi-module.css',
                [],
                filemtime(plugin_dir_path(__FILE__) . 'css/divi-module.css')
            );
            
            // Scripts
            wp_enqueue_script(
                'calendrier-rdv-divi',
                plugin_dir_url(__FILE__) . 'js/divi-module.js',
                ['jquery'],
                filemtime(plugin_dir_path(__FILE__) . 'js/divi-module.js'),
                true
            );
            
            // Localisation des variables pour JavaScript
            wp_localize_script('calendrier-rdv-divi', 'calendrierRdvDiviVars', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => esc_url_raw(rest_url('calendrier-rdv/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
                'i18n' => [
                    'loading' => __('Chargement...', 'calendrier-rdv'),
                    'error' => __('Une erreur est survenue', 'calendrier-rdv'),
                ]
            ]);
        }
    }
    
    public function get_fields() {
        return [
            'prestataire_id' => [
                'label' => __('ID du prestataire', 'calendrier-rdv'),
                'type' => 'text',
                'option_category' => 'basic_option',
                'description' => __('Laissez vide pour permettre le choix du prestataire', 'calendrier-rdv'),
                'toggle_slug' => 'main_content',
            ],
            'service_id' => [
                'label' => __('ID du service', 'calendrier-rdv'),
                'type' => 'text',
                'option_category' => 'basic_option',
                'description' => __('Laissez vide pour permettre le choix du service', 'calendrier-rdv'),
                'toggle_slug' => 'main_content',
            ],
            'show_title' => [
                'label' => __('Afficher le titre', 'calendrier-rdv'),
                'type' => 'yes_no_button',
                'options' => [
                    'on' => __('Oui', 'calendrier-rdv'),
                    'off' => __('Non', 'calendrier-rdv'),
                ],
                'default' => 'on',
                'toggle_slug' => 'elements',
            ],
            'show_description' => [
                'label' => __('Afficher la description', 'calendrier-rdv'),
                'type' => 'yes_no_button',
                'options' => [
                    'on' => __('Oui', 'calendrier-rdv'),
                    'off' => __('Non', 'calendrier-rdv'),
                ],
                'default' => 'on',
                'toggle_slug' => 'elements',
            ],
            // Nouvelles options de personnalisation
            'primary_color' => [
                'label' => __('Couleur principale', 'calendrier-rdv'),
                'type' => 'color-alpha',
                'custom_color' => true,
                'default' => '#2b87da',
                'tab_slug' => 'design',
                'toggle_slug' => 'styles',
                'description' => __('Couleur des boutons et éléments principaux', 'calendrier-rdv'),
            ],
            'secondary_color' => [
                'label' => __('Couleur secondaire', 'calendrier-rdv'),
                'type' => 'color-alpha',
                'custom_color' => true,
                'default' => '#1a4b8c',
                'tab_slug' => 'design',
                'toggle_slug' => 'styles',
                'description' => __('Couleur des éléments au survol', 'calendrier-rdv'),
            ],
            'text_color' => [
                'label' => __('Couleur du texte', 'calendrier-rdv'),
                'type' => 'color',
                'custom_color' => true,
                'default' => '#333333',
                'tab_slug' => 'design',
                'toggle_slug' => 'styles',
            ],
            'border_radius' => [
                'label' => __('Rayon des coins', 'calendrier-rdv'),
                'type' => 'range',
                'option_category' => 'layout',
                'tab_slug' => 'design',
                'toggle_slug' => 'styles',
                'default' => '4px',
                'range_settings' => [
                    'min' => '0',
                    'max' => '50',
                    'step' => '1',
                ],
                'mobile_options' => true,
            ],
            'show_google_calendar' => [
                'label' => __('Afficher l\'option Google Calendar', 'calendrier-rdv'),
                'type' => 'yes_no_button',
                'options' => [
                    'on' => __('Oui', 'calendrier-rdv'),
                    'off' => __('Non', 'calendrier-rdv'),
                ],
                'default' => 'on',
                'toggle_slug' => 'integration',
                'tab_slug' => 'general',
            ],
            'enable_sms_notifications' => [
                'label' => __('Activer les notifications SMS', 'calendrier-rdv'),
                'type' => 'yes_no_button',
                'options' => [
                    'on' => __('Oui', 'calendrier-rdv'),
                    'off' => __('Non', 'calendrier-rdv'),
                ],
                'default' => 'off',
                'toggle_slug' => 'notifications',
                'tab_slug' => 'general',
            ],
            'custom_css' => [
                'label' => __('CSS personnalisé', 'calendrier-rdv'),
                'type' => 'custom_css',
                'tab_slug' => 'custom_css',
                'toggle_slug' => 'custom_css',
            ],
        ];
    }
    
    public function render($attrs, $content = null, $render_slug) {
        // Récupérer les propriétés
        $prestataire_id = $this->props['prestataire_id'];
        $service_id = $this->props['service_id'];
        $show_title = $this->props['show_title'] === 'on';
        $show_description = $this->props['show_description'] === 'on';
        $show_google_calendar = $this->props['show_google_calendar'] === 'on';
        $enable_sms_notifications = $this->props['enable_sms_notifications'] === 'on';
        
        // Récupérer les couleurs personnalisées
        $primary_color = $this->props['primary_color'];
        $secondary_color = $this->props['secondary_color'];
        $text_color = $this->props['text_color'];
        $border_radius = $this->props['border_radius'];
        
        // Générer l'ID unique pour ce module
        $module_id = 'calendrier-rdv-' . $this->props['module_id'];
        
        // Ajouter des styles en ligne pour les couleurs personnalisées
        $custom_css = '';
        
        if (!empty($primary_color)) {
            $custom_css .= "
                #$module_id .calendrier-rdv-form .btn-submit,
                #$module_id .ui-datepicker-header,
                #$module_id .time-slot.selected {
                    background-color: $primary_color !important;
                    border-color: $primary_color !important;
                }
                #$module_id .calendrier-rdv-form .btn-submit:hover {
                    background-color: $secondary_color !important;
                    border-color: $secondary_color !important;
                }
            ";
        }
        
        if (!empty($text_color)) {
            $custom_css .= "
                #$module_id {
                    color: $text_color;
                }
                #$module_id .calendrier-rdv-title,
                #$module_id .calendrier-rdv-form label {
                    color: $text_color;
                }
            ";
        }
        
        if (!empty($border_radius)) {
            $custom_css .= "
                #$module_id .calendrier-rdv-container,
                #$module_id .calendrier-rdv-form select,
                #$module_id .calendrier-rdv-form input[type=\"text\"],
                #$module_id .calendrier-rdv-form input[type=\"email\"],
                #$module_id .calendrier-rdv-form input[type=\"tel\"],
                #$module_id .calendrier-rdv-form textarea,
                #$module_id .calendrier-rdv-form .btn-submit,
                #$module_id .time-slot {
                    border-radius: {$border_radius}px !important;
                }
            ";
        }
        
        // Ajouter le CSS personnalisé
        if (!empty($custom_css)) {
            wp_add_inline_style('calendrier-rdv-divi', $custom_css);
        }
        
        // Données à passer au JavaScript
        $js_data = [
            'prestataire_id' => $prestataire_id,
            'service_id' => $service_id,
            'show_google_calendar' => $show_google_calendar,
            'enable_sms_notifications' => $enable_sms_notifications,
            'module_id' => $module_id,
        ];
        
        wp_localize_script('calendrier-rdv-divi', 'calendrierRdvDiviModule_' . str_replace('-', '_', $module_id), $js_data);
        
        // Démarrer la mise en mémoire tampon de sortie
        ob_start();
        ?>
        <div class="calendrier-rdv-divi-module" id="<?php echo esc_attr($module_id); ?>">
            <?php if ($show_title) : ?>
                <h3 class="calendrier-rdv-title" role="alert"><?php esc_html_e('Prendre rendez-vous', 'calendrier-rdv'); ?></h3>
            <?php endif; ?>

            <?php if ($show_description) : ?>
                <p class="calendrier-rdv-description">
                    <?php esc_html_e('Remplissez le formulaire ci-dessous pour réserver votre créneau.', 'calendrier-rdv'); ?>
                </p>
            <?php endif; ?>

            <div class="calendrier-rdv-container">
                <!-- Le contenu sera chargé dynamiquement via JavaScript -->
                <div class="calendrier-rdv-loading">
                    <span class="spinner"></span>
                    <span role="status" aria-live="polite"><?php esc_html_e('Chargement du calendrier...', 'calendrier-rdv'); ?></span>
                </div>
            </div>

            <?php if ($show_google_calendar) : ?>
                <div class="calendrier-rdv-google-calendar" style="margin-top: 20px; display: none;">
                    <div class="et_pb_toggle et_pb_toggle_close">
                        <h5 class="et_pb_toggle_title">
                            <?php esc_html_e('Ajouter à Google Calendar', 'calendrier-rdv'); ?>
                        </h5>
                        <div class="et_pb_toggle_content clearfix">
                            <p><?php esc_html_e('Souhaitez-vous ajouter ce rendez-vous à votre calendrier Google ?', 'calendrier-rdv'); ?></p>
                            <a href="#" class="et_pb_button et_pb_button_0 et_pb_bg_layout_light google-calendar-btn" target="_blank">
                                <?php esc_html_e('Ajouter à Google Calendar', 'calendrier-rdv'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($enable_sms_notifications) : ?>
                <div class="calendrier-rdv-sms-notification" style="margin-top: 20px;">
                    <label>
                        <input type="checkbox" name="enable_sms_reminder" class="sms-reminder-checkbox">
                        <?php esc_html_e('Recevoir un rappel par SMS avant le rendez-vous', 'calendrier-rdv'); ?>
                    </label>
                </div>
            <?php endif; ?>
        </div>
        
        <script type="text/template" id="calendrier-rdv-template">
            <div class="calendrier-rdv-form">
                <!-- Le template sera rempli dynamiquement par JavaScript -->
            </div>
        </script>
        
        <style type="text/css">
            /* Styles dynamiques ajoutés ici */
            <?php echo esc_html($custom_css); ?>
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Récupère la liste des prestataires
     */
    private function get_prestataires() {
        // Implémentez la logique pour récupérer les prestataires
        return [];
    }
    
    /**
     * Récupère la liste des services pour un prestataire
     */
    private function get_services($prestataire_id = '') {
        // Implémentez la logique pour récupérer les services
        return [];
    }
    
    /**
     * Vérifie si le module est présent sur la page
     */
    private function is_module_present() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        return has_shortcode($post->post_content, 'et_pb_' . $this->slug);
    }
}

// Enregistrer le module
new CalendrierRdvModule;
