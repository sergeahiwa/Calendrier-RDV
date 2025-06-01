# 📘 PROJECT-GUIDELINES.md  
**Projet** : Calendrier de Rendez-vous - SAN Digital Solutions  
**Dernière mise à jour** : 26/05/2025  
**Version** : 1.3.0

> **Note importante :**
> Le MVP (Minimum Viable Product) a été entièrement livré, testé et documenté. Le projet est officiellement entré en **phase V1**. Toutes les évolutions et corrections sont désormais rattachées à la feuille de route V1.

---

## ⚠️ Problèmes identifiés (Phase 1)

### Structure
- **Conflit de fichiers principaux** : Presence de deux fichiers de plugin (`calendrier-rdv.php` et `reservation-rdv-plugin.php`)
- **Structure non standard** : Besoin de reorganisation selon les standards WordPress

### Sécurité
- **Requêtes SQL non sécurisées** : Utilisation directe de `$wpdb->query()` sans préparation
- **Vérifications de capacités incohérentes**
- **Manque de nonces pour les actions AJAX**

### Performance
- **Manque de mise en cache** : Requêtes fréquentes non optimisées
- **Chargement d'assets non conditionnel**
- **Requêtes de base de données non optimisées**

### Internationalisation
- **Chaînes non traduisibles**
- **Gestion des formats de date/heure insuffisante**

---

## ✅ Plan d'amélioration (Phase 1)

### 1. Réorganisation de la structure
- [ ] Supprimer `reservation-rdv-plugin.php` redondant
- [ ] Mettre en place l'autoloading PSR-4
- [ ] Réorganiser les dossiers selon les standards WordPress

### 2. Sécurité
- [ ] Implémenter `wpdb->prepare()` pour toutes les requêtes SQL
- [ ] Renforcer les vérifications de capacités
- [ ] Ajouter des nonces pour toutes les actions AJAX
- [ ] Mettre en place une validation stricte des entrées

### 3. Performance
- [ ] Mettre en cache les requêtes fréquentes
- [ ] Charger les assets de manière conditionnelle
- [ ] Optimiser les requêtes de base de données

### 4. Internationalisation
- [ ] S'assurer que toutes les chaînes sont traduisibles
- [ ] Ajouter la gestion des formats de date/heure localisés

---

## 📁 Organisation des fichiers (à mettre à jour)

```
calendrier-rdv/
├── admin/                    # Interface d'administration
│   ├── includes/            # Fonctions PHP (auth, menus, queries)
│   ├── css/                 # Styles admin
│   ├── js/                 # Scripts JS admin (FullCalendar, AJAX)
│   └── *.php               # Pages PHP admin
│
├── public/                 # Partie visible utilisateur
│   ├── formulaire.php       # Formulaire de prise de rendez-vous
│   ├── style.css            # CSS frontend
│   └── script.js            # JS frontend
│
├── rdv-handler/            # Traitement des données RDV
│   ├── traitement-rdv.php   # Traitement du formulaire
│   └── rappel-rdv.php       # Gestion des rappels par cron
│
├── includes/               # Configurations globales
│   ├── config.php          # Connexion BDD
│   └── email_config.php    # Clés API / MailerSend
│
├── cron/                   # Tâches automatisées (cron jobs)
│   └── rappel-cron.php
│
├── sql/                    # Scripts de création de tables
│   ├── create-admin-users.sql
│   └── create-reservations.sql
│
├── tests/                  # Tests manuels et automatisés
│   ├── unit/
│   ├── integration/
│   └── functional/
│
├── .vscode/                # Config déploiement SFTP
│   └── sftp.json
│
├── README.md               # Documentation générale
├── README_ADMIN_CALENDRIER.md # Documentation d'administration
└── PROJECT-GUIDELINES.md    # Ce fichier
```

---

## 🧩 Intégrations externes

### MailerLite
- Envoi du guide via automatisation
- Groupe spécifique "Audit/Stratégie digitale"
- API POST utilisée via `submit.php` et `traitement-rdv.php`

### MailerSend
- Envoi d'emails transactionnels (confirmation, rappel)
- Appels via `curl` avec header `Authorization: Bearer <API_KEY>`

---

## 🔁 Déploiement & Synchronisation

- Utiliser `.vscode/sftp.json` pour upload depuis VS Code
- Masquer les identifiants via `${config:sftp.username}` dans les paramètres
- Nettoyer les fichiers `.old`, `.bak`, `.DS_Store` avant zip ou FTP

---

## ⚠️ À faire / Idées d'amélioration

### Phase 1 (Immédiate)
- [ ] Réorganiser la structure du plugin
- [ ] Sécuriser les requêtes SQL
- [ ] Optimiser les performances
- [ ] Améliorer l'internationalisation

### Phase 2 (À venir)
- [ ] Intégrer un vrai système d'auth (session, login sécurisé)
- [ ] Ajouter interface pour modifier les créneaux disponibles
- [ ] Permettre aux prestataires de gérer leurs propres RDV
- [ ] Ajouter option de paiement (Stripe ou Orange Money)
- [ ] Ajouter logs d'action avec horodatage (`logs/` ou BDD)
- [ ] Rendre le module compatible avec mobile / responsive 100 %
- [ ] Créer interface pour exporter les RDV au format CSV / Excel
- [ ] Implémenter un système de cache pour les requêtes fréquentes
- [ ] Ajouter des tests automatisés pour les fonctionnalités critiques

