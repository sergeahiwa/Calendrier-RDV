<?php
/**
 * Tableau de bord prestataire
 *
 * @package Calendrier_RDV
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Affiche le tableau de bord personnalisé pour les prestataires
 */
function calendrier_rdv_provider_dashboard() {
    // Vérifier que l'utilisateur est bien un prestataire
    if (!current_user_can('service_provider') && !current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.', 'calendrier-rdv'));
    }
    
    // Récupérer l'ID du prestataire actuel
    $user_id = get_current_user_id();
    $provider_id = calendrier_rdv_get_provider_id_by_user($user_id);
    
    if (!$provider_id && !current_user_can('manage_options')) {
        wp_die(__('Vous n\'êtes pas enregistré comme prestataire.', 'calendrier-rdv'));
    }
    
    // Pour les administrateurs, permettre de voir tous les prestataires
    $is_admin = current_user_can('manage_options');
    
    // Gérer les onglets
    $tabs = [
        'appointments' => __('Mes rendez-vous', 'calendrier-rdv'),
        'schedule'     => __('Mon planning', 'calendrier-rdv'),
        'services'     => __('Mes services', 'calendrier-rdv'),
        'profile'      => __('Mon profil', 'calendrier-rdv'),
    ];
    
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'appointments';
    
    if (!array_key_exists($current_tab, $tabs)) {
        $current_tab = 'appointments';
    }
    
    // En-tête de la page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Tableau de bord prestataire', 'calendrier-rdv'); ?></h1>
        
        <nav class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_id => $tab_name) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=calendrier-rdv-provider&tab=' . $tab_id)); ?>" 
                   class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab_name); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="tab-content">
            <?php
            switch ($current_tab) {
                case 'appointments':
                    provider_appointments_tab($provider_id, $is_admin);
                    break;
                    
                case 'schedule':
                    provider_schedule_tab($provider_id, $is_admin);
                    break;
                    
                case 'services':
                    provider_services_tab($provider_id, $is_admin);
                    break;
                    
                case 'profile':
                    provider_profile_tab($provider_id, $is_admin);
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Affiche l'onglet des rendez-vous du prestataire
 * 
 * @param int $provider_id ID du prestataire
 * @param bool $is_admin Est-ce un admin
 */
function provider_appointments_tab($provider_id, $is_admin) {
    // Statut du filtre
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'upcoming';
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d');
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d', strtotime('+30 days'));
    
    // Récupérer les rendez-vous
    $appointments = calendrier_rdv_get_provider_appointments($provider_id, [
        'status' => $status,
        'date_from' => $date_from,
        'date_to' => $date_to,
    ]);
    
    // Afficher les filtres
    ?>
    <div class="appointments-filters" style="margin: 20px 0;">
        <form method="get">
            <input type="hidden" name="page" value="calendrier-rdv-provider">
            <input type="hidden" name="tab" value="appointments">
            
            <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 15px;">
                <div>
                    <label for="status"><?php echo esc_html__('Statut :', 'calendrier-rdv'); ?></label>
                    <select name="status" id="status">
                        <option value="upcoming" <?php selected($status, 'upcoming'); ?>><?php echo esc_html__('À venir', 'calendrier-rdv'); ?></option>
                        <option value="past" <?php selected($status, 'past'); ?>><?php echo esc_html__('Passés', 'calendrier-rdv'); ?></option>
                        <option value="all" <?php selected($status, 'all'); ?>><?php echo esc_html__('Tous', 'calendrier-rdv'); ?></option>
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
                
                <div>
                    <button type="submit" class="button"><?php echo esc_html__('Filtrer', 'calendrier-rdv'); ?></button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (empty($appointments)) : ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('Aucun rendez-vous trouvé pour cette période.', 'calendrier-rdv'); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Date', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Heure', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Client', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Service', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Statut', 'calendrier-rdv'); ?></th>
                    <th><?php echo esc_html__('Actions', 'calendrier-rdv'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment) : ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($appointment->start_date))); ?></td>
                        <td>
                            <?php 
                            echo esc_html(date_i18n(get_option('time_format'), strtotime($appointment->start_date)));
                            echo ' - ';
                            echo esc_html(date_i18n(get_option('time_format'), strtotime($appointment->end_date)));
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($appointment->customer_id) {
                                $customer = calendrier_rdv_get_customer($appointment->customer_id);
                                if ($customer) {
                                    echo esc_html($customer->name);
                                    echo '<div class="row-actions">';
                                    echo '<span class="email">' . esc_html($customer->email) . '</span>';
                                    echo '</div>';
                                } else {
                                    echo esc_html__('Client inconnu', 'calendrier-rdv');
                                }
                            } else {
                                echo esc_html__('Aucun client', 'calendrier-rdv');
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $service = calendrier_rdv_get_service($appointment->service_id);
                            echo $service ? esc_html($service->name) : esc_html__('Service inconnu', 'calendrier-rdv');
                            ?>
                        </td>
                        <td>
                            <?php 
                            $status_labels = [
                                'pending'   => __('En attente', 'calendrier-rdv'),
                                'confirmed' => __('Confirmé', 'calendrier-rdv'),
                                'cancelled' => __('Annulé', 'calendrier-rdv'),
                                'completed' => __('Terminé', 'calendrier-rdv'),
                                'no-show'   => __('Absent', 'calendrier-rdv'),
                            ];
                            
                            $status_class = [
                                'pending'   => 'notice-warning',
                                'confirmed' => 'notice-success',
                                'cancelled' => 'notice-error',
                                'completed' => 'notice-success',
                                'no-show'   => 'notice-error',
                            ];
                            
                            $status_label = isset($status_labels[$appointment->status]) ? $status_labels[$appointment->status] : $appointment->status;
                            $status_class = isset($status_class[$appointment->status]) ? $status_class[$appointment->status] : '';
                            
                            echo '<span class="status-badge ' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';
                            ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=calendrier-rdv-appointments&action=view&id=' . $appointment->id)); ?>">
                                        <?php echo esc_html__('Voir', 'calendrier-rdv'); ?>
                                    </a> | 
                                </span>
                                
                                <?php if ($appointment->status === 'pending') : ?>
                                    <span class="confirm">
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=calendrier-rdv-appointments&action=confirm&id=' . $appointment->id), 'confirm_appointment_' . $appointment->id)); ?>">
                                            <?php echo esc_html__('Confirmer', 'calendrier-rdv'); ?>
                                        </a> | 
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (in_array($appointment->status, ['pending', 'confirmed'])) : ?>
                                    <span class="cancel">
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=calendrier-rdv-appointments&action=cancel&id=' . $appointment->id), 'cancel_appointment_' . $appointment->id)); ?>" 
                                           onclick="return confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir annuler ce rendez-vous ?', 'calendrier-rdv')); ?>');">
                                            <?php echo esc_html__('Annuler', 'calendrier-rdv'); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}

