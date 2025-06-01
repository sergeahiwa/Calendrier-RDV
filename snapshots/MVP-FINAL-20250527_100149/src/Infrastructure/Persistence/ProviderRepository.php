<?php
/**
 * Implémentation du repository des prestataires
 *
 * @package CalendrierRdv\Infrastructure\Persistence
 */

namespace CalendrierRdv\Infrastructure\Persistence;

use CalendrierRdv\Domain\Model\Provider;
use CalendrierRdv\Domain\Repository\ProviderRepositoryInterface;
use CalendrierRdv\Database\QueryBuilder;

/**
 * Implémentation concrète du repository des prestataires
 */
class ProviderRepository implements ProviderRepositoryInterface
{
    /**
     * @var string Nom de la table
     */
    private const TABLE_NAME = 'cal_rdv_providers';

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructeur
     *
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?Provider
    {
        $result = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME)
            ->where('id', '=', $id)
            ->getFirst();

        return $result ? Provider::fromArray($result) : null;
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
            fn(array $data) => Provider::fromArray($data),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria): ?Provider
    {
        $results = $this->findBy($criteria, [], 1);
        return $results[0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function findActive(bool $active = true): array
    {
        return $this->findBy(['active' => $active], ['last_name' => 'ASC', 'first_name' => 'ASC']);
    }

    /**
     * {@inheritdoc}
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->queryBuilder
            ->select(['COUNT(*) as count'])
            ->from(self::TABLE_NAME)
            ->where('email', '=', $email);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $result = $query->getFirst();
        return $result && $result['count'] > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUserId(int $userId): ?Provider
    {
        return $this->findOneBy(['user_id' => $userId]);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Provider $provider): Provider
    {
        $data = $provider->toArray();
        
        // Ne pas inclure les champs de date s'ils ne sont pas définis
        unset($data['created_at'], $data['updated_at'], $data['full_name']);
        
        if ($provider->getId() === null) {
            // Insertion
            $id = $this->queryBuilder
                ->insert(self::TABLE_NAME, $data)
                ->execute();
                
            $provider->setId($id);
        } else {
            // Mise à jour
            $this->queryBuilder
                ->update(self::TABLE_NAME, $data)
                ->where('id', '=', $provider->getId())
                ->execute();
        }
        
        // Récupérer l'entité fraîche depuis la base pour s'assurer d'avoir les bonnes dates
        if ($provider->getId() !== null) {
            $savedProvider = $this->find($provider->getId());
            if ($savedProvider) {
                return $savedProvider;
            }
        }
        
        return $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Provider $provider): bool
    {
        if ($provider->getId() === null) {
            return false;
        }
        
        // Vérifier s'il n'y a pas de rendez-vous à venir pour ce prestataire
        $hasFutureAppointments = $this->queryBuilder
            ->select(['COUNT(*) as count'])
            ->from('cal_rdv_appointments')
            ->where('provider_id', '=', $provider->getId())
            ->where('start_date', '>', current_time('mysql'))
            ->getFirst();
            
        if ($hasFutureAppointments && $hasFutureAppointments['count'] > 0) {
            throw new \RuntimeException(
                "Impossible de supprimer ce prestataire car il a des rendez-vous à venir"
            );
        }
        
        $affected = $this->queryBuilder
            ->delete()
            ->from(self::TABLE_NAME)
            ->where('id', '=', $provider->getId())
            ->execute();
            
        return $affected > 0;
    }
}
