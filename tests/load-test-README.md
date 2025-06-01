# Tests de Charge - Calendrier RDV

Ce dossier contient les scripts pour effectuer des tests de charge sur l'application Calendrier RDV.

## Prérequis

- Windows 10/11 ou Windows Server 2016+
- PowerShell 5.1 ou supérieur
- Accès administrateur pour installer des modules PowerShell
- WordPress configuré et accessible localement

## Installation

1. **Installer les modules PowerShell requis**

   ```powershell
   # Exécuter en tant qu'administrateur
   Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
   Install-Module -Name PSWriteHTML -Force -AllowClobber -Scope CurrentUser
   ```

2. **Configurer l'URL de base**

   Modifiez la variable `$baseUrl` dans le fichier `load-test.ps1` pour pointer vers votre installation WordPress locale.

## Exécution des tests

1. **Lancer le test de charge**

   ```powershell
   .\load-test.ps1
   ```

   Options disponibles :
   - `-baseUrl` : URL de base du site (par défaut : "http://localhost/wordpress/")
   - `-totalUsers` : Nombre total d'utilisateurs virtuels (par défaut : 50)
   - `-rampUp` : Nombre d'utilisateurs à démarrer par seconde (par défaut : 10)
   - `-testDuration` : Durée du test en secondes (par défaut : 300)
   - `-resultsDir` : Répertoire de sortie pour les résultats (par défaut : "load-test-results")

   Exemple :
   ```powershell
   .\load-test.ps1 -baseUrl "http://localhost/mon-site/" -totalUsers 100 -rampUp 20 -testDuration 600
   ```

2. **Générer le rapport**

   ```powershell
   .\analyze-results.ps1 -resultsDir "chemin/vers/les/resultats"
   ```

   Le rapport sera généré au format HTML et ouvert automatiquement dans votre navigateur par défaut.

## Analyse des résultats

Le rapport généré contient les sections suivantes :

1. **Résumé du test**
   - Métriques clés (utilisateurs, requêtes, taux d'échec, temps de réponse)
   - Vue d'ensemble des performances

2. **Temps de réponse**
   - Graphique des temps de réponse au fil du temps
   - Analyse des percentiles

3. **Répartition des requêtes**
   - Répartition par type de requête (affichage du calendrier, vérification des disponibilités, prise de rendez-vous)
   - Taux de réussite par type de requête

4. **Erreurs**
   - Liste des erreurs rencontrées
   - Analyse des codes d'erreur HTTP

5. **Détails par utilisateur**
   - Statistiques détaillées pour chaque utilisateur virtuel
   - Taux de réussite et temps de réponse par utilisateur

## Conseils d'interprétation

- **Temps de réponse** : Un temps de réponse moyen inférieur à 1 seconde est considéré comme bon pour une application web.
- **Taux d'échec** : Un taux d'échec supérieur à 1% nécessite une investigation.
- **Évolutivité** : Surveillez comment le temps de réponse évolue avec le nombre d'utilisateurs.

## Dépannage

### Erreur "Impossible de charger le fichier"
Assurez-vous d'avoir les bonnes autorisations d'exécution :
```powershell
Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Temps de réponse élevés
- Vérifiez les performances du serveur (CPU, mémoire, disque)
- Activez le cache WordPress si ce n'est pas déjà fait
- Optimisez les requêtes de base de données

### Taux d'échec élevé
- Vérifiez les journaux d'erreurs de WordPress
- Assurez-vous que la configuration du serveur peut gérer le nombre de connexions simultanées
- Vérifiez les limites de mémoire PHP

## Personnalisation

Vous pouvez modifier les paramètres suivants dans le script `load-test.ps1` :

- `$thinkTimeMin` / `$thinkTimeMax` : Temps de réflexion entre les actions
- Distribution des actions (lignes `$action -le 6`, etc.)
- Données des rendez-vous de test

## Sécurité

- Ne pas utiliser ce script contre des environnements de production sans autorisation
- Les identifiants ne sont pas stockés, mais soyez prudent avec les données de test
- Utilisez toujours HTTPS pour les tests sur des réseaux non sécurisés
