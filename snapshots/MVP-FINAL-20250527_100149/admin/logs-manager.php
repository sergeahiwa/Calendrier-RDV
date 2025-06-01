<?php
/**
 * Page d'administration pour la gestion des logs
 *
 * @package Calendrier_RDV
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Affiche la page de gestion des logs
 */
function calendrier_rdv_admin_logs_page() {
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.', 'calendrier-rdv'));
    }
    
    // Récupérer les filtres
    $action = isset($_GET['action_filter']) ? sanitize_text_field($_GET['action_filter']) : '';
    $object_type = isset($_GET['object_type_filter']) ? sanitize_text_field($_GET['object_type_filter']) : '';
    $user_id = isset($_GET['user_id_filter']) ? intval($_GET['user_id_filter']) : 0;
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    
    // Purger les anciens logs (action)
    if (isset($_POST['purge_logs']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'purge_logs')) {
        $days = isset($_POST['purge_days']) ? intval($_POST['purge_days']) : 90;
        $count = calendrier_rdv_logger()->purge_old_logs($days);
        
        // Message de succès
        add_settings_error(
            'calendrier_rdv_logs',
            'logs_purged',
            sprintf(
                __('%1$d logs plus anciens que %2$d jours ont été supprimés.', 'calendrier-rdv'),
                $count,
                $days
            ),
            'success'
        );
    }
    
    // Récupérer les logs avec les filtres
    $logs_data = calendrier_rdv_logger()->get_logs([
        'action' => $action,
        'object_type' => $object_type,
        'user_id' => $user_id,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'per_page' => $per_page,
        'page' => $page,
    ]);
    
    $logs = $logs_data['logs'];
    $total_items = $logs_data['total'];
    $total_pages = $logs_data['total_pages'];
    
    // Options pour les filtres
    $action_types = [
        '' => __('Toutes les actions', 'calendrier-rdv'),
        'create' => __('Création', 'calendrier-rdv'),
        'update' => __('Modification', 'calendrier-rdv'),
        'delete' => __('Suppression', 'calendrier-rdv'),
        'cancel' => __('Annulation', 'calendrier-rdv'),
        'payment' => __('Paiement', 'calendrier-rdv'),
        'settings' => __('Paramètres', 'calendrier-rdv'),
        'login' => __('Connexion', 'calendrier-rdv'),
    ];
    
    $object_types = [
        '' => __('Tous les objets', 'calendrier-rdv'),
        'appointment' => __('Rendez-vous', 'calendrier-rdv'),
        'service' => __('Service', 'calendrier-rdv'),
        'provider' => __('Prestataire', 'calendrier-rdv'),
        'booking' => __('Réservation', 'calendrier-rdv'),
        'plugin' => __('Plugin', 'calendrier-rdv'),
    ];
    
    // Récupérer tous les utilisateurs avec accès au module
    $users_query = new WP_User_Query([
        'role__in' => ['administrator', 'calendar_manager', 'service_provider'],
        'orderby' => 'display_name',
        'order' => 'ASC',
    ]);
    
    $users = $users_query->get_results();
    $users_options = [0 => __('Tous les utilisateurs', 'calendrier-rdv')];
    
    foreach ($users as $user) {
        $users_options[$user->ID] = $user->display_name;
    }
    
    // Afficher la page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Journal d\'activité', 'calendrier-rdv'); ?></h1>
        
        <?php settings_errors('calendrier_rdv_logs'); ?>
        
        <div class="card">
            <h2><?php echo esc_html__('Filtres', 'calendrier-rdv'); ?></h2>
            <form method="get">
                <input type="hidden" name="page" value="calendrier-rdv-logs">
                
                <div class="logs-filters" style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="action_filter"><?php echo esc_html__('Action :', 'calendrier-rdv'); ?></label>
                        <select name="action_filter" id="action_filter">
                            <?php foreach ($action_types as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($action, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="object_type_filter"><?php echo esc_html__('Type d\'objet :', 'calendrier-rdv'); ?></label>
                        <select name="object_type_filter" id="object_type_filter">
                            <?php foreach ($object_types as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($object_type, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="user_id_filter"><?php echo esc_html__('Utilisateur :', 'calendrier-rdv'); ?></label>
                        <select name="user_id_filter" id="user_id_filter">
                            <?php foreach ($users_options as $id => $name) : ?>
                                <option value="<?php echo esc_attr($id); ?>" <?php selected($user_id, $id); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from"><?php echo esc_html__('Du :', 'calendrier-rdv'); ?></label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                    </div>
                    
                    <div>
                        <label for="date_to"><?php echo esc_html__('Au :', 'calendrier-rdv'); ?></label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                    </div>
                    
                    <div style="align-self: flex-end;">
                        <button type="submit" class="button"><?php echo esc_html__('Filtrer', 'calendrier-rdv'); ?></button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=calendrier-rdv-logs')); ?>" class="button-link">
                            <?php echo esc_html__('Réinitialiser', 'calendrier-rdv'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="post" style="display: inline-block;">
                    <?php wp_nonce_field('purge_logs'); ?>
                    <label for="purge_days">
                        <?php echo esc_html__('Supprimer les logs plus anciens que', 'calendrier-rdv'); ?>
                    </label>
                    <input type="number" id="purge_days" name="purge_days" value="90" min="1" max="365" step="1" style="width: 60px;">
                    <?php echo esc_html__('jours', 'calendrier-rdv'); ?>
                    <button type="submit" name="purge_logs" class="button" onclick="return confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir supprimer les anciens logs ? Cette action est irréversible.', 'calendrier-rdv')); ?>');">
                        <?php echo esc_html__('Purger', 'calendrier-rdv'); ?>
                    </button>
                </form>
            </div>
            
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        _n('%s élément', '%s éléments', $total_items, 'calendrier-rdv'),
                        number_format_i18n($total_items)
                    ); ?>
                </span>
                
                <?php if ($total_pages > 1) : ?>
                    <span class="pagination-links">
                        <?php
                        $current_url = add_query_arg([
                            'action_filter' => $action,
                            'object_type_filter' => $object_type,
                            'user_id_filter' => $user_id,
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                        ]);
                        
                        // Premier
                        if ($page > 1) {
                            printf(
                                '<a class="first-page button" href="%s"><span aria-hidden="true">%s</span></a>',
                                esc_url(remove_query_arg('paged', $current_url)),
                                '«'
                            );
                        } else {
                            printf(
                                '<span class="first-page button disabled"><span aria-hidden="true">%s</span></span>',
                                '«'
                            );
                        }
                        
                        // Précédent
                        if ($page > 1) {
                            printf(
                                '<a class="prev-page button" href="%s"><span aria-hidden="true">%s</span></a>',
                                esc_url(add_query_arg('paged', max(1, $page - 1), $current_url)),
                                '‹'
                            );
                        } else {
                            printf(
                                '<span class="prev-page button disabled"><span aria-hidden="true">%s</span></span>',
                                '‹'
                            );
                        }
                        
                        printf(
                            '<span class="paging-input">%s / <span class="total-pages">%s</span></span>',
                            $page,
                            $total_pages
                        );
                        
                        // Suivant
                        if ($page < $total_pages) {
                            printf(
                                '<a class="next-page button" href="%s"><span aria-hidden="true">%s</span></a>',
                                esc_url(add_query_arg('paged', min($total_pages, $page + 1), $current_url)),
                                '›'
                            );
                        } else {
                            printf(
                                '<span class="next-page button disabled"><span aria-hidden="true">%s</span></span>',
                                '›'
                            );
                        }
                        
                        // Dernier
                        if ($page < $total_pages) {
                            printf(
                                '<a class="last-page button" href="%s"><span aria-hidden="true">%s</span></a>',
                                esc_url(add_query_arg('paged', $total_pages, $current_url)),
                                '»'
                            );
                        } else {
                            printf(
                                '<span class="last-page button disabled"><span aria-hidden="true">%s</span></span>',
                                '»'
                            );
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Date', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Utilisateur', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Action', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Type', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('ID', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Message', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Détails', 'calendrier-rdv'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)) : ?>
                    <tr>
                        <td colspan="7"><?php echo esc_html__('Aucun log trouvé.', 'calendrier-rdv'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?>
                            </td>
                            <td>
                                <?php if ($log->user_id) : ?>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $log->user_id)); ?>">
                                        <?php echo esc_html($log->user_name); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html($log->user_name); ?>
                                <?php endif; ?>
                                <div class="row-actions">
                                    <span><?php echo esc_html($log->ip_address); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $action_label = '';
                                switch ($log->action) {
                                    case 'create':
                                        $action_label = __('Création', 'calendrier-rdv');
                                        break;
                                    case 'update':
                                        $action_label = __('Modification', 'calendrier-rdv');
                                        break;
                                    case 'delete':
                                        $action_label = __('Suppression', 'calendrier-rdv');
                                        break;
                                    case 'cancel':
                                        $action_label = __('Annulation', 'calendrier-rdv');
                                        break;
                                    case 'payment':
                                        $action_label = __('Paiement', 'calendrier-rdv');
                                        break;
                                    case 'settings':
                                        $action_label = __('Paramètres', 'calendrier-rdv');
                                        break;
                                    case 'login':
                                        $action_label = __('Connexion', 'calendrier-rdv');
                                        break;
                                    default:
                                        $action_label = ucfirst($log->action);
                                }
                                echo esc_html($action_label);
                                ?>
                            </td>
                            <td>
                                <?php
                                $object_label = '';
                                switch ($log->object_type) {
                                    case 'appointment':
                                        $object_label = __('Rendez-vous', 'calendrier-rdv');
                                        break;
                                    case 'service':
                                        $object_label = __('Service', 'calendrier-rdv');
                                        break;
                                    case 'provider':
                                        $object_label = __('Prestataire', 'calendrier-rdv');
                                        break;
                                    case 'booking':
                                        $object_label = __('Réservation', 'calendrier-rdv');
                                        break;
                                    case 'plugin':
                                        $object_label = __('Plugin', 'calendrier-rdv');
                                        break;
                                    default:
                                        $object_label = ucfirst($log->object_type);
                                }
                                echo esc_html($object_label);
                                ?>
                            </td>
                            <td>
                                <?php if ($log->object_id) : ?>
                                    <?php echo esc_html($log->object_id); ?>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($log->message); ?></td>
                            <td>
                                <?php if (!empty($log->context)) : ?>
                                    <button type="button" class="button button-small toggle-details" data-log-id="<?php echo esc_attr($log->id); ?>">
                                        <?php echo esc_html__('Voir', 'calendrier-rdv'); ?>
                                    </button>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($log->context)) : ?>
                            <tr class="log-details log-details-<?php echo esc_attr($log->id); ?>" style="display: none;">
                                <td colspan="7">
                                    <div class="log-context">
                                        <pre><?php echo esc_html(json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th><?php echo esc_html__('Date', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Utilisateur', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Action', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Type', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('ID', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Message', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Détails', 'calendrier-rdv'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Boutons pour afficher/masquer les détails
        $('.toggle-details').on('click', function() {
            var logId = $(this).data('log-id');
            $('.log-details-' + logId).toggle();
            
            if ($(this).text() === '<?php echo esc_js(__('Voir', 'calendrier-rdv')); ?>') {
                $(this).text('<?php echo esc_js(__('Masquer', 'calendrier-rdv')); ?>');
            } else {
                $(this).text('<?php echo esc_js(__('Voir', 'calendrier-rdv')); ?>');
            }
        });
    });
    </script>
    <?php
}
