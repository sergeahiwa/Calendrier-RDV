<?php
/**
 * Modèle représentant un prestataire de services
 *
 * @package CalendrierRdv\Domain\Model
 */

namespace CalendrierRdv\Domain\Model;

/**
 * Représente un prestataire de services
 */
class Provider extends AbstractModel {
    /**
     * @var int|null ID de l'utilisateur WordPress associé
     */
    private ?int $userId;

    /**
     * @var string Prénom du prestataire
     */
    private string $firstName;

    /**
     * @var string Nom du prestataire
     */
    private string $lastName;

    /**
     * @var string Email du prestataire
     */
    private string $email;

    /**
     * @var string|null Téléphone du prestataire
     */
    private ?string $phone;

    /**
     * @var string Description du prestataire
     */
    private string $description;

    /**
     * @var bool Si le prestataire est actif
     */
    private bool $active;

    // Getters et Setters

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        if (empty($firstName)) {
            throw new \InvalidArgumentException("Le prénom est obligatoire");
        }
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        if (empty($lastName)) {
            throw new \InvalidArgumentException("Le nom est obligatoire");
        }
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("L'email n'est pas valide");
        }
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Convertit le prestataire en tableau
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'user_id' => $this->userId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'description' => $this->description,
            'active' => $this->active,
        ]);
    }

    /**
     * Crée une instance à partir d'un tableau de données
     */
    public static function fromArray(array $data): self
    {
        $provider = new self();
        $provider
            ->setId($data['id'] ?? null)
            ->setUserId($data['user_id'] ?? null)
            ->setFirstName($data['first_name'] ?? '')
            ->setLastName($data['last_name'] ?? '')
            ->setEmail($data['email'] ?? '')
            ->setPhone($data['phone'] ?? null)
            ->setDescription($data['description'] ?? '')
            ->setActive((bool) ($data['active'] ?? true));

        if (isset($data['created_at'])) {
            $provider->setCreatedAt(new \DateTimeImmutable($data['created_at']));
        }
        
        if (isset($data['updated_at'])) {
            $provider->setUpdatedAt(new \DateTimeImmutable($data['updated_at']));
        }

        return $provider;
    }
}
