# 📊 Système de Suivi de Projet Automatisé

## Fonctionnalités

- Mise à jour automatique de la date de dernière modification
- Journalisation des activités récentes
- Gestion des erreurs avec logs détaillés
- Exécution quotidienne via une tâche planifiée WordPress

## Structure des Fichiers

- `includes/class-project-tracker.php` : Classe principale du suivi
- `suivi-projet.md` : Fichier de suivi principal
- `logs/project-tracker.log` : Fichier de logs des opérations

## Configuration

Le système est automatiquement activé avec le plugin. Aucune configuration supplémentaire n'est nécessaire.

## Format du Fichier de Suivi

Le fichier `suivi-projet.md` suit ce format :

```markdown
# Suivi du Projet

Dernière mise à jour : YYYY-MM-DD

## Activités Récentes
- [Date] : Description de l'activité

## Tâches
- [ ] Tâche en cours
- [x] Tâche terminée
```

## Personnalisation

Pour ajouter des sections personnalisées, modifiez la méthode `update_recent_activities()` dans `class-project-tracker.php`.

## Dépannage

Consultez les logs dans `logs/project-tracker.log` pour diagnostiquer les problèmes.
