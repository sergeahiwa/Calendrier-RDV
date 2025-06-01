<?php
/**
 * Service pour la gestion des rendez-vous
 *
 * @package CalendrierRdv\Domain\Service
 */

namespace CalendrierRdv\Domain\Service;

use CalendrierRdv\Domain\Model\Appointment;
use CalendrierRdv\Domain\Model\Provider;
use CalendrierRdv\Domain\Model\Service;
use CalendrierRdv\Domain\Repository\AppointmentRepositoryInterface;
use CalendrierRdv\Domain\Repository\ProviderRepositoryInterface;
use CalendrierRdv\Domain\Repository\ServiceRepositoryInterface;
use CalendrierRdv\Domain\Exception\InvalidStatusException;
use DateTimeInterface;
use DateTimeImmutable;
use DateInterval;

/**
 * Service pour la gestion des rendez-vous
 */
class AppointmentService
{
    /**
     * @var AppointmentRepositoryInterface
     */
    private $appointmentRepository;

    /**
     * @var ServiceRepositoryInterface
     */
    private $serviceRepository;

    /**
     * @var ProviderRepositoryInterface
     */
    private $providerRepository;

    /**
     * Constructeur
     *
     * @param AppointmentRepositoryInterface $appointmentRepository
     * @param ServiceRepositoryInterface $serviceRepository
     * @param ProviderRepositoryInterface $providerRepository
     */
    public function __construct(
        AppointmentRepositoryInterface $appointmentRepository,
        ServiceRepositoryInterface $serviceRepository,
        ProviderRepositoryInterface $providerRepository
    ) {
        $this->appointmentRepository = $appointmentRepository;
        $this->serviceRepository = $serviceRepository;
        $this->providerRepository = $providerRepository;
    }

    /**
     * Crée un nouveau rendez-vous
     *
     * @param array $data Données du rendez-vous
     * @return Appointment
     * @throws \InvalidArgumentException Si les données sont invalides
     * @throws \RuntimeException Si le créneau n'est pas disponible
     */
    public function createAppointment(array $data): Appointment
    {
        $this->validateAppointmentData($data);
        
        // Récupérer le service
        $service = $this->serviceRepository->find((int) $data['service_id']);
        if (!$service) {
            throw new \InvalidArgumentException("Le service demandé n'existe pas");
        }
        
        // Récupérer le prestataire
        $provider = $this->providerRepository->find((int) $data['provider_id']);
        if (!$provider) {
            throw new \InvalidArgumentException("Le prestataire demandé n'existe pas");
        }
        
        // Créer les objets de date
        $startDate = new DateTimeImmutable($data['start_date']);
        $endDate = $startDate->add(new DateInterval('PT' . $service->getDuration() . 'M'));
        
        // Vérifier la disponibilité du créneau
        if (!$this->isTimeSlotAvailable($provider->getId(), $startDate, $endDate)) {
            throw new \RuntimeException("Ce créneau n'est plus disponible");
        }
        
        // Créer le rendez-vous
        $appointment = new Appointment();
        $appointment
            ->setService($service)
            ->setProvider($provider)
            ->setCustomerId($data['customer_id'] ?? null)
            ->setCustomerName($data['customer_name'])
            ->setCustomerEmail($data['customer_email'])
            ->setCustomerPhone($data['customer_phone'] ?? null)
            ->setCustomerNotes($data['customer_notes'] ?? '')
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setStatus(Appointment::STATUS_PENDING)
            ->setPrice($data['price'] ?? $service->getPrice())
            ->setPaymentStatus(Appointment::PAYMENT_STATUS_PENDING)
            ->setPaymentMethod($data['payment_method'] ?? null);
            
        return $this->appointmentRepository->save($appointment);
    }

