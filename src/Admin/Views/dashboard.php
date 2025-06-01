<?php
/**
 * Tableau de bord administrateur
 *
 * @package CalendrierRdv\Admin\Views
 *
 * @var array $stats Statistiques
 * @var array $recentAppointments Derniers rendez-vous
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pageTitle = 'Tableau de bord';
?>

<div class="cal-rdv-row">
	<!-- Carte Statistiques générales -->
	<div class="cal-rdv-col-12">
		<div class="cal-rdv-card">
			<h2><?php esc_html_e( 'Statistiques générales', 'calendrier-rdv' ); ?></h2>
			<div class="cal-rdv-stats-grid">
				<div class="cal-rdv-stat-card">
					<div class="cal-rdv-stat-value"><?php echo esc_html( $stats['total_appointments'] ); ?></div>
					<div class="cal-rdv-stat-label"><?php esc_html_e( 'Rendez-vous', 'calendrier-rdv' ); ?></div>
				</div>
				<div class="cal-rdv-stat-card">
					<div class="cal-rdv-stat-value"><?php echo esc_html( $stats['pending_appointments'] ); ?></div>
					<div class="cal-rdv-stat-label"><?php esc_html_e( 'En attente', 'calendrier-rdv' ); ?></div>
				</div>
				<div class="cal-rdv-stat-card">
					<div class="cal-rdv-stat-value"><?php echo esc_html( $stats['confirmed_appointments'] ); ?></div>
					<div class="cal-rdv-stat-label"><?php esc_html_e( 'Confirmés', 'calendrier-rdv' ); ?></div>
				</div>
				<div class="cal-rdv-stat-card">
					<div class="cal-rdv-stat-value"><?php echo esc_html( $stats['total_providers'] ); ?></div>
					<div class="cal-rdv-stat-label"><?php esc_html_e( 'Prestataires', 'calendrier-rdv' ); ?></div>
				</div>
				<div class="cal-rdv-stat-card">
					<div class="cal-rdv-stat-value"><?php echo esc_html( $stats['total_services'] ); ?></div>
					<div class="cal-rdv-stat-label"><?php esc_html_e( 'Services', 'calendrier-rdv' ); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="cal-rdv-row">
	<!-- Prochains rendez-vous -->
	<div class="cal-rdv-col-8">
		<div class="cal-rdv-card">
			<div class="cal-rdv-card-header">
				<h2><?php esc_html_e( 'Prochains rendez-vous', 'calendrier-rdv' ); ?></h2>
				<a href="<?php echo admin_url( 'admin.php?page=calendrier-rdv-appointments' ); ?>" class="button">
					Voir tout
				</a>
			</div>
			
			<?php if ( ! empty( $recentAppointments ) ) : ?>
				<div class="cal-rdv-table-responsive">
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'calendrier-rdv' ); ?></th>
								<th><?php esc_html_e( 'Client', 'calendrier-rdv' ); ?></th>
								<th><?php esc_html_e( 'Service', 'calendrier-rdv' ); ?></th>
								<th><?php esc_html_e( 'Prestataire', 'calendrier-rdv' ); ?></th>
								<th><?php esc_html_e( 'Statut', 'calendrier-rdv' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'calendrier-rdv' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recentAppointments as $appointment ) : ?>
								<tr>
									<td>
										<?php echo esc_html( $appointment->getStartDate()->format( 'd/m/Y H:i' ) ); ?>
									</td>
									<td>
										<?php echo esc_html( $appointment->getCustomerName() ); ?>
										<br>
										<small><?php echo esc_html( $appointment->getCustomerEmail() ); ?></small>
									</td>
									<td>
										<?php
										$service = $this->serviceRepository->findById( $appointment->getServiceId() );
										echo $service ? esc_html( $service->getName() ) : 'N/A';
										?>
									</td>
									<td>
										<?php
										$provider = $this->providerRepository->findById( $appointment->getProviderId() );
										echo $provider ? esc_html( $provider->getDisplayName() ) : 'N/A';
										?>
									</td>
									<td>
										<span class="cal-rdv-status cal-rdv-status-<?php echo esc_attr( $appointment->getStatus() ); ?>">
											<?php echo esc_html( ucfirst( $appointment->getStatus() ) ); ?>
										</span>
									</td>
									<td>
										<div class="cal-rdv-actions">
											<a href="<?php echo admin_url( 'admin.php?page=calendrier-rdv-appointments&action=view&id=' . $appointment->getId() ); ?>" class="button button-small">
												<span class="dashicons dashicons-visibility"></span>
											</a>
											<?php if ( $appointment->getStatus() === 'pending' ) : ?>
												<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=calendrier-rdv-appointments&action=confirm&id=' . $appointment->getId() ), 'cal_rdv_confirm_appointment_' . $appointment->getId() ); ?>" class="button button-small button-primary">
													<span class="dashicons dashicons-yes"></span>
												</a>
												<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=calendrier-rdv-appointments&action=cancel&id=' . $appointment->getId() ), 'cal_rdv_cancel_appointment_' . $appointment->getId() ); ?>" class="button button-small">
													<span class="dashicons dashicons-no"></span>
												</a>
											<?php endif; ?>
											<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=calendrier-rdv-appointments&action=delete&id=' . $appointment->getId() ), 'cal_rdv_delete_appointment_' . $appointment->getId() ); ?>" class="button button-small button-link-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?');">
												<span class="dashicons dashicons-trash"></span>
											</a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<div class="cal-rdv-empty-state">
					<p><?php esc_html_e( 'Aucun rendez-vous à venir.', 'calendrier-rdv' ); ?></p>
					<a href="<?php echo admin_url( 'admin.php?page=calendrier-rdv-appointments&action=add' ); ?>" class="button button-primary">
						Planifier un rendez-vous
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
	
	<!-- Aide et informations -->
	<div class="cal-rdv-col-4">
		<div class="cal-rdv-card">
			<h2><?php esc_html_e( 'Aide rapide', 'calendrier-rdv' ); ?></h2>
			<div class="cal-rdv-help-section">
				<h3><?php esc_html_e( 'Comment ajouter un rendez-vous ?', 'calendrier-rdv' ); ?></h3>
				<p><?php esc_html_e( 'Pour ajouter un nouveau rendez-vous, cliquez sur le bouton "Planifier un rendez-vous" dans la liste des rendez-vous.', 'calendrier-rdv' ); ?></p>
			</div>
			<div class="cal-rdv-help-section">
				<h3><?php esc_html_e( 'Gérer les prestataires', 'calendrier-rdv' ); ?></h3>
				<p><?php esc_html_e( 'Vous pouvez ajouter et gérer les prestataires depuis la section', 'calendrier-rdv' ); ?> <a href="<?php echo admin_url( 'admin.php?page=calendrier-rdv-providers' ); ?>"><?php esc_html_e( 'Prestataires', 'calendrier-rdv' ); ?></a>.</p>
			</div>
			<div class="cal-rdv-help-section">
				<h3><?php esc_html_e( 'Configuration', 'calendrier-rdv' ); ?></h3>
				<p><?php esc_html_e( 'Personnalisez les paramètres du plugin dans la section', 'calendrier-rdv' ); ?> <a href="<?php echo admin_url( 'admin.php?page=calendrier-rdv-settings' ); ?>"><?php esc_html_e( 'Paramètres', 'calendrier-rdv' ); ?></a>.</p>
			</div>
		</div>
		
		<div class="cal-rdv-card">
			<h2><?php esc_html_e( 'Statut du système', 'calendrier-rdv' ); ?></h2>
			<ul class="cal-rdv-system-status">
				<li>
					<span class="cal-rdv-status-indicator cal-rdv-status-success"></span>
					<strong><?php esc_html_e( 'Version du plugin', 'calendrier-rdv' ); ?> :</strong> <?php echo esc_html( CAL_RDV_VERSION ); ?>
				</li>
				<li>
					<span class="cal-rdv-status-indicator cal-rdv-status-success"></span>
					<strong><?php esc_html_e( 'PHP Version', 'calendrier-rdv' ); ?> :</strong> <?php echo esc_html( phpversion() ); ?>
				</li>
				<li>
					<span class="cal-rdv-status-indicator cal-rdv-status-success"></span>
					<strong><?php esc_html_e( 'WordPress Version', 'calendrier-rdv' ); ?> :</strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?>
				</li>
			</ul>
		</div>
	</div>
</div>
		<div class="calendrier-rdv-stat-box">
			<h3><?php esc_html_e( 'Rendez-vous à venir', 'calendrier-rdv' ); ?></h3>
			<p class="stat-number">0</p>
		</div>
		
		<div class="calendrier-rdv-stat-box">
			<h3><?php esc_html_e( 'Rendez-vous aujourd\'hui', 'calendrier-rdv' ); ?></h3>
			<p class="stat-number">0</p>
		</div>
		
		<div class="calendrier-rdv-stat-box">
			<h3><?php esc_html_e( 'Prestataires actifs', 'calendrier-rdv' ); ?></h3>
			<p class="stat-number">0</p>
		</div>
	</div>
	
	<div class="calendrier-rdv-recent-activity">
		<h2><?php esc_html_e( 'Activité récente', 'calendrier-rdv' ); ?></h2>
		<div class="activity-list">
			<p><?php esc_html_e( 'Aucune activité récente.', 'calendrier-rdv' ); ?></p>
		</div>
	</div>
</div>