/**
 * Affiche l'onglet du planning du prestataire
 * 
 * @param int $provider_id ID du prestataire
 * @param bool $is_admin Est-ce un admin
 */
function provider_schedule_tab($provider_id, $is_admin) {
    // Cette fonction affiche un calendrier permettant au prestataire de voir et de modifier ses disponibilités
    ?>
    <div class="notice notice-info">
        <p><?php echo esc_html__('Utilisez le calendrier ci-dessous pour gérer vos disponibilités.', 'calendrier-rdv'); ?></p>
    </div>
    
    <div id="calendar-container" style="margin-top: 20px;">
        <!-- Le calendrier sera injecté ici par FullCalendar -->
    </div>
    <?php
    
    // Enqueue FullCalendar scripts et styles
    wp_enqueue_style('fullcalendar', CAL_RDV_PLUGIN_URL . 'assets/css/fullcalendar.min.css');
    wp_enqueue_script('fullcalendar', CAL_RDV_PLUGIN_URL . 'assets/js/fullcalendar.min.js', ['jquery'], '', true);
    
    // Script pour initialiser le calendrier
    $schedule_data = calendrier_rdv_get_provider_schedule($provider_id);
    $current_date = date('Y-m-d');
    
    // AJAX nonce
    $nonce = wp_create_nonce('calendrier_rdv_schedule_nonce');
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        var calendarEl = document.getElementById('calendar-container');
        
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            initialDate: '<?php echo esc_js($current_date); ?>',
            editable: true,
            selectable: true,
            businessHours: true,
            dayMaxEvents: true,
            events: <?php echo json_encode($schedule_data['events']); ?>,
            businessHours: <?php echo json_encode($schedule_data['business_hours']); ?>,
            select: function(info) {
                // Logique pour ajouter une disponibilité
                if (confirm('<?php echo esc_js(__('Ajouter une disponibilité du ', 'calendrier-rdv')); ?>' + info.startStr + ' <?php echo esc_js(__('au', 'calendrier-rdv')); ?> ' + info.endStr + '?')) {
                    // Envoyer la requête AJAX pour ajouter
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'calendrier_rdv_add_availability',
                            provider_id: <?php echo intval($provider_id); ?>,
                            start: info.startStr,
                            end: info.endStr,
                            nonce: '<?php echo esc_js($nonce); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                calendar.addEvent({
                                    id: response.data.id,
                                    title: '<?php echo esc_js(__('Disponible', 'calendrier-rdv')); ?>',
                                    start: info.startStr,
                                    end: info.endStr,
                                    backgroundColor: '#28a745',
                                    borderColor: '#28a745'
                                });
                            } else {
                                alert(response.data.message);
                            }
                        }
                    });
                }
                calendar.unselect();
            },
            eventClick: function(info) {
                // Logique pour supprimer une disponibilité
                if (confirm('<?php echo esc_js(__('Supprimer cette disponibilité ?', 'calendrier-rdv')); ?>')) {
                    // Envoyer la requête AJAX pour supprimer
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'calendrier_rdv_remove_availability',
                            availability_id: info.event.id,
                            provider_id: <?php echo intval($provider_id); ?>,
                            nonce: '<?php echo esc_js($nonce); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                info.event.remove();
                            } else {
                                alert(response.data.message);
                            }
                        }
                    });
                }
            }
        });
        
        calendar.render();
    });
    </script>
    <?php
}

