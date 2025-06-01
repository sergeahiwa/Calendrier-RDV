<?php
/**
 * Interface pour le repository des services
 *
 * @package CalendrierRdv\Domain\Repository
 */

namespace CalendrierRdv\Domain\Repository;

use CalendrierRdv\Domain\Model\Service;

/**
 * Interface pour le repository des services
 */
interface ServiceRepositoryInterface extends RepositoryInterface
{
    /**
     * Trouve les services actifs
     *
     * @param bool $active Si vrai, ne retourne que les services actifs
     * @return Service[]
     */
    public function findActive(bool $active = true): array;
    
    /**
     * Vérifie si un service avec le même nom existe déjà
     *
     * @param string $name Nom du service
     * @param int|null $excludeId ID à exclure de la recherche (pour les mises à jour)
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool;
}
