<?php
/**
 * Gestionnaire de notifications
 *
 * @package CalendrierRdv\Domain\Notification
 */

namespace CalendrierRdv\Domain\Notification;

use CalendrierRdv\Database\QueryBuilder;

/**
 * Gestionnaire de notifications
 */
class NotificationManager
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;
    
    /**
     * @var array Configuration des notifications
     */
    private $config;
    
    /**
     * Constructeur
     *
     * @param QueryBuilder $queryBuilder
     * @param array $config Configuration des notifications
     */
    public function __construct(QueryBuilder $queryBuilder, array $config = [])
    {
        $this->queryBuilder = $queryBuilder;
        $this->config = $config;
    }
    
    /**
     * Envoie une notification
     *
     * @param NotificationInterface $notification
     * @return bool
     */
    public function send(NotificationInterface $notification): bool
    {
        if (!$notification->shouldSend()) {
            return false;
        }
        
        // Vérifier si la notification est activée dans la configuration
        $notificationClass = get_class($notification);
        if (isset($this->config[$notificationClass]) && $this->config[$notificationClass] === false) {
            return false;
        }
        
        // Mettre en file d'attente si nécessaire
        if ($notification instanceof QueueableNotification && $notification->shouldQueue) {
            return $this->queueNotification($notification);
        }
        
        // Envoyer immédiatement
        return $notification->send();
    }
    
    /**
     * Met une notification en file d'attente
     *
     * @param QueueableNotification $notification
     * @return bool
     */
    protected function queueNotification(QueueableNotification $notification): bool
    {
        $data = [
            'type' => $notification->getType(),
            'recipient' => $notification->getRecipient(),
            'subject' => $notification->getSubject(),
            'template' => $notification->getTemplate(),
            'data' => json_encode($notification->getData()),
            'scheduled_at' => $notification->getScheduledAt() ?? current_time('mysql'),
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        
        $result = $this->queryBuilder
            ->insert('cal_rdv_notifications', $data)
            ->execute();
            
        return (bool) $result;
    }
    
    /**
     * Traite les notifications en file d'attente
     *
     * @param int $limit Nombre maximum de notifications à traiter
     * @return int Nombre de notifications traitées
     */
    public function processQueue(int $limit = 50): int
    {
        $notifications = $this->queryBuilder
            ->select()
            ->from('cal_rdv_notifications')
            ->where('status', '=', 'pending')
            ->where('scheduled_at', '<=', current_time('mysql'))
            ->orderBy('created_at', 'ASC')
            ->limit($limit)
            ->get();
            
        $processed = 0;
        
        foreach ($notifications as $notificationData) {
            $notification = $this->createNotificationFromQueue($notificationData);
            
            if ($notification && $notification->send()) {
                $this->markAsSent($notificationData['id']);
                $processed++;
            } else {
                $this->handleFailedNotification($notificationData);
            }
        }
        
        return $processed;
    }
    
    /**
     * Crée une notification à partir des données de la file d'attente
     *
     * @param array $data
     * @return NotificationInterface|null
     */
    protected function createNotificationFromQueue(array $data): ?NotificationInterface
    {
        // Cette méthode doit être étendue dans les classes filles
        // pour créer les notifications spécifiques
        return null;
    }
    
    /**
     * Marque une notification comme envoyée
     *
     * @param int $id
     * @return bool
     */
    protected function markAsSent(int $id): bool
    {
        return (bool) $this->queryBuilder
            ->update('cal_rdv_notifications', [
                'status' => 'sent',
                'sent_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ])
            ->where('id', '=', $id)
            ->execute();
    }
    
    /**
     * Gère une notification en échec
     *
     * @param array $notificationData
     * @return void
     */
    protected function handleFailedNotification(array $notificationData): void
    {
        $maxAttempts = $this->config['max_attempts'] ?? 3;
        $attempts = $notificationData['attempts'] + 1;
        
        if ($attempts >= $maxAttempts) {
            $status = 'failed';
            // Ajout : loguer l'échec définitif dans rdv_email_failures
            $this->queryBuilder
                ->insert('rdv_email_failures', [
                    'recipient' => $notificationData['recipient'] ?? '',
                    'subject' => $notificationData['subject'] ?? '',
                    'body' => $notificationData['data'] ?? '',
                    'error_message' => $notificationData['last_error'] ?? 'Echec définitif après tentatives',
                    'attempts' => $attempts,
                    'last_attempt' => current_time('mysql'),
                    'created_at' => current_time('mysql'),
                ])
                ->execute();
        } else {
            $status = 'pending';
            // Planifier une nouvelle tentative avec un délai exponentiel
            $delay = min(pow(2, $attempts), 24); // Maximum 24 heures
            $scheduledAt = date('Y-m-d H:i:s', strtotime("+{$delay} hours"));
        }
        
        $this->queryBuilder
            ->update('cal_rdv_notifications', [
                'attempts' => $attempts,
                'status' => $status,
                'scheduled_at' => $scheduledAt ?? null,
                'updated_at' => current_time('mysql'),
            ])
            ->where('id', '=', $notificationData['id'])
            ->execute();
    }
}
