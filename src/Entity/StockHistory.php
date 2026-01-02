<?php

namespace App\Entity;

use App\Repository\StockHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StockHistoryRepository::class)]
#[ORM\Table(name: 'stock_history')]
#[ORM\Index(name: 'idx_stock_history_merchant_user_id', columns: ['merchant_user_id'])]
#[ORM\Index(name: 'idx_stock_history_created_at', columns: ['created_at'])]
class StockHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $merchantUser = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['DEPOSIT', 'RECOVERY', 'ADJUSTMENT'])]
    private ?string $operationType = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private ?string $amountKg = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $previousStockKg = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $newStockKg = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sourceType = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $sourceId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $performedByUser = null;

    #[ORM\ManyToOne(targetEntity: Deposit::class, inversedBy: 'stockHistories')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Deposit $sourceDeposit = null;

    #[ORM\ManyToOne(targetEntity: Recovery::class, inversedBy: 'stockHistories')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Recovery $sourceRecovery = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

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

    public function getOperationType(): ?string
    {
        return $this->operationType;
    }

    public function setOperationType(string $operationType): self
    {
        $this->operationType = $operationType;
        return $this;
    }

    public function getAmountKg(): ?string
    {
        return $this->amountKg;
    }

    public function setAmountKg(string $amountKg): self
    {
        $this->amountKg = $amountKg;
        return $this;
    }

    public function getPreviousStockKg(): ?string
    {
        return $this->previousStockKg;
    }

    public function setPreviousStockKg(string $previousStockKg): self
    {
        $this->previousStockKg = $previousStockKg;
        return $this;
    }

    public function getNewStockKg(): ?string
    {
        return $this->newStockKg;
    }

    public function setNewStockKg(string $newStockKg): self
    {
        $this->newStockKg = $newStockKg;
        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): self
    {
        $this->sourceType = $sourceType;
        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function setSourceId(?int $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getPerformedByUser(): ?User
    {
        return $this->performedByUser;
    }

    public function setPerformedByUser(?User $performedByUser): self
    {
        $this->performedByUser = $performedByUser;
        return $this;
    }

    public function getSourceDeposit(): ?Deposit
    {
        return $this->sourceDeposit;
    }

    public function setSourceDeposit(?Deposit $sourceDeposit): self
    {
        $this->sourceDeposit = $sourceDeposit;
        return $this;
    }

    public function getSourceRecovery(): ?Recovery
    {
        return $this->sourceRecovery;
    }

    public function setSourceRecovery(?Recovery $sourceRecovery): self
    {
        $this->sourceRecovery = $sourceRecovery;
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

    // ==================== ALIAS METHODS FOR API ====================

    /**
     * Alias pour getOperationType() pour correspondre à l'API
     */
    public function getType(): ?string
    {
        return $this->getOperationType();
    }

    /**
     * Alias pour getAmountKg() pour correspondre à l'API
     */
    public function getWeightKg(): ?string
    {
        return $this->getAmountKg();
    }

    /**
     * Alias pour getSourceDeposit() pour correspondre à l'API
     */
    public function getRelatedDeposit(): ?Deposit
    {
        return $this->getSourceDeposit();
    }

    /**
     * Alias pour getSourceRecovery() pour correspondre à l'API
     */
    public function getRelatedRecovery(): ?Recovery
    {
        return $this->getSourceRecovery();
    }
}

