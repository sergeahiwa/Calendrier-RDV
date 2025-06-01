<?php
/**
 * Modèle représentant un rendez-vous
 *
 * @package CalendrierRdv\Domain\Model
 */

namespace CalendrierRdv\Domain\Model;

use CalendrierRdv\Domain\Exception\InvalidStatusException;

/**
 * Représente un rendez-vous entre un client et un prestataire pour un service
 */
class Appointment extends AbstractModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';
    public const PAYMENT_STATUS_CANCELLED = 'cancelled';

    /**
     * @var Service Service concerné par le rendez-vous
     */
    private Service $service;

    /**
     * @var Provider Prestataire du service
     */
    private Provider $provider;

    /**
     * @var int|null ID du client (si connecté)
     */
    private ?int $customerId;

    /**
     * @var string Nom du client
     */
    private string $customerName;

    /**
     * @var string Email du client
     */
    private string $customerEmail;

    /**
     * @var string|null Téléphone du client
     */
    private ?string $customerPhone;

    /**
     * @var string Notes supplémentaires
     */
    private string $customerNotes;

    /**
     * @var \DateTimeInterface Date et heure de début
     */
    private \DateTimeInterface $startDate;

    /**
     * @var \DateTimeInterface Date et heure de fin
     */
    private \DateTimeInterface $endDate;

    /**
     * @var string Statut du rendez-vous
     */
    private string $status;

    /**
     * @var float|null Prix convenu
     */
    private ?float $price;

    /**
     * @var string Statut du paiement
     */
    private string $paymentStatus;

    /**
     * @var string|null Méthode de paiement
     */
    private ?string $paymentMethod;

    /**
     * @var string|null ID externe du paiement
     */
    private ?string $paymentId;

    // Getters et Setters

    public function getService(): Service
    {
        return $this->service;
    }

    public function setService(Service $service): self
    {
        $this->service = $service;
        return $this;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function setProvider(Provider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): self
    {
        if (empty($customerName)) {
            throw new \InvalidArgumentException("Le nom du client est obligatoire");
        }
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): self
    {
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("L'email du client n'est pas valide");
        }
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $customerPhone): self
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    public function getCustomerNotes(): string
    {
        return $this->customerNotes;
    }

    public function setCustomerNotes(string $customerNotes): self
    {
        $this->customerNotes = $customerNotes;
        return $this;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        if ($endDate <= $this->startDate) {
            throw new \InvalidArgumentException("La date de fin doit être postérieure à la date de début");
        }
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, self::getValidStatuses(), true)) {
            throw new InvalidStatusException(sprintf(
                'Statut "%s" invalide. Statuts valides : %s',
                $status,
                implode(', ', self::getValidStatuses())
            ));
        }
        $this->status = $status;
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

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        if (!in_array($paymentStatus, self::getValidPaymentStatuses(), true)) {
            throw new InvalidStatusException(sprintf(
                'Statut de paiement "%s" invalide. Statuts valides : %s',
                $paymentStatus,
                implode(', ', self::getValidPaymentStatuses())
            ));
        }
        $this->paymentStatus = $paymentStatus;
        return $this;
    }


    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * Retourne la liste des statuts valides
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
        ];
    }

    /**
     * Retourne la liste des statuts de paiement valides
     */
    public static function getValidPaymentStatuses(): array
    {
        return [
            self::PAYMENT_STATUS_PENDING,
            self::PAYMENT_STATUS_PAID,
            self::PAYMENT_STATUS_REFUNDED,
            self::PAYMENT_STATUS_CANCELLED,
        ];
    }

    /**
     * Convertit le rendez-vous en tableau
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'service_id' => $this->service->getId(),
            'provider_id' => $this->provider->getId(),
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'customer_notes' => $this->customerNotes,
            'start_date' => $this->startDate->format('c'),
            'end_date' => $this->endDate->format('c'),
            'status' => $this->status,
            'price' => $this->price,
            'payment_status' => $this->paymentStatus,
            'payment_method' => $this->paymentMethod,
            'payment_id' => $this->paymentId,
        ]);
    }

    /**
     * Crée une instance à partir d'un tableau de données
     */
    public static function fromArray(array $data, ?Service $service = null, ?Provider $provider = null): self
    {
        $appointment = new self();
        
        if ($service) {
            $appointment->setService($service);
        }
        
        if ($provider) {
            $appointment->setProvider($provider);
        }

        $appointment
            ->setId($data['id'] ?? null)
            ->setCustomerId($data['customer_id'] ?? null)
            ->setCustomerName($data['customer_name'] ?? '')
            ->setCustomerEmail($data['customer_email'] ?? '')
            ->setCustomerPhone($data['customer_phone'] ?? null)
            ->setCustomerNotes($data['customer_notes'] ?? '')
            ->setStatus($data['status'] ?? self::STATUS_PENDING)
            ->setPrice(isset($data['price']) ? (float) $data['price'] : null)
            ->setPaymentStatus($data['payment_status'] ?? self::PAYMENT_STATUS_PENDING)
            ->setPaymentMethod($data['payment_method'] ?? null)
            ->setPaymentId($data['payment_id'] ?? null);

        if (isset($data['start_date'])) {
            $appointment->setStartDate(new \DateTimeImmutable($data['start_date']));
        }
        
        if (isset($data['end_date'])) {
            $appointment->setEndDate(new \DateTimeImmutable($data['end_date']));
        } elseif (isset($data['start_date']) && $service) {
            $endDate = (new \DateTimeImmutable($data['start_date']))
                ->add(new \DateInterval('PT' . $service->getDuration() . 'M'));
            $appointment->setEndDate($endDate);
        }

        if (isset($data['created_at'])) {
            $appointment->setCreatedAt(new \DateTimeImmutable($data['created_at']));
        }
        
        if (isset($data['updated_at'])) {
            $appointment->setUpdatedAt(new \DateTimeImmutable($data['updated_at']));
        }

        return $appointment;
    }
}
