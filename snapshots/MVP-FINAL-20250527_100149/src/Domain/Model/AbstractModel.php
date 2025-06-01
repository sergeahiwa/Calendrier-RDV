<?php
/**
 * Classe de base pour tous les modèles du domaine
 *
 * @package CalendrierRdv\Domain\Model
 */

namespace CalendrierRdv\Domain\Model;

use DateTimeInterface;

/**
 * Classe abstraite de base pour les modèles du domaine
 */
abstract class AbstractModel {
    /**
     * @var int|null ID du modèle
     */
    protected ?int $id = null;

    /**
     * @var DateTimeInterface Date de création
     */
    protected DateTimeInterface $createdAt;

    /**
     * @var DateTimeInterface Date de dernière mise à jour
     */
    protected DateTimeInterface $updatedAt;

    // Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Convertit le modèle en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
        ];
    }
}
