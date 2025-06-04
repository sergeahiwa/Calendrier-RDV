
# Calendrier de Rendez-vous - SAN Digital Solutions

[![Tests CI](https://github.com/sergeahiwa/Calendrier-RDV/actions/workflows/php-tests.yml/badge.svg)](https://github.com/sergeahiwa/Calendrier-RDV/actions/workflows/php-tests.yml)

## 📋 Table des matières
- [Fonctionnalités](#-fonctionnalités)
- [Installation](#-installation)
- [Utilisation](#-utilisation)
- [Développement](#-développement)
- [Sécurité](#-sécurité)
- [Tests](#-tests)
- [Contribution](#-contribution)
- [Licence](#-licence)

## 🔄 Gestion du Cache

Le plugin utilise un système de cache pour optimiser les performances. Consultez [la documentation complète sur le cache](docs/CACHE.md) pour plus de détails.

### Fonctionnalités clés
- Mise en cache des requêtes fréquentes
- Invalidation automatique lors des mises à jour
- Nettoyage automatique à la désactivation

### Utilisation basique

```php
use CalendrierRdv\Core\Cache_Manager;

// Mettre en cache
Cache_Manager::set('ma_cle', $donnees, HOUR_IN_SECONDS);

// Récupérer du cache
$donnees = Cache_Manager::get('ma_cle');

// Supprimer du cache
Cache_Manager::delete('ma_cle');
```

## 🧪 Stratégie de Test

Consultez le document [TESTING-STRATEGY.md](docs/TESTING-STRATEGY.md) pour une description complète de notre stratégie de test.

### Commandes principales

```bash
# Tests rapides (unité + intégration SQLite)
composer test:quick

# Tous les tests (unité, intégration, performance, sécurité, accessibilité)
composer test:all

# Tests unitaires uniquement (SQLite)
composer test:unit:sqlite

# Tests d'intégration (MySQL)
composer test:integration:mysql

# Tests d'intégration SMS (nécessite la configuration Twilio)
composer test:sms

# Tests de performance
composer test:performance

# Générer un rapport de couverture de code
composer test:coverage

# Lancer les tests avec un fichier de configuration spécifique
./vendor/bin/phpunit -c phpunit.performance.xml
```

### Tests de performance

Les tests de performance sont conçus pour évaluer les performances du système sous charge. Par défaut, ils sont exclus des exécutions de test normales car ils peuvent prendre plus de temps à s'exécuter.

Pour exécuter les tests de performance :

```bash
# Exécuter tous les tests de performance
composer test:performance

# Exécuter un test spécifique
./vendor/bin/phpunit -c phpunit.performance.xml --filter testEmailNotificationLoad

# Générer un rapport JUnit pour l'intégration continue
composer test:performance:ci
```

#### Configuration des tests de performance

Les tests de performance peuvent être configurés à l'aide de variables d'environnement :

```bash
# Nombre d'itérations pour les petits tests de charge (par défaut: 100)
TEST_LOAD_SMALL=100

# Nombre d'itérations pour les tests de charge moyens (par défaut: 1000)
TEST_LOAD_MEDIUM=1000

# Nombre d'itérations pour les tests de charge importants (par défaut: 10000)
TEST_LOAD_LARGE=10000

# Seuil d'avertissement en secondes (par défaut: 5.0)
TEST_WARNING_THRESHOLD=5.0

# Seuil critique en secondes (par défaut: 10.0)
TEST_CRITICAL_THRESHOLD=10.0
```

### Configuration des tests SMS

Pour exécuter les tests d'intégration SMS avec Twilio, vous devez configurer les variables d'environnement suivantes :

```bash
# Dans un fichier .env à la racine du projet
TWILIO_ACCOUNT_SID=votre_sid_twilio
TWILIO_AUTH_TOKEN=votre_token_twilio
```

Ou les exporter dans votre shell :

```bash
export TWILIO_ACCOUNT_SID=votre_sid_twilio
export TWILIO_AUTH_TOKEN=votre_token_twilio
```

**Note :** Les tests utiliseront le numéro de test Twilio `+15005550006` pour les tests d'envoi réel.

## 🛡 Charte de Non-Régression

Pour garantir la stabilité et la qualité du projet, nous suivons une [charte de non-régression](docs/NON-REGRESSION.md) stricte qui encadre toutes les modifications apportées au code. Cette charte définit les bonnes pratiques et les processus à suivre pour éviter toute régression.

## 🏗 Structure du Projet

Le plugin suit une architecture modulaire moderne avec une séparation claire des responsabilités :

```
calendrier-rdv/
├── src/                      # Code source du plugin
│   ├── Admin/               # Gestion de l'administration
│   │   ├── Views/          # Templates d'administration
│   │   └── class-admin.php # Classe principale d'administration
│   │
│   ├── Api/               # Points d'entrée de l'API REST
│   │   └── RestController.php
│   │
│   ├── Core/              # Fonctionnalités de base
│   │   ├── Security/       # Sécurité et authentification
│   │   ├── Hooks/          # Hooks WordPress
│   │   └── ...
│   │
│   ├── Domain/            # Logique métier
│   │   ├── Model/         # Modèles de données
│   │   ├── Repository/     # Accès aux données
│   │   └── Service/       # Services métier
│   │
│   ├── Infrastructure/    # Implémentations techniques
│   │   ├── Database/      # Accès à la base de données
│   │   └── Export/        # Fonctionnalités d'export
│   │
│   └── Public/            # Gestion du front-end
│       ├── assets/       # Assets du front-end
│       ├── Views/        # Templates front-end
│       └── class-public.php
│
├── assets/                # Fichiers statiques globaux
│   ├── css/              # Feuilles de style
│   ├── js/               # Scripts JavaScript
│   └── images/           # Images et médias
│
├── templates/            # Templates globaux
├── tests/                # Tests automatisés
├── vendor/               # Dépendances Composer
└── languages/            # Fichiers de traduction
```

### Architecture Technique

1. **Couche Présentation**
   - `src/Public/` : Gestion du front-end
   - `src/Admin/` : Interface d'administration
   - `templates/` : Templates réutilisables

2. **Couche Application**
   - `src/Api/` : Points d'entrée de l'API
   - `src/Core/` : Fonctionnalités centrales

3. **Couche Domaine**
   - `src/Domain/` : Logique métier pure
   - Modèles, règles métier, validation

4. **Couche Infrastructure**
   - `src/Infrastructure/` : Implémentations techniques
   - Base de données, services externes, etc.

### Bonnes Pratiques

- **PSR-4** : Autoloading des classes via Composer
- **MVC** : Séparation claire Modèle-Vue-Contrôleur
- **SOLID** : Principes de conception orientée objet
- **Tests** : Couverture de code avec PHPUnit
- **Sécurité** : Protection CSRF, validation des entrées, requêtes préparées

### Prérequis Techniques

- PHP 7.4+ (recommandé : PHP 8.1+)
- WordPress 5.8+
- Composer pour la gestion des dépendances
- MySQL 5.7+ ou MariaDB 10.3+

### Installation

1. Télécharger et installer via le répertoire des plugins WordPress
2. OU installer manuellement via FTP :
   ```bash
   cd wp-content/plugins/
   git clone [url-du-depot] calendrier-rdv
   cd calendrier-rdv
   composer install
   ```
3. Activer le plugin dans l'administration WordPress

### Développement

Pour contribuer au développement :

```bash
# Cloner le dépôt
git clone [url-du-depot] calendrier-rdv
cd calendrier-rdv

# Installer les dépendances
composer install

# Lancer les tests
composer test

# Générer la documentation
composer docs
```

### Sécurité

Pour signaler une vulnérabilité de sécurité, veuillez consulter notre [politique de sécurité](SECURITY.md).

---

**Dernière mise à jour :** 3 Juin 2025  
**Version :** 1.5.3


Application de prise de rendez-vous en ligne complète avec gestion multi-prestataires, notifications, interface d'administration avancée et sécurité renforcée contre les attaques par force brute.

---

## 🔒 Sécurité Renforcée

Le plugin intègre un système complet de protection contre les attaques par force brute avec les fonctionnalités suivantes :

### Protection des Connexions
- Limitation du nombre de tentatives de connexion échouées
- Verrouillage temporaire des comptes après plusieurs échecs
- Blacklist automatique des adresses IP suspectes
- Intégration avec reCAPTCHA pour les formulaires de connexion

### Journalisation et Surveillance
- Enregistrement détaillé de toutes les tentatives de connexion
- Notifications par email pour les activités suspectes
- Tableau de bord de sécurité dans l'administration
- Nettoyage automatique des anciennes entrées

### Configuration Recommandée
```php
// Dans wp-config.php
define('RECAPTCHA_SITE_KEY', 'votre_cle_site');
define('RECAPTCHA_SECRET_KEY', 'votre_cle_secrete');
```

Pour plus de détails sur la configuration avancée, consultez la [documentation complète](docs/SECURITY-IMPLEMENTATION.md).

## 🛠 Outils de Suivi d'Avancement

### Génération de Rapports

Générez un rapport d'avancement à tout moment :

```bash
# Générer un rapport en Markdown
php scripts/generate-progress-report.php

# Générer un rapport HTML
php scripts/generate-progress-report.php --output=html

# Générer un rapport JSON
php scripts/generate-progress-report.php --output=json
```

### Configuration des Hooks Git

Pour configurer les hooks Git qui génèrent automatiquement un rapport à chaque commit :

```bash
# Rendre le script exécutable (Linux/Mac)
chmod +x scripts/setup-git-hooks.sh

# Exécuter le script d'installation
./scripts/setup-git-hooks.sh
```

### Intégration CI/CD

Le workflow GitHub Actions `progress-report.yml` génère automatiquement un rapport à chaque push sur les branches principales.

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

### 🔒 Sécurité Avancée
- **Protection contre les attaques par force brute** avec limitation des tentatives de connexion
- **Verrouillage temporaire** des comptes après plusieurs échecs
- **Journalisation détaillée** des activités suspectes
- **Gestion sécurisée des sessions**
- **Protection CSRF** intégrée

> ℹ️ Consultez le fichier [SECURITY.md](SECURITY.md) pour une documentation complète sur les fonctionnalités de sécurité.

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

## 📚 Documentation Technique

### Gestion des Tentatives de Connexion

#### Fonctionnement
Le système de limitation des tentatives de connexion protège contre les attaques par force brute en :
1. Enregistrant chaque tentative de connexion échouée
2. Verrouillant temporairement le compte après 5 échecs
3. Envoyant une notification à l'administrateur en cas de verrouillage
4. Déverrouillant automatiquement le compte après 30 minutes

#### Fichiers Clés
- `src/Core/Security/LoginAttempts.php` : Gestion des tentatives de connexion
- `src/Core/Hooks/LoginHooks.php` : Intégration avec les hooks WordPress
- `tests/test-login-hooks.php` : Tests d'intégration

#### Méthodes Principales
- `record_failed_attempt()` : Enregistre une tentative échouée
- `is_locked()` : Vérifie si un compte est verrouillé
- `clear_attempts()` : Réinitialise les tentatives après une connexion réussie
- `cleanup_old_attempts()` : Nettoie les anciennes tentatives

## 🛡️ Sécurité Renforcée

### Dernières Améliorations de Sécurité

#### 🔐 Correction du Déverrouillage des Comptes (v1.5.2)
- **Problème résolu** : Correction d'un problème critique empêchant le déverrouillage des comptes après une période de blocage
- **Solution** : Refonte de l'algorithme de gestion des tentatives de connexion pour une meilleure fiabilité
- **Avantages** :
  - Détection et déblocage fiables des comptes verrouillés
  - Journalisation détaillée pour le débogage
  - Meilleure expérience utilisateur

### Fonctionnalités de Sécurité

#### Protection contre les attaques par force brute
- Limitation des tentatives de connexion échouées
- Verrouillage temporaire des comptes après plusieurs échecs
- Notifications par email pour les activités suspectes

#### Bonnes pratiques implémentées
- Validation et assainissement des entrées utilisateur
- Utilisation de requêtes préparées pour la base de données
- Gestion sécurisée des sessions
- Protection CSRF sur les formulaires

### Audit de Sécurité

Des audits de sécurité réguliers sont effectués pour identifier et corriger les vulnérabilités potentielles. Consultez le fichier [SECURITY.md](SECURITY.md) pour plus d'informations sur la politique de sécurité et le signalement des vulnérabilités.

### Outils de Sécurité et Audit

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
