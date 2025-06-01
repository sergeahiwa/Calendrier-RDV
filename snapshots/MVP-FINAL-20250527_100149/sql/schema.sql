-- =============================================
-- Fichier : schema.sql
-- Description : Schéma de la base de données pour le plugin Calendrier RDV
-- Auteur : SAN Digital Solutions
-- Version : 2.0.0
-- =============================================

-- Désactiver la vérification des clés étrangères temporairement
SET FOREIGN_KEY_CHECKS = 0;

-- Supprimer les tables existantes si nécessaire
DROP TABLE IF EXISTS `{PREFIX}rdv_appointments`;
DROP TABLE IF EXISTS `{PREFIX}rdv_services`;
DROP TABLE IF EXISTS `{PREFIX}rdv_providers`;
DROP TABLE IF EXISTS `{PREFIX}rdv_availability`;
DROP TABLE IF EXISTS `{PREFIX}rdv_holidays`;
DROP TABLE IF EXISTS `{PREFIX}rdv_settings`;
DROP TABLE IF EXISTS `{PREFIX}rdv_logs`;

-- Table des prestataires
CREATE TABLE IF NOT EXISTS `{PREFIX}rdv_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID de l\'utilisateur WordPress',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `bio` text,
  `specialty` varchar(255) DEFAULT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'Europe/Paris',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_provider_user` FOREIGN KEY (`user_id`) REFERENCES `{PREFIX}users` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des services
CREATE TABLE IF NOT EXISTS `{PREFIX}rdv_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `duration` int(11) NOT NULL DEFAULT '30' COMMENT 'Durée en minutes',
  `price` decimal(10,2) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3a87ad',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des rendez-vous
CREATE TABLE IF NOT EXISTS `{PREFIX}rdv_appointments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference` varchar(20) NOT NULL,
  `client_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID de l\'utilisateur WordPress',
  `client_first_name` varchar(100) NOT NULL,
  `client_last_name` varchar(100) NOT NULL,
  `client_email` varchar(255) NOT NULL,
  `client_phone` varchar(50) DEFAULT NULL,
  `provider_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'Europe/Paris',
  `status` enum('pending','confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'pending',
  `price` decimal(10,2) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('pending','partially_paid','paid','refunded','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `notes` text,
  `internal_notes` text COMMENT 'Notes internes non visibles par le client',
  `reminder_sent` tinyint(1) NOT NULL DEFAULT '0',
  `reminder_sent_at` datetime DEFAULT NULL,
  `cancellation_reason` text,
  `cancelled_by` enum('system','admin','provider','client') DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID de l\'utilisateur qui a créé le RDV',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `provider_id` (`provider_id`),
  KEY `service_id` (`service_id`),
  KEY `client_id` (`client_id`),
  KEY `client_email` (`client_email`),
  KEY `start_datetime` (`start_datetime`),
  KEY `end_datetime` (`end_datetime`),
  KEY `status` (`status`),
  KEY `payment_status` (`payment_status`),
  CONSTRAINT `fk_appointment_provider` FOREIGN KEY (`provider_id`) REFERENCES `{PREFIX}rdv_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_service` FOREIGN KEY (`service_id`) REFERENCES `{PREFIX}rdv_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des disponibilités
CREATE TABLE IF NOT EXISTS `{PREFIX}rdv_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) DEFAULT NULL COMMENT 'NULL pour les disponibilités globales',
  `service_id` int(11) DEFAULT NULL COMMENT 'NULL pour toutes les prestations',
  `day_of_week` tinyint(1) DEFAULT NULL COMMENT '0=dimanche, 1=lundi, ..., 6=samedi, NULL pour les dates spécifiques',
  `specific_date` date DEFAULT NULL COMMENT 'Date spécifique (au lieu du jour de la semaine)',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `max_capacity` int(11) DEFAULT '1' COMMENT 'Capacité maximale pour les créneaux groupés',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `provider_id` (`provider_id`),
  KEY `service_id` (`service_id`),
  KEY `day_of_week` (`day_of_week`),
  KEY `specific_date` (`specific_date`),
  CONSTRAINT `fk_availability_provider` FOREIGN KEY (`provider_id`) REFERENCES `{PREFIX}rdv_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_availability_service` FOREIGN KEY (`service_id`) REFERENCES `{PREFIX}rdv_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des jours fériés
