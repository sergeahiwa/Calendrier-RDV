<?php
/**
 * Classe principale de l'administration
 *
 * @package CalendrierRdv\Admin
 */

namespace CalendrierRdv\Admin;

use CalendrierRdv\Domain\Repository\AppointmentRepositoryInterface;
use CalendrierRdv\Domain\Repository\ProviderRepositoryInterface;
use CalendrierRdv\Domain\Repository\ServiceRepositoryInterface;
use CalendrierRdv\Domain\Service\NotificationService;

/**
 * Classe principale de l'administration
 */
class Admin {

	/**
	 * @var string Chemin vers le répertoire des vues
	 */
	private string $viewsPath;

	/**
	 * @var ServiceRepositoryInterface
	 */
	private ServiceRepositoryInterface $serviceRepository;

	/**
	 * @var ProviderRepositoryInterface
	 */
	private ProviderRepositoryInterface $providerRepository;

	/**
	 * @var AppointmentRepositoryInterface
	 */
	private AppointmentRepositoryInterface $appointmentRepository;

	/**
	 * @var NotificationService
	 */
	private NotificationService $notificationService;

	/**
	 * Constructeur
	 *
	 * @param ServiceRepositoryInterface     $serviceRepository
	 * @param ProviderRepositoryInterface    $providerRepository
	 * @param AppointmentRepositoryInterface $appointmentRepository
	 * @param NotificationService            $notificationService
	 */
	public function __construct(
		ServiceRepositoryInterface $serviceRepository,
		ProviderRepositoryInterface $providerRepository,
		AppointmentRepositoryInterface $appointmentRepository,
		NotificationService $notificationService
	) {
		$this->serviceRepository     = $serviceRepository;
		$this->providerRepository    = $providerRepository;
		$this->appointmentRepository = $appointmentRepository;
		$this->notificationService   = $notificationService;
		$this->viewsPath             = CAL_RDV_PLUGIN_DIR . 'src/Admin/views/';
	}

	/**
	 * Initialise l'administration
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
		add_action( 'admin_init', array( $this, 'handleAdminActions' ) );
	}

	/**
	 * Ajoute les éléments au menu d'administration
	 */
	public function addAdminMenu(): void {
		// Menu principal
		add_menu_page(
			'Calendrier RDV',
			'Calendrier RDV',
			'manage_options',
			'calendrier-rdv',
			array( $this, 'renderDashboard' ),
			'dashicons-calendar-alt',
			30
		);

		// Sous-menus
		add_submenu_page(
			'calendrier-rdv',
			'Tableau de bord',
			'Tableau de bord',
			'manage_options',
			'calendrier-rdv',
			array( $this, 'renderDashboard' )
		);

		add_submenu_page(
			'calendrier-rdv',
			'Rendez-vous',
			'Rendez-vous',
			'manage_options',
			'calendrier-rdv-appointments',
			array( $this, 'renderAppointments' )
		);

		add_submenu_page(
			'calendrier-rdv',
			'Prestataires',
			'Prestataires',
			'manage_options',
			'calendrier-rdv-providers',
			array( $this, 'renderProviders' )
		);

		add_submenu_page(
			'calendrier-rdv',
			'Services',
			'Services',
			'manage_options',
			'calendrier-rdv-services',
			array( $this, 'renderServices' )
		);

		add_submenu_page(
			'calendrier-rdv',
			'Paramètres',
			'Paramètres',
			'manage_options',
			'calendrier-rdv-settings',
			array( $this, 'renderSettings' )
		);
	}

