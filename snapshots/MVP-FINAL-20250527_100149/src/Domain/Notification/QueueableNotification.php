<?php
/**
 * Interface pour les notifications pouvant être mises en file d'attente
 *
 * @package CalendrierRdv\Domain\Notification
 */

namespace CalendrierRdv\Domain\Notification;

/**
 * Interface pour les notifications pouvant être mises en file d'attente
 */
interface QueueableNotification extends NotificationInterface
{
    /**
     * Obtient le nom du template de la notification
     *
     * @return string
     */
    public function getTemplate(): string;
    
    /**
     * Obtient les données de la notification
     * 
     * @return array
     */
    public function getData(): array;
    
    /**
     * Obtient la date de planification de l'envoi
     * 
     * @return string|null Date au format MySQL (Y-m-d H:i:s) ou null pour immédiat
     */
    public function getScheduledAt(): ?string;
}
