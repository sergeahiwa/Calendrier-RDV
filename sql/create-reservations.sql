-- ================================
-- Fichier : create-reservations.sql
-- Rôle    : Création de la table des réservations
-- Auteur  : SAN Digital Solutions
-- ================================

CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL COMMENT 'Nom du client',
  `email` VARCHAR(150) NOT NULL COMMENT 'Adresse email du client',
  `telephone` VARCHAR(20) NULL COMMENT 'Numéro de téléphone (facultatif)',
  `prestation` VARCHAR(100) NOT NULL COMMENT 'Type de service choisi',
  `date_rdv` DATE NOT NULL COMMENT 'Date du rendez-vous',
  `heure_rdv` TIME NOT NULL COMMENT 'Heure du rendez-vous',
  `prestataire` VARCHAR(100) NULL COMMENT 'Nom du prestataire sélectionné',
  `statut` ENUM('en_attente','confirmé','annulé') NOT NULL DEFAULT 'en_attente'
    COMMENT 'Statut de la réservation',
  `commentaire` TEXT NULL COMMENT 'Commentaire ou précision client',
  `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    COMMENT 'Date et heure de la création'
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
