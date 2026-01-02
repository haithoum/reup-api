<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_users_email', columns: ['email'])]
#[ORM\Index(name: 'idx_users_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_users_google_sub', columns: ['google_sub'])]
#[ORM\Index(name: 'idx_users_deleted_at', columns: ['deleted_at'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $uuid = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordHash = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $googleSub = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['ROLE_CITIZEN', 'ROLE_MERCHANT', 'ROLE_PRO', 'ROLE_ADMIN'])]
    private ?string $role = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerifiedAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RefreshToken::class, orphanRemoval: true)]
    private Collection $refreshTokens;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: CitizenProfile::class, cascade: ['persist', 'remove'])]
    private ?CitizenProfile $citizenProfile = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: MerchantProfile::class, cascade: ['persist', 'remove'])]
    private ?MerchantProfile $merchantProfile = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ProProfile::class, cascade: ['persist', 'remove'])]
    private ?ProProfile $proProfile = null;

    #[ORM\OneToMany(mappedBy: 'citizenUser', targetEntity: Deposit::class)]
    private Collection $depositsAsCitizen;

    #[ORM\OneToMany(mappedBy: 'merchantUser', targetEntity: Deposit::class)]
    private Collection $depositsAsMerchant;

    #[ORM\OneToMany(mappedBy: 'proUser', targetEntity: Recovery::class)]
    private Collection $recoveriesAsPro;

    #[ORM\OneToMany(mappedBy: 'merchantUser', targetEntity: Recovery::class)]
    private Collection $recoveriesAsMerchant;

    #[ORM\OneToMany(mappedBy: 'citizenUser', targetEntity: CitizenMerchantWallet::class)]
    private Collection $walletsAsCitizen;

    #[ORM\OneToMany(mappedBy: 'merchantUser', targetEntity: CitizenMerchantWallet::class)]
    private Collection $walletsAsMerchant;

    #[ORM\OneToMany(mappedBy: 'citizenUser', targetEntity: Coupon::class)]
    private Collection $coupons;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AuditLog::class)]
    private Collection $auditLogs;

    public function __construct()
    {
        $this->uuid = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->refreshTokens = new ArrayCollection();
        $this->depositsAsCitizen = new ArrayCollection();
        $this->depositsAsMerchant = new ArrayCollection();
        $this->recoveriesAsPro = new ArrayCollection();
        $this->recoveriesAsMerchant = new ArrayCollection();
        $this->walletsAsCitizen = new ArrayCollection();
        $this->walletsAsMerchant = new ArrayCollection();
        $this->coupons = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->auditLogs = new ArrayCollection();
    }

    // Getters et setters

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getGoogleSub(): ?string
    {
        return $this->googleSub;
    }

    public function setGoogleSub(?string $googleSub): self
    {
        $this->googleSub = $googleSub;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
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

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeInterface $emailVerifiedAt): self
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
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

    // Relations

    public function getRefreshTokens(): Collection
    {
        return $this->refreshTokens;
    }

    public function addRefreshToken(RefreshToken $refreshToken): self
    {
        if (!$this->refreshTokens->contains($refreshToken)) {
            $this->refreshTokens->add($refreshToken);
            $refreshToken->setUser($this);
        }
        return $this;
    }

    public function removeRefreshToken(RefreshToken $refreshToken): self
    {
        if ($this->refreshTokens->removeElement($refreshToken)) {
            if ($refreshToken->getUser() === $this) {
                $refreshToken->setUser(null);
            }
        }
        return $this;
    }

    public function getCitizenProfile(): ?CitizenProfile
    {
        return $this->citizenProfile;
    }

    public function setCitizenProfile(?CitizenProfile $citizenProfile): self
    {
        if ($citizenProfile === null && $this->citizenProfile !== null) {
            $this->citizenProfile->setUser(null);
        }

        if ($citizenProfile !== null && $citizenProfile->getUser() !== $this) {
            $citizenProfile->setUser($this);
        }

        $this->citizenProfile = $citizenProfile;
        return $this;
    }

    public function getMerchantProfile(): ?MerchantProfile
    {
        return $this->merchantProfile;
    }

    public function setMerchantProfile(?MerchantProfile $merchantProfile): self
    {
        if ($merchantProfile === null && $this->merchantProfile !== null) {
            $this->merchantProfile->setUser(null);
        }

        if ($merchantProfile !== null && $merchantProfile->getUser() !== $this) {
            $merchantProfile->setUser($this);
        }

        $this->merchantProfile = $merchantProfile;
        return $this;
    }

    public function getProProfile(): ?ProProfile
    {
        return $this->proProfile;
    }

    public function setProProfile(?ProProfile $proProfile): self
    {
        if ($proProfile === null && $this->proProfile !== null) {
            $this->proProfile->setUser(null);
        }

        if ($proProfile !== null && $proProfile->getUser() !== $this) {
            $proProfile->setUser($this);
        }

        $this->proProfile = $proProfile;
        return $this;
    }

    public function getDepositsAsCitizen(): Collection
    {
        return $this->depositsAsCitizen;
    }

    public function getDepositsAsMerchant(): Collection
    {
        return $this->depositsAsMerchant;
    }

    public function getRecoveriesAsPro(): Collection
    {
        return $this->recoveriesAsPro;
    }

    public function getRecoveriesAsMerchant(): Collection
    {
        return $this->recoveriesAsMerchant;
    }

    public function getWalletsAsCitizen(): Collection
    {
        return $this->walletsAsCitizen;
    }

    public function getWalletsAsMerchant(): Collection
    {
        return $this->walletsAsMerchant;
    }

    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function getAuditLogs(): Collection
    {
        return $this->auditLogs;
    }

    // UserInterface methods

    public function getRoles(): array
    {
        return [$this->role];
    }

    public function eraseCredentials(): void
    {
        // Effacer les données sensibles temporaires si nécessaire
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    // PasswordAuthenticatedUserInterface method

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }
}

