<?php
/**
 * Service pour la gestion des services
 *
 * @package CalendrierRdv\Domain\Service
 */

namespace CalendrierRdv\Domain\Service;

use CalendrierRdv\Domain\Model\Service;
use CalendrierRdv\Domain\Repository\ServiceRepositoryInterface;
use CalendrierRdv\Domain\Exception\InvalidStatusException;

/**
 * Service pour la gestion des services
 */
class ServiceService
{
    /**
     * @var ServiceRepositoryInterface
     */
    private $serviceRepository;

    /**
     * Constructeur
     *
     * @param ServiceRepositoryInterface $serviceRepository
     */
    public function __construct(ServiceRepositoryInterface $serviceRepository)
    {
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Crée un nouveau service
     *
     * @param array $data Données du service
     * @return Service
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    public function createService(array $data): Service
    {
        $this->validateServiceData($data);
        
        $service = new Service();
        $service
            ->setName(trim($data['name']))
            ->setDescription(trim($data['description'] ?? ''))
            ->setDuration((int) ($data['duration'] ?? 30))
            ->setPrice(isset($data['price']) ? (float) $data['price'] : null)
            ->setActive((bool) ($data['active'] ?? true));
            
        return $this->serviceRepository->save($service);
    }

    /**
     * Met à jour un service existant
     *
     * @param int $id ID du service
     * @param array $data Données à mettre à jour
     * @return Service
     * @throws \InvalidArgumentException Si les données sont invalides
     * @throws \RuntimeException Si le service n'existe pas
     */
    public function updateService(int $id, array $data): Service
    {
        $service = $this->serviceRepository->find($id);
        
        if (!$service) {
            throw new \RuntimeException("Le service demandé n'existe pas");
        }
        
        // Ne valider que les champs fournis
        if (array_key_exists('name', $data)) {
            $service->setName(trim($data['name']));
        }
        
        if (array_key_exists('description', $data)) {
            $service->setDescription(trim($data['description']));
        }
        
        if (array_key_exists('duration', $data)) {
            $service->setDuration((int) $data['duration']);
        }
        
        if (array_key_exists('price', $data)) {
            $service->setPrice($data['price'] !== null ? (float) $data['price'] : null);
        }
        
        if (array_key_exists('active', $data)) {
            $service->setActive((bool) $data['active']);
        }
        
        $this->validateService($service, $id);
        
        return $this->serviceRepository->save($service);
    }

    /**
     * Supprime un service
     *
     * @param int $id ID du service
     * @return bool
     * @throws \RuntimeException Si le service n'existe pas ou ne peut pas être supprimé
     */
    public function deleteService(int $id): bool
    {
        $service = $this->serviceRepository->find($id);
        
        if (!$service) {
            throw new \RuntimeException("Le service demandé n'existe pas");
        }
        
        // Vérifier si le service peut être supprimé (pas de rendez-vous associés, etc.)
        // Cette logique dépendra de votre domaine métier
        
        return $this->serviceRepository->delete($service);
    }

    /**
     * Récupère un service par son ID
     *
     * @param int $id ID du service
     * @return Service
     * @throws \RuntimeException Si le service n'existe pas
     */
    public function getService(int $id): Service
    {
        $service = $this->serviceRepository->find($id);
        
        if (!$service) {
            throw new \RuntimeException("Le service demandé n'existe pas");
        }
        
        return $service;
    }

    /**
     * Récupère tous les services, éventuellement filtrés par statut
     *
     * @param bool|null $active Si non null, filtre par statut actif/inactif
     * @return Service[]
     */
    public function getServices(?bool $active = null): array
    {
        if ($active === null) {
            return $this->serviceRepository->findBy([], ['name' => 'ASC']);
        }
        
        return $this->serviceRepository->findActive($active);
    }

    /**
     * Valide les données d'un service
     *
     * @param array $data Données à valider
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    private function validateServiceData(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException("Le nom du service est obligatoire");
        }
        
        if (isset($data['duration']) && $data['duration'] <= 0) {
            throw new \InvalidArgumentException("La durée doit être supérieure à 0");
        }
        
        if (isset($data['price']) && $data['price'] !== null && $data['price'] < 0) {
            throw new \InvalidArgumentException("Le prix ne peut pas être négatif");
        }
    }
    
    /**
     * Valide un service avant enregistrement
     *
     * @param Service $service Service à valider
     * @param int|null $excludeId ID à exclure de la vérification d'unicité (pour les mises à jour)
     * @throws \InvalidArgumentException Si le service n'est pas valide
     */
    private function validateService(Service $service, ?int $excludeId = null): void
    {
        if ($this->serviceRepository->nameExists($service->getName(), $excludeId)) {
            throw new \InvalidArgumentException("Un service avec ce nom existe déjà");
        }
        
        // Autres validations si nécessaire
    }
}
