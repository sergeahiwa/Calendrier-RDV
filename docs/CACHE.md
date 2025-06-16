# Gestion du Cache

Ce document explique comment le système de cache est implémenté dans le plugin Calendrier RDV.

## Fonctionnalités

- Mise en cache des requêtes fréquentes avec expiration personnalisable
- Invalidation automatique du cache lors des mises à jour
- Support des tests unitaires et d'intégration
- Nettoyage du cache à la désactivation du plugin
- Compatibilité avec les environnements hors WordPress pour les tests

## Architecture

Le système de cache utilise le pattern Singleton et implémente les méthodes essentielles :

- `get($key)` : Récupère une valeur du cache
- `set($key, $value, $expiration = 0)` : Stocke une valeur dans le cache
- `delete($key)` : Supprime une entrée du cache
- `flush()` : Vide tout le cache du plugin

## Utilisation

### Initialisation

Le Cache_Manager s'initialise automatiquement lors de sa première utilisation. Aucune configuration n'est nécessaire.

### Mettre en cache une requête

```php
use CalendrierRdv\Includes\Cache_Manager;

function get_popular_services() {
    $cache_key = 'popular_services';
    
    // Essayer de récupérer depuis le cache
    $services = Cache_Manager::get($cache_key);
    
    if ($services === null) {
        // Requête coûteuse si non en cache
        global $wpdb;
        $services = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rdv_services 
             WHERE is_active = 1 
             ORDER BY view_count DESC 
             LIMIT 5"
        );
        
        // Mise en cache pour 1 heure
        Cache_Manager::set($cache_key, $services, HOUR_IN_SECONDS);
    }
    
    return $services;
}
```

### Invalider le cache

```php
// Supprimer un élément spécifique
Cache_Manager::delete('popular_services');

// Vider tout le cache du plugin (préfixe 'cal_rdv_')
Cache_Manager::flush();
```

## Tests

Le système inclut des tests unitaires complets :

- Tests des opérations de base (get/set/delete)
- Tests d'expiration du cache
- Tests de nettoyage du cache
- Tests en environnement isolé

Pour exécuter les tests :

```bash
composer test
# ou
./vendor/bin/phpunit tests/unit/CacheManagerTest.php
```

## Bonnes pratiques

1. **Clés de cache** :
   - Utilisez des préfixes descriptifs
   - Incluez les paramètres importants dans la clé
   - Exemple : `user_{$user_id}_appointments`

2. **Durée de vie** :
   - Court terme (quelques minutes) pour les données fréquemment mises à jour
   - Long terme (plusieurs heures) pour les données stables
   - Utilisez les constantes WordPress comme `HOUR_IN_SECONDS`

3. **Invalidation** :
   - Invalidez le cache après les opérations d'écriture
   - Utilisez des hooks d'action pour une invalidation automatique

## Dépannage

### Problèmes courants

1. **Cache non mis à jour** :
   - Vérifiez que la clé de cache est cohérente
   - Assurez-vous que `flush()` est appelé après les mises à jour

2. **Performances médiocres** :
   - Vérifiez la taille des objets mis en cache
   - Évitez de mettre en cache des objets trop volumineux

## Sécurité

- Toutes les clés de cache sont préfixées avec `cal_rdv_`
- Les données sont sérialisées avant stockage
- Le cache est isolé par site dans les installations multisite

## Références

- [Documentation WordPress sur le cache](https://developer.wordpress.org/apis/handbook/caching/)
- [Bonnes pratiques de mise en cache](https://www.smashingmagazine.com/2016/09/making-a-service-worker/)

1. **Durée de vie** : 
   - Données rarement mises à jour : `WEEK_IN_SECONDS`
   - Données fréquemment mises à jour : `HOUR_IN_SECONDS`
   - Données critiques : `0` (pas de cache)

2. **Clés de cache** :
   - Utilisez des noms descriptifs
   - Évitez les espaces et caractères spéciaux
   - Utilisez des préfixes pour grouper les données similaires

3. **Invalidation** :
   - Invalidez le cache à chaque mise à jour des données
   - Utilisez des hooks WordPress pour une invalidation automatique

## Dépannage

### Le cache ne se met pas à jour
- Vérifiez que la durée de vie n'est pas trop longue
- Vérifiez que l'invalidation du cache est bien appelée après les mises à jour

### Problèmes de performances
- Réduisez la durée de vie du cache pour les données fréquemment mises à jour
- Vérifiez que seules les données coûteuses sont mises en cache
