# ✅ Plan d'Action Post-Audit - Système de Réservation de Rendez-vous

## 📌 Contexte
Ce plan d'action fait suite à l'audit réalisé sur l'environnement de développement local du MVP (Docker + WordPress). Les objectifs sont : renforcer la sécurité, améliorer l'architecture, optimiser l'emailing et préparer une base stable pour la montée en production.

---

## 🔐 SÉCURITÉ

- [x] Ajouter une **protection CSRF** sur tous les formulaires (nonce + vérification dans traitement-rdv.php)
  - ✅ Implémenté la vérification du nonce avec `check_ajax_referer()`
  - ✅ Ajout de la vérification des permissions utilisateur avec `current_user_can()`
  - ✅ Sécurisation de l'accès direct au fichier

- [x] Renforcer la **validation des entrées** (email, texte, dates, etc.)
  - ✅ Validation du format des dates
  - ✅ Vérification de la longueur maximale pour le titre (255 caractères)
  - ✅ Vérification que la date de fin est postérieure à la date de début
  - ✅ Nettoyage des entrées avec `sanitize_text_field()` et `wp_unslash()`
  - ✅ Messages d'erreur traduisibles

- [x] Implémenter une **limitation de taux (rate limiting)** pour éviter les abus
  - ✅ Création de la classe `CalRdv_Rate_Limiter`
  - ✅ Configuration personnalisable par action
  - ✅ Gestion des bannissements temporaires
  - ✅ Intégration avec le système de cache WordPress
  - ✅ Détection d'IP derrière un proxy
  - ✅ Désactivation en mode débogage
  - ✅ Journalisation des bannissements

---

## ✉️ MAILERSEND

- [x] Créer un **template HTML pour les emails** (confirmation de rendez-vous)
  - ✅ Template responsive et moderne
  - ✅ Variables personnalisables
  - ✅ Version texte automatique
  - ✅ Support multilingue
  - ✅ Lien d'annulation conditionnel

- [x] Modifier traitement-rdv.php pour **utiliser ce template HTML** à l'envoi
  - ✅ Intégration avec le système de templates
  - ✅ Gestion des données dynamiques
  - ✅ Validation des entrées

- [x] Ajouter une **gestion des erreurs** : log en base ou fichier si échec d'envoi MailerSend
  - ✅ Journalisation des erreurs
  - ✅ Codes d'erreur personnalisés
  - ✅ Détails complets des erreurs

- [x] Préparer un système de **file d'attente** pour les envois échoués
  - ✅ Création de la table `rdv_email_failures`
  - ✅ Classe de gestion de file d'attente
  - ✅ Tâches planifiées pour le retry automatique
  - ✅ Gestion des erreurs temporaires
  - ✅ Backoff exponentiel pour les tentatives
  - ✅ Nettoyage automatique des anciennes entrées

---

## 🧱 BASE DE DONNÉES

- [x] Implémenter une **migration de schéma** (ajout/modif de colonnes sans perte)
  - ✅ Tables créées : `rdv_appointments`, `rdv_providers`, `rdv_services`, `rdv_email_failures`
  - ✅ Données de test ajoutées (2 prestataires, 2 services, 2 rendez-vous)
  - ✅ Vérification des contraintes et relations
- [x] Ajouter une **table `rdv_email_failures`** pour les tentatives échouées (si activé)

---

## 🌐 INTÉGRATION WORDPRESS

- [x] Renommer le dossier `rdv-handler/` en `modules/rdv/` (convention plugins WordPress)
- [x] Ajouter des **hooks personnalisés** (`do_action()`, `apply_filters()`) pour extensibilité future

---

## 📚 DOCUMENTATION

- [x] Mettre à jour la **documentation technique** (README, commentaires de code)
  - ✅ Mise à jour du CHANGELOG.md
  - ✅ Documentation complète du système de file d'attente
  - ✅ Commentaires de code mis à jour

- [x] Ajouter des **exemples d'utilisation** pour chaque fonctionnalité
  - ✅ Exemples dans la documentation technique
  - ✅ Snippets de code commentés

- [x] Documenter les **hooks et filtres** disponibles
  - ✅ Liste des hooks dans la documentation
  - ✅ Exemples d'utilisation des filtres

---

## ⚙️ PERFORMANCE

- [ ] Ajouter un **système de cache** sur les requêtes fréquentes du calendrier
- [ ] Implémenter la **pagination** dans les appels AJAX si nécessaire
- [ ] Charger les **scripts JS en mode différé** (`defer`, `async`) si possible

---

## 🧪 QUALITÉ & TESTS

- [ ] Ajouter des **tests unitaires** pour les fonctions critiques
  - [ ] Tests CRUD pour les rendez-vous
  - [ ] Tests de validation des données
  - [ ] Tests des règles métier (disponibilité, chevauchement)
- [ ] Créer un **scénario de test manuel** documenté pour valider le flux complet
  - [ ] Création/modification/suppression de rendez-vous
  - [ ] Gestion des conflits de réservation
  - [ ] Notification des parties prenantes

---

## 🧭 SUIVI ET CHECKLIST AUTOMATISÉE

Chaque tâche sera cochée automatiquement au fur et à mesure de son exécution par Cascade.

---

## 🏁 Prochaine étape immédiate :

> 🔄 Commencer par la sécurité : CSRF + validation serveur côté `traitement-rdv.php`

---

_Fichier généré pour automatisation dans Cascade – SAN Digital Solutions / Dev local MVP Calendrier_
