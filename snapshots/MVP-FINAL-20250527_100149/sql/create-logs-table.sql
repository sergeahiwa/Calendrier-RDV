-- Script de création de la table des logs
CREATE TABLE IF NOT EXISTS `{prefix}cal_rdv_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT 0,
  `action` varchar(50) NOT NULL,
  `object_type` varchar(50) NOT NULL,
  `object_id` bigint(20) UNSIGNED DEFAULT 0,
  `message` text NOT NULL,
  `context` longtext,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `object_type` (`object_type`),
  KEY `object_id` (`object_id`),
  KEY `created_at` (`created_at`)
) {charset_collate};