CREATE TABLE IF NOT EXISTS `{PREFIX}rdv_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'Pour les congés sur plusieurs jours',
  `is_recurring` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 si le jour férié est annuel',
  `applies_to_all` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 si s\'applique à tous les prestataires',
  `provider_id` int(11) DEFAULT NULL COMMENT 'Si spécifique à un prestataire',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_provider` (`date`, `provider_id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `fk_holiday_provider` FOREIGN KEY (`provider_id`) REFERENCES `{PREFIX}rdv_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des paramètres
CREATE TABLE IF NOT EXISTS `{PREFIX}rdv_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `setting_type` enum('string','integer','float','boolean','json','serialized') NOT NULL DEFAULT 'string',
  `is_autoload` tinyint(1) NOT NULL DEFAULT '1',
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `setting_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des logs
CREATE TABLE IF NOT EXISTS `{PREFIX}rdv_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` enum('debug','info','notice','warning','error','critical','alert','emergency') NOT NULL,
  `message` text NOT NULL,
  `context` longtext,
  `source` varchar(100) DEFAULT NULL COMMENT 'Source du log (ex: appointments, payments, etc.)',
  `reference_id` varchar(100) DEFAULT NULL COMMENT 'ID de référence (ex: ID de rendez-vous, de paiement, etc.)',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `source` (`source`),
  KEY `reference_id` (`reference_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des métadonnées de rendez-vous
CREATE TABLE IF NOT EXISTS `{PREFIX}rdv_appointment_meta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint(20) UNSIGNED NOT NULL,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` longtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`meta_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `meta_key` (`meta_key`),
  CONSTRAINT `fk_appointment_meta` FOREIGN KEY (`appointment_id`) REFERENCES `{PREFIX}rdv_appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données initiales - Horaires d'ouverture par défaut (lundi au vendredi)
INSERT IGNORE INTO `{PREFIX}rdv_availability` (`day_of_week`, `start_time`, `end_time`, `is_available`, `max_capacity`) VALUES
(1, '09:00:00', '12:00:00', 1, 1),  -- Lundi matin
(1, '14:00:00', '18:00:00', 1, 1),  -- Lundi après-midi
(2, '09:00:00', '12:00:00', 1, 1),  -- Mardi matin
(2, '14:00:00', '18:00:00', 1, 1),  -- Mardi après-midi
(3, '09:00:00', '12:00:00', 1, 1),  -- Mercredi matin
(3, '14:00:00', '18:00:00', 1, 1),  -- Mercredi après-midi
(4, '09:00:00', '12:00:00', 1, 1),  -- Jeudi matin
(4, '14:00:00', '18:00:00', 1, 1),  -- Jeudi après-midi
(5, '09:00:00', '12:00:00', 1, 1),  -- Vendredi matin
(5, '14:00:00', '17:00:00', 1, 1); -- Vendredi après-midi

-- Jours fériés français
INSERT IGNORE INTO `{PREFIX}rdv_holidays` (`name`, `date`, `is_recurring`, `applies_to_all`) VALUES
('Jour de l\'An', '2025-01-01', 1, 1),
('Lundi de Pâques', '2025-04-21', 1, 1),
('Fête du Travail', '2025-05-01', 1, 1),
('Victoire 1945', '2025-05-08', 1, 1),
('Jeudi de l\'Ascension', '2025-05-29', 1, 1),
('Lundi de Pentecôte', '2025-06-09', 1, 1),
('Fête Nationale', '2025-07-14', 1, 1),
('Assomption', '2025-08-15', 1, 1),
('Toussaint', '2025-11-01', 1, 1),
('Armistice 1918', '2025-11-11', 1, 1),
('Noël', '2025-12-25', 1, 1);

-- Paramètres par défaut
INSERT IGNORE INTO `{PREFIX}rdv_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `is_autoload`, `description`) VALUES
('appointment_duration', '30', 'appointments', 'integer', 1, 'Durée par défaut d\'un rendez-vous en minutes'),
('appointment_buffer_before', '0', 'appointments', 'integer', 1, 'Temps de battement avant un rendez-vous (en minutes)'),
('appointment_buffer_after', '15', 'appointments', 'integer', 1, 'Temps de battement après un rendez-vous (en minutes)'),
('reminder_time', '24', 'notifications', 'integer', 1, 'Délai avant le RDV pour l\'envoi du rappel (en heures)'),
('confirmation_required', '1', 'appointments', 'boolean', 1, 'Nécessite une confirmation manuelle des rendez-vous'),
('cancellation_allowed', '1', 'appointments', 'boolean', 1, 'Autoriser l\'annulation des rendez-vous'),
('cancellation_min_hours', '24', 'appointments', 'integer', 1, 'Délai minimum avant l\'annulation (en heures)'),
('timezone', 'Europe/Paris', 'general', 'string', 1, 'Fuseau horaire par défaut'),
('date_format', 'd/m/Y', 'general', 'string', 1, 'Format de date'),
('time_format', 'H:i', 'general', 'string', 1, 'Format d\'heure'),
('week_starts_on', '1', 'general', 'integer', 1, 'Premier jour de la semaine (0=dimanche, 1=lundi)'),
('email_from_name', '', 'email', 'string', 1, 'Nom de l\'expéditeur des emails'),
('email_from_address', '', 'email', 'string', 1, 'Adresse email de l\'expéditeur'),
('email_admin_notifications', '1', 'email', 'boolean', 1, 'Activer les notifications administrateur'),
('email_admin_address', '', 'email', 'string', 1, 'Adresse email de l\'administrateur pour les notifications'),
('terms_page_id', '0', 'general', 'integer', 1, 'Page des conditions générales'),
('privacy_page_id', '0', 'general', 'integer', 1, 'Page de politique de confidentialité');

-- Réactiver la vérification des clés étrangères
SET FOREIGN_KEY_CHECKS = 1;
