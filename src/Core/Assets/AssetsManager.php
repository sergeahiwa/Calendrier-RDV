<?php

namespace CalendrierRdv\Core\Assets;

/**
 * Gestionnaire des assets du plugin
 */
class AssetsManager {
	private static $instance = null;
	private $pluginUrl;
	private $pluginDir;
	private $registeredStyles  = array();
	private $registeredScripts = array();

	/**
	 * Constructeur privé pour le singleton
	 */
	private function __construct() {
		$this->pluginUrl         = plugin_dir_url( CAL_RDV_PLUGIN_FILE );
		$this->pluginDir         = plugin_dir_path( CAL_RDV_PLUGIN_FILE );
		$this->registeredStyles  = array();
		$this->registeredScripts = array();
	}

	/**
	 * Méthode de singleton
	 *
	 * @return AssetsManager
	 */
	public static function getInstance(): AssetsManager {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Enregistrement des assets
	 */
	public function registerAssets(): void {
		// Styles
		$this->registerStyles();

		// Scripts
		$this->registerScripts();

		// Sauvegarder les assets enregistrés
		$this->registeredStyles = array_merge(
			$this->registeredStyles,
			array(
				'calendrier-rdv-frontend',
				'calendrier-rdv-builder',
			)
		);

		$this->registeredScripts = array_merge(
			$this->registeredScripts,
			array(
				'calendrier-rdv-main',
			)
		);
	}

	/**
	 * Enregistrement des styles
	 */
	private function registerStyles(): void {
		// Styles frontend
		wp_register_style(
			'calendrier-rdv-frontend',
			$this->pluginUrl . 'assets/css/divi-module.css',
			array(),
			CAL_RDV_VERSION,
			'all'
		);

		// Styles builder
		wp_register_style(
			'calendrier-rdv-builder',
			$this->pluginUrl . 'assets/css/divi-module-builder.css',
			array(),
			CAL_RDV_VERSION,
			'all'
		);

		// Styles spécifiques Divi
		wp_register_style(
			'calendrier-rdv-divi',
			$this->pluginUrl . 'assets/css/divi-specific.css',
			array( 'et-builder-modules-style' ),
			CAL_RDV_VERSION,
			'all'
		);
	}

	/**
	 * Enregistrement des scripts
	 */
	private function registerScripts(): void {
		// React
		if ( ! wp_script_is( 'react', 'enqueued' ) ) {
			wp_enqueue_script( 'react' );
			wp_enqueue_script( 'react-dom' );
		}

		// Scripts principaux
		wp_register_script(
			'calendrier-rdv-main',
			$this->pluginUrl . 'assets/js/CalendrierRdv.jsx',
			array( 'react', 'react-dom' ),
			CAL_RDV_VERSION,
			true
		);

		// Scripts spécifiques Divi
		wp_register_script(
			'calendrier-rdv-divi',
			$this->pluginUrl . 'assets/js/divi-specific.js',
			array( 'calendrier-rdv-main' ),
			CAL_RDV_VERSION,
			true
		);

		// Localisation des scripts
		wp_localize_script(
			'calendrier-rdv-main',
			'calendrierRdvConfig',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'calendrier_rdv_nonce' ),
				'i18n'     => array(
					'loading'         => __( 'Chargement...', 'calendrier-rdv' ),
					'error'           => __( 'Une erreur est survenue', 'calendrier-rdv' ),
					'success'         => __( 'Réservation réussie', 'calendrier-rdv' ),
					'no_services'     => __( 'Aucun service disponible', 'calendrier-rdv' ),
					'no_providers'    => __( 'Aucun prestataire disponible', 'calendrier-rdv' ),
					'invalid_date'    => __( 'Date invalide', 'calendrier-rdv' ),
					'invalid_time'    => __( 'Horaire invalide', 'calendrier-rdv' ),
					'required_fields' => __( 'Veuillez remplir tous les champs requis', 'calendrier-rdv' ),
				),
			)
		);
	}
}

    /**
     * Désenregistrement des assets
     */
    public function unregisterAssets(): void {
        // Désenregistrer les styles
        foreach ( $this->registeredStyles as $style ) {
            wp_dequeue_style( $style );
            wp_deregister_style( $style );
        }

        // Désenregistrer les scripts
        foreach ( $this->registeredScripts as $script ) {
            wp_dequeue_script( $script );
            wp_deregister_script( $script );
        }
    }
}

// Empêcher l'instantiation directe
if ( defined( 'ABSPATH' ) ) {
	AssetsManager::getInstance();
}