	/**
	 * Charge les assets de l'administration
	 *
	 * @param string $hook
	 */
	public function enqueueAdminAssets( string $hook ): void {
		if ( strpos( $hook, 'calendrier-rdv' ) === false ) {
			return;
		}

		// CSS
		wp_enqueue_style(
			'cal-rdv-admin',
			CAL_RDV_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			CAL_RDV_VERSION
		);

		// JS
		wp_enqueue_script(
			'cal-rdv-admin',
			CAL_RDV_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-datepicker', 'wp-util' ),
			CAL_RDV_VERSION,
			true
		);

		// Localisation
		wp_localize_script(
			'cal-rdv-admin',
			'calRdvAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cal_rdv_admin_nonce' ),
				'texts'   => array(
					'confirmDelete' => 'Êtes-vous sûr de vouloir supprimer cet élément ?',
					'error'         => 'Une erreur est survenue. Veuillez réessayer.',
				),
			)
		);

		// Datepicker
		wp_enqueue_style(
			'jquery-ui',
			'//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
			array(),
			'1.12.1'
		);
	}

	/**
	 * Gère les actions de l'administration
	 */
	public function handleAdminActions(): void {
		if ( ! isset( $_GET['page'] ) || strpos( $_GET['page'], 'calendrier-rdv' ) !== 0 ) {
			return;
		}

		// Vérifier les nonces pour les actions
		if ( isset( $_POST['cal_rdv_nonce'] ) && ! wp_verify_nonce( $_POST['cal_rdv_nonce'], 'cal_rdv_admin_action' ) ) {
			wp_die( 'Action non autorisée.' );
		}

		// Traitement des actions
		if ( isset( $_GET['action'] ) ) {
			$this->processAction();
		}
	}

	/**
	 * Traite une action d'administration
	 */
	private function processAction(): void {
		$action = sanitize_text_field( $_GET['action'] );
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		switch ( $action ) {
			case 'delete_appointment':
				$this->handleDeleteAppointment( $id );
				break;
			case 'confirm_appointment':
				$this->handleConfirmAppointment( $id );
				break;
			case 'cancel_appointment':
				$this->handleCancelAppointment( $id );
				break;
			// Ajouter d'autres actions ici
		}
	}

	/**
	 * Affiche la vue du tableau de bord
	 */
	public function renderDashboard(): void {
		// Récupérer les statistiques
		$stats = array(
			'total_appointments'     => $this->appointmentRepository->count(),
			'pending_appointments'   => $this->appointmentRepository->countByStatus( 'pending' ),
			'confirmed_appointments' => $this->appointmentRepository->countByStatus( 'confirmed' ),
			'cancelled_appointments' => $this->appointmentRepository->countByStatus( 'cancelled' ),
			'total_providers'        => $this->providerRepository->count(),
			'total_services'         => $this->serviceRepository->count(),
		);

		// Derniers rendez-vous
		$recentAppointments = $this->appointmentRepository->findRecent( 5 );

		// Afficher la vue
		$this->renderView(
			'dashboard',
			array(
				'stats'              => $stats,
				'recentAppointments' => $recentAppointments,
			)
		);
	}

	/**
	 * Affiche la vue des rendez-vous
	 */
	public function renderAppointments(): void {
		// Logique pour afficher la liste des rendez-vous
		$status  = $_GET['status'] ?? 'all';
		$paged   = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
		$perPage = 20;

		$appointments = $this->appointmentRepository->findAllPaginated( $status, $paged, $perPage );
		$totalItems   = $this->appointmentRepository->countByStatus( $status === 'all' ? null : $status );

		$this->renderView(
			'appointments/list',
			array(
				'appointments'  => $appointments,
				'currentStatus' => $status,
				'pagination'    => array(
					'current' => $paged,
					'total'   => ceil( $totalItems / $perPage ),
					'base'    => admin_url( 'admin.php?page=calendrier-rdv-appointments' ),
				),
			)
		);
	}

	/**
	 * Affiche la vue des prestataires
	 */
	public function renderProviders(): void {
		// Logique pour afficher la liste des prestataires
		$paged   = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
		$perPage = 20;

		$providers  = $this->providerRepository->findAllPaginated( $paged, $perPage );
		$totalItems = $this->providerRepository->count();

		$this->renderView(
			'providers/list',
			array(
				'providers'  => $providers,
				'pagination' => array(
					'current' => $paged,
					'total'   => ceil( $totalItems / $perPage ),
					'base'    => admin_url( 'admin.php?page=calendrier-rdv-providers' ),
				),
			)
		);
	}

	/**
	 * Affiche la vue des services
	 */
	public function renderServices(): void {
		// Logique pour afficher la liste des services
		$paged   = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
		$perPage = 20;

		$services   = $this->serviceRepository->findAllPaginated( $paged, $perPage );
		$totalItems = $this->serviceRepository->count();

		$this->renderView(
			'services/list',
			array(
				'services'   => $services,
				'pagination' => array(
					'current' => $paged,
					'total'   => ceil( $totalItems / $perPage ),
					'base'    => admin_url( 'admin.php?page=calendrier-rdv-services' ),
				),
			)
		);
	}

	/**
	 * Affiche la vue des paramètres
	 */
	public function renderSettings(): void {
		// Enregistrement des paramètres
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'cal_rdv_save_settings' ) ) {
			// Traiter la sauvegarde des paramètres
			$settings = array(
				'time_slot_duration'  => (int) ( $_POST['time_slot_duration'] ?? 30 ),
				'min_advance_booking' => (int) ( $_POST['min_advance_booking'] ?? 2 ),
				'max_advance_booking' => (int) ( $_POST['max_advance_booking'] ?? 30 ),
				'work_days'           => isset( $_POST['work_days'] ) ? array_map( 'intval', $_POST['work_days'] ) : array( 1, 2, 3, 4, 5 ),
				'work_hours_start'    => sanitize_text_field( $_POST['work_hours_start'] ?? '09:00' ),
				'work_hours_end'      => sanitize_text_field( $_POST['work_hours_end'] ?? '18:00' ),
				'email_notifications' => isset( $_POST['email_notifications'] ) ? 1 : 0,
				'email_from'          => sanitize_email( $_POST['email_from'] ?? get_bloginfo( 'admin_email' ) ),
				'email_from_name'     => sanitize_text_field( $_POST['email_from_name'] ?? get_bloginfo( 'name' ) ),
			);

			update_option( 'cal_rdv_settings', $settings );

			add_settings_error(
				'cal_rdv_settings',
				'settings_updated',
				'Les paramètres ont été enregistrés avec succès.',
				'success'
			);
		}

		$settings = get_option(
			'cal_rdv_settings',
			array(
				'time_slot_duration'  => 30,
				'min_advance_booking' => 2,
				'max_advance_booking' => 30,
				'work_days'           => array( 1, 2, 3, 4, 5 ),
				'work_hours_start'    => '09:00',
				'work_hours_end'      => '18:00',
				'email_notifications' => 1,
				'email_from'          => get_bloginfo( 'admin_email' ),
				'email_from_name'     => get_bloginfo( 'name' ),
			)
		);

		$this->renderView(
			'settings/form',
			array(
				'settings'   => $settings,
				'daysOfWeek' => array(
					0 => 'Dimanche',
					1 => 'Lundi',
					2 => 'Mardi',
					3 => 'Mercredi',
					4 => 'Jeudi',
					5 => 'Vendredi',
					6 => 'Samedi',
				),
			)
		);
	}

	/**
	 * Gère la suppression d'un rendez-vous
	 *
	 * @param int $id ID du rendez-vous
	 */
	private function handleDeleteAppointment( int $id ): void {
		try {
			$appointment = $this->appointmentRepository->findById( $id );

			if ( ! $appointment ) {
				throw new \Exception( 'Rendez-vous introuvable.' );
			}

			// Envoyer une notification d'annulation
			if ( $appointment->getStatus() !== 'cancelled' ) {
				$this->notificationService->sendAppointmentCancellation(
					$appointment,
					'Votre rendez-vous a été annulé par l\'administrateur.'
				);
			}

			// Supprimer le rendez-vous
			$this->appointmentRepository->delete( $id );

			// Rediriger avec un message de succès
			wp_redirect( admin_url( 'admin.php?page=calendrier-rdv-appointments&deleted=1' ) );
			exit;

		} catch ( \Exception $e ) {
			wp_die( $e->getMessage() );
		}
	}

	/**
	 * Gère la confirmation d'un rendez-vous
	 *
	 * @param int $id ID du rendez-vous
	 */
	private function handleConfirmAppointment( int $id ): void {
		try {
			$appointment = $this->appointmentRepository->findById( $id );

			if ( ! $appointment ) {
				throw new \Exception( 'Rendez-vous introuvable.' );
			}

			// Mettre à jour le statut
			$appointment->setStatus( 'confirmed' );
			$this->appointmentRepository->save( $appointment );

			// Envoyer une notification de confirmation
			$this->notificationService->sendAppointmentConfirmation( $appointment );

			// Rediriger avec un message de succès
			wp_redirect( admin_url( 'admin.php?page=calendrier-rdv-appointments&confirmed=1' ) );
			exit;

		} catch ( \Exception $e ) {
			wp_die( $e->getMessage() );
		}
	}

	/**
	 * Gère l'annulation d'un rendez-vous
	 *
	 * @param int $id ID du rendez-vous
	 */
	private function handleCancelAppointment( int $id ): void {
		try {
			$appointment = $this->appointmentRepository->findById( $id );

			if ( ! $appointment ) {
				throw new \Exception( 'Rendez-vous introuvable.' );
			}

			// Mettre à jour le statut
			$appointment->setStatus( 'cancelled' );
			$this->appointmentRepository->save( $appointment );

			// Envoyer une notification d'annulation
			$this->notificationService->sendAppointmentCancellation(
				$appointment,
				'Votre rendez-vous a été annulé.'
			);

			// Rediriger avec un message de succès
			wp_redirect( admin_url( 'admin.php?page=calendrier-rdv-appointments&cancelled=1' ) );
			exit;

		} catch ( \Exception $e ) {
			wp_die( $e->getMessage() );
		}
	}

	/**
	 * Affiche une vue
	 *
	 * @param string $view Nom de la vue
	 * @param array  $data Données à passer à la vue
	 */
	private function renderView( string $view, array $data = array() ): void {
		$viewFile = $this->viewsPath . $view . '.php';

		if ( ! file_exists( $viewFile ) ) {
			wp_die( sprintf( 'Vue non trouvée : %s', $viewFile ) );
		}

		// Extraire les données pour les rendre disponibles dans la vue
		extract( $data );

		// Démarrer la mise en mémoire tampon
		ob_start();

		// Inclure l'en-tête
		include $this->viewsPath . 'partials/header.php';

		// Inclure la vue
		include $viewFile;

		// Inclure le pied de page
		include $this->viewsPath . 'partials/footer.php';

		// Afficher le contenu mis en mémoire tampon
		echo ob_get_clean();
	}
}
