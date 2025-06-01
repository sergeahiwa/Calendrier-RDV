
# Calendrier de Rendez-vous - SAN Digital Solutions

## 📦 Version 1.5.0 - Architecture Modulaire

**Date de sortie :** 28 Mai 2025

### Nouvelles Fonctionnalités
- Architecture modulaire pour une meilleure maintenabilité
- Intégration native avec Divi Builder
- Système de chargement conditionnel des fonctionnalités

### Améliorations
- Meilleure séparation entre le cœur du plugin et les intégrations
- Documentation technique complète
- Scripts de déploiement automatisés


Application de prise de rendez-vous en ligne complète avec gestion multi-prestataires, notifications et interface d'administration avancée.

---

## 🏗️ Architecture Modulaire

Le plugin est conçu avec une architecture modulaire qui permet :
- Un cœur de plugin indépendant du thème
- Des intégrations spécifiques (Divi, etc.)
- Une extensibilité facile

### Structure des Dossiers
- `/includes` - Fonctionnalités principales
- `/includes/integrations` - Modules d'intégration (Divi, etc.)
- `/admin` - Interface d'administration
- `/public` - Fonctionnalités frontend

Voir [ARCHITECTURE.md](docs/ARCHITECTURE.md) pour plus de détails.

## 🚀 Fonctionnalités principales

### Gestion des Rendez-vous
- **Prise de RDV en ligne** avec sélection de créneaux disponibles  
- **Gestion complète des rendez-vous** (création, modification, annulation)  
- **Calendrier interactif** avec vue jour/semaine/mois  
- **Système de notifications** par email (confirmations, rappels, annulations)  

### Gestion des Utilisateurs
- **Gestion multi-prestataires** avec plannings individuels  
- **Interface prestataire** pour gérer les disponibilités  
- **Sécurité avancée** avec gestion des rôles et permissions  

### Configuration Avancée
- **Paramètres d'administration** complets et personnalisables
  - Configuration des créneaux horaires
  - Personnalisation des formats de date et d'heure
  - Gestion des notifications
  - Paramètres avancés de sécurité
- **Gestion des services** avec durées et tarifs variables  
- **Paiements sécurisés** incluant Mobile Money pour l'Afrique  

### Rapports et Export
- **Tableau de bord** avec indicateurs et statistiques  
- **Export des données** (CSV, Excel, PDF)  
- **Logs détaillés** de toutes les actions  

### Développement et Maintenance
- **Tests unitaires complets** avec 100% de couverture de code  
- **Environnement Docker** pour le développement et les tests  
- **Documentation technique** complète et à jour  
- **API REST** complète pour l'intégration  
- **Responsive design** compatible mobile  
- **Multilingue** (français/anglais par défaut)  

---

## 🌟 Dernières Mises à Jour (v1.3.0 - 2025-05-25)

- **[INFO]** Passage en phase V1 : le MVP est validé, livré et documenté. Voir la feuille de route V1 pour les prochaines évolutions.

### Nouvelles Fonctionnalités
- **Gestion complète des rendez-vous**
  - Création, modification et annulation de rendez-vous
  - Vérification en temps réel des créneaux disponibles
  - Notifications automatiques pour toutes les actions

### Améliorations Techniques
- **Tests unitaires**
  - Couverture de code à 100%
  - Tests automatisés pour toutes les fonctionnalités clés
  - Intégration continue avec GitHub Actions

- **Sécurité renforcée**
  - Validation stricte des entrées utilisateur
  - Protection contre les attaques CSRF
  - Gestion sécurisée des sessions

- **Performance**
  - Optimisation des requêtes SQL
  - Mise en cache avancée
  - Temps de chargement réduit

## ⚙️ Configuration des Paramètres

### Structure des Paramètres
Le système de paramètres est organisé en onglets thématiques pour une navigation intuitive :

1. **Général**
   - Informations de l'entreprise
   - Coordonnées de contact
   - Préférences de base

2. **Créneaux Horaires**
   - Plages d'ouverture/fermeture
   - Durée des créneaux
   - Jours d'ouverture
   - Pauses déjeuner

3. **Notifications**
   - Modèles d'emails
   - Paramètres SMTP
   - Rappels automatiques

4. **Paiements**
   - Méthodes de paiement
   - Paramètres de facturation
   - Taux de TVA

5. **Avancé**
   - Paramètres de débogage
   - Outils de maintenance
   - Sauvegardes

### Bonnes Pratiques
- Toujours utiliser l'interface d'administration pour modifier les paramètres
- Tester les changements dans un environnement de développement avant la production
- Sauvegarder la configuration avant les mises à jour majeures

## 📜 Charte de non-régression IA ✅

Ce projet intègre une **charte de non-régression** spécifiquement conçue pour les modules propulsés ou assistés par intelligence artificielle. Elle garantit que :

- Toute mise à jour d’un module IA **doit être testée** pour s’assurer qu’elle **ne dégrade pas** les performances ou les fonctionnalités existantes.
- Les résultats des tests **doivent être comparés** aux versions précédentes et **archivés**.
- **Aucun modèle IA** n’est déployé en production **sans validation humaine** préalable.
- Les **modèles sont versionnés**, traçables et documentés.
- En cas de doute sur la fiabilité d'une prédiction IA, le système privilégie l’**intervention humaine** ou la **désactivation automatique** de l'IA concernée.

Cette charte permet d’instaurer un climat de confiance pour les utilisateurs finaux tout en facilitant l’évolution responsable des fonctionnalités augmentées par l’IA.

---

## 📁 Structure du Projet

