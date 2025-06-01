<?php
/**
 * Service pour la gestion des prestataires
 *
 * @package CalendrierRdv\Domain\Service
 */

namespace CalendrierRdv\Domain\Service;

use CalendrierRdv\Domain\Model\Provider;
use CalendrierRdv\Domain\Repository\ProviderRepositoryInterface;
use CalendrierRdv\Domain\Exception\InvalidStatusException;

/**
 * Service pour la gestion des prestataires
 */
class ProviderService
{
    /**
     * @var ProviderRepositoryInterface
     */
    private $providerRepository;

    /**
     * Constructeur
     *
     * @param ProviderRepositoryInterface $providerRepository
     */
    public function __construct(ProviderRepositoryInterface $providerRepository)
    {
        $this->providerRepository = $providerRepository;
    }

    /**
     * Crée un nouveau prestataire
     *
     * @param array $data Données du prestataire
     * @return Provider
     * @throws \InvalidArgumentException Si les données sont invalides
     * @throws \RuntimeException Si l'email est déjà utilisé
     */
    public function createProvider(array $data): Provider
    {
        $this->validateProviderData($data);
        
        $provider = new Provider();
        $provider
            ->setUserId($data['user_id'] ?? null)
            ->setFirstName(trim($data['first_name']))
            ->setLastName(trim($data['last_name']))
            ->setEmail(trim($data['email']))
            ->setPhone(isset($data['phone']) ? trim($data['phone']) : null)
            ->setDescription(trim($data['description'] ?? ''))
            ->setActive((bool) ($data['active'] ?? true));
            
        $this->validateProvider($provider);
            
        return $this->providerRepository->save($provider);
    }

    /**
     * Met à jour un prestataire existant
     *
     * @param int $id ID du prestataire
     * @param array $data Données à mettre à jour
     * @return Provider
     * @throws \InvalidArgumentException Si les données sont invalides
     * @throws \RuntimeException Si le prestataire n'existe pas
     */
    public function updateProvider(int $id, array $data): Provider
    {
        $provider = $this->getProvider($id);
        
        // Ne mettre à jour que les champs fournis
        if (array_key_exists('first_name', $data)) {
            $provider->setFirstName(trim($data['first_name']));
        }
        
        if (array_key_exists('last_name', $data)) {
            $provider->setLastName(trim($data['last_name']));
        }
        
        if (array_key_exists('email', $data)) {
            $provider->setEmail(trim($data['email']));
        }
        
        if (array_key_exists('phone', $data)) {
            $provider->setPhone($data['phone'] !== null ? trim($data['phone']) : null);
        }
        
        if (array_key_exists('description', $data)) {
            $provider->setDescription(trim($data['description']));
        }
        
        if (array_key_exists('active', $data)) {
            $provider->setActive((bool) $data['active']);
        }
        
        $this->validateProvider($provider, $id);
        
        return $this->providerRepository->save($provider);
    }

    /**
     * Supprime un prestataire
     *
     * @param int $id ID du prestataire
     * @return bool
     * @throws \RuntimeException Si le prestataire n'existe pas ou ne peut pas être supprimé
     */
    public function deleteProvider(int $id): bool
    {
        $provider = $this->getProvider($id);
        return $this->providerRepository->delete($provider);
    }

    /**
     * Récupère un prestataire par son ID
     *
     * @param int $id ID du prestataire
     * @return Provider
     * @throws \RuntimeException Si le prestataire n'existe pas
     */
    public function getProvider(int $id): Provider
    {
        $provider = $this->providerRepository->find($id);
        
        if (!$provider) {
            throw new \RuntimeException("Le prestataire demandé n'existe pas");
        }
        
        return $provider;
    }

    /**
     * Récupère un prestataire par son ID utilisateur WordPress
     *
     * @param int $userId ID de l'utilisateur WordPress
     * @return Provider
     * @throws \RuntimeException Si le prestataire n'existe pas
     */
    public function getProviderByUserId(int $userId): Provider
    {
        $provider = $this->providerRepository->findByUserId($userId);
        
        if (!$provider) {
            throw new \RuntimeException("Aucun prestataire trouvé pour cet utilisateur");
        }
        
        return $provider;
    }

    /**
     * Récupère tous les prestataires, éventuellement filtrés par statut
     *
     * @param bool|null $active Si non null, filtre par statut actif/inactif
     * @return Provider[]
     */
    public function getProviders(?bool $active = null): array
    {
        if ($active === null) {
            return $this->providerRepository->findBy(
                [],
                ['last_name' => 'ASC', 'first_name' => 'ASC']
            );
        }
        
        return $this->providerRepository->findActive($active);
    }

    /**
     * Valide les données d'un prestataire
     *
     * @param array $data Données à valider
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    private function validateProviderData(array $data): void
    {
        if (empty($data['first_name'])) {
            throw new \InvalidArgumentException("Le prénom est obligatoire");
        }
        
        if (empty($data['last_name'])) {
            throw new \InvalidArgumentException("Le nom est obligatoire");
        }
        
        if (empty($data['email'])) {
            throw new \InvalidArgumentException("L'email est obligatoire");
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("L'email n'est pas valide");
        }
    }
    
    /**
     * Valide un prestataire avant enregistrement
     *
     * @param Provider $provider Prestataire à valider
     * @param int|null $excludeId ID à exclure de la vérification d'unicité (pour les mises à jour)
     * @throws \InvalidArgumentException Si le prestataire n'est pas valide
     */
    private function validateProvider(Provider $provider, ?int $excludeId = null): void
    {
        if ($this->providerRepository->emailExists($provider->getEmail(), $excludeId)) {
            throw new \InvalidArgumentException("Un prestataire avec cet email existe déjà");
        }
        
        // Autres validations si nécessaire
    }
}
