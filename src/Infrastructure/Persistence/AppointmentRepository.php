<?php
/**
 * Implémentation du repository des rendez-vous
 *
 * @package CalendrierRdv\Infrastructure\Persistence
 */

namespace CalendrierRdv\Infrastructure\Persistence;

use CalendrierRdv\Domain\Model\Appointment;
use CalendrierRdv\Domain\Model\Provider;
use CalendrierRdv\Domain\Model\Service;
use CalendrierRdv\Domain\Repository\AppointmentRepositoryInterface;
use CalendrierRdv\Database\QueryBuilder;
use DateTimeInterface;
use Exception;

/**
 * Implémentation concrète du repository des rendez-vous
 */
class AppointmentRepository implements AppointmentRepositoryInterface
{
    /**
     * @var string Nom de la table
     */
    private const TABLE_NAME = 'cal_rdv_appointments';

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var ServiceRepository
     */
    private $serviceRepository;

    /**
     * @var ProviderRepository
     */
    private $providerRepository;

    /**
     * Constructeur
     *
     * @param QueryBuilder $queryBuilder
     * @param ServiceRepository $serviceRepository
     * @param ProviderRepository $providerRepository
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        ServiceRepository $serviceRepository,
        ProviderRepository $providerRepository
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->serviceRepository = $serviceRepository;
        $this->providerRepository = $providerRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?Appointment
    {
        $result = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME)
            ->where('id', '=', $id)
            ->getFirst();

        if (!$result) {
            return null;
        }

        return $this->hydrateAppointment($result);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(
        array $criteria = [],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME);

        // Appliquer les critères
        foreach ($criteria as $field => $value) {
            $query->where($field, '=', $value);
        }

        // Appliquer le tri
        foreach ($orderBy as $field => $direction) {
            $query->orderBy($field, $direction);
        }

        // Appliquer la limite et l'offset
        if ($limit !== null) {
            $query->limit($limit);
            if ($offset !== null) {
                $query->offset($offset);
            }
        }

        $results = $query->get();
        
        return array_map(
            fn(array $data) => $this->hydrateAppointment($data),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria): ?Appointment
    {
        $results = $this->findBy($criteria, [], 1);
        return $results[0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function findUpcomingByProvider(
        int $providerId,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): array {
        $query = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME)
            ->where('provider_id', '=', $providerId)
            ->where('start_date', '>=', current_time('mysql'))
            ->orderBy('start_date', 'ASC');

        if ($startDate) {
            $query->where('start_date', '>=', $startDate->format('Y-m-d H:i:s'));
        }

        if ($endDate) {
            $query->where('start_date', '<=', $endDate->format('Y-m-d H:i:s'));
        }

        $results = $query->get();
        
        return array_map(
            fn(array $data) => $this->hydrateAppointment($data),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findPastByCustomer(string $customerEmail, int $limit = 10): array
    {
        $results = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME)
            ->where('customer_email', '=', $customerEmail)
            ->where('start_date', '<', current_time('mysql'))
            ->orderBy('start_date', 'DESC')
            ->limit($limit)
            ->get();
            
        return array_map(
            fn(array $data) => $this->hydrateAppointment($data),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findUpcomingByCustomer(string $customerEmail): array
    {
        $results = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME)
            ->where('customer_email', '=', $customerEmail)
            ->where('start_date', '>=', current_time('mysql'))
            ->where('status', '!=', Appointment::STATUS_CANCELLED)
            ->orderBy('start_date', 'ASC')
            ->get();
            
        return array_map(
            fn(array $data) => $this->hydrateAppointment($data),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isTimeSlotAvailable(
        int $providerId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?int $excludeAppointmentId = null
    ): bool {
        $query = $this->queryBuilder
            ->select(['COUNT(*) as count'])
            ->from(self::TABLE_NAME)
            ->where('provider_id', '=', $providerId)
            ->where('status', '!=', Appointment::STATUS_CANCELLED) // Ne pas compter les RDV annulés
            ->where(
                function($query) use ($startDate, $endDate) {
                    $query->where(
                        function($q) use ($startDate, $endDate) {
                            // Début du RDV dans le créneau
                            $q->where('start_date', '>=', $startDate->format('Y-m-d H:i:s'))
                              ->where('start_date', '<', $endDate->format('Y-m-d H:i:s'));
                        }
                    )->orWhere(
                        function($q) use ($startDate, $endDate) {
                            // Fin du RDV dans le créneau
                            $q->where('end_date', '>', $startDate->format('Y-m-d H:i:s'))
                              ->where('end_date', '<=', $endDate->format('Y-m-d H:i:s'));
                        }
                    )->orWhere(
                        function($q) use ($startDate, $endDate) {
                            // Créneau qui englobe le RDV
                            $q->where('start_date', '<=', $startDate->format('Y-m-d H:i:s'))
                              ->where('end_date', '>=', $endDate->format('Y-m-d H:i:s'));
                        }
                    );
                }
            );

        if ($excludeAppointmentId !== null) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        $result = $query->getFirst();
        return $result && $result['count'] === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function findByStatus(
        string $status,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): array {
        $query = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME)
            ->where('status', '=', $status);

        if ($startDate) {
            $query->where('start_date', '>=', $startDate->format('Y-m-d H:i:s'));
        }

        if ($endDate) {
            $query->where('start_date', '<=', $endDate->format('Y-m-d H:i:s'));
        }

        $query->orderBy('start_date', 'ASC');
        
        $results = $query->get();
        
        return array_map(
            fn(array $data) => $this->hydrateAppointment($data),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByPaymentStatus(
        string $paymentStatus,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): array {
        $query = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME)
            ->where('payment_status', '=', $paymentStatus);

        if ($startDate) {
            $query->where('start_date', '>=', $startDate->format('Y-m-d H:i:s'));
        }

        if ($endDate) {
            $query->where('start_date', '<=', $endDate->format('Y-m-d H:i:s'));
        }

        $query->orderBy('start_date', 'ASC');
        
        $results = $query->get();
        
        return array_map(
            fn(array $data) => $this->hydrateAppointment($data),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function save(Appointment $appointment): Appointment
    {
        $data = $appointment->toArray();
        
        // Extraire les relations
        $serviceId = $data['service_id'];
        $providerId = $data['provider_id'];
        
        // Ne pas inclure les champs de date s'ils ne sont pas définis
        unset(
            $data['created_at'],
            $data['updated_at'],
            $data['service_id'],
            $data['provider_id'],
            $data['service'],
            $data['provider']
        );
        
        // Formater les dates
        $data['start_date'] = $appointment->getStartDate()->format('Y-m-d H:i:s');
        $data['end_date'] = $appointment->getEndDate()->format('Y-m-d H:i:s');
        
        if ($appointment->getId() === null) {
            // Insertion
            $id = $this->queryBuilder
                ->insert(self::TABLE_NAME, $data)
                ->execute();
                
            $appointment->setId($id);
        } else {
            // Mise à jour
            $this->queryBuilder
                ->update(self::TABLE_NAME, $data)
                ->where('id', '=', $appointment->getId())
                ->execute();
        }
        
        // Récupérer l'entité fraîche depuis la base pour s'assurer d'avoir les bonnes dates
        if ($appointment->getId() !== null) {
            $savedAppointment = $this->find($appointment->getId());
            if ($savedAppointment) {
                return $savedAppointment;
            }
        }
        
        return $appointment;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Appointment $appointment): bool
    {
        if ($appointment->getId() === null) {
            return false;
        }
        
        $affected = $this->queryBuilder
            ->delete()
            ->from(self::TABLE_NAME)
            ->where('id', '=', $appointment->getId())
            ->execute();
            
        return $affected > 0;
    }

    /**
     * Hydrate un rendez-vous à partir de données brutes
     *
     * @param array $data Données brutes
     * @return Appointment
     */
    private function hydrateAppointment(array $data): Appointment
    {
        // Récupérer le service associé
        $service = null;
        if (!empty($data['service_id'])) {
            $service = $this->serviceRepository->find((int) $data['service_id']);
        }
        
        // Récupérer le prestataire associé
        $provider = null;
        if (!empty($data['provider_id'])) {
            $provider = $this->providerRepository->find((int) $data['provider_id']);
        }
        
        // Créer une instance de rendez-vous avec les données de base
        $appointment = Appointment::fromArray($data, $service, $provider);
        
        return $appointment;
    }
}
