=== Calendrier de Rendez-vous ===
Contributors: votre-nom
Donate link: https://example.com/donate/
Tags: rendez-vous, calendrier, prise de rendez-vous, réservation
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Un plugin de prise de rendez-vous simple et efficace pour WordPress.

== Description ==

Calendrier de Rendez-vous est une solution complète pour gérer les prises de rendez-vous sur votre site WordPress. Il permet à vos visiteurs de prendre rendez-vous en ligne de manière intuitive, tout en offrant une interface d'administration complète pour gérer les disponibilités et les rendez-vous.

### Fonctionnalités principales

* Prise de rendez-vous en ligne
* Gestion des créneaux horaires
* Notifications par email
* Gestion des prestataires
* Interface responsive
* Personnalisation des créneaux horaires
* Rappels automatiques
* Export des rendez-vous

== Installation ==

1. Téléchargez le dossier `rdv` dans le répertoire `/wp-content/plugins/`
2. Activez le plugin dans le menu 'Extensions' de WordPress
3. Allez dans la page de configuration pour paramétrer le plugin
4. Utilisez le shortcode `[calendrier_rdv]` sur la page de votre choix

== Utilisation ==

### Shortcodes

- `[calendrier_rdv]` - Affiche le formulaire de prise de rendez-vous
- `[mes_rendez_vous]` - Affiche les rendez-vous de l'utilisateur connecté

### Fonctionnalités avancées

#### Personnalisation des créneaux horaires

Vous pouvez personnaliser les créneaux horaires en utilisant les filtres suivants :

```php
add_filter('rdv_available_hours', 'custom_available_hours', 10, 1);
function custom_available_hours($hours) {
    // Modifier les heures d'ouverture
    return $hours;
}
```

#### Personnalisation des emails

Les templates d'emails se trouvent dans le dossier `templates/emails/`. Vous pouvez les surcharger dans votre thème en créant un dossier `rdv/emails/` dans votre thème enfant.

## Capture d'écran

1. Capture du formulaire de réservation
2. Capture de l'administration des rendez-vous

== Changelog ==

= 1.0.0 =
* Version initiale

== Améliorations à venir ==

* Synchronisation avec Google Calendar
* Paiement en ligne
* Formulaire de feedback post-rendez-vous
* Statistiques avancées

== Support ==

Pour toute question ou problème, veuillez utiliser le [forum de support](https://wordpress.org/support/plugin/calendrier-rdv).
