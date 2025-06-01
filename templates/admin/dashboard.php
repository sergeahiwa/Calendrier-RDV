<?php
/**
 * Template pour le tableau de bord d'administration
 *
 * @package     CalendrierRdv\Templates\Admin
 * @since       1.0.0
 */

// Vérification de sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les statistiques
$stats = [
    'today' => [
        'label' => __('Aujourd\'hui', 'calendrier-rdv'),
        'value' => 0,
        'icon'  => 'dashicons-calendar-alt',
    ],
    'upcoming' => [
        'label' => __('À venir (7j)', 'calendrier-rdv'),
        'value' => 0,
        'icon'  => 'dashicons-calendar',
    ],
    'pending' => [
        'label' => __('En attente', 'calendrier-rdv'),
        'value' => 0,
        'icon'  => 'dashicons-clock',
    ],
    'providers' => [
        'label' => __('Prestataires', 'calendrier-rdv'),
        'value' => 0,
        'icon'  => 'dashicons-businessperson',
    ],
];

// Ici, vous pouvez remplacer ces valeurs par des appels à votre modèle de données
// Par exemple : $stats['today']['value'] = $appointments_model->count_today_appointments();
?>

<div class="wrap cal-rdv-wrap">
    <div class="cal-rdv-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="description">
            <?php esc_html_e('Bienvenue dans le tableau de bord de gestion des rendez-vous.', 'calendrier-rdv'); ?>
        </p>
    </div>

    <!-- Cartes de statistiques -->
    <div class="cal-rdv-stats">
        <?php foreach ($stats as $key => $stat) : ?>
            <div class="cal-rdv-stat-card">
                <h3>
                    <span class="dashicons <?php echo esc_attr($stat['icon']); ?>"></span>
                    <?php echo esc_html($stat['label']); ?>
                </h3>
                <div class="stat-value"><?php echo esc_html($stat['value']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Prochains rendez-vous -->
    <div class="cal-rdv-card">
        <h2><?php esc_html_e('Prochains rendez-vous', 'calendrier-rdv'); ?></h2>
        
        <table class="cal-rdv-table widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Client', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Prestataire', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Service', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Statut', 'calendrier-rdv'); ?></th>
                    <th><?php esc_html_e('Actions', 'calendrier-rdv'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="text-center">
                        <?php esc_html_e('Aucun rendez-vous à venir.', 'calendrier-rdv'); ?>
                    </td>
                </tr>
                <!-- Les données des rendez-vous seront chargées ici dynamiquement -->
            </tbody>
        </table>
    </div>

    <div class="cal-rdv-row">
        <!-- Calendrier -->
        <div class="cal-rdv-col-2-3">
            <div class="cal-rdv-card">
                <h2><?php esc_html_e('Calendrier', 'calendrier-rdv'); ?></h2>
                <div id="cal-rdv-calendar">
                    <!-- Le calendrier sera chargé ici par JavaScript -->
                    <p class="text-center">
                        <?php esc_html_e('Chargement du calendrier...', 'calendrier-rdv'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Activité récente -->
        <div class="cal-rdv-col-1-3">
            <div class="cal-rdv-card">
                <h2><?php esc_html_e('Activité récente', 'calendrier-rdv'); ?></h2>
                <ul class="cal-rdv-activity-feed">
                    <li class="no-activity">
                        <?php esc_html_e('Aucune activité récente.', 'calendrier-rdv'); ?>
                    </li>
                    <!-- Les activités récentes seront chargées ici dynamiquement -->
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modale pour les détails du rendez-vous -->
<div id="cal-rdv-appointment-modal" class="cal-rdv-modal" style="display: none;">
    <div class="cal-rdv-modal-content">
        <div class="cal-rdv-modal-header">
            <h3><?php esc_html_e('Détails du rendez-vous', 'calendrier-rdv'); ?></h3>
            <button type="button" class="cal-rdv-modal-close">&times;</button>
        </div>
        <div class="cal-rdv-modal-body">
            <!-- Le contenu sera chargé dynamiquement -->
        </div>
        <div class="cal-rdv-modal-footer">
            <button type="button" class="button button-secondary cal-rdv-modal-close">
                <?php esc_html_e('Fermer', 'calendrier-rdv'); ?>
            </button>
            <div class="cal-rdv-modal-actions">
                <!-- Les boutons d'action seront ajoutés ici dynamiquement -->
            </div>
        </div>
    </div>
</div>
