# Journal d'activités Cascade

Toutes les actions critiques, sauvegardes, modifications et restaurations seront tracées ici.

---

## [27/05/2025 - 04:13] Initialisation du système de sauvegarde
- ✅ Création du dossier backup/
- ✅ Génération du fichier log.md

## [27/05/2025 - 04:19] Activation de la stratégie de sécurisation
- ✅ Mode “sécurisé” activé : toute commande à impact nécessite confirmation explicite (oui/non)
- ✅ Interdiction de relancer une installation complète sans sauvegarde préalable ET validation utilisateur
- ✅ Toute action critique sera consignée dans ce journal
- ✅ En cas d’erreur critique ou rollback, la cause, les fichiers affectés et les étapes de restauration seront documentés ici
- 🎯 Objectif : ne plus jamais repartir de zéro, même en cas de dysfonctionnement majeur

## [27/05/2025 - 04:31] Sauvegarde complète du projet validée et exécutée
- ✅ Copie du dossier `wp-content` dans `backup/`
- ✅ Copie du fichier `docker-compose.yml` dans `backup/`
- ✅ Dump complet de la base MySQL `wordpress` dans `backup/`
- 🔒 Point de restauration prêt avant toute modification critique

## [27/05/2025 - 04:36] Migration de schéma et création de la table `rdv_email_failures`
- 🔎 Vérification de la structure de la base : table absente
- 🛠️ Création de la table `rdv_email_failures` (id, destinataire, sujet, body, message d'erreur, tentatives, timestamps)
- ✅ Table présente et prête pour la gestion des erreurs d'envoi email

## [27/05/2025 - 04:43] Évolution sécurité : traçage automatique des échecs critiques d'emails
- 🔒 Ajout d'un insert dans `rdv_email_failures` lors d'un échec définitif d'envoi (NotificationManager.php)
- 📋 Conformité totale avec le plan d'audit et la stratégie de traçabilité

## [27/05/2025 - 04:46] Ajout d'un test unitaire de traçabilité
- ✅ Création de `tests/unit/NotificationManagerTest.php` pour vérifier l'insertion dans `rdv_email_failures`
- 🧪 Robustesse et conformité du système garanties par test automatisé

## [27/05/2025 - 07:40] Migration de la base de données
- 🗃️ Création des tables manquantes dans la base de données :
  - `rdv_appointments` (rendez-vous)
  - `rdv_providers` (prestataires)
  - `rdv_services` (services)
  - `rdv_email_failures` (déjà existante)
- ✅ Structure validée et conforme au schéma de référence
- 📊 Ajout des données de test :
  - **Prestataires** : Jean Dupont (Médecine Générale) et Marie Martin (Dermatologie)
  - **Services** : Consultation Générale (30min, 25€) et Dermatologie (45min, 50€)
  - **Rendez-vous** :
    - RDV-001 : Alice Dupont avec Jean Dupont (01/06/2025 09:00-09:30)
    - RDV-002 : Bob Martin avec Marie Martin (01/06/2025 10:00-10:45)
- 🔄 Base de données prête pour les tests et l'intégration