```

calendrier-rdv/
├── admin/                      # Administration WordPress
│   ├── css/
│   ├── js/
│   ├── partials/
│   └── class-admin.php
├── includes/                  # Cœur du plugin
│   ├── class-calendrier-rdv.php
│   ├── class-installer.php
│   ├── class-appointment.php
│   ├── class-provider.php
│   ├── class-service.php
│   ├── class-notification.php
│   └── class-api.php
├── public/
│   ├── css/
│   ├── js/
│   └── partials/
├── sql/
│   └── schema.sql
├── languages/
│   ├── calendrier-rdv-fr\_FR.po
│   └── calendrier-rdv-fr\_FR.mo
├── templates/
│   ├── emails/
│   └── booking/
├── tests/                     # Tests automatisés
│   ├── unit/                 # Tests unitaires
│   ├── integration/          # Tests d'intégration
│   ├── performance/          # Tests de performance
│   ├── security/             # Tests de sécurité
│   ├── accessibility/        # Tests d'accessibilité
│   └── functional/           # Tests fonctionnels
└── vendor/                    # Dépendances externes

````

---

## 🛠 Installation

### Prérequis

- PHP 7.4 ou supérieur  
- MySQL 5.7+ ou MariaDB 10.3+  
- WordPress 5.8 ou supérieur  
- Extensions PHP : `PDO`, `JSON`, `cURL`, `MBString`, `XML`

### Étapes

1. **Téléchargement**
   - Zip ou `git clone` dans `wp-content/plugins/`

2. **Activation**
   - Via le menu Extensions de WordPress

3. **Configuration initiale**
   - Assistant étape par étape : services, notifications, pages, etc.

4. **Configuration avancée**
   - Paramètres horaires, jours fériés, modèles emails, intégrations

---

## 🔁 Mise à jour

1. Sauvegarde préalable
2. Mise à jour via WordPress
3. Exécution automatique des migrations

---

## ❌ Désinstallation

1. Désactivation dans WordPress
2. Suppression des données via `Calendrier RDV > Outils > Désinstaller`

---

## 🧪 Tests

- **Unitaires** : composants isolés (ex: `tests/unit/test-db.php`)
- **Intégration** : communication entre modules
- **Fonctionnels** : cas d’usage finaux (ex: `tests/functional/test_connexion.txt`)

---

## 🚀 Déploiement (SFTP - VS Code)

1. Identifiants via `${config:sftp.username}`
2. `.vscode/sftp.json` sécurisé
3. Mode passif activé
4. Configuration des chemins distants

---

## 🔌 API REST : Modification d’un rendez-vous

- **Endpoint** : `/wp-json/calendrier-rdv/v1/appointments/update`
- **Méthode** : `POST`
- **Sécurité** : `X-WP-Nonce` ou `_wpnonce`
- **Champs supportés** :
  - `id`, `date`, `time`, `provider_id`, `service_id`
  - `customer_name`, `customer_email`, `customer_phone`, `notes`, `status`

### Réponses

- ✅ Succès :
  ```json
  { "id": 123, "message": "Rendez-vous mis à jour avec succès" }
````

* ⚠️ Erreurs :

  * Créneau indisponible :

    ```json
    { "message": "Ce créneau n'est pas disponible" }
    ```
  * Nonce invalide :

    ```json
    { "message": "Nonce invalide" }
    ```
  * Rendez-vous introuvable :

    ```json
    { "message": "Rendez-vous introuvable" }
    ```
  * Données manquantes :

    ```json
    { "message": "ID du rendez-vous manquant" }
    ```

---

## 🕒 Modifications récentes

### 08/05/2025

* 🔒 Sécurisation SFTP par variables VS Code
* 🧹 Suppression de fichiers obsolètes (`formulaire.old.html`)
* 📁 Organisation des tests (unit, integration, functional)
* 📝 Ajout de la Charte IA et révision du README

---

## 🛡️ Outils de Sécurité et Audit

### 🔍 Scripts d'Audit Git

Le projet inclut des scripts pour auditer les modifications apportées au code source :

- `audit.sh` : Version Bash pour systèmes Unix/Linux
- `audit.ps1` : Version PowerShell pour Windows

**Utilisation :**
```bash
# Sur Linux/Mac
./audit.sh

# Sur Windows (PowerShell)
.\audit.ps1
```

### 🔒 Hooks Git de Sécurité

Des hooks Git ont été configurés pour renforcer la qualité et la sécurité du code :

- **pre-commit** : Empêche les commits automatiques sur les fichiers liés à l'IA
  - Vérifie les modifications dans les fichiers contenant `ia`, `intelligence`, `ml` ou `ai`
  - Demande une confirmation manuelle avant de permettre le commit
  - Annule le commit si l'utilisateur ne confirme pas

**Fonctionnement :**
1. À chaque commit, Git exécute automatiquement le hook
2. Si des fichiers sensibles sont détectés :
   - La liste des fichiers concernés est affichée
   - Une confirmation manuelle est demandée
   - Le commit est annulé si l'utilisateur ne confirme pas

## 📌 À faire

* [x] Mettre en place des hooks Git de sécurité
* [ ] Renforcer la sécurité `.htaccess`
* [ ] Finaliser la politique de log
* [ ] Couvrir 100 % du code avec des tests automatisés

## 🔄 Dernières Mises à Jour

### 24/05/2025
* 🔒 Ajout des hooks Git de sécurité pour les fichiers IA
* 📊 Intégration des scripts d'audit Git
* 📝 Mise à jour de la documentation

### 08/05/2025
* 🔒 Sécurisation SFTP par variables VS Code
* 🧹 Suppression de fichiers obsolètes (`formulaire.old.html`)
* 📁 Organisation des tests (unit, integration, functional)
* 📝 Ajout de la Charte IA et révision du README
