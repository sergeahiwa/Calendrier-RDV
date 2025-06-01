# Système de File d'Attente pour les Emails

## Vue d'ensemble

Le système de file d'attente gère les échecs d'envoi d'emails en les mettant en attente pour des tentatives ultérieures. Il est conçu pour être fiable, évolutif et facile à maintenir.

## Fonctionnalités

- Gestion des échecs d'envoi d'emails
- Tentatives automatiques avec backoff exponentiel
- Nettoyage automatique des anciennes entrées
- Journalisation complète des erreurs
- Interface simple d'intégration

## Architecture

### Composants principaux

1. **Table de base de données**
   - `wp_rdv_email_failures` : Stocke les échecs d'emails

2. **Classes principales**
   - `CalRdv_Email_Queue` : Gère la file d'attente
   - `CalRdv_Cron_Handler` : Gère les tâches planifiées
   - `CalRdv_Migration_1_0_0` : Gère la migration de la base de données

## Utilisation

### Ajouter un email en échec

```php
$queue = CalRdv_Email_Queue::get_instance();
$queue->add_failed_email([
    'recipient_email' => 'client@example.com',
    'recipient_name' => 'Nom du Client',
    'subject' => 'Confirmation de rendez-vous',
    'error_code' => 'smtp_error',
    'error_message' => 'Erreur de connexion SMTP',
    'email_data' => [
        'date_rdv' => '2025-05-27 14:30',
        'service' => 'Consultation',
        // autres données du template
    ]
]);
```

### Traiter la file d'attente manuellement

```php
$queue = CalRdv_Email_Queue::get_instance();
$results = $queue->process_queue(10); // Traite 10 emails maximum
```

## Configuration

### Hooks disponibles

- `calrdv_max_email_retries` : Nombre maximum de tentatives (défaut: 3)
- `calrdv_retry_delay_multiplier` : Multiplicateur du délai entre les tentatives (défaut: 2)

### Constantes de configuration

- `CAL_RDV_EMAIL_QUEUE_ENABLED` : Activer/désactiver la file d'attente (true par défaut)
- `CAL_RDV_EMAIL_QUEUE_LIMIT` : Nombre maximum d'emails à traiter par exécution (10 par défaut)

## Dépannage

### Logs

Les erreurs sont enregistrées dans les logs WordPress :
- `wp-content/debug.log` en mode débogage
- Table `wp_rdv_email_failures` pour l'historique des échecs

### Commandes utiles

```sql
-- Voir les échecs en attente
SELECT * FROM wp_rdv_email_failures 
WHERE status IN ('pending', 'retrying')
ORDER BY next_retry ASC;

-- Voir les statistiques d'échecs
SELECT status, COUNT(*) as count, MAX(created_at) as last_attempt
FROM wp_rdv_email_failures
GROUP BY status;
```

## Maintenance

### Nettoyage automatique

Les entrées sont automatiquement nettoyées après 30 jours. Pour forcer un nettoyage :

```php
$queue = CalRdv_Email_Queue::get_instance();
$deleted = $queue->cleanup_old_failures(30); // Jours à conserver
```

### Migration

La table est créée automatiquement lors de l'activation du plugin via la classe `CalRdv_Migration_1_0_0`.
