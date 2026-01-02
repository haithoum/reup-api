<?php

namespace App\Entity;

use App\Repository\RewardRuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RewardRuleRepository::class)]
#[ORM\Table(name: 'reward_rules')]
#[ORM\Index(name: 'idx_reward_rules_scope', columns: ['scope'])]
#[ORM\Index(name: 'idx_reward_rules_merchant_user_id', columns: ['merchant_user_id'])]
#[ORM\Index(name: 'idx_reward_rules_is_active', columns: ['is_active'])]
class RewardRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['GLOBAL', 'MERCHANT_SPECIFIC', 'CATEGORY'])]
    private ?string $scope = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $merchantUser = null;

    #[ORM\ManyToOne(targetEntity: MerchantCategory::class, inversedBy: 'rewardRules')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?MerchantCategory $category = null;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $thresholdKg = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['FREE_ITEM', 'DISCOUNT_PERCENTAGE', 'FIXED_AMOUNT'])]
    private ?string $rewardType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $productSku = null;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?string $valueAmount = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $expiresInDays = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    private ?int $maxRedemptionsPerUser = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $totalAvailable = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $validFrom = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $validUntil = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'rewardRule', targetEntity: Coupon::class)]
    private Collection $coupons;

    public function __construct()
    {
        $this->uuid = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->coupons = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;
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

    public function getCategory(): ?MerchantCategory
    {
        return $this->category;
    }

    public function setCategory(?MerchantCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getThresholdKg(): ?string
    {
        return $this->thresholdKg;
    }

    public function setThresholdKg(string $thresholdKg): self
    {
        $this->thresholdKg = $thresholdKg;
        return $this;
    }

    public function getRewardType(): ?string
    {
        return $this->rewardType;
    }

    public function setRewardType(string $rewardType): self
    {
        $this->rewardType = $rewardType;
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

    public function getExpiresInDays(): ?int
    {
        return $this->expiresInDays;
    }

    public function setExpiresInDays(int $expiresInDays): self
    {
        $this->expiresInDays = $expiresInDays;
        return $this;
    }

    public function getMaxRedemptionsPerUser(): ?int
    {
        return $this->maxRedemptionsPerUser;
    }

    public function setMaxRedemptionsPerUser(?int $maxRedemptionsPerUser): self
    {
        $this->maxRedemptionsPerUser = $maxRedemptionsPerUser;
        return $this;
    }

    public function getTotalAvailable(): ?int
    {
        return $this->totalAvailable;
    }

    public function setTotalAvailable(?int $totalAvailable): self
    {
        $this->totalAvailable = $totalAvailable;
        return $this;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTimeInterface $validFrom): self
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeInterface $validUntil): self
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
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

    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function isValidNow(): bool
    {
        $now = new \DateTime();

        if ($this->validFrom !== null && $now < $this->validFrom) {
            return false;
        }

        if ($this->validUntil !== null && $now > $this->validUntil) {
            return false;
        }

        return $this->isActive;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%s) - Seuil: %s kg',
            $this->name ?? 'Règle sans nom',
            $this->scope ?? 'N/A',
            $this->thresholdKg ?? '0'
        );
    }
}

