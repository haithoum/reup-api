<?php

namespace App\Entity;

use App\Repository\WalletTransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WalletTransactionRepository::class)]
#[ORM\Table(name: 'wallet_transactions')]
#[ORM\Index(name: 'idx_wallet_transactions_citizen_user_id', columns: ['citizen_user_id'])]
#[ORM\Index(name: 'idx_wallet_transactions_merchant_user_id', columns: ['merchant_user_id'])]
#[ORM\Index(name: 'idx_wallet_transactions_created_at', columns: ['created_at'])]
class WalletTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $citizenUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $merchantUser = null;

    #[ORM\ManyToOne(targetEntity: CitizenMerchantWallet::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CitizenMerchantWallet $wallet = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['CREDIT', 'DEBIT', 'REFUND', 'ADJUSTMENT'])]
    private ?string $type = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sourceType = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $sourceId = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Assert\NotBlank]
    private ?string $amountKg = null;

    #[ORM\ManyToOne(targetEntity: Coupon::class, inversedBy: 'walletTransactions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Coupon $coupon = null;

    #[ORM\ManyToOne(targetEntity: Deposit::class, inversedBy: 'walletTransactions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Deposit $sourceDeposit = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getWallet(): ?CitizenMerchantWallet
    {
        return $this->wallet;
    }

    public function setWallet(?CitizenMerchantWallet $wallet): self
    {
        $this->wallet = $wallet;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
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

    public function getAmountKg(): ?string
    {
        return $this->amountKg;
    }

    public function setAmountKg(string $amountKg): self
    {
        $this->amountKg = $amountKg;
        return $this;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): self
    {
        $this->coupon = $coupon;
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

    public function isCredit(): bool
    {
        return $this->type === 'CREDIT';
    }

    public function isDebit(): bool
    {
        return $this->type === 'DEBIT';
    }
}

