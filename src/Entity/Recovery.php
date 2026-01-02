<?php

namespace App\Entity;

use App\Repository\RecoveryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecoveryRepository::class)]
#[ORM\Table(name: 'recoveries')]
#[ORM\Index(name: 'idx_recoveries_pro_user_id', columns: ['pro_user_id'])]
#[ORM\Index(name: 'idx_recoveries_merchant_user_id', columns: ['merchant_user_id'])]
#[ORM\Index(name: 'idx_recoveries_status', columns: ['status'])]
#[ORM\Index(name: 'idx_recoveries_created_at', columns: ['created_at'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Recovery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $uuid = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'recoveriesAsPro')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $proUser = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'recoveriesAsMerchant')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $merchantUser = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $qtyKg = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    #[Assert\Positive]
    private ?string $actualQtyKg = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    #[Assert\Choice(choices: ['PENDING', 'PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\OneToOne(mappedBy: 'recovery', targetEntity: ValorizationTransaction::class, cascade: ['persist', 'remove'])]
    private ?ValorizationTransaction $valorizationTransaction = null;

    #[ORM\OneToMany(mappedBy: 'sourceRecovery', targetEntity: StockHistory::class)]
    private Collection $stockHistories;

    public function __construct()
    {
        $this->uuid = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
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

    public function getProUser(): ?User
    {
        return $this->proUser;
    }

    public function setProUser(?User $proUser): self
    {
        $this->proUser = $proUser;
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

    public function getQtyKg(): ?string
    {
        return $this->qtyKg;
    }

    public function setQtyKg(string $qtyKg): self
    {
        $this->qtyKg = $qtyKg;
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

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeInterface $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;
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

    public function getValorizationTransaction(): ?ValorizationTransaction
    {
        return $this->valorizationTransaction;
    }

    public function setValorizationTransaction(?ValorizationTransaction $valorizationTransaction): self
    {
        if ($valorizationTransaction === null && $this->valorizationTransaction !== null) {
            $this->valorizationTransaction->setRecovery(null);
        }

        if ($valorizationTransaction !== null && $valorizationTransaction->getRecovery() !== $this) {
            $valorizationTransaction->setRecovery($this);
        }

        $this->valorizationTransaction = $valorizationTransaction;
        return $this;
    }

    public function getStockHistories(): Collection
    {
        return $this->stockHistories;
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'CANCELLED';
    }

    public function getActualQtyKg(): ?string
    {
        return $this->actualQtyKg;
    }

    public function setActualQtyKg(?string $actualQtyKg): self
    {
        $this->actualQtyKg = $actualQtyKg;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): self
    {
        $this->cancellationReason = $cancellationReason;
        return $this;
    }

    // Alias methods for API compatibility
    public function getPlannedWeightKg(): ?string
    {
        return $this->qtyKg;
    }

    public function setPlannedWeightKg(string $weightKg): self
    {
        $this->qtyKg = $weightKg;
        return $this;
    }

    public function getActualWeightKg(): ?string
    {
        return $this->actualQtyKg;
    }

    public function setActualWeightKg(?string $weightKg): self
    {
        $this->actualQtyKg = $weightKg;
        return $this;
    }

    public function getPlannedDate(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setPlannedDate(?\DateTimeInterface $date): self
    {
        $this->scheduledAt = $date;
        return $this;
    }
}

