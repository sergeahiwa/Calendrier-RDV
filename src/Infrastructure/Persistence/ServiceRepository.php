<?php
/**
 * Implémentation du repository des services
 *
 * @package CalendrierRdv\Infrastructure\Persistence
 */

namespace CalendrierRdv\Infrastructure\Persistence;

use CalendrierRdv\Domain\Model\Service;
use CalendrierRdv\Domain\Repository\ServiceRepositoryInterface;
use CalendrierRdv\Database\QueryBuilder;

/**
 * Implémentation concrète du repository des services
 */
class ServiceRepository implements ServiceRepositoryInterface
{
    /**
     * @var string Nom de la table
     */
    private const TABLE_NAME = 'cal_rdv_services';

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
    public function find(int $id): ?Service
    {
        $result = $this->queryBuilder
            ->select()
            ->from(self::TABLE_NAME)
            ->where('id', '=', $id)
            ->getFirst();

        return $result ? Service::fromArray($result) : null;
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
            fn(array $data) => Service::fromArray($data),
            $results
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria): ?Service
    {
        $results = $this->findBy($criteria, [], 1);
        return $results[0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function findActive(bool $active = true): array
    {
        return $this->findBy(['active' => $active], ['name' => 'ASC']);
    }

    /**
     * {@inheritdoc}
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = $this->queryBuilder
            ->select(['COUNT(*) as count'])
            ->from(self::TABLE_NAME)
            ->where('name', '=', $name);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $result = $query->getFirst();
        return $result && $result['count'] > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Service $service): Service
    {
        $data = $service->toArray();
        
        // Ne pas inclure les champs de date s'ils ne sont pas définis
        unset($data['created_at'], $data['updated_at']);
        
        if ($service->getId() === null) {
            // Insertion
            $id = $this->queryBuilder
                ->insert(self::TABLE_NAME, $data)
                ->execute();
                
            $service->setId($id);
        } else {
            // Mise à jour
            $this->queryBuilder
                ->update(self::TABLE_NAME, $data)
                ->where('id', '=', $service->getId())
                ->execute();
        }
        
        // Récupérer l'entité fraîche depuis la base pour s'assurer d'avoir les bonnes dates
        if ($service->getId() !== null) {
            $savedService = $this->find($service->getId());
            if ($savedService) {
                return $savedService;
            }
        }
        
        return $service;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Service $service): bool
    {
        if ($service->getId() === null) {
            return false;
        }
        
        $affected = $this->queryBuilder
            ->delete()
            ->from(self::TABLE_NAME)
            ->where('id', '=', $service->getId())
            ->execute();
            
        return $affected > 0;
    }
}
