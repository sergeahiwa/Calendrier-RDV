# Module Divi 5 - Calendrier RDV

Ce module moderne pour Divi 5+ offre une intégration native et performante du système de prise de rendez-vous dans le Builder Divi.

## Fonctionnalités

- **Interface moderne** : Utilisation de React pour une expérience utilisateur fluide
- **Personnalisation complète** : Contrôle total sur les styles et les couleurs
- **Intégration native** : Module Divi 5+ optimisé
- **Performance** : Chargement conditionnel des assets
- **Responsive** : Design adaptatif pour tous les appareils
- **Validation en temps réel** : Feedback immédiat aux utilisateurs
- **Notifications** : Intégration avec Google Calendar et SMS

## Architecture du Système

### 1. Plugin Principal (Universel)
- Fonctionne avec TOUS les thèmes WordPress
- Fournit des shortcodes universels
  ```php
  // Utilisation de base
  echo do_shortcode('[calendrier_rdv]');
  
  // Avec options
  [calendrier_rdv style="modern"]
  ```

### 2. Module Divi (Optionnel)
- Extension spécifique pour Divi 5+
- Offre une intégration native avec le Divi Builder
- Utilise les mêmes fonctions de base que le plugin principal

## Installation

1. Installez le plugin principal **Calendrier RDV**
2. Activez le plugin
3. Le module sera automatiquement disponible dans Divi Builder
4. Aucune configuration supplémentaire nécessaire


## Utilisation

1. Ajoutez le module "Calendrier RDV" dans Divi Builder
2. Configurez les options selon vos besoins
3. Personnalisez le style avec les options de design
4. Sauvegardez et publiez votre page

## Options de configuration

### Général
- Titre du module
- Description
- Affichage des services
- Affichage des prestataires
- Mode de réservation
- Messages personnalisés

### Style
- Couleurs
- Polices
- Espacements
- Bordures
- Coins arrondis
- Ombres
- Transitions

### Comportement
- Mode de sélection des créneaux
- Durée minimale des rendez-vous
- Intervalle entre les créneaux
- Date minimum/maximum
- Nombre maximum de rendez-vous


### Hooks et filtres

Le module expose plusieurs hooks WordPress pour une personnalisation avancée :

```php
// Modifier les données du formulaire avant soumission
add_filter('calendrier_rdv_form_data', function($form_data) {
    // Vos modifications ici
    return $form_data;
});

// Exécuter du code après une réservation réussie
add_action('calendrier_rdv_booking_created', function($booking_id, $form_data) {
    // Votre code ici
}, 10, 2);
```

## Dépannage

### Le module ne s'affiche pas
- Vérifiez que le plugin est activé dans WordPress.
- Assurez-vous que le thème Divi est installé et activé.

### Les créneaux ne se chargent pas
- Vérifiez que l'API REST est accessible.
- Vérifiez les erreurs dans la console JavaScript (F12 > Console).

## Support

Pour toute question ou problème, consultez d'abord la documentation du plugin principal. Pour les problèmes spécifiques au module Divi, contactez le support technique.

## Gestion des messages utilisateur (UX)

### Centralisation et traduction
- Tous les messages affichés à l’utilisateur sont centralisés dans un objet `messages` JavaScript (français/anglais).
- Utilisation de la fonction `t('clé')` pour afficher un message dans la langue courante.
- Exemple :

```js
this.showMessage('submitSuccess', 'success'); // Affiche le message de succès traduit
```

### Accessibilité
- Les messages dynamiques intègrent les rôles ARIA (`role="alert"`, `aria-live`) pour être lus par les lecteurs d’écran.
- Exemple HTML généré :

```html
<div class="calendrier-rdv-message success" role="alert" aria-live="assertive">Votre rendez-vous a bien été enregistré !</div>
```

### Personnalisation
- Ajoutez vos propres messages ou traductions en complétant l’objet `messages` dans `divi-module.js`.
- Côté PHP, tous les textes passent par les fonctions `__()` ou `esc_html_e()` pour permettre la traduction via les fichiers `.po/.mo`.

### Bonnes pratiques
- Utilisez toujours les fonctions de centralisation pour garantir la cohérence, la traduction et l’accessibilité.
- Vérifiez le contraste et la lisibilité des messages dans vos personnalisations CSS.

## Licence

Ce plugin est sous licence GPL v2 ou ultérieure.