---

## 📋 Procédure de test complet

1. Créer un nouveau RDV depuis le frontend
2. Vérifier insertion en BDD (`reservations`)
3. Vérifier réception de l'email automatique
4. Confirmer manuellement dans le back-office
5. Vérifier affichage dans FullCalendar admin
6. Vérifier envoi du rappel automatique après déclenchement de `rappel-cron.php`
7. Tester l'annulation de rendez-vous
8. Vérifier la gestion des créneaux indisponibles
9. Tester les rappels par email/SMS
10. Vérifier les logs d'activité

---

## 📞 Support et maintenance

### Documentation
- Documentation fonctionnelle → `README.md`
- Documentation admin/plugin → `README_ADMIN_CALENDRIER.md`
- Documentation technique → `PROJECT-GUIDELINES.md`

### Contacts
- **Support technique** : [email]@sansolutions.com
- **Urgences** : +33 X XX XX XX XX
- **Réunion hebdomadaire** : Jeudi 10h

### Maintenance
- **Sauvegardes** : Quotidiennes (bases de données + fichiers)
- **Mises à jour de sécurité** : Dernier vendredi du mois
- **Revue de code** : À chaque pull request

### Métriques
- Temps de réponse moyen : < 4h (jours ouvrés)
- Délai de résolution des bugs critiques : 24h
- Fréquence des mises à jour : Bi-mensuelle

---

## 📌 Conventions de nommage

- **Fichiers PHP** : `nom-action.php` ou `traitement-chose.php`
- **Variables** : `$camelCase`
- **Fonctions** : `snake_case()` (ex. `insert_rdv()`), sauf si API
- **JS** : variables et fonctions en camelCase

---

## 🔒 Sécurité

- Pas d'URL d'accès direct aux fichiers dans `/includes/` ou `/cron/`
- Ajout recommandé d'un `.htaccess` pour bloquer l'accès aux fichiers sensibles
- Échapper toutes les entrées utilisateur avec `htmlspecialchars()` ou `prepare()`
- Authentification simple côté admin à renforcer plus tard avec sessions

---

## 🧩 Intégrations externes

### MailerLite
- Envoi du guide via automatisation
- Groupe spécifique "Audit/Stratégie digitale"
- API POST utilisée via `submit.php` et `traitement-rdv.php`

### MailerSend
- Envoi d'emails transactionnels (confirmation, rappel)
- Appels via `curl` avec header `Authorization: Bearer <API_KEY>`

---

## 🔁 Déploiement & Synchronisation

- Utiliser `.vscode/sftp.json` pour upload depuis VS Code
- Masquer les identifiants via `${config:sftp.username}` dans les paramètres
- Nettoyer les fichiers `.old`, `.bak`, `.DS_Store` avant zip ou FTP

---

## ⚠️ À faire / Idées d'amélioration

- [ ] Intégrer un vrai système d'auth (session, login sécurisé)
- [ ] Ajouter interface pour modifier les créneaux disponibles
- [ ] Permettre aux prestataires de gérer leurs propres RDV
- [ ] Ajouter option de paiement (Stripe ou Orange Money)
- [ ] Ajouter logs d'action avec horodatage (`logs/` ou BDD)
- [ ] Rendre le module compatible avec mobile / responsive 100 %
- [ ] Créer interface pour exporter les RDV au format CSV / Excel
- [ ] Implémenter un système de cache pour les requêtes fréquentes
- [ ] Ajouter des tests automatisés pour les fonctionnalités critiques
- [ ] Documenter l'API pour les développeurs tiers

---

## 🧪 Procédure de test complet

1. Créer un nouveau RDV depuis le frontend
2. Vérifier insertion en BDD (`reservations`)
3. Vérifier réception de l'email automatique
4. Confirmer manuellement dans le back-office
5. Vérifier affichage dans FullCalendar admin
6. Vérifier envoi du rappel automatique après déclenchement de `rappel-cron.php`
7. Tester l'annulation de rendez-vous
8. Vérifier la gestion des créneaux indisponibles
9. Tester les rappels par email/SMS
10. Vérifier les logs d'activité

---

## 📞 Support et maintenance

### Documentation
- Documentation fonctionnelle → `README.md`
- Documentation admin/plugin → `README_ADMIN_CALENDRIER.md`
- Documentation technique → `PROJECT-GUIDELINES.md`

### Contacts
- **Support technique** : [email]@sansolutions.com
- **Urgences** : +33 X XX XX XX XX
- **Réunion hebdomadaire** : Jeudi 10h

### Maintenance
- **Sauvegardes** : Quotidiennes (bases de données + fichiers)
- **Mises à jour de sécurité** : Dernier vendredi du mois
- **Revue de code** : À chaque pull request

### Métriques
- Temps de réponse moyen : < 4h (jours ouvrés)
- Délai de résolution des bugs critiques : 24h
- Fréquence des mises à jour : Bi-mensuelle
