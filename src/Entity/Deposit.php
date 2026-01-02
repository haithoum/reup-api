<?php

namespace App\Entity;

use App\Repository\DepositRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DepositRepository::class)]
#[ORM\Table(name: 'deposits')]
#[ORM\Index(name: 'idx_deposits_citizen_user_id', columns: ['citizen_user_id'])]
#[ORM\Index(name: 'idx_deposits_merchant_user_id', columns: ['merchant_user_id'])]
#[ORM\Index(name: 'idx_deposits_status', columns: ['status'])]
#[ORM\Index(name: 'idx_deposits_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_deposits_uuid', columns: ['uuid'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Deposit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $uuid = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'depositsAsCitizen')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $citizenUser = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'depositsAsMerchant')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $merchantUser = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0.1, max: 50.0)]
    private ?string $weightKg = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    #[Assert\Choice(choices: ['PENDING', 'ACCEPTED', 'REFUSED', 'CANCELLED'])]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $refusedReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $acceptedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $refusedAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\OneToMany(mappedBy: 'issuedFromDeposit', targetEntity: Coupon::class)]
    private Collection $triggeredCoupons;

    #[ORM\OneToMany(mappedBy: 'sourceDeposit', targetEntity: WalletTransaction::class)]
    private Collection $walletTransactions;

    #[ORM\OneToMany(mappedBy: 'sourceDeposit', targetEntity: StockHistory::class)]
    private Collection $stockHistories;

    public function __construct()
    {
        $this->uuid = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->triggeredCoupons = new ArrayCollection();
        $this->walletTransactions = new ArrayCollection();
        $this->stockHistories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getCitizenUser(): ?User
    {
        return $this->citizenUser;
    }

    public function setCitizenUser(?User $citizenUser): self
    {
        $this->citizenUser = $citizenUser;
        return $this;
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

    public function getWeightKg(): ?string
    {
        return $this->weightKg;
    }

    public function setWeightKg(string $weightKg): self
    {
        $this->weightKg = $weightKg;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getRefusedReason(): ?string
    {
        return $this->refusedReason;
    }

    public function setRefusedReason(?string $refusedReason): self
    {
        $this->refusedReason = $refusedReason;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeInterface
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeInterface $acceptedAt): self
    {
        $this->acceptedAt = $acceptedAt;
        return $this;
    }

    public function getRefusedAt(): ?\DateTimeInterface
    {
        return $this->refusedAt;
    }

    public function setRefusedAt(?\DateTimeInterface $refusedAt): self
    {
        $this->refusedAt = $refusedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
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

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getTriggeredCoupons(): Collection
    {
        return $this->triggeredCoupons;
    }

    public function getWalletTransactions(): Collection
    {
        return $this->walletTransactions;
    }

    public function getStockHistories(): Collection
    {
        return $this->stockHistories;
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'ACCEPTED';
    }

    public function isRefused(): bool
    {
        return $this->status === 'REFUSED';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'CANCELLED';
    }

    public function __toString(): string
    {
        $citizenName = $this->citizenUser ? $this->citizenUser->getEmail() : 'N/A';
        $merchantName = $this->merchantUser ? $this->merchantUser->getEmail() : 'N/A';

        return sprintf(
            'Dépôt #%s - %s kg - %s (Citoyen: %s, Commerçant: %s)',
            $this->id ?? 'nouveau',
            $this->weightKg ?? '0',
            $this->status,
            $citizenName,
            $merchantName
        );
    }
}

