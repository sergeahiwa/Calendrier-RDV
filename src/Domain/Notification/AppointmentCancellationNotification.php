<?php
/**
 * Notification d'annulation de rendez-vous
 *
 * @package CalendrierRdv\Domain\Notification
 */

namespace CalendrierRdv\Domain\Notification;

use CalendrierRdv\Domain\Model\Appointment;

/**
 * Notification d'annulation de rendez-vous
 */
class AppointmentCancellationNotification extends EmailNotification
{
    /**
     * @var string Modèle de notification
     */
    protected string $template = 'appointment-cancellation';
    
    /**
     * @var string Sujet de la notification
     */
    protected string $subject = 'Annulation de votre rendez-vous';
    
    /**
     * @var string Raison de l'annulation
     */
    private string $cancellationReason;
    
    /**
     * Constructeur
     *
     * @param Appointment $appointment
     * @param string $cancellationReason Raison de l'annulation
     */
    public function __construct(Appointment $appointment, string $cancellationReason = '')
    {
        parent::__construct();
        
        $this->cancellationReason = $cancellationReason;
        $this->data = array_merge($this->data, $this->prepareData($appointment), [
            'cancellation_reason' => $cancellationReason,
        ]);
        
        $this->to($appointment->getCustomerEmail());
        $this->subject = sprintf(
            'Annulation de votre rendez-vous du %s',
            $appointment->getStartDate()->format('d/m/Y à H:i')
        );
    }
    
    /**
     * Prépare les données pour le modèle
     *
     * @param Appointment $appointment
     * @return array
     */
    protected function prepareData(Appointment $appointment): array
    {
        $data = parent::prepareData($appointment);
        $data['cancellation_reason'] = $this->cancellationReason;
        return $data;
    }
}
