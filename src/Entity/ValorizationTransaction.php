<?php

namespace App\Entity;

use App\Repository\ValorizationTransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ValorizationTransactionRepository::class)]
#[ORM\Table(name: 'valorization_transactions')]
#[ORM\Index(name: 'idx_valorization_transactions_recovery_id', columns: ['recovery_id'])]
#[ORM\Index(name: 'idx_valorization_transactions_payment_status', columns: ['payment_status'])]
#[ORM\Index(name: 'idx_valorization_transactions_created_at', columns: ['created_at'])]
class ValorizationTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Recovery::class, inversedBy: 'valorizationTransaction')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Recovery $recovery = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $qtyKg = null;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $pricePerKg = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $merchantAmount = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $reupAmount = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    #[Assert\Choice(choices: ['PENDING', 'PAID'])]
    private string $paymentStatus = 'PENDING';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecovery(): ?Recovery
    {
        return $this->recovery;
    }

    public function setRecovery(?Recovery $recovery): self
    {
        $this->recovery = $recovery;
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

    public function getPricePerKg(): ?string
    {
        return $this->pricePerKg;
    }

    public function setPricePerKg(string $pricePerKg): self
    {
        $this->pricePerKg = $pricePerKg;
        return $this;
    }

    public function getMerchantAmount(): ?string
    {
        return $this->merchantAmount;
    }

    public function setMerchantAmount(string $merchantAmount): self
    {
        $this->merchantAmount = $merchantAmount;
        return $this;
    }

    public function getReupAmount(): ?string
    {
        return $this->reupAmount;
    }

    public function setReupAmount(string $reupAmount): self
    {
        $this->reupAmount = $reupAmount;
        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): self
    {
        $this->paidAt = $paidAt;
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

    public function getTotalAmount(): string
    {
        return bcadd($this->merchantAmount, $this->reupAmount, 2);
    }

    public function isPending(): bool
    {
        return $this->paymentStatus === 'PENDING';
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'PAID';
    }

    // ==================== ALIAS METHODS FOR API ====================

    /**
     * Génère un UUID pour correspondre à l'API
     */
    public function getUuid(): string
    {
        return 'valorization-' . $this->getId();
    }

    /**
     * Alias pour getQtyKg() pour correspondre à l'API
     */
    public function getWeightKg(): ?string
    {
        return $this->getQtyKg();
    }

    /**
     * Alias pour getMerchantAmount() pour correspondre à l'API
     */
    public function getAmountEarned(): ?string
    {
        return $this->getMerchantAmount();
    }
}

