<?php
/**
 * Interface pour le repository des rendez-vous
 *
 * @package CalendrierRdv\Domain\Repository
 */

namespace CalendrierRdv\Domain\Repository;

use CalendrierRdv\Domain\Model\Appointment;
use CalendrierRdv\Domain\Model\Provider;
use CalendrierRdv\Domain\Model\Service;
use DateTimeInterface;

/**
 * Interface pour le repository des rendez-vous
 */
interface AppointmentRepositoryInterface extends RepositoryInterface
{
    /**
     * Trouve les rendez-vous à venir pour un prestataire
     *
     * @param int $providerId ID du prestataire
     * @param DateTimeInterface|null $startDate Date de début (optionnel)
     * @param DateTimeInterface|null $endDate Date de fin (optionnel)
     * @return Appointment[]
     */
    public function findUpcomingByProvider(
        int $providerId,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): array;
    
    /**
     * Trouve les rendez-vous passés pour un client
     *
     * @param string $customerEmail Email du client
     * @param int $limit Limite de résultats
     * @return Appointment[]
     */
    public function findPastByCustomer(string $customerEmail, int $limit = 10): array;
    
    /**
     * Vérifie la disponibilité d'un créneau pour un prestataire
     *
     * @param int $providerId ID du prestataire
     * @param DateTimeInterface $startDate Date et heure de début
     * @param DateTimeInterface $endDate Date et heure de fin
     * @param int|null $excludeAppointmentId ID du rendez-vous à exclure (pour les mises à jour)
     * @return bool True si le créneau est disponible
     */
    public function isTimeSlotAvailable(
        int $providerId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?int $excludeAppointmentId = null
    ): bool;
    
    /**
     * Trouve les rendez-vous à venir pour un client
     *
     * @param string $customerEmail Email du client
     * @return Appointment[]
     */
    public function findUpcomingByCustomer(string $customerEmail): array;
    
    /**
     * Trouve les rendez-vous par statut
     *
     * @param string $status Statut à filtrer
     * @param DateTimeInterface|null $startDate Date de début (optionnel)
     * @param DateTimeInterface|null $endDate Date de fin (optionnel)
     * @return Appointment[]
     */
    public function findByStatus(
        string $status,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): array;
    
    /**
     * Trouve les rendez-vous par statut de paiement
     *
     * @param string $paymentStatus Statut de paiement à filtrer
     * @param DateTimeInterface|null $startDate Date de début (optionnel)
     * @param DateTimeInterface|null $endDate Date de fin (optionnel)
     * @return Appointment[]
     */
    public function findByPaymentStatus(
        string $paymentStatus,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): array;
}
