<?php

namespace CalendrierRdv\Admin;

class AdminDashboard {

	private $plugin_slug = 'calendrier-rdv-dashboard';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_assets' ) );
	}

	public function register_admin_menu() {
		// Ajout du menu principal "Calendrier RDV"
		add_menu_page(
			__( 'Calendrier RDV', 'calendrier-rdv' ), // Titre de la page
			__( 'Calendrier RDV', 'calendrier-rdv' ), // Titre du menu
			'manage_options',                      // Capacité requise
			'calendrier-rdv-main',                 // Slug du menu
			array( $this, 'display_dashboard_page' ),     // Fonction d'affichage de la première page (sera notre tableau de bord)
			'dashicons-calendar-alt',              // Icône du menu
			26                                     // Position
		);

		// Ajout du sous-menu "Tableau de bord"
		add_submenu_page(
			'calendrier-rdv-main',                 // Slug du menu parent
			__( 'Tableau de bord', 'calendrier-rdv' ), // Titre de la page
			__( 'Tableau de bord', 'calendrier-rdv' ), // Titre du menu
			'manage_options',                      // Capacité requise
			$this->plugin_slug,                    // Slug de ce sous-menu (identique au slug du menu principal si c'est la page par défaut)
			array( $this, 'display_dashboard_page' )      // Fonction d'affichage
		);

		// On pourrait ajouter d'autres sous-menus ici (Prestataires, Services, etc. en se basant sur README_ADMIN_CALENDRIER.md)
		// add_submenu_page('calendrier-rdv-main', __('Prestataires', 'calendrier-rdv'), __('Prestataires', 'calendrier-rdv'), 'manage_options', 'calendrier-rdv-providers', [$this, 'display_providers_page']);
	}

	public function display_dashboard_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="admin-calendar-container"></div>
			<!-- Fenêtre modale de détails de rendez-vous -->
			<div id="appointment-details-modal" class="modal fade" tabindex="-1" role="dialog">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Détails du rendez-vous</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<!-- Contenu de la fenêtre modale -->
							<p><strong>Titre :</strong> <span id="modal-appointment-title"></span></p>
							<p><strong>Début :</strong> <span id="modal-appointment-start"></span></p>
							<p><strong>Fin :</strong> <span id="modal-appointment-end"></span></p>
							<p><strong>Client :</strong> <span id="modal-appointment-customer"></span></p>
							<p><strong>Service :</strong> <span id="modal-appointment-service"></span></p>
							<p><strong>Prestataire :</strong> <span id="modal-appointment-provider"></span></p>
							<p><strong>Durée :</strong> <span id="modal-appointment-duration"></span> minutes</p>
							<p><strong>Statut :</strong> <span id="modal-appointment-status"></span></p>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function enqueue_dashboard_assets( $hook_suffix ) {
		// Vérifier si nous sommes sur la bonne page d'administration
		// Le hook_suffix pour la page principale créée avec add_menu_page est 'toplevel_page_{menu_slug}'
		// Le hook_suffix pour la page de sous-menu est '{parent_slug}_page_{sub_menu_slug}' ou si le slug est le même que le parent, c'est le hook du parent.
		// Dans notre cas, la page du tableau de bord est la page principale du menu Calendrier RDV.
		if ( 'toplevel_page_calendrier-rdv-main' !== $hook_suffix && 'calendrier-rdv_page_calendrier-rdv-dashboard' !== $hook_suffix ) {
			return;
		}

		// FullCalendar via CDN
		wp_enqueue_style( 'fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css', array(), '6.1.11' );
		wp_enqueue_script( 'fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.js', array(), '6.1.11', true );
		// Pour la localisation en français (optionnel, mais recommandé)
		wp_enqueue_script( 'fullcalendar-locale-fr', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/fr.js', array( 'fullcalendar-js' ), '6.1.11', true );

		// Notre script JS personnalisé pour initialiser FullCalendar
		wp_enqueue_script(
			'admin-dashboard-js',
			plugin_dir_url( __FILE__ ) . '../../assets/js/admin-dashboard.js', // Assurez-vous que ce chemin est correct
			array( 'fullcalendar-js' ),
			filemtime( plugin_dir_path( __FILE__ ) . '../../assets/js/admin-dashboard.js' ),
			true
		);

		// Localiser des données pour notre script JS (ex: URL de l'API REST, nonce)
		wp_localize_script(
			'admin-dashboard-js',
			'calendrierRdvDashboard',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ), // Ou l'URL de l'API REST
				'nonce'           => wp_create_nonce( 'wp_rest' ), // Nonce pour l'API REST si nécessaire
				'api_base_url'    => esc_url_raw( rest_url( 'calendrier-rdv/v1/' ) ), // Base URL de notre API
				'calendar_locale' => 'fr', // Passer la locale au JS
			)
		);

		// Notre CSS personnalisé
		wp_enqueue_style(
			'admin-dashboard-css',
			plugin_dir_url( __FILE__ ) . '../../assets/css/admin-dashboard.css',
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . '../../assets/css/admin-dashboard.css' )
		);
	}

	// Placeholder pour d'autres pages d'admin si nécessaire
	// public function display_providers_page() { echo '<h1>Page des Prestataires</h1>'; }
}
