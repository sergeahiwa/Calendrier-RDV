<?php
/**
 * Notification de rappel de rendez-vous
 *
 * @package CalendrierRdv\Domain\Notification
 */

namespace CalendrierRdv\Domain\Notification;

use CalendrierRdv\Domain\Model\Appointment;

/**
 * Notification de rappel de rendez-vous
 */
class AppointmentReminderNotification extends EmailNotification
{
    /**
     * @var string Modèle de notification
     */
    protected string $template = 'appointment-reminder';
    
    /**
     * @var string Sujet de la notification
     */
    protected string $subject = 'Rappel de votre rendez-vous à venir';
    
    /**
     * @var int Délai de rappel en heures avant le rendez-vous (par défaut: 24h)
     */
    private int $reminderHours;
    
    /**
     * Constructeur
     *
     * @param Appointment $appointment
     * @param int $reminderHours Délai de rappel en heures
     */
    public function __construct(Appointment $appointment, int $reminderHours = 24)
    {
        parent::__construct();
        
        $this->reminderHours = $reminderHours;
        $this->data = array_merge($this->data, $this->prepareData($appointment));
        $this->to($appointment->getCustomerEmail());
        $this->subject = sprintf(
            'Rappel : Rendez-vous le %1$s à %2$s',
            $appointment->getStartDate()->format('d/m/Y'),
            $appointment->getStartDate()->format('H:i')
        );
        
        // Planifier l'envoi du rappel
        $reminderTime = $appointment->getStartDate()->modify("-{$reminderHours} hours");
        $this->data['scheduled_at'] = $reminderTime->format('Y-m-d H:i:s');
    }
    
    /**
     * Vérifie si le rappel doit être envoyé
     * 
     * @return bool
     */
    public function shouldSend(): bool
    {
        if (!parent::shouldSend()) {
            return false;
        }
        
        // Ne pas envoyer si le rendez-vous est déjà passé
        $now = new \DateTimeImmutable();
        $appointmentDate = $this->data['appointment']->getStartDate();
        
        return $appointmentDate > $now;
    }
}
