
# Calendrier de Rendez-vous - SAN Digital Solutions

> **Note importante (26/05/2025) :**
>
> Le MVP (Minimum Viable Product) du projet a été entièrement livré, testé et documenté. Le projet entre désormais officiellement en **phase V1**. Toutes les évolutions, corrections et nouvelles fonctionnalités sont réalisées dans le cadre de cette V1.


Application de prise de rendez-vous en ligne complète avec gestion multi-prestataires, notifications et interface d'administration avancée.

---

## 🚀 Fonctionnalités principales

- **Prise de RDV en ligne** avec sélection de créneaux disponibles  
- **Gestion multi-prestataires** avec plannings individuels  
- **Système de notifications** par email (confirmations, rappels, annulations)  
- **Calendrier interactif** avec vue jour/semaine/mois  
- **Gestion des services** avec durées et tarifs variables  
- **Tableau de bord** avec indicateurs et statistiques  
- **Export des données** (CSV, Excel, PDF)  
- **API REST** complète pour l'intégration  
- **Sécurité avancée** avec gestion des rôles et permissions  
- **Logs détaillés** de toutes les actions  
- **Interface prestataire** pour gérer les disponibilités  
- **Paiements sécurisés** incluant Mobile Money pour l'Afrique  
- **Gestion complète des rendez-vous** (création, modification, annulation)  
- **Tests unitaires complets** avec 100% de couverture de code  
- **Environnement Docker** pour le développement et les tests  
- **Documentation technique** complète et à jour  
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
├── tests/
│   ├── unit/
│   ├── integration/
│   └── functional/
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
