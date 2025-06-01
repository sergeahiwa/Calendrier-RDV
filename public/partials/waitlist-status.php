<?php
/**
 * Template partiel pour afficher le statut de la liste d'attente d'un utilisateur
 *
 * Variables disponibles :
 * - $waitlist_entries: Tableau des entrées en liste d'attente de l'utilisateur
 * - $atts: Attributs du shortcode
 */
?>

<div class="calendrier-rdv-waitlist-status">
    <h2 class="calendrier-rdv-title"><?php echo esc_html__('Mes listes d\'attente', 'calendrier-rdv'); ?></h2>
    
    <?php if (empty($waitlist_entries)) : ?>
        <div class="calendrier-rdv-notice notice notice-info">
            <p><?php echo esc_html__('Vous n\'êtes actuellement sur aucune liste d\'attente.', 'calendrier-rdv'); ?></p>
        </div>
    <?php else : ?>
        <div class="calendrier-rdv-waitlist-entries">
            <div class="calendrier-rdv-table-responsive">
                <table class="calendrier-rdv-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Service', 'calendrier-rdv'); ?></th>
                            <th><?php echo esc_html__('Prestataire', 'calendrier-rdv'); ?></th>
                            <th><?php echo esc_html__('Date', 'calendrier-rdv'); ?></th>
                            <th><?php echo esc_html__('Créneau', 'calendrier-rdv'); ?></th>
                            <th><?php echo esc_html__('Position', 'calendrier-rdv'); ?></th>
                            <th><?php echo esc_html__('Statut', 'calendrier-rdv'); ?></th>
                            <th><?php echo esc_html__('Actions', 'calendrier-rdv'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($waitlist_entries as $entry) : 
                            $entry_date = new DateTime($entry->date);
                            $now = new DateTime();
                            $is_past = $entry_date < $now;
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($entry->status) {
                                case 'waiting':
                                    $status_class = 'status-waiting';
                                    $status_text = __('En attente', 'calendrier-rdv');
                                    break;
                                case 'notified':
                                    $status_class = 'status-notified';
                                    $status_text = __('Notifié', 'calendrier-rdv');
                                    break;
                                case 'expired':
                                    $status_class = 'status-expired';
                                    $status_text = __('Expiré', 'calendrier-rdv');
                                    break;
                                case 'booked':
                                    $status_class = 'status-booked';
                                    $status_text = __('Réservé', 'calendrier-rdv');
                                    break;
                                default:
                                    $status_class = 'status-unknown';
                                    $status_text = ucfirst($entry->status);
                            }
                            
                            // Si l'entrée est passée et toujours en attente, on la marque comme expirée
                            if ($is_past && in_array($entry->status, ['waiting', 'notified'])) {
                                $status_class = 'status-expired';
                                $status_text = __('Expiré', 'calendrier-rdv');
                            }
                            ?>
                            <tr class="calendrier-rdv-waitlist-entry <?php echo esc_attr($status_class); ?>" data-entry-id="<?php echo esc_attr($entry->id); ?>">
                                <td><?php echo esc_html($entry->service_nom); ?></td>
                                <td><?php echo esc_html($entry->prestataire_nom); ?></td>
                                <td><?php echo esc_html($entry_date->format(get_option('date_format'))); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($entry->start_time))); ?></td>
                                <td>
                                    <?php if ($entry->position > 0) : ?>
                                        #<?php echo intval($entry->position); ?>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="calendrier-rdv-status-badge <?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (in_array($entry->status, ['waiting', 'notified'])) : ?>
                                        <button type="button" class="button button-small calendrier-rdv-leave-waitlist" 
                                                data-entry-id="<?php echo esc_attr($entry->id); ?>">
                                            <?php echo esc_html__('Quitter', 'calendrier-rdv'); ?>
                                        </button>
                                        
                                        <?php if ($entry->status === 'notified') : ?>
                                            <a href="#" class="button button-primary button-small calendrier-rdv-book-now" 
                                               data-service-id="<?php echo esc_attr($entry->service_id); ?>"
                                               data-prestataire-id="<?php echo esc_attr($entry->prestataire_id); ?>"
                                               data-date="<?php echo esc_attr($entry->date); ?>"
                                               data-time="<?php echo esc_attr($entry->start_time); ?>">
                                                <?php echo esc_html__('Réserver', 'calendrier-rdv'); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="calendrier-rdv-waitlist-actions">
                <p class="calendrier-rdv-notice notice notice-info">
                    <?php echo esc_html__('Vous serez notifié par email dès qu\'une place se libérera pour l\'un de ces créneaux.', 'calendrier-rdv'); ?>
                </p>
            </div>
        </div>
        
        <script type="text/template" id="tmpl-calendrier-rdv-waitlist-row">
            <tr class="calendrier-rdv-waitlist-entry status-{{ data.status }}" data-entry-id="{{ data.id }}">
                <td>{{ data.service_name }}</td>
                <td>{{ data.prestataire_name }}</td>
                <td>{{ data.date_formatted }}</td>
                <td>{{ data.time_formatted }}</td>
                <td>
                    <# if (data.position > 0) { #>
                        #{{ data.position }}
                    <# } else { #>
                        -
                    <# } #>
                </td>
                <td>
                    <span class="calendrier-rdv-status-badge status-{{ data.status }}">
                        {{ data.status_text }}
                    </span>
                </td>
                <td>
                    <# if (['waiting', 'notified'].includes(data.status)) { #>
                        <button type="button" class="button button-small calendrier-rdv-leave-waitlist" 
                                data-entry-id="{{ data.id }}">
                            <?php echo esc_js(__('Quitter', 'calendrier-rdv')); ?>
                        </button>
                        
                        <# if (data.status === 'notified') { #>
                            <a href="#" class="button button-primary button-small calendrier-rdv-book-now" 
                               data-service-id="{{ data.service_id }}"
                               data-prestataire-id="{{ data.prestataire_id }}"
                               data-date="{{ data.date }}"
                               data-time="{{ data.time }}">
                                <?php echo esc_js(__('Réserver', 'calendrier-rdv')); ?>
                            </a>
                        <# } #>
                    <# } #>
                </td>
            </tr>
        </script>
    <?php endif; ?>
</div>
