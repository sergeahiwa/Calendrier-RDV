<?php
/**
 * Classe de base pour les notifications par email
 *
 * @package CalendrierRdv\Domain\Notification
 */

namespace CalendrierRdv\Domain\Notification;

use CalendrierRdv\Domain\Model\Appointment;

/**
 * Classe de base pour les notifications par email
 */
abstract class EmailNotification extends AbstractNotification implements QueueableNotification
{
    /**
     * @var string Type de notification
     */
    protected string $type = NotificationInterface::TYPE_EMAIL;
    
    /**
     * @var string Adresse email de l'expéditeur
     */
    protected string $fromEmail;
    
    /**
     * @var string Nom de l'expéditeur
     */
    protected string $fromName;
    
    /**
     * @var array En-têtes additionnels
     */
    protected array $headers = [];
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->fromEmail = get_bloginfo('admin_email');
        $this->fromName = get_bloginfo('name');
    }
    
    /**
     * Définit l'expéditeur
     *
     * @param string $email
     * @param string $name
     * @return self
     */
    public function from(string $email, string $name = ''): self
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
        return $this;
    }
    
    /**
     * Ajoute un en-tête personnalisé
     *
     * @param string $header
     * @return self
     */
    public function withHeader(string $header): self
    {
        $this->headers[] = $header;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function send(): bool
    {
        if (!$this->shouldSend()) {
            return false;
        }
        
        $headers = array_merge([
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %1$s <%2$s>', $this->fromName, $this->fromEmail),
        ], $this->headers);
        
        $message = $this->getTemplateContent();
        
        return wp_mail(
            $this->recipient,
            $this->subject,
            $message,
            $headers
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTemplate(): string
    {
        return $this->template;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getScheduledAt(): ?string
    {
        return $this->data['scheduled_at'] ?? null;
    }
}
