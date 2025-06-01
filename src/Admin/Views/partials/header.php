<?php
/**
 * En-tête des vues d'administration
 *
 * @var string $pageTitle Titre de la page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Empêcher l'accès direct
}

$pageTitle = $pageTitle ?? 'Calendrier RDV';
?>
<div class="wrap calendrier-rdv">
	<h1 class="wp-heading-inline"><?php echo esc_html( $pageTitle ); ?></h1>
	
	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>Les modifications ont été enregistrées avec succès.</p>
		</div>
	<?php endif; ?>
	
	<?php if ( isset( $_GET['error'] ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p>Une erreur est survenue : <?php echo esc_html( $_GET['error'] ); ?></p>
		</div>
	<?php endif; ?>
	
	<?php settings_errors( 'cal_rdv_settings' ); ?>
	
	<div class="cal-rdv-content">