    /**
     * Met à jour un rendez-vous existant
     *
     * @param int $id ID du rendez-vous
     * @param array $data Données à mettre à jour
     * @return Appointment
     * @throws \InvalidArgumentException Si les données sont invalides
     * @throws \RuntimeException Si le rendez-vous n'existe pas ou n'est pas modifiable
     */
    public function updateAppointment(int $id, array $data): Appointment
    {
        $appointment = $this->getAppointment($id);
        
        // Vérifier si le rendez-vous peut être modifié
        if (!$this->isAppointmentModifiable($appointment)) {
            throw new \RuntimeException("Ce rendez-vous ne peut plus être modifié");
        }
        
        // Mettre à jour les champs fournis
        if (isset($data['customer_name'])) {
            $appointment->setCustomerName($data['customer_name']);
        }
        
        if (isset($data['customer_phone'])) {
            $appointment->setCustomerPhone($data['customer_phone']);
        }
        
        if (isset($data['customer_notes'])) {
            $appointment->setCustomerNotes($data['customer_notes']);
        }
        
        // Si le service change, mettre à jour la durée et le prix
        if (isset($data['service_id']) && $data['service_id'] != $appointment->getService()->getId()) {
            $service = $this->serviceRepository->find((int) $data['service_id']);
            if (!$service) {
                throw new \InvalidArgumentException("Le service demandé n'existe pas");
            }
            
            $appointment
                ->setService($service)
                ->setEndDate($appointment->getStartDate()->add(new DateInterval('PT' . $service->getDuration() . 'M')))
                ->setPrice($data['price'] ?? $service->getPrice());
        }
        
        // Si le créneau change, vérifier la disponibilité
        if (isset($data['start_date'])) {
            $newStartDate = new DateTimeImmutable($data['start_date']);
            $newEndDate = $newStartDate->add(
                new DateInterval('PT' . $appointment->getService()->getDuration() . 'M')
            );
            
            // Vérifier la disponibilité du nouveau créneau (en excluant le RDV actuel)
            if (!$this->isTimeSlotAvailable(
                $appointment->getProvider()->getId(),
                $newStartDate,
                $newEndDate,
                $appointment->getId()
            )) {
                throw new \RuntimeException("Le nouveau créneau n'est pas disponible");
            }
            
            $appointment
                ->setStartDate($newStartDate)
                ->setEndDate($newEndDate);
        }
        
        return $this->appointmentRepository->save($appointment);
    }

    /**
     * Annule un rendez-vous
     *
     * @param int $id ID du rendez-vous
     * @param string $reason Raison de l'annulation
     * @return Appointment
     * @throws \RuntimeException Si le rendez-vous n'existe pas ou ne peut pas être annulé
     */
    public function cancelAppointment(int $id, string $reason = ''): Appointment
    {
        $appointment = $this->getAppointment($id);
        
        // Vérifier si le rendez-vous peut être annulé
        if (!$this->isAppointmentCancellable($appointment)) {
            throw new \RuntimeException("Ce rendez-vous ne peut plus être annulé");
        }
        
        // Mettre à jour le statut
        $appointment
            ->setStatus(Appointment::STATUS_CANCELLED)
            ->setCustomerNotes(trim($appointment->getCustomerNotes() . "\n\nAnnulé: " . $reason));
            
        // Si un paiement a été effectué, le marquer comme à rembourser
        if ($appointment->getPaymentStatus() === Appointment::PAYMENT_STATUS_PAID) {
            $appointment->setPaymentStatus(Appointment::PAYMENT_STATUS_REFUNDED);
        }
        
        return $this->appointmentRepository->save($appointment);
    }

    /**
     * Confirme un rendez-vous
     *
     * @param int $id ID du rendez-vous
     * @return Appointment
     * @throws \RuntimeException Si le rendez-vous n'existe pas ou ne peut pas être confirmé
     */
    public function confirmAppointment(int $id): Appointment
    {
        $appointment = $this->getAppointment($id);
        
        if ($appointment->getStatus() !== Appointment::STATUS_PENDING) {
            throw new \RuntimeException("Seuls les rendez-vous en attente peuvent être confirmés");
        }
        
        $appointment->setStatus(Appointment::STATUS_CONFIRMED);
        
        return $this->appointmentRepository->save($appointment);
    }

