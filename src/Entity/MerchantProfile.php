<?php

namespace App\Entity;

use App\Repository\MerchantProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MerchantProfileRepository::class)]
#[ORM\Table(name: 'merchant_profiles')]
#[ORM\Index(name: 'idx_merchant_profiles_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_merchant_profiles_public_id', columns: ['public_id'])]
#[ORM\Index(name: 'idx_merchant_profiles_validation_status', columns: ['validation_status'])]
class MerchantProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'merchantProfile')]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $publicId = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $shopName = null;

    #[ORM\ManyToOne(targetEntity: MerchantCategory::class, inversedBy: 'merchantProfiles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MerchantCategory $category = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $addressStreet = null;

    #[ORM\ManyToOne(targetEntity: City::class, inversedBy: 'merchantProfiles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?City $addressCity = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    private ?string $addressPostalCode = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    #[Assert\NotBlank]
    #[Assert\Range(min: -90, max: 90)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8)]
    #[Assert\NotBlank]
    #[Assert\Range(min: -180, max: 180)]
    private ?string $longitude = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $openingHours = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    #[Assert\Positive]
    private ?string $maxStorageCapacityKg = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    #[Assert\Choice(choices: ['PENDING', 'APPROVED', 'REJECTED'])]
    private string $validationStatus = 'PENDING';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $validatedByUser = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $validatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToOne(mappedBy: 'merchantUser', targetEntity: Stock::class, cascade: ['persist', 'remove'])]
    private ?Stock $stock = null;

    #[ORM\OneToMany(mappedBy: 'merchantUser', targetEntity: Deposit::class)]
    private Collection $deposits;

    #[ORM\OneToMany(mappedBy: 'merchantUser', targetEntity: Recovery::class)]
    private Collection $recoveries;

    #[ORM\OneToMany(mappedBy: 'merchantUser', targetEntity: CitizenMerchantWallet::class)]
    private Collection $citizenWallets;

    #[ORM\OneToMany(mappedBy: 'merchantUser', targetEntity: Coupon::class)]
    private Collection $coupons;

    public function __construct()
    {
        $this->publicId = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->deposits = new ArrayCollection();
        $this->recoveries = new ArrayCollection();
        $this->citizenWallets = new ArrayCollection();
        $this->coupons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getShopName(): ?string
    {
        return $this->shopName;
    }

    public function setShopName(string $shopName): self
    {
        $this->shopName = $shopName;
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

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;
        return $this;
    }

    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    public function setAddressStreet(string $addressStreet): self
    {
        $this->addressStreet = $addressStreet;
        return $this;
    }

    public function getAddressCity(): ?City
    {
        return $this->addressCity;
    }

    public function setAddressCity(?City $addressCity): self
    {
        $this->addressCity = $addressCity;
        return $this;
    }

    public function getAddressPostalCode(): ?string
    {
        return $this->addressPostalCode;
    }

    public function setAddressPostalCode(string $addressPostalCode): self
    {
        $this->addressPostalCode = $addressPostalCode;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getOpeningHours(): ?array
    {
        return $this->openingHours;
    }

    public function setOpeningHours(?array $openingHours): self
    {
        $this->openingHours = $openingHours;
        return $this;
    }

    public function getMaxStorageCapacityKg(): ?string
    {
        return $this->maxStorageCapacityKg;
    }

    public function setMaxStorageCapacityKg(?string $maxStorageCapacityKg): self
    {
        $this->maxStorageCapacityKg = $maxStorageCapacityKg;
        return $this;
    }

    public function getValidationStatus(): string
    {
        return $this->validationStatus;
    }

    public function setValidationStatus(string $validationStatus): self
    {
        $this->validationStatus = $validationStatus;
        return $this;
    }

    public function getValidatedByUser(): ?User
    {
        return $this->validatedByUser;
    }

    public function setValidatedByUser(?User $validatedByUser): self
    {
        $this->validatedByUser = $validatedByUser;
        return $this;
    }

    public function getValidatedAt(): ?\DateTimeInterface
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeInterface $validatedAt): self
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;
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

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        if ($stock === null && $this->stock !== null) {
            $this->stock->setMerchantUser(null);
        }

        if ($stock !== null && $stock->getMerchantUser() !== $this->user) {
            $stock->setMerchantUser($this->user);
        }

        $this->stock = $stock;
        return $this;
    }

    public function getDeposits(): Collection
    {
        return $this->deposits;
    }

    public function getRecoveries(): Collection
    {
        return $this->recoveries;
    }

    public function getCitizenWallets(): Collection
    {
        return $this->citizenWallets;
    }

    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function isApproved(): bool
    {
        return $this->validationStatus === 'APPROVED';
    }

    public function isPending(): bool
    {
        return $this->validationStatus === 'PENDING';
    }

    public function isRejected(): bool
    {
        return $this->validationStatus === 'REJECTED';
    }
}