/**
 * Affiche l'onglet des services du prestataire
 * 
 * @param int $provider_id ID du prestataire
 * @param bool $is_admin Est-ce un admin
 */
function provider_services_tab($provider_id, $is_admin) {
    // Récupérer les services du prestataire
    $services = calendrier_rdv_get_provider_services($provider_id);
    
    // Si admin, on peut assigner des services au prestataire
    if ($is_admin) {
        // Logique pour assigner des services
        if (isset($_POST['assign_services']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'assign_services_' . $provider_id)) {
            $assigned_services = isset($_POST['services']) ? array_map('intval', $_POST['services']) : [];
            calendrier_rdv_update_provider_services($provider_id, $assigned_services);
            
            echo '<div class="notice notice-success"><p>' . esc_html__('Services mis à jour avec succès.', 'calendrier-rdv') . '</p></div>';
            
            // Actualiser la liste des services
            $services = calendrier_rdv_get_provider_services($provider_id);
        }
        
        // Récupérer tous les services disponibles
        $all_services = calendrier_rdv_get_services();
        
        // Formulaire d'assignation de services
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('assign_services_' . $provider_id); ?>
            
            <h3><?php echo esc_html__('Assigner des services au prestataire', 'calendrier-rdv'); ?></h3>
            
            <div class="services-list" style="margin: 20px 0;">
                <?php foreach ($all_services as $service) : ?>
                    <div class="service-item" style="margin-bottom: 10px;">
                        <label>
                            <input type="checkbox" name="services[]" value="<?php echo esc_attr($service->id); ?>"
                                <?php checked(in_array($service->id, array_column($services, 'id'))); ?>>
                            <?php echo esc_html($service->name); ?>
                            (<?php echo esc_html(calendrier_rdv_format_duration($service->duration)); ?> -
                            <?php echo esc_html(calendrier_rdv_format_price($service->price)); ?>)
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" name="assign_services" class="button button-primary">
                <?php echo esc_html__('Enregistrer les services', 'calendrier-rdv'); ?>
            </button>
        </form>
        <?php
    }
    
    // Afficher les services actuels
    ?>
    <h3><?php echo esc_html__('Mes services', 'calendrier-rdv'); ?></h3>
    
    <?php if (empty($services)) : ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('Aucun service n\'est actuellement assigné à ce prestataire.', 'calendrier-rdv'); ?></p>
        </div>
    <?php else : ?>
        <div class="services-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php foreach ($services as $service) : ?>
                <div class="service-card" style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background-color: #fff;">
                    <h4 style="margin-top: 0;"><?php echo esc_html($service->name); ?></h4>
                    
                    <p><?php echo wp_kses_post($service->description); ?></p>
                    
                    <div class="service-details" style="margin-top: 10px;">
                        <div><strong><?php echo esc_html__('Durée:', 'calendrier-rdv'); ?></strong> <?php echo esc_html(calendrier_rdv_format_duration($service->duration)); ?></div>
                        <div><strong><?php echo esc_html__('Prix:', 'calendrier-rdv'); ?></strong> <?php echo esc_html(calendrier_rdv_format_price($service->price)); ?></div>
                        <div><strong><?php echo esc_html__('Capacité:', 'calendrier-rdv'); ?></strong> <?php echo esc_html($service->capacity); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
}

