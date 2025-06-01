=== Calendrier RDV ===
Contributors: SAN Digital Solutions
Donate link: https://sansolutions.com/don
Tags: rendez-vous, calendrier, prise de rendez-vous, réservation, divi
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Système de prise de rendez-vous en ligne avec gestion multi-prestataires et intégration Divi.

== Description ==

Calendrier RDV est une solution complète de gestion de rendez-vous en ligne pour les professionnels. Le plugin permet de gérer facilement les disponibilités, les rendez-vous et les clients, avec une intégration transparente avec Divi.

### Fonctionnalités principales

* Gestion des rendez-vous en temps réel
* Interface intuitive et conviviale
* Intégration avec Divi Builder
* Gestion multi-prestataires
* Notifications par email
* Rappels automatiques
* Gestion des créneaux horaires
* Compatible mobile

### Intégration Divi

Le module Divi est automatiquement disponible lorsque le thème Divi est actif. Il ajoute un nouveau module personnalisé dans le constructeur de page Divi.

== Installation ==

1. Téléchargez le plugin
2. Installez-le via le menu 'Extensions' de WordPress
3. Activez le plugin
4. Configurez vos paramètres dans 'Calendrier RDV' > 'Paramètres'

Pour l'intégration Divi, assurez-vous que le thème Divi est actif. Le module sera automatiquement disponible dans le constructeur de page Divi.

== Mises à jour ==

### 1.5.0
* Refonte de l'architecture pour une meilleure modularité
* Amélioration de l'intégration Divi
* Chargement conditionnel des fonctionnalités

### 1.4.0
* Ajout du système de file d'attente pour les emails
* Amélioration de la gestion des erreurs

### 1.3.1
* Sécurisation des accès fichiers
* Export CSV des rendez-vous

== Captures d'écran ==

1. Interface principale du calendrier
2. Formulaire de prise de rendez-vous
3. Module Divi dans le constructeur

== Foire aux questions ==

= Le plugin est-il compatible avec mon thème ?
Oui, le plugin est compatible avec tous les thèmes WordPress. Une intégration spécifique est disponible pour Divi.

= Comment ajouter le calendrier à une page ?
Utilisez le shortcode [calendrier_rdv] ou le module Divi si vous utilisez Divi Builder.

== Support ==

Pour toute question ou problème, veuillez utiliser notre [système de support](https://sansolutions.com/support).

== Notes de développement ==

### Structure du projet

```
calendrier-rdv/
├── admin/                  # Interface d'administration
├── includes/               # Fonctionnalités principales
│   ├── integrations/       # Intégrations (Divi, etc.)
├── public/                 # Code frontend
└── templates/              # Templates réutilisables
```

### Hooks et filtres

Le plugin expose plusieurs hooks et filtres pour les développeurs. Consultez le fichier DEVELOPPEMENT.md pour plus d'informations.

== Licence ==

Ce plugin est sous licence GPL v2 ou ultérieure.

== À propos ==

Développé par [SAN Digital Solutions](https://sansolutions.com).
