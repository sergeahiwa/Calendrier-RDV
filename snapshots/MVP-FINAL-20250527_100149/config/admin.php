<?php
/**
 * Configuration de l'administration pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

return [
    // Paramètres des menus et sous-menus
    'menus' => [
        'main' => [
            'page_title' => 'Calendrier RDV',
            'menu_title' => 'Calendrier RDV',
            'capability' => 'manage_options',
            'menu_slug' => 'calendrier-rdv',
            'icon_url' => 'dashicons-calendar-alt',
            'position' => 30,
        ],
        'submenus' => [
            'dashboard' => [
                'parent_slug' => 'calendrier-rdv',
                'page_title' => 'Tableau de bord',
                'menu_title' => 'Tableau de bord',
                'capability' => 'manage_options',
                'menu_slug' => 'calendrier-rdv',
            ],
            'appointments' => [
                'parent_slug' => 'calendrier-rdv',
                'page_title' => 'Rendez-vous',
                'menu_title' => 'Rendez-vous',
                'capability' => 'manage_options',
                'menu_slug' => 'calendrier-rdv-appointments',
            ],
            'providers' => [
                'parent_slug' => 'calendrier-rdv',
                'page_title' => 'Prestataires',
                'menu_title' => 'Prestataires',
                'capability' => 'manage_options',
                'menu_slug' => 'calendrier-rdv-providers',
            ],
            'services' => [
                'parent_slug' => 'calendrier-rdv',
                'page_title' => 'Services',
                'menu_title' => 'Services',
                'capability' => 'manage_options',
                'menu_slug' => 'calendrier-rdv-services',
            ],
            'settings' => [
                'parent_slug' => 'calendrier-rdv',
                'page_title' => 'Paramètres',
                'menu_title' => 'Paramètres',
                'capability' => 'manage_options',
                'menu_slug' => 'calendrier-rdv-settings',
            ],
        ],
    ],
    
    // Paramètres des colonnes de la liste des rendez-vous
    'appointment_columns' => [
        'cb' => '<input type="checkbox" />',
        'title' => 'Titre',
        'customer' => 'Client',
        'service' => 'Service',
        'provider' => 'Prestataire',
        'date' => 'Date et heure',
        'status' => 'Statut',
        'actions' => 'Actions',
    ],
    
    // Paramètres des colonnes de la liste des prestataires
    'provider_columns' => [
        'cb' => '<input type="checkbox" />',
        'name' => 'Nom',
        'email' => 'Email',
        'services' => 'Services',
        'appointments' => 'Rendez-vous',
        'status' => 'Statut',
        'actions' => 'Actions',
    ],
    
    // Paramètres des colonnes de la liste des services
    'service_columns' => [
        'cb' => '<input type="checkbox" />',
        'name' => 'Nom',
        'duration' => 'Durée',
        'price' => 'Prix',
        'providers' => 'Prestataires',
        'appointments' => 'Rendez-vous',
        'status' => 'Statut',
        'actions' => 'Actions',
    ],
    
    // Paramètres de pagination
    'pagination' => [
        'per_page' => 20,
        'show_all' => false,
        'end_size' => 2,
        'mid_size' => 2,
    ],
    
    // Paramètres des onglets des paramètres
    'settings_tabs' => [
        'general' => [
            'title' => 'Général',
            'sections' => [
                'general' => [
                    'title' => 'Paramètres généraux',
                    'fields' => [
                        'timezone' => [
                            'title' => 'Fuseau horaire',
                            'type' => 'select',
                            'options' => 'timezone_choices',
                            'default' => 'Europe/Paris',
                            'description' => 'Définissez le fuseau horaire par défaut pour les rendez-vous.',
                        ],
                        'date_format' => [
                            'title' => 'Format de date',
                            'type' => 'text',
                            'default' => 'd/m/Y',
                            'description' => 'Définissez le format d\'affichage des dates.',
                        ],
                        'time_format' => [
                            'title' => 'Format d\'heure',
                            'type' => 'text',
                            'default' => 'H:i',
                            'description' => 'Définissez le format d\'affichage des heures.',
                        ],
                        'start_of_week' => [
                            'title' => 'Premier jour de la semaine',
                            'type' => 'select',
                            'options' => [
                                0 => 'Dimanche',
                                1 => 'Lundi',
                            ],
                            'default' => 1,
                            'description' => 'Définissez le premier jour de la semaine dans le calendrier.',
                        ],
                    ],
                ],
            ],
        ],
        'appointments' => [
            'title' => 'Rendez-vous',
            'sections' => [
                'general' => [
                    'title' => 'Paramètres des rendez-vous',
                    'fields' => [
                        'min_advance_booking' => [
                            'title' => 'Délai minimum de réservation (heures)',
                            'type' => 'number',
                            'default' => 2,
                            'min' => 0,
                            'step' => 1,
                            'description' => 'Délai minimum avant lequel un rendez-vous peut être pris (en heures).',
                        ],
                        'max_advance_booking' => [
                            'title' => 'Délai maximum de réservation (jours)',
                            'type' => 'number',
                            'default' => 90,
                            'min' => 1,
                            'step' => 1,
                            'description' => 'Délai maximum avant lequel un rendez-vous peut être pris (en jours).',
                        ],
                        'default_duration' => [
                            'title' => 'Durée par défaut (minutes)',
                            'type' => 'number',
                            'default' => 30,
                            'min' => 5,
                            'step' => 5,
                            'description' => 'Durée par défaut d\'un rendez-vous (en minutes).',
                        ],
                        'cancellation_policy' => [
                            'title' => 'Délai d\'annulation (heures)',
                            'type' => 'number',
                            'default' => 24,
                            'min' => 0,
                            'step' => 1,
                            'description' => 'Délai minimum avant lequel un rendez-vous peut être annulé (en heures).',
                        ],
                    ],
                ],
            ],
        ],
        'notifications' => [
            'title' => 'Notifications',
            'sections' => [
                'admin' => [
                    'title' => 'Notifications administrateur',
                    'fields' => [
                        'admin_notification' => [
                            'title' => 'Activer les notifications administrateur',
                            'type' => 'checkbox',
                            'default' => true,
                            'description' => 'Envoyer une notification par email à l\'administrateur lors de la prise de rendez-vous.',
                        ],
                        'admin_email' => [
                            'title' => 'Email de notification',
                            'type' => 'email',
                            'default' => get_bloginfo('admin_email'),
                            'description' => 'Adresse email qui recevra les notifications.',
                        ],
                    ],
                ],
                'customer' => [
                    'title' => 'Notifications client',
                    'fields' => [
                        'customer_notification' => [
                            'title' => 'Activer les notifications client',
                            'type' => 'checkbox',
                            'default' => true,
                            'description' => 'Envoyer une notification par email au client lors de la prise de rendez-vous.',
                        ],
                        'reminder_enabled' => [
                            'title' => 'Activer les rappels',
                            'type' => 'checkbox',
                            'default' => true,
                            'description' => 'Envoyer un rappel par email avant le rendez-vous.',
                        ],
                        'reminder_time' => [
                            'title' => 'Délai du rappel (heures)',
                            'type' => 'number',
                            'default' => 24,
                            'min' => 1,
                            'step' => 1,
                            'description' => 'Heures avant le rendez-vous pour envoyer le rappel.',
                        ],
                    ],
                ],
            ],
        ],
        'display' => [
            'title' => 'Affichage',
            'sections' => [
                'calendar' => [
                    'title' => 'Options du calendrier',
                    'fields' => [
                        'show_week_numbers' => [
                            'title' => 'Afficher les numéros de semaine',
                            'type' => 'checkbox',
                            'default' => true,
                        ],
                        'show_past_days' => [
                            'title' => 'Afficher les jours passés',
                            'type' => 'checkbox',
                            'default' => false,
                        ],
                        'show_service_description' => [
                            'title' => 'Afficher la description des services',
                            'type' => 'checkbox',
                            'default' => true,
                        ],
                        'show_provider_photo' => [
                            'title' => 'Afficher la photo du prestataire',
                            'type' => 'checkbox',
                            'default' => true,
                        ],
                    ],
                ],
            ],
        ],
    ],
    
    // Paramètres des champs de formulaire
    'form_fields' => [
        'appointment' => [
            'title' => [
                'label' => 'Titre',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Ex: Consultation initiale',
            ],
            'customer_name' => [
                'label' => 'Nom du client',
                'type' => 'text',
                'required' => true,
            ],
            'customer_email' => [
                'label' => 'Email du client',
                'type' => 'email',
                'required' => true,
            ],
            'customer_phone' => [
                'label' => 'Téléphone',
                'type' => 'tel',
                'required' => false,
            ],
            'service_id' => [
                'label' => 'Service',
                'type' => 'select',
                'required' => true,
                'options' => 'get_services_list',
            ],
            'provider_id' => [
                'label' => 'Prestataire',
                'type' => 'select',
                'required' => true,
                'options' => 'get_providers_list',
            ],
            'start_date' => [
                'label' => 'Date de début',
                'type' => 'datetime-local',
                'required' => true,
            ],
            'end_date' => [
                'label' => 'Date de fin',
                'type' => 'datetime-local',
                'required' => true,
            ],
            'status' => [
                'label' => 'Statut',
                'type' => 'select',
                'options' => [
                    'pending' => 'En attente',
                    'confirmed' => 'Confirmé',
                    'cancelled' => 'Annulé',
                    'completed' => 'Terminé',
                ],
                'default' => 'pending',
            ],
            'notes' => [
                'label' => 'Notes',
                'type' => 'textarea',
                'required' => false,
                'rows' => 3,
            ],
        ],
    ],
];
