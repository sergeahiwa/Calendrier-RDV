<?php
/**
 * Interface de base pour les repositories
 *
 * @package CalendrierRdv\Domain\Repository
 */

namespace CalendrierRdv\Domain\Repository;

use CalendrierRdv\Domain\Model\AbstractModel;

/**
 * Interface de base pour les repositories
 * 
 * @template T of AbstractModel
 */
interface RepositoryInterface
{
    /**
     * Trouve une entité par son ID
     * 
     * @param int $id ID de l'entité
     * @return T|null
     */
    public function find(int $id): ?AbstractModel;

    /**
     * Trouve toutes les entités
     * 
     * @param array $criteria Critères de recherche
     * @param array $orderBy Tri
     * @param int|null $limit Limite
     * @param int|null $offset Offset
     * @return T[]
     */
    public function findBy(
        array $criteria = [],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null
    ): array;

    /**
     * Trouve une entité selon des critères
     * 
     * @param array $criteria Critères de recherche
     * @return T|null
     */
    public function findOneBy(array $criteria): ?AbstractModel;

    /**
     * Sauvegarde une entité
     * 
     * @param AbstractModel $entity Entité à sauvegarder
     * @return T
     */
    public function save(AbstractModel $entity): AbstractModel;

    /**
     * Supprime une entité
     * 
     * @param AbstractModel $entity Entité à supprimer
     * @return bool
     */
    public function delete(AbstractModel $entity): bool;
}
