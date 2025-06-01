<?php
/**
 * Interface pour le repository des prestataires
 *
 * @package CalendrierRdv\Domain\Repository
 */

namespace CalendrierRdv\Domain\Repository;

use CalendrierRdv\Domain\Model\Provider;

/**
 * Interface pour le repository des prestataires
 */
interface ProviderRepositoryInterface extends RepositoryInterface
{
    /**
     * Trouve les prestataires actifs
     *
     * @param bool $active Si vrai, ne retourne que les prestataires actifs
     * @return Provider[]
     */
    public function findActive(bool $active = true): array;
    
    /**
     * Vérifie si un email est déjà utilisé par un autre prestataire
     *
     * @param string $email Email à vérifier
     * @param int|null $excludeId ID à exclure de la vérification (pour les mises à jour)
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;
    
    /**
     * Trouve un prestataire par son ID utilisateur WordPress
     * 
     * @param int $userId ID de l'utilisateur WordPress
     * @return Provider|null
     */
    public function findByUserId(int $userId): ?Provider;
}
