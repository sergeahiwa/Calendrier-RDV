<?php
/**
 * Fichier de traduction pour Calendrier RDV
 *
 * @package CalendrierRdv\Config
 */

return [
    // Textes généraux
    'general' => [
        'save_changes' => 'Enregistrer les modifications',
        'cancel' => 'Annuler',
        'delete' => 'Supprimer',
        'edit' => 'Modifier',
        'view' => 'Voir',
        'back' => 'Retour',
        'next' => 'Suivant',
        'previous' => 'Précédent',
        'search' => 'Rechercher',
        'filter' => 'Filtrer',
        'reset' => 'Réinitialiser',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cet élément ?',
        'no_items_found' => 'Aucun élément trouvé.',
        'items_found' => '%d élément(s) trouvé(s)',
        'select_option' => 'Sélectionner',
        'loading' => 'Chargement...',
        'saving' => 'Enregistrement...',
        'deleting' => 'Suppression...',
        'error' => 'Erreur',
        'success' => 'Succès',
        'warning' => 'Avertissement',
        'info' => 'Information',
    ],
    
    // Textes du tableau de bord
    'dashboard' => [
        'title' => 'Tableau de bord',
        'welcome' => 'Bienvenue dans Calendrier RDV',
        'stats' => [
            'appointments' => 'Rendez-vous',
            'pending' => 'En attente',
            'confirmed' => 'Confirmés',
            'providers' => 'Prestataires',
            'services' => 'Services',
        ],
        'upcoming_appointments' => 'Prochains rendez-vous',
        'view_all' => 'Voir tout',
        'no_upcoming_appointments' => 'Aucun rendez-vous à venir.',
        'schedule_appointment' => 'Planifier un rendez-vous',
        'quick_help' => 'Aide rapide',
        'system_status' => 'Statut du système',
        'version' => 'Version',
    ],
    
    // Textes des rendez-vous
    'appointments' => [
        'title' => 'Rendez-vous',
        'add_new' => 'Ajouter un rendez-vous',
        'edit' => 'Modifier le rendez-vous',
        'view' => 'Voir le rendez-vous',
        'delete' => 'Supprimer le rendez-vous',
        'confirm' => 'Confirmer le rendez-vous',
        'cancel' => 'Annuler le rendez-vous',
        'status' => [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'cancelled' => 'Annulé',
            'completed' => 'Terminé',
        ],
        'fields' => [
            'title' => 'Titre',
            'customer_name' => 'Nom du client',
            'customer_email' => 'Email du client',
            'customer_phone' => 'Téléphone',
            'service' => 'Service',
            'provider' => 'Prestataire',
            'start_date' => 'Date et heure de début',
            'end_date' => 'Date et heure de fin',
            'notes' => 'Notes',
        ],
        'no_appointments' => 'Aucun rendez-vous trouvé.',
        'appointment_added' => 'Le rendez-vous a été ajouté avec succès.',
        'appointment_updated' => 'Le rendez-vous a été mis à jour avec succès.',
        'appointment_deleted' => 'Le rendez-vous a été supprimé avec succès.',
        'appointment_confirmed' => 'Le rendez-vous a été confirmé avec succès.',
        'appointment_cancelled' => 'Le rendez-vous a été annulé avec succès.',
        'select_service_first' => 'Veuillez d\'abord sélectionner un service.',
        'select_provider_first' => 'Veuillez d\'abord sélectionner un prestataire.',
    ],
    
    // Textes des prestataires
    'providers' => [
        'title' => 'Prestataires',
        'add_new' => 'Ajouter un prestataire',
        'edit' => 'Modifier le prestataire',
        'view' => 'Voir le prestataire',
        'delete' => 'Supprimer le prestataire',
        'fields' => [
            'first_name' => 'Prénom',
            'last_name' => 'Nom',
            'email' => 'Email',
            'phone' => 'Téléphone',
            'bio' => 'Biographie',
            'services' => 'Services proposés',
            'avatar' => 'Photo de profil',
            'status' => 'Statut',
        ],
        'no_providers' => 'Aucun prestataire trouvé.',
        'provider_added' => 'Le prestataire a été ajouté avec succès.',
        'provider_updated' => 'Le prestataire a été mis à jour avec succès.',
        'provider_deleted' => 'Le prestataire a été supprimé avec succès.',
    ],
    
    // Textes des services
    'services' => [
        'title' => 'Services',
        'add_new' => 'Ajouter un service',
        'edit' => 'Modifier le service',
        'view' => 'Voir le service',
        'delete' => 'Supprimer le service',
        'fields' => [
            'name' => 'Nom',
            'description' => 'Description',
            'duration' => 'Durée (minutes)',
            'price' => 'Prix',
            'providers' => 'Prestataires',
            'color' => 'Couleur',
            'status' => 'Statut',
        ],
        'no_services' => 'Aucun service trouvé.',
        'service_added' => 'Le service a été ajouté avec succès.',
        'service_updated' => 'Le service a été mis à jour avec succès.',
        'service_deleted' => 'Le service a été supprimé avec succès.',
    ],
    
    // Textes des paramètres
    'settings' => [
        'title' => 'Paramètres',
        'save_changes' => 'Enregistrer les modifications',
        'changes_saved' => 'Les modifications ont été enregistrées avec succès.',
        'tabs' => [
            'general' => 'Général',
            'appointments' => 'Rendez-vous',
            'notifications' => 'Notifications',
            'display' => 'Affichage',
            'payments' => 'Paiements',
            'integrations' => 'Intégrations',
        ],
        'sections' => [
            'general' => 'Paramètres généraux',
            'business_hours' => 'Heures d\'ouverture',
            'admin_notifications' => 'Notifications administrateur',
            'customer_notifications' => 'Notifications client',
            'calendar' => 'Options du calendrier',
            'payment_gateways' => 'Moyens de paiement',
        ],
    ],
    
    // Textes des notifications
    'notifications' => [
        'appointment_booked' => [
            'subject' => 'Confirmation de votre rendez-vous du {appointment_date}',
            'message' => 'Bonjour {customer_name},\n\nVotre rendez-vous pour {service_name} avec {provider_name} a été confirmé pour le {appointment_date} à {appointment_time}.\n\nCordialement,\n{site_name}',
        ],
        'appointment_reminder' => [
            'subject' => 'Rappel : Votre rendez-vous du {appointment_date}',
            'message' => 'Bonjour {customer_name},\n\nCeci est un rappel pour votre rendez-vous de {service_name} avec {provider_name} prévu pour le {appointment_date} à {appointment_time}.\n\nCordialement,\n{site_name}',
        ],
        'appointment_cancelled' => [
            'subject' => 'Annulation de votre rendez-vous du {appointment_date}',
            'message' => 'Bonjour {customer_name},\n\nVotre rendez-vous pour {service_name} avec {provider_name} prévu pour le {appointment_date} à {appointment_time} a été annulé.\n\nCordialement,\n{site_name}',
        ],
    ],
];
