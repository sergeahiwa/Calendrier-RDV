<?php
/**
 * Page des paramètres
 *
 * @package CalendrierRdv\Admin\Views
 */

// Sécurité : empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Message de succès
if ( isset( $_GET['settings-updated'] ) ) {
	add_settings_error(
		'calendrier_rdv_messages',
		'calendrier_rdv_message',
		__( 'Paramètres enregistrés', 'calendrier-rdv' ),
		'updated'
	);
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<?php settings_errors( 'calendrier_rdv_messages' ); ?>
	
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="save_calendrier_rdv_settings">
		<?php wp_nonce_field( 'save_calendrier_rdv_settings', 'cal_rdv_settings_nonce' ); ?>
		
		<h2 class="title"><?php esc_html_e( 'Paramètres généraux', 'calendrier-rdv' ); ?></h2>
		
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="cal_rdv_settings[timezone]"><?php esc_html_e( 'Fuseau horaire', 'calendrier-rdv' ); ?></label>
					</th>
					<td>
						<select name="cal_rdv_settings[timezone]" id="cal_rdv_settings[timezone]" class="regular-text">
							<?php echo wp_timezone_choice( $options['timezone'] ?? get_option( 'timezone_string', 'Europe/Paris' ) ); ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Définissez le fuseau horaire par défaut pour les rendez-vous.', 'calendrier-rdv' ); ?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="cal_rdv_settings[date_format]"><?php esc_html_e( 'Format de date', 'calendrier-rdv' ); ?></label>
					</th>
					<td>
						<input type="text" 
								name="cal_rdv_settings[date_format]" 
								id="cal_rdv_settings[date_format]" 
								value="<?php echo esc_attr( $options['date_format'] ?? 'd/m/Y' ); ?>" 
								class="regular-text">
						<p class="description">
							<?php
							printf(
								/* translators: %s: Documentation link */
								esc_html__( 'Format d\'affichage des dates. Voir %s pour les options disponibles.', 'calendrier-rdv' ),
								'<a href="https://www.php.net/manual/fr/datetime.format.php" target="_blank">' . esc_html__( 'la documentation PHP', 'calendrier-rdv' ) . '</a>'
							);
							?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="cal_rdv_settings[time_format]"><?php esc_html_e( 'Format d\'heure', 'calendrier-rdv' ); ?></label>
					</th>
					<td>
						<input type="text" 
								name="cal_rdv_settings[time_format]" 
								id="cal_rdv_settings[time_format]" 
								value="<?php echo esc_attr( $options['time_format'] ?? 'H:i' ); ?>" 
								class="regular-text">
						<p class="description">
							<?php
							printf(
								/* translators: %s: Documentation link */
								esc_html__( 'Format d\'affichage des heures. Voir %s pour les options disponibles.', 'calendrier-rdv' ),
								'<a href="https://www.php.net/manual/fr/datetime.format.php" target="_blank">' . esc_html__( 'la documentation PHP', 'calendrier-rdv' ) . '</a>'
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		
		<?php submit_button( __( 'Enregistrer les modifications', 'calendrier-rdv' ) ); ?>
	</form>
</div>
