# Scénarios de Test - Calendrier RDV

## 1. Tests Fonctionnels

### 1.1 Gestion des Rendez-vous
- [ ] **Création d'un rendez-vous**
  - Créer un nouveau rendez-vous avec des données valides
  - Vérifier l'ajout en base de données
  - Vérifier la génération de la référence unique
  - Vérifier l'envoi des notifications

- [ ] **Modification d'un rendez-vous**
  - Changer l'horaire d'un rendez-vous existant
  - Changer le prestataire
  - Vérifier les mises à jour en base
  - Vérifier l'envoi des notifications de mise à jour

- [ ] **Annulation d'un rendez-vous**
  - Annuler un rendez-vous existant
  - Vérifier le statut en base
  - Vérifier l'envoi des notifications d'annulation

### 1.2 Gestion des Prestataires
- [ ] Ajout d'un nouveau prestataire
- [ ] Modification des informations d'un prestataire
- [ ] Désactivation d'un prestataire

## 2. Tests d'Intégration

### 2.1 Calendrier
- [ ] Affichage des créneaux disponibles
- [ ] Gestion des chevauchements
- [ ] Prise en compte des jours fériés
- [ ] Gestion des fuseaux horaires

### 2.2 Notifications
- [ ] Notification de confirmation
- [ ] Rappel de rendez-vous
- [ ] Notification d'annulation
- [ ] Gestion des échecs d'envoi

## 3. Tests de Performance

- [ ] Temps de chargement du calendrier avec 100+ rendez-vous
- [ ] Temps de réponse de l'API avec charge élevée
- [ ] Gestion des accès concurrentiels

## 4. Tests de Sécurité

- [ ] Validation des entrées utilisateur
- [ ] Protection CSRF
- [ ] Gestion des permissions
- [ ] Journalisation des actions sensibles

## 5. Scénarios de Test Manuels

### 5.1 Création de Rendez-vous
1. Se connecter à l'interface d'administration
2. Naviguer vers la section "Nouveau Rendez-vous"
3. Remplir le formulaire avec des données valides
4. Soumettre le formulaire
5. Vérifier :
   - L'affichage du message de succès
   - La présence du rendez-vous dans le calendrier
   - La réception des notifications

### 5.2 Gestion des Conflits
1. Essayer de créer un rendez-vous sur un créneau déjà réservé
2. Vérifier que le système refuse la réservation
3. Vérifier l'affichage d'un message d'erreur approprié

## 6. Données de Test Recommandées

### Prestataires
- Jean Dupont (Médecine Générale)
- Marie Martin (Dermatologie)
- Paul Durand (Ophtalmologie)

### Services
- Consultation Générale (30 min)
- Dermatologie (45 min)
- Bilan de Santé (60 min)

## 7. Environnement de Test

- URL : http://localhost:8000
- Identifiants admin : admin / [mot de passe]
- Base de données : wordpress_test
- Fuseau horaire : Europe/Paris

## 8. Journal des Tests

| Date       | Test Effectué | Résultat | Commentaire |
|------------|---------------|----------|-------------|
| 2025-05-27 | Création RDV  | ✅       | Fonctionne  |
| 2025-05-27 | Annulation    | ⚠️       | Notification manquante |
