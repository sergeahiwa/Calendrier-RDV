<?php
/**
 * Notification de confirmation de rendez-vous
 *
 * @package CalendrierRdv\Domain\Notification
 */

namespace CalendrierRdv\Domain\Notification;

use CalendrierRdv\Domain\Model\Appointment;

/**
 * Notification de confirmation de rendez-vous
 */
class AppointmentConfirmationNotification extends EmailNotification
{
    /**
     * @var string Modèle de notification
     */
    protected string $template = 'appointment-confirmation';
    
    /**
     * @var string Sujet de la notification
     */
    protected string $subject = 'Confirmation de votre rendez-vous';
    
    /**
     * Constructeur
     *
     * @param Appointment $appointment
     */
    public function __construct(Appointment $appointment)
    {
        parent::__construct();
        
        $this->data = array_merge($this->data, $this->prepareData($appointment));
        $this->to($appointment->getCustomerEmail());
        $this->subject = sprintf(
            'Confirmation de votre rendez-vous du %s',
            $appointment->getStartDate()->format('d/m/Y à H:i')
        );
    }
}
