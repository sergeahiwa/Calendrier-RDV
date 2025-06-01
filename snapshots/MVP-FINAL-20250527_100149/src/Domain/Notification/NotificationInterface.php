<?php
/**
 * Interface pour les notifications
 *
 * @package CalendrierRdv\Domain\Notification
 */

namespace CalendrierRdv\Domain\Notification;

/**
 * Interface pour les notifications
 */
interface NotificationInterface
{
    /**
     * Types de notification
     */
    public const TYPE_EMAIL = 'email';
    public const TYPE_SMS = 'sms';
    public const TYPE_ADMIN = 'admin';
    
    /**
     * Envoie la notification
     *
     * @return bool Succès de l'envoi
     */
    public function send(): bool;
    
    /**
     * Définit le destinataire
     *
     * @param string $recipient
     * @return self
     */
    public function to(string $recipient): self;
    
    /**
     * Définit les données du modèle
     *
     * @param array $data
     * @return self
     */
    public function with(array $data): self;
    
    /**
     * Vérifie si la notification peut être envoyée
     *
     * @return bool
     */
    public function shouldSend(): bool;
}
