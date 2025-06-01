<?php
/**
 * Modèle représentant un service proposé par un prestataire
 *
 * @package CalendrierRdv\Domain\Model
 */

namespace CalendrierRdv\Domain\Model;

/**
 * Représente un service proposé par un prestataire
 */
class Service extends AbstractModel {
    /**
     * @var string Nom du service
     */
    private string $name;

    /**
     * @var string Description du service
     */
    private string $description;

    /**
     * @var int Durée du service en minutes
     */
    private int $duration;

    /**
     * @var float Prix du service
     */
    private ?float $price;

    /**
     * @var bool Si le service est actif
     */
    private bool $active;

    // Getters et Setters

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        if ($duration <= 0) {
            throw new \InvalidArgumentException("La durée doit être supérieure à 0");
        }
        $this->duration = $duration;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        if ($price !== null && $price < 0) {
            throw new \InvalidArgumentException("Le prix ne peut pas être négatif");
        }
        $this->price = $price;
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
     * Convertit le service en tableau
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'name' => $this->name,
            'description' => $this->description,
            'duration' => $this->duration,
            'price' => $this->price,
            'active' => $this->active,
        ]);
    }

    /**
     * Crée une instance à partir d'un tableau de données
     */
    public static function fromArray(array $data): self
    {
        $service = new self();
        $service
            ->setId($data['id'] ?? null)
            ->setName($data['name'])
            ->setDescription($data['description'] ?? '')
            ->setDuration((int) ($data['duration'] ?? 30))
            ->setPrice(isset($data['price']) ? (float) $data['price'] : null)
            ->setActive((bool) ($data['active'] ?? true));

        if (isset($data['created_at'])) {
            $service->setCreatedAt(new \DateTimeImmutable($data['created_at']));
        }
        
        if (isset($data['updated_at'])) {
            $service->setUpdatedAt(new \DateTimeImmutable($data['updated_at']));
        }

        return $service;
    }
}
