<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupons')]
#[ORM\Index(name: 'idx_coupons_citizen_user_id', columns: ['citizen_user_id'])]
#[ORM\Index(name: 'idx_coupons_merchant_user_id', columns: ['merchant_user_id'])]
#[ORM\Index(name: 'idx_coupons_public_id', columns: ['public_id'])]
#[ORM\Index(name: 'idx_coupons_status', columns: ['status'])]
#[ORM\Index(name: 'idx_coupons_expires_at', columns: ['expires_at'])]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $publicId = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'coupons')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $citizenUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $merchantUser = null;

    #[ORM\ManyToOne(targetEntity: RewardRule::class, inversedBy: 'coupons')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?RewardRule $rewardRule = null;

    #[ORM\ManyToOne(targetEntity: Deposit::class, inversedBy: 'triggeredCoupons')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Deposit $issuedFromDeposit = null;

    #[ORM\Column(length: 20, options: ['default' => 'ISSUED'])]
    #[Assert\Choice(choices: ['ISSUED', 'REDEEMED', 'EXPIRED', 'CANCELLED'])]
    private string $status = 'ISSUED';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $productSku = null;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $valueAmount = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $issuedAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $redeemedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $redeemedByMerchantUser = null;

    #[ORM\OneToMany(mappedBy: 'coupon', targetEntity: WalletTransaction::class)]
    private Collection $walletTransactions;

    public function __construct()
    {
        $this->publicId = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->issuedAt = new \DateTime();
        $this->walletTransactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): ?string
    {
        return $this->publicId;
    }

    public function setPublicId(string $publicId): self
    {
        $this->publicId = $publicId;
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

    public function getRewardRule(): ?RewardRule
    {
        return $this->rewardRule;
    }

    public function setRewardRule(?RewardRule $rewardRule): self
    {
        $this->rewardRule = $rewardRule;
        return $this;
    }

    public function getIssuedFromDeposit(): ?Deposit
    {
        return $this->issuedFromDeposit;
    }

    public function setIssuedFromDeposit(?Deposit $issuedFromDeposit): self
    {
        $this->issuedFromDeposit = $issuedFromDeposit;
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

    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    public function setProductSku(?string $productSku): self
    {
        $this->productSku = $productSku;
        return $this;
    }

    public function getValueAmount(): ?string
    {
        return $this->valueAmount;
    }

    public function setValueAmount(?string $valueAmount): self
    {
        $this->valueAmount = $valueAmount;
        return $this;
    }

    public function getIssuedAt(): ?\DateTimeInterface
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(\DateTimeInterface $issuedAt): self
    {
        $this->issuedAt = $issuedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getRedeemedAt(): ?\DateTimeInterface
    {
        return $this->redeemedAt;
    }

    public function setRedeemedAt(?\DateTimeInterface $redeemedAt): self
    {
        $this->redeemedAt = $redeemedAt;
        return $this;
    }

    public function getRedeemedByMerchantUser(): ?User
    {
        return $this->redeemedByMerchantUser;
    }

    public function setRedeemedByMerchantUser(?User $redeemedByMerchantUser): self
    {
        $this->redeemedByMerchantUser = $redeemedByMerchantUser;
        return $this;
    }

    public function getWalletTransactions(): Collection
    {
        return $this->walletTransactions;
    }

    public function isIssued(): bool
    {
        return $this->status === 'ISSUED';
    }

    public function isRedeemed(): bool
    {
        return $this->status === 'REDEEMED';
    }

    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED' || ($this->expiresAt !== null && $this->expiresAt < new \DateTime());
    }

    public function isCancelled(): bool
    {
        return $this->status === 'CANCELLED';
    }

    public function isUsable(): bool
    {
        return $this->isIssued() && !$this->isExpired();
    }

    // Alias methods for API compatibility
    public function getUuid(): ?string
    {
        return $this->publicId;
    }

    public function getQrCodeValue(): ?string
    {
        return $this->publicId;
    }

    public function setQrCodeValue(string $qrCode): self
    {
        $this->publicId = $qrCode;
        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->redeemedAt;
    }

    public function setUsedAt(?\DateTimeInterface $usedAt): self
    {
        $this->redeemedAt = $usedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->issuedAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->issuedAt = $createdAt;
        return $this;
    }
}