    /**
     * Marque un rendez-vous comme terminé
     *
     * @param int $id ID du rendez-vous
     * @param string $notes Notes complémentaires
     * @return Appointment
     * @throws \RuntimeException Si le rendez-vous n'existe pas ou ne peut pas être marqué comme terminé
     */
    public function completeAppointment(int $id, string $notes = ''): Appointment
    {
        $appointment = $this->getAppointment($id);
        
        if ($appointment->getStatus() === Appointment::STATUS_CANCELLED) {
            throw new \RuntimeException("Un rendez-vous annulé ne peut pas être marqué comme terminé");
        }
        
        if ($appointment->getStatus() === Appointment::STATUS_COMPLETED) {
            return $appointment; // Déjà terminé
        }
        
        $appointment
            ->setStatus(Appointment::STATUS_COMPLETED)
            ->setCustomerNotes(trim($appointment->getCustomerNotes() . "\n\nTerminé: " . $notes));
            
        return $this->appointmentRepository->save($appointment);
    }

    /**
     * Récupère un rendez-vous par son ID
     *
     * @param int $id ID du rendez-vous
     * @return Appointment
     * @throws \RuntimeException Si le rendez-vous n'existe pas
     */
    public function getAppointment(int $id): Appointment
    {
        $appointment = $this->appointmentRepository->find($id);
        
        if (!$appointment) {
            throw new \RuntimeException("Le rendez-vous demandé n'existe pas");
        }
        
        return $appointment;
    }

    /**
     * Récupère les rendez-vous à venir pour un prestataire
     *
     * @param int $providerId ID du prestataire
     * @param DateTimeInterface|null $startDate Date de début (optionnel)
     * @param DateTimeInterface|null $endDate Date de fin (optionnel)
     * @return Appointment[]
     */
    public function getUpcomingAppointmentsByProvider(
        int $providerId,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): array {
        return $this->appointmentRepository->findUpcomingByProvider($providerId, $startDate, $endDate);
    }

    /**
     * Récupère les rendez-vous passés pour un client
     *
     * @param string $customerEmail Email du client
     * @param int $limit Nombre maximum de résultats
     * @return Appointment[]
     */
    public function getPastAppointmentsByCustomer(string $customerEmail, int $limit = 10): array
    {
        return $this->appointmentRepository->findPastByCustomer($customerEmail, $limit);
    }

    /**
     * Récupère les rendez-vous à venir pour un client
     *
     * @param string $customerEmail Email du client
     * @return Appointment[]
     */
    public function getUpcomingAppointmentsByCustomer(string $customerEmail): array
    {
        return $this->appointmentRepository->findUpcomingByCustomer($customerEmail);
    }

    /**
     * Vérifie si un créneau est disponible pour un prestataire
     *
     * @param int $providerId ID du prestataire
     * @param DateTimeInterface $startDate Date et heure de début
     * @param DateTimeInterface $endDate Date et heure de fin
     * @param int|null $excludeAppointmentId ID du rendez-vous à exclure (pour les mises à jour)
     * @return bool
     */
    public function isTimeSlotAvailable(
        int $providerId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?int $excludeAppointmentId = null
    ): bool {
        return $this->appointmentRepository->isTimeSlotAvailable(
            $providerId,
            $startDate,
            $endDate,
            $excludeAppointmentId
        );
    }

