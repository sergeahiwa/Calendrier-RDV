<?php
/**
 * Classe de base abstraite pour les notifications
 *
 * @package CalendrierRdv\Domain\Notification
 */

namespace CalendrierRdv\Domain\Notification;

use CalendrierRdv\Domain\Model\Appointment;

/**
 * Classe de base abstraite pour les notifications
 */
abstract class AbstractNotification implements NotificationInterface
{
    /**
     * @var string Destinataire de la notification
     */
    protected string $recipient = '';
    
    /**
     * @var array Données du modèle
     */
    protected array $data = [];
    
    /**
     * @var string Type de notification
     */
    protected string $type;
    
    /**
     * @var string Modèle de notification
     */
    protected string $template;
    
    /**
     * @var string Sujet de la notification
     */
    protected string $subject = '';
    
    /**
     * @var bool Si la notification doit être mise en file d'attente
     */
    protected bool $shouldQueue = true;
    
    /**
     * {@inheritdoc}
     */
    public function to(string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function shouldSend(): bool
    {
        return !empty($this->recipient) && !empty($this->template);
    }
    
    /**
     * Définit si la notification doit être mise en file d'attente
     *
     * @param bool $shouldQueue
     * @return self
     */
    public function shouldQueue(bool $shouldQueue = true): self
    {
        $this->shouldQueue = $shouldQueue;
        return $this;
    }
    
    /**
     * Prépare les données pour le modèle
     *
     * @param Appointment $appointment
     * @return array
     */
    protected function prepareData(Appointment $appointment): array
    {
        return [
            'appointment' => $appointment,
            'service' => $appointment->getService(),
            'provider' => $appointment->getProvider(),
            'customer_name' => $appointment->getCustomerName(),
            'customer_email' => $appointment->getCustomerEmail(),
            'start_date' => $appointment->getStartDate(),
            'end_date' => $appointment->getEndDate(),
            'status' => $appointment->getStatus(),
            'payment_status' => $appointment->getPaymentStatus(),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'admin_url' => admin_url(),
            'current_time' => current_time('mysql'),
        ];
    }
    
    /**
     * Récupère le contenu du template
     *
     * @return string
     */
    protected function getTemplateContent(): string
    {
        $template_path = CAL_RDV_PLUGIN_DIR . 'templates/notifications/' . $this->template . '.php';
        
        if (!file_exists($template_path)) {
            return '';
        }
        
        ob_start();
        extract($this->data);
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Obtient le type de notification
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Obtient le destinataire
     *
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }
    
    /**
     * Obtient le sujet
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }
}
