<?php

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\Index(name: 'idx_refresh_tokens_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_refresh_tokens_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_refresh_tokens_expires_at', columns: ['expires_at'])]
class RefreshToken implements RefreshTokenInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'refreshTokens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $tokenHash = null;

    // Property used by the bundle (not persisted, just for compatibility)
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $revokedAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

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

    public function getTokenHash(): ?string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->tokenHash = $tokenHash;
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

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getRevokedAt(): ?\DateTimeInterface
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?\DateTimeInterface $revokedAt): self
    {
        $this->revokedAt = $revokedAt;
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

    public function isValid(): bool
    {
        return $this->revokedAt === null && $this->expiresAt > new \DateTime();
    }

    // Methods required by RefreshTokenInterface
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken($refreshToken = null)
    {
        $this->refreshToken = $refreshToken;
        // Also store the hash
        if ($refreshToken) {
            $this->tokenHash = hash('sha256', $refreshToken);
        }
        return $this;
    }

    public function getValid(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setValid($valid)
    {
        $this->expiresAt = $valid;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->user ? $this->user->getEmail() : null;
    }

    public function setUsername($username)
    {
        // This method is required by the interface but we manage user relationship directly
        return $this;
    }

    public function __toString(): string
    {
        return $this->refreshToken ?? '';
    }

    public static function createForUserWithTtl(string $refreshToken, $user, int $ttl): RefreshTokenInterface
    {
        $instance = new self();
        $instance->setRefreshToken($refreshToken);
        $instance->setUser($user);
        $instance->setValid((new \DateTime())->modify("+{$ttl} seconds"));

        return $instance;
    }
}
