<?php

namespace App\Repository;

use App\Entity\RewardRule;
use App\Entity\MerchantProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RewardRule>
 */
class RewardRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RewardRule::class);
    }

    public function findActiveRules(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('r')
            ->where('r.isActive = true')
            ->andWhere('r.validFrom IS NULL OR r.validFrom <= :now')
            ->andWhere('r.validUntil IS NULL OR r.validUntil >= :now')
            ->setParameter('now', $now)
            ->orderBy('r.pointsCost', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveRuleForMerchant(MerchantProfile $merchant): ?RewardRule
    {
        $now = new \DateTime();

        $qb = $this->createQueryBuilder('r')
            ->where('r.isActive = true')
            ->andWhere('r.validFrom IS NULL OR r.validFrom <= :now')
            ->andWhere('r.validUntil IS NULL OR r.validUntil >= :now')
            ->setParameter('now', $now);

        // Règle par catégorie
        if ($merchant->getCategory()) {
            $result = (clone $qb)
                ->andWhere('r.scope = :scopeCategory')
                ->andWhere('r.merchantCategory = :category')
                ->setParameter('scopeCategory', 'CATEGORY')
                ->setParameter('category', $merchant->getCategory())
                ->orderBy('r.pointsPerKg', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($result) {
                return $result;
            }
        }

        // Règle globale
        return $qb
            ->andWhere('r.scope = :scopeGlobal')
            ->setParameter('scopeGlobal', 'GLOBAL')
            ->orderBy('r.pointsPerKg', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUuid(string $uuid): ?RewardRule
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }
}