    /**
     * Récupère les créneaux disponibles pour un prestataire et une journée donnée
     *
     * @param int $providerId ID du prestataire
     * @param DateTimeInterface $date Date pour laquelle chercher les créneaux
     * @param int $duration Durée du créneau en minutes
     * @param string $timeFormat Format d'heure (par défaut: H:i)
     * @param int $interval Intervalle entre les créneaux en minutes (par défaut: 30)
     * @param string $startTime Heure de début (format H:i, par défaut: 09:00)
     * @param string $endTime Heure de fin (format H:i, par défaut: 19:00)
     * @return array Liste des créneaux disponibles au format demandé
     */
    public function getAvailableTimeSlots(
        int $providerId,
        DateTimeInterface $date,
        int $duration,
        string $timeFormat = 'H:i',
        int $interval = 30,
        string $startTime = '09:00',
        string $endTime = '19:00'
    ): array {
        $availableSlots = [];
        $currentDate = $date->format('Y-m-d');
        
        // Créer des objets DateTime pour les heures de début et de fin
        $startDateTime = new DateTimeImmutable($currentDate . ' ' . $startTime);
        $endDateTime = new DateTimeImmutable($currentDate . ' ' . $endTime);
        
        // Vérifier si c'est aujourd'hui, si oui, commencer à partir de maintenant + 1h
        $now = new DateTimeImmutable();
        if ($date->format('Y-m-d') === $now->format('Y-m-d')) {
            $startDateTime = $now->modify('+1 hour');
            // Arrondir aux 30 minutes supérieures
            $minutes = (int) $startDateTime->format('i');
            $roundedMinutes = ceil($minutes / 30) * 30;
            $startDateTime = $startDateTime->setTime(
                (int) $startDateTime->format('H') + (int) ($roundedMinutes / 60),
                $roundedMinutes % 60
            );
        }
        
        // Parcourir les créneaux par intervalles
        $currentSlot = $startDateTime;
        
        while ($currentSlot < $endDateTime) {
            $slotEnd = $currentSlot->add(new DateInterval('PT' . $duration . 'M'));
            
            // Si le créneau dépasse l'heure de fin, on arrête
            if ($slotEnd > $endDateTime) {
                break;
            }
            
            // Vérifier si le créneau est disponible
            if ($this->isTimeSlotAvailable($providerId, $currentSlot, $slotEnd)) {
                $availableSlots[] = $currentSlot->format($timeFormat);
            }
            
            // Passer au créneau suivant
            $currentSlot = $currentSlot->add(new DateInterval('PT' . $interval . 'M'));
        }
        
        return $availableSlots;
    }

    /**
     * Vérifie si un rendez-vous peut être modifié
     *
     * @param Appointment $appointment Rendez-vous à vérifier
     * @return bool
     */
    private function isAppointmentModifiable(Appointment $appointment): bool
    {
        // Ne peut pas modifier un rendez-vous annulé ou terminé
        return !in_array($appointment->getStatus(), [
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_COMPLETED
        ]);
    }

    /**
     * Vérifie si un rendez-vous peut être annulé
     *
     * @param Appointment $appointment Rendez-vous à vérifier
     * @return bool
     */
    private function isAppointmentCancellable(Appointment $appointment): bool
    {
        // Ne peut pas annuler un rendez-vous déjà annulé ou terminé
        return !in_array($appointment->getStatus(), [
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_COMPLETED
        ]);
    }

    /**
     * Valide les données d'un rendez-vous
     *
     * @param array $data Données à valider
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    private function validateAppointmentData(array $data): void
    {
        $requiredFields = [
            'service_id',
            'provider_id',
            'customer_name',
            'customer_email',
            'start_date'
        ];
        
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new \InvalidArgumentException(sprintf(
                'Les champs suivants sont obligatoires : %s',
                implode(', ', $missingFields)
            ));
        }
        
        if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("L'email du client n'est pas valide");
        }
        
        if (isset($data['start_date'])) {
            try {
                $startDate = new DateTimeImmutable($data['start_date']);
                if ($startDate < new DateTimeImmutable()) {
                    throw new \InvalidArgumentException("La date de début doit être dans le futur");
                }
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Format de date invalide");
            }
        }
    }
}
