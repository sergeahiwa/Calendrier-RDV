<?php
/**
 * Configuration des assets pour le plugin Calendrier RDV
 */

// Configuration des assets
return [
    'styles' => [
        'frontend' => [
            'handle' => 'calendrier-rdv-frontend',
            'src' => CAL_RDV_PLUGIN_URL . 'assets/css/divi-module.css',
            'deps' => [],
            'version' => CAL_RDV_VERSION,
            'media' => 'all',
            'condition' => 'is_front_page() || is_page_template("template-calendrier.php")'
        ],
        'builder' => [
            'handle' => 'calendrier-rdv-builder',
            'src' => CAL_RDV_PLUGIN_URL . 'assets/css/divi-module-builder.css',
            'deps' => [],
            'version' => CAL_RDV_VERSION,
            'media' => 'all',
            'condition' => 'is_admin() && et_core_is_builder_plugin_active()'
        ],
        'divi' => [
            'handle' => 'calendrier-rdv-divi',
            'src' => CAL_RDV_PLUGIN_URL . 'assets/css/divi-specific.css',
            'deps' => ['et-builder-modules-style'],
            'version' => CAL_RDV_VERSION,
            'media' => 'all',
            'condition' => 'is_page_template("template-calendrier.php")'
        ]
    ],
    'scripts' => [
        'main' => [
            'handle' => 'calendrier-rdv-main',
            'src' => CAL_RDV_PLUGIN_URL . 'assets/js/CalendrierRdv.jsx',
            'deps' => ['react', 'react-dom'],
            'version' => CAL_RDV_VERSION,
            'in_footer' => true,
            'condition' => 'is_front_page() || is_page_template("template-calendrier.php")'
        ],
        'divi' => [
            'handle' => 'calendrier-rdv-divi',
            'src' => CAL_RDV_PLUGIN_URL . 'assets/js/divi-specific.js',
            'deps' => ['calendrier-rdv-main'],
            'version' => CAL_RDV_VERSION,
            'in_footer' => true,
            'condition' => 'is_page_template("template-calendrier.php")'
        ]
    ],
    'cache' => [
        'enabled' => true,
        'duration' => 3600, // 1 heure en secondes
        'keys' => [
            'services' => 'calendrier_rdv_services',
            'providers' => 'calendrier_rdv_providers',
            'appointments' => 'calendrier_rdv_appointments'
        ]
    ]
];
