<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenRepositoryInterface;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    /**
     * @param \DateTimeInterface $datetime
     * @return RefreshTokenInterface[]
     */
    public function findInvalid($datetime = null): array
    {
        $datetime = $datetime ?? new \DateTime();

        return $this->createQueryBuilder('rt')
            ->where('rt.expiresAt < :datetime')
            ->orWhere('rt.revokedAt IS NOT NULL')
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a token by its refresh token string
     *
     * @param string $refreshToken
     * @return RefreshTokenInterface|null
     */
    public function findOneByRefreshToken(string $refreshToken): ?RefreshTokenInterface
    {
        $tokenHash = hash('sha256', $refreshToken);

        return $this->createQueryBuilder('rt')
            ->where('rt.tokenHash = :tokenHash')
            ->andWhere('rt.expiresAt > :now')
            ->andWhere('rt.revokedAt IS NULL')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Override findOneBy to handle refreshToken field
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @return object|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        // If searching by refreshToken, use our custom method
        if (isset($criteria['refreshToken'])) {
            return $this->findOneByRefreshToken($criteria['refreshToken']);
        }

        return parent::findOneBy($criteria, $orderBy);
    }
}
