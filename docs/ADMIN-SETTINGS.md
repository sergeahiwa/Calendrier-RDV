# Documentation des Paramètres d'Administration

## Table des matières
1. [Vue d'ensemble](#vue-densemble)
2. [Structure des paramètres](#structure-des-paramètres)
3. [Guide d'utilisation](#guide-dutilisation)
4. [Développement](#développement)
5. [Dépannage](#dépannage)

## Vue d'ensemble

Le système de paramètres d'administration du plugin Calendrier RDV offre une interface complète et intuitive pour configurer tous les aspects du plugin. Cette documentation s'adresse à la fois aux administrateurs qui utiliseront l'interface et aux développeurs qui souhaiteront l'étendre.

## Structure des paramètres

### 1. Général
- **Informations de l'entreprise** : Nom, adresse, coordonnées
- **Coordonnées** : Email, téléphone, site web
- **Paramètres de base** : Devise, fuseau horaire, langue

### 2. Créneaux Horaires
- **Plages d'ouverture** : Jours et heures d'ouverture
- **Durée des créneaux** : Durée par défaut des rendez-vous
- **Jours fériés** : Gestion des jours de fermeture exceptionnels
- **Pauses** : Configuration des pauses déjeuner

### 3. Notifications
- **Modèles d'emails** : Personnalisation des messages
- **Paramètres SMTP** : Configuration du serveur d'envoi
- **Rappels** : Configuration des rappels automatiques

### 4. Paiements
- **Méthodes acceptées** : Activer/désactiver les méthodes
- **Paramètres de facturation** : TVA, mentions légales
- **Paiement en ligne** : Configuration des passerelles

### 5. Avancé
- **Maintenance** : Mode maintenance, sauvegardes
- **Débogage** : Logs, informations système
- **API** : Clés d'API, accès aux endpoints

## Guide d'utilisation

### Accès aux paramètres
1. Connectez-vous à l'administration WordPress
2. Dans le menu de gauche, cliquez sur "Calendrier RDV"
3. Sélectionnez "Paramètres" dans le sous-menu

### Sauvegarde des paramètres
1. Effectuez vos modifications
2. Cliquez sur "Enregistrer les modifications"
3. Un message de confirmation s'affichera

### Réinitialisation
1. Allez dans l'onglet "Avancé"
2. Cliquez sur "Réinitialiser les paramètres"
3. Confirmez l'action

## Développement

### Ajout d'un nouveau paramètre

1. **Créer le champ dans l'interface**
   ```php
   // Dans la méthode register_settings()
   add_settings_field(
       'nouveau_parametre',
       __('Nouveau Paramètre', 'calendrier-rdv'),
       [$this, 'text_field_callback'],
       'cal_rdv_settings',
       'cal_rdv_general_section',
       [
           'id' => 'nouveau_parametre',
           'description' => __('Description du paramètre', 'calendrier-rdv')
       ]
   );
   ```

2. **Ajouter la validation**
   ```php
   // Dans la méthode sanitize_settings()
   if (isset($input['nouveau_parametre'])) {
       $output['nouveau_parametre'] = sanitize_text_field($input['nouveau_parametre']);
   }
   ```

3. **Utiliser la valeur dans le code**
   ```php
   $options = get_option('cal_rdv_settings');
   $valeur = $options['nouveau_parametre'] ?? 'valeur_par_defaut';
   ```

### Hooks disponibles

- `cal_rdv_before_settings_save` : Avant la sauvegarde
  ```php
  add_action('cal_rdv_before_settings_save', function($options) {
      // Votre code ici
  });
  ```

- `cal_rdv_after_settings_save` : Après la sauvegarde
- `cal_rdv_settings_tabs` : Filtre pour modifier les onglets

## Dépannage

### Les modifications ne sont pas enregistrées
1. Vérifiez les permissions utilisateur
2. Vérifiez les erreurs PHP dans les logs
3. Désactivez temporairement les extensions pouvant interférer

### Problème d'affichage
1. Videz le cache de votre navigateur
2. Vérifiez la console JavaScript pour des erreurs
3. Vérifiez les conflits de CSS/JS

### Récupération après erreur
1. Utilisez la sauvegarde automatique
2. Consultez la table `wp_options` avec la clé `cal_rdv_settings_backup`

## Bonnes pratiques

1. **Sauvegardes** : Toujours sauvegarder avant les modifications
2. **Environnement de test** : Tester les changements en préproduction
3. **Documentation** : Documenter les nouveaux paramètres
4. **Sécurité** : Ne jamais exposer les paramètres sensibles
5. **Performance** : Éviter les requêtes lourdes dans les callbacks

## Référence des fonctions

### Méthodes principales
- `get_settings()` : Récupère tous les paramètres
- `update_setting($key, $value)` : Met à jour un paramètre
- `reset_settings()` : Réinitialise aux valeurs par défaut

### Fonctions utilitaires
- `validate_email($email)` : Validation d'email
- `sanitize_text($text)` : Nettoyage de texte
- `is_valid_setting($key)` : Vérifie si une clé existe

## Exemples avancés

### Ajout d'un onglet personnalisé

```php
// Ajouter l'onglet
add_filter('cal_rdv_settings_tabs', function($tabs) {
    $tabs['personnalise'] = __('Personnalisé', 'calendrier-rdv');
    return $tabs;
});

// Ajouter le contenu de l'onglet
add_action('cal_rdv_settings_tab_personnalise', function() {
    include CAL_RDV_PLUGIN_DIR . 'templates/admin/settings/tabs/personnalise.php';
});
```

### Validation personnalisée

```php
add_filter('cal_rdv_validate_setting', function($value, $key) {
    if ($key === 'mon_champ' && !preg_match('/^[a-z0-9]+$/', $value)) {
        add_settings_error(
            'cal_rdv_settings',
            'invalid_value',
            __('Valeur invalide pour le champ personnalisé', 'calendrier-rdv')
        );
        return get_option('cal_rdv_settings')[$key]; // Garder l'ancienne valeur
    }
    return $value;
}, 10, 2);
```

## Sécurité

### Protection des données
- Toutes les entrées sont validées et échappées
- Les capacités utilisateur sont vérifiées
- Les nonces sont utilisés pour la sécurité des formulaires

### Recommandations
1. Restreindre l'accès aux paramètres aux seuls administrateurs
2. Ne jamais exposer les clés API dans le frontend
3. Utiliser les fonctions de sécurité WordPress pour les données sensibles

## Support

Pour toute question ou problème, veuillez consulter :
- La documentation officielle
- Le support technique
- Les forums de la communauté

---

*Dernière mise à jour : 01/06/2025*
