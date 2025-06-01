# 📘 Guide d'Utilisation du Système de Suivi de Projet

## Fonctionnalités Principales

1. **Tableau de Bord Complet**
   - Vue d'ensemble des métriques clés
   - Suivi des tâches en temps réel
   - Calendrier des livrables

2. **Suivi des Activités**
   - Journal des modifications
   - Historique des versions
   - Suivi des résolutions de bugs

3. **Métriques et Statistiques**
   - Taux de complétion
   - Progression globale
   - Temps moyen par tâche

## Comment Utiliser

### Ajouter une Tâche
```markdown
- [ ] Description de la tâche
  - [ ] Sous-tâche 1
  - [ ] Sous-tâche 2
```

### Marquer une Tâche comme Terminée
```markdown
- [x] Tâche terminée
```

### Ajouter une Activité
Utilisez les hooks WordPress pour enregistrer des activités :
```php
do_action('calendrier_rdv_feature_added', 'Nouvelle fonctionnalité', 'Détails supplémentaires');
do_action('calendrier_rdv_issue_resolved', 123, 'Correction du bug');
```

### Sections du Fichier de Suivi
1. **Aperçu** : Informations générales du projet
2. **Métriques Clés** : Indicateurs de performance
3. **Activités Récentes** : Journal des modifications
4. **Tâches en Cours** : Liste des tâches actives
5. **Calendrier** : Dates importantes
6. **Statistiques** : Données de progression
7. **Notes de Version** : Historique des versions

## Bonnes Pratiques

1. Mettez à jour le fichier après chaque modification importante
2. Utilisez les emojis pour une meilleure lisibilité
3. Maintenez la structure hiérarchique des tâches
4. Documentez les décisions importantes dans les notes de version

## Personnalisation

### Ajouter une Nouvelle Section
1. Modifiez `init_tracking_file()` dans `class-project-tracker.php`
2. Ajoutez votre section au format Markdown
3. Implémentez la logique de mise à jour si nécessaire

### Personnaliser les Métriques
1. Modifiez la méthode `update_metrics()`
2. Ajoutez vos propres indicateurs
3. Mettez à jour le template dans `init_tracking_file()`

## Dépannage

### Les Mises à Jour ne s'Affichent Pas
1. Vérifiez les permissions des fichiers
2. Consultez les logs dans `wp-content/uploads/calendrier-rdv/logs/`
3. Vérifiez que la tâche planifiée est active

### Problèmes de Formatage
1. Respectez la syntaxe Markdown
2. Vérifiez l'indentation
3. Évitez les caractères spéciaux non échappés
