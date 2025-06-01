-- ================================
-- Fichier : create-admin-users.sql
-- Rôle    : Création de la table des utilisateurs admin
-- Auteur  : SAN Digital Solutions
-- ================================

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL COMMENT 'Nom d\'utilisateur pour la connexion',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hash du mot de passe (bcrypt)',
  `nom_complet` VARCHAR(100) NOT NULL COMMENT 'Nom complet de l\'administrateur',
  `email` VARCHAR(150) NOT NULL COMMENT 'Adresse email de l\'administrateur',
  `role` ENUM('admin', 'gestionnaire') NOT NULL DEFAULT 'gestionnaire' COMMENT 'Rôle de l\'utilisateur',
  `est_actif` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Compte actif (1) ou désactivé (0)',
  `derniere_connexion` DATETIME NULL COMMENT 'Date et heure de la dernière connexion',
  `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création du compte',
  UNIQUE KEY `username_unique` (`username`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion d'un administrateur par défaut
-- Mot de passe par défaut: SanAdmin2025! (à changer après la première connexion)
INSERT INTO `admin_users` (
  `username`,
  `password_hash`,
  `nom_complet`,
  `email`,
  `role`
) VALUES (
  'admin',
  '$2y$10$MKLDj/PJ/fYBzI9b2o3qSuysY7OwGFUmSzrIucD4LHKkZyUC4qh4G',
  'Administrateur SAN',
  'admin@sandigitalsolutions.com',
  'admin'
) ON DUPLICATE KEY UPDATE `username` = `username`;
