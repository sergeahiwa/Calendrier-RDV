# Calendrier RDV - Journal des modifications

## [1.6.0] - 2025-06-04

### Ajouté
- **Système de cache avancé** : Nouvelle classe `Cache_Manager` pour optimiser les performances
  - Gestion des opérations de base (get/set/delete/flush)
  - Support de l'expiration automatique des entrées
  - Compatibilité avec les environnements de test
- **Tests unitaires** : Couverture complète des tests pour le système de cache
- **Documentation** : Guide détaillé sur l'utilisation du cache dans `docs/CACHE.md`
- **Intégration continue** : Configuration GitHub Actions pour exécuter les tests automatiquement

### Modifié
- **Architecture** : Refactorisation du code pour une meilleure maintenabilité
- **Sécurité** : Renforcement des validations des clés de cache
- **Documentation** : Mise à jour du README avec les nouvelles fonctionnalités

## [1.5.1] - 2025-06-01

### Ajouté
- **Paramètres d'administration** : Nouvelle interface de gestion des paramètres avec onglets thématiques
  - Onglet Général : Informations de l'entreprise et paramètres de base
  - Créneaux Horaires : Gestion des plages d'ouverture et des créneaux
  - Notifications : Configuration des emails et rappels
  - Paiements : Paramètres des méthodes de paiement
  - Avancé : Outils de maintenance et débogage
- **Documentation** : Ajout d'un guide complet des paramètres d'administration
- **Sécurité** : Renforcement des validations et des nonces

### Modifié
- **Structure du code** : Refonte de l'architecture des paramètres pour une meilleure maintenabilité
- **Interface utilisateur** : Amélioration de l'ergonomie et de la réactivité
- **Documentation** : Mise à jour du README avec les nouvelles fonctionnalités

### Corrigé
- Problèmes de validation des champs de formulaire
- Conflits potentiels avec d'autres plugins
- Amélioration de la compatibilité avec les thèmes WordPress

## [1.5.0] - 2025-05-28

### Ajouté
- Architecture modulaire pour une meilleure maintenabilité
- Intégration native avec Divi Builder
- Système de chargement conditionnel des fonctionnalités

### Améliorations
- Meilleure séparation entre le cœur du plugin et les intégrations
- Documentation technique complète
- Scripts de déploiement automatisés

---

*Note : Ce fichier suit le format [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/)*