/**
 * Affiche l'onglet du profil du prestataire
 * 
 * @param int $provider_id ID du prestataire
 * @param bool $is_admin Est-ce un admin
 */
function provider_profile_tab($provider_id, $is_admin) {
    // Récupérer les informations du prestataire
    $provider = calendrier_rdv_get_provider($provider_id);
    
    // Traitement du formulaire
    if (isset($_POST['update_profile']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'update_provider_profile_' . $provider_id)) {
        // Récupérer et sanitizer les données
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $bio = isset($_POST['bio']) ? wp_kses_post($_POST['bio']) : '';
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '#3498db';
        
        // Mettre à jour le profil
        $result = calendrier_rdv_update_provider_profile($provider_id, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'bio' => $bio,
            'color' => $color,
        ]);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Profil mis à jour avec succès.', 'calendrier-rdv') . '</p></div>';
            
            // Recharger les informations du prestataire
            $provider = calendrier_rdv_get_provider($provider_id);
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Erreur lors de la mise à jour du profil.', 'calendrier-rdv') . '</p></div>';
        }
    }
    
    // Afficher le formulaire
    ?>
    <form method="post" action="">
        <?php wp_nonce_field('update_provider_profile_' . $provider_id); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="name"><?php echo esc_html__('Nom complet', 'calendrier-rdv'); ?></label></th>
                <td>
                    <input type="text" name="name" id="name" value="<?php echo esc_attr($provider->name); ?>" class="regular-text" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="email"><?php echo esc_html__('Email', 'calendrier-rdv'); ?></label></th>
                <td>
                    <input type="email" name="email" id="email" value="<?php echo esc_attr($provider->email); ?>" class="regular-text" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="phone"><?php echo esc_html__('Téléphone', 'calendrier-rdv'); ?></label></th>
                <td>
                    <input type="text" name="phone" id="phone" value="<?php echo esc_attr($provider->phone); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="bio"><?php echo esc_html__('Biographie', 'calendrier-rdv'); ?></label></th>
                <td>
                    <?php
                    wp_editor($provider->bio, 'bio', [
                        'textarea_name' => 'bio',
                        'textarea_rows' => 10,
                        'media_buttons' => false,
                    ]);
                    ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="color"><?php echo esc_html__('Couleur', 'calendrier-rdv'); ?></label></th>
                <td>
                    <input type="color" name="color" id="color" value="<?php echo esc_attr($provider->color); ?>">
                    <p class="description"><?php echo esc_html__('Cette couleur sera utilisée pour identifier vos rendez-vous dans le calendrier.', 'calendrier-rdv'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="update_profile" class="button button-primary">
                <?php echo esc_html__('Mettre à jour le profil', 'calendrier-rdv'); ?>
            </button>
        </p>
    </form>
    <?php
}

/**
 * Récupère l'ID du prestataire associé à un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return int|false ID du prestataire ou false
 */
function calendrier_rdv_get_provider_id_by_user($user_id) {
    global $wpdb;
    
    $provider_table = $wpdb->prefix . 'cal_rdv_providers';
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $provider_table WHERE user_id = %d",
        $user_id
    ));
}
