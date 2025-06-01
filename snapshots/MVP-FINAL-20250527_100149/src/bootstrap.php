<?php
/**
 * Fichier d'amorçage du plugin Calendrier RDV
 *
 * @package CalendrierRdv
 * @since 1.0.0
 */

// Si ce fichier est appelé directement, on sort immédiatement.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialise le plugin.
 *
 * @since 1.0.0
 * @return \CalendrierRdv\Plugin Instance du plugin.
 */
function init_calendrier_rdv() {
    return \CalendrierRdv\Plugin::get_instance();
}

// Démarrer le plugin.
add_action( 'plugins_loaded', 'CalendrierRdv\\init_calendrier_rdv' );
