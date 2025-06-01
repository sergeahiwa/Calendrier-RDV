<?php
/**
 * Définition des constantes du plugin
 */

// Version du plugin
define('CAL_RDV_VERSION', '1.2.0');

// Chemins du plugin
define('CAL_RDV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAL_RDV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CAL_RDV_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('CAL_RDV_PLUGIN_FILE', __FILE__);

// Mode débogage
define('CAL_RDV_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

// Version de la base de données
define('CAL_RDV_DB_VERSION', '1.2.0');

// Préfixe des tables
define('CAL_RDV_TABLE_PREFIX', 'rdv_');

// Types de rendez-vous
define('CAL_RDV_APPOINTMENT_STATUS_PENDING', 'pending');
define('CAL_RDV_APPOINTMENT_STATUS_CONFIRMED', 'confirmed');
define('CAL_RDV_APPOINTMENT_STATUS_CANCELLED', 'cancelled');
define('CAL_RDV_APPOINTMENT_STATUS_COMPLETED', 'completed');

// Actions AJAX
define('CAL_RDV_ACTION_CREATE_APPOINTMENT', 'calendrier_rdv_create_appointment');
define('CAL_RDV_ACTION_GET_SERVICES', 'calendrier_rdv_get_services');
define('CAL_RDV_ACTION_GET_PROVIDERS', 'calendrier_rdv_get_providers');
define('CAL_RDV_ACTION_GET_APPOINTMENTS', 'calendrier_rdv_get_appointments');

// Cache
define('CAL_RDV_CACHE_DURATION', 3600); // 1 heure en secondes

// Messages
define('CAL_RDV_MSG_SUCCESS', 'Votre rendez-vous a été réservé avec succès !');
define('CAL_RDV_MSG_ERROR', 'Une erreur est survenue lors de la réservation.');
define('CAL_RDV_MSG_NO_SERVICES', 'Aucun service disponible.');
define('CAL_RDV_MSG_NO_PROVIDERS', 'Aucun prestataire disponible.');
define('CAL_RDV_MSG_INVALID_DATE', 'Date invalide.');
define('CAL_RDV_MSG_INVALID_TIME', 'Horaire invalide.');
define('CAL_RDV_MSG_REQUIRED_FIELDS', 'Veuillez remplir tous les champs requis.');
