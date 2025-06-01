<?php
/**
 * Service de gestion des notifications
 *
 * @package CalendrierRdv\Domain\Service
 */

namespace CalendrierRdv\Domain\Service;

use CalendrierRdv\Domain\Model\Appointment;
use CalendrierRdv\Domain\Notification\AppointmentCancellationNotification;
use CalendrierRdv\Domain\Notification\AppointmentConfirmationNotification;
use CalendrierRdv\Domain\Notification\AppointmentReminderNotification;
use CalendrierRdv\Domain\Notification\NotificationManager;

/**
 * Service de gestion des notifications
 */
class NotificationService
{
    /**
     * @var NotificationManager
     */
    private $notificationManager;
    
    /**
     * @var array Configuration des notifications
     */
    private $config;
    
    /**
     * Constructeur
     *
     * @param NotificationManager $notificationManager
     * @param array $config Configuration des notifications
     */
    public function __construct(NotificationManager $notificationManager, array $config = [])
    {
        $this->notificationManager = $notificationManager;
        $this->config = array_merge([
            'email' => [
                'enabled' => true,
                'from_email' => get_bloginfo('admin_email'),
                'from_name' => get_bloginfo('name'),
            ],
            'reminder' => [
                'enabled' => true,
                'hours_before' => 24, // Envoyer 24h avant
            ],
            'notifications' => [
                'confirmation' => true,
                'reminder' => true,
                'cancellation' => true,
                'admin_notification' => true,
            ],
        ], $config);
    }
    
    /**
     * Envoie une notification de confirmation de rendez-vous
     *
     * @param Appointment $appointment
     * @return bool
     */
    public function sendAppointmentConfirmation(Appointment $appointment): bool
    {
        if (empty($this->config['notifications']['confirmation'])) {
            return false;
        }
        
        $notification = new AppointmentConfirmationNotification($appointment);
        $this->configureNotification($notification);
        
        return $this->notificationManager->send($notification);
    }
    
    /**
     * Planifie un rappel de rendez-vous
     *
     * @param Appointment $appointment
     * @param int|null $hoursBefore Nombre d'heures avant le rendez-vous (optionnel)
     * @return bool
     */
    public function scheduleAppointmentReminder(Appointment $appointment, ?int $hoursBefore = null): bool
    {
        if (empty($this->config['notifications']['reminder'])) {
            return false;
        }
        
        $hoursBefore = $hoursBefore ?? $this->config['reminder']['hours_before'];
        $notification = new AppointmentReminderNotification($appointment, $hoursBefore);
        $this->configureNotification($notification);
        
        return $this->notificationManager->send($notification);
    }
    
    /**
     * Envoie une notification d'annulation de rendez-vous
     *
     * @param Appointment $appointment
     * @param string $reason Raison de l'annulation
     * @return bool
     */
    public function sendAppointmentCancellation(Appointment $appointment, string $reason = ''): bool
    {
        if (empty($this->config['notifications']['cancellation'])) {
            return false;
        }
        
        $notification = new AppointmentCancellationNotification($appointment, $reason);
        $this->configureNotification($notification);
        
        return $this->notificationManager->send($notification);
    }
    
    /**
     * Configure une notification avec les paramètres par défaut
     *
     * @param mixed $notification
     * @return void
     */
    protected function configureNotification($notification): void
    {
        if (method_exists($notification, 'from')) {
            $notification->from(
                $this->config['email']['from_email'],
                $this->config['email']['from_name']
            );
        }
    }
    
    /**
     * Traite les notifications en file d'attente
     *
     * @param int $limit Nombre maximum de notifications à traiter
     * @return int Nombre de notifications traitées
     */
    public function processQueue(int $limit = 50): int
    {
        return $this->notificationManager->processQueue($limit);
    }
}
