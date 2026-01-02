<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'stocks')]
#[ORM\Index(name: 'idx_stocks_merchant_user_id', columns: ['merchant_user_id'])]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $merchantUser = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private string $availableKg = '0.00';

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    #[Assert\Positive]
    private ?string $alertThresholdKg = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastRecoveryAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'merchantUser', targetEntity: StockHistory::class)]
    private Collection $histories;

    public function __construct()
    {
        $this->histories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMerchantUser(): ?User
    {
        return $this->merchantUser;
    }

    public function setMerchantUser(?User $merchantUser): self
    {
        $this->merchantUser = $merchantUser;
        return $this;
    }

    public function getAvailableKg(): string
    {
        return $this->availableKg;
    }

    public function setAvailableKg(string $availableKg): self
    {
        $this->availableKg = $availableKg;
        return $this;
    }

    public function getAlertThresholdKg(): ?string
    {
        return $this->alertThresholdKg;
    }

    public function setAlertThresholdKg(?string $alertThresholdKg): self
    {
        $this->alertThresholdKg = $alertThresholdKg;
        return $this;
    }

    public function getLastRecoveryAt(): ?\DateTimeInterface
    {
        return $this->lastRecoveryAt;
    }

    public function setLastRecoveryAt(?\DateTimeInterface $lastRecoveryAt): self
    {
        $this->lastRecoveryAt = $lastRecoveryAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getHistories(): Collection
    {
        return $this->histories;
    }

    public function addQuantity(string $kg): void
    {
        $this->availableKg = bcadd($this->availableKg, $kg, 2);
    }

    public function removeQuantity(string $kg): void
    {
        $this->availableKg = bcsub($this->availableKg, $kg, 2);
        if (bccomp($this->availableKg, '0', 2) < 0) {
            $this->availableKg = '0.00';
        }
    }

    public function isAboveThreshold(): bool
    {
        if ($this->alertThresholdKg === null) {
            return false;
        }
        return bccomp($this->availableKg, $this->alertThresholdKg, 2) >= 0;
    }

    // Alias methods for API compatibility
    public function getCurrentWeightKg(): string
    {
        return $this->availableKg;
    }

    public function setCurrentWeightKg(string $weightKg): self
    {
        $this->availableKg = $weightKg;
        return $this;
    }

    public function getMerchantProfile(): ?User
    {
        return $this->merchantUser;
    }

    public function setMerchantProfile(?User $merchantUser): self
    {
        $this->merchantUser = $merchantUser;
        return $this;
    }

    public function __toString(): string
    {
        $merchantName = $this->merchantUser ? $this->merchantUser->getEmail() : 'N/A';

        return sprintf(
            'Stock #%s - %s kg (Commerçant: %s)',
            $this->id ?? 'nouveau',
            $this->availableKg,
            $merchantName
        );
    }
}

