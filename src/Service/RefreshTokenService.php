<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class RefreshTokenService
{
    private const TOKEN_LENGTH = 64;
    private const TTL_SECONDS = 2592000; // 30 jours

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Crée un nouveau refresh token pour un utilisateur
     */
    public function createRefreshToken(User $user, Request $request): string
    {
        // Générer un token aléatoire sécurisé
        $tokenString = bin2hex(random_bytes(self::TOKEN_LENGTH));

        // Créer l'entité RefreshToken
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setTokenHash(hash('sha256', $tokenString));
        $refreshToken->setExpiresAt(new \DateTime('+' . self::TTL_SECONDS . ' seconds'));
        $refreshToken->setIpAddress($request->getClientIp());
        $refreshToken->setUserAgent($request->headers->get('User-Agent'));

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $tokenString;
    }

    /**
     * Valide un refresh token et retourne l'utilisateur associé
     */
    public function validateRefreshToken(string $tokenString): ?User
    {
        $tokenHash = hash('sha256', $tokenString);

        /** @var RefreshToken|null $refreshToken */
        $refreshToken = $this->entityManager->getRepository(RefreshToken::class)
            ->findOneBy(['tokenHash' => $tokenHash]);

        if (!$refreshToken) {
            return null;
        }

        // Vérifier si le token est révoqué
        if ($refreshToken->getRevokedAt() !== null) {
            return null;
        }

        // Vérifier si le token est expiré
        if ($refreshToken->getExpiresAt() < new \DateTime()) {
            return null;
        }

        // Vérifier si l'utilisateur est actif
        if (!$refreshToken->getUser()->isActive()) {
            return null;
        }

        return $refreshToken->getUser();
    }

    /**
     * Révoque un refresh token spécifique
     */
    public function revokeRefreshToken(string $tokenString): bool
    {
        $tokenHash = hash('sha256', $tokenString);

        /** @var RefreshToken|null $refreshToken */
        $refreshToken = $this->entityManager->getRepository(RefreshToken::class)
            ->findOneBy(['tokenHash' => $tokenHash]);

        if (!$refreshToken) {
            return false;
        }

        $refreshToken->setRevokedAt(new \DateTime());
        $this->entityManager->flush();

        return true;
    }

    /**
     * Révoque tous les refresh tokens d'un utilisateur
     */
    public function revokeAllUserTokens(User $user): int
    {
        $tokens = $this->entityManager->getRepository(RefreshToken::class)
            ->findBy([
                'user' => $user,
                'revokedAt' => null
            ]);

        $count = 0;
        $now = new \DateTime();

        foreach ($tokens as $token) {
            $token->setRevokedAt($now);
            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Nettoie les tokens expirés (à exécuter périodiquement)
     */
    public function cleanExpiredTokens(): int
    {
        $qb = $this->entityManager->createQueryBuilder();

        $query = $qb->delete(RefreshToken::class, 'rt')
            ->where('rt.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery();

        return $query->execute();
    }

    /**
     * Prolonge la durée de vie d'un refresh token
     */
    public function extendRefreshToken(string $tokenString, Request $request): bool
    {
        $tokenHash = hash('sha256', $tokenString);

        /** @var RefreshToken|null $refreshToken */
        $refreshToken = $this->entityManager->getRepository(RefreshToken::class)
            ->findOneBy(['tokenHash' => $tokenHash]);

        if (!$refreshToken || $refreshToken->getRevokedAt() !== null) {
            return false;
        }

        // Prolonger la durée de vie
        $refreshToken->setExpiresAt(new \DateTime('+' . self::TTL_SECONDS . ' seconds'));

        // Mettre à jour les informations de contexte
        $refreshToken->setIpAddress($request->getClientIp());
        $refreshToken->setUserAgent($request->headers->get('User-Agent'));

        $this->entityManager->flush();

        return true;
    }

    /**
     * Compte le nombre de tokens actifs pour un utilisateur
     */
    public function countActiveTokens(User $user): int
    {
        $qb = $this->entityManager->createQueryBuilder();

        return (int) $qb->select('COUNT(rt.id)')
            ->from(RefreshToken::class, 'rt')
            ->where('rt.user = :user')
            ->andWhere('rt.revokedAt IS NULL')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }
}

