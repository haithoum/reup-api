<?php

namespace App\Entity;

use App\Repository\CitizenMerchantWalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CitizenMerchantWalletRepository::class)]
#[ORM\Table(name: 'citizen_merchant_wallets')]
#[ORM\UniqueConstraint(name: 'unique_citizen_merchant', columns: ['citizen_user_id', 'merchant_user_id'])]
#[ORM\Index(name: 'idx_wallets_citizen_user_id', columns: ['citizen_user_id'])]
#[ORM\Index(name: 'idx_wallets_merchant_user_id', columns: ['merchant_user_id'])]
class CitizenMerchantWallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'walletsAsCitizen')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $citizenUser = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'walletsAsMerchant')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $merchantUser = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private string $kgCredit = '0.00';

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'wallet', targetEntity: WalletTransaction::class)]
    private Collection $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

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

    public function getKgCredit(): string
    {
        return $this->kgCredit;
    }

    public function setKgCredit(string $kgCredit): self
    {
        $this->kgCredit = $kgCredit;
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

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addCredit(string $kg): void
    {
        $this->kgCredit = bcadd($this->kgCredit, $kg, 2);
    }

    public function deductCredit(string $kg): bool
    {
        if (bccomp($this->kgCredit, $kg, 2) < 0) {
            return false;
        }
        $this->kgCredit = bcsub($this->kgCredit, $kg, 2);
        return true;
    }

    public function hasEnoughCredit(string $kg): bool
    {
        return bccomp($this->kgCredit, $kg, 2) >= 0;
    }
}

