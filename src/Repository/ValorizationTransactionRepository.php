<?php

namespace App\Repository;

use App\Entity\ValorizationTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ValorizationTransaction>
 */
class ValorizationTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ValorizationTransaction::class);
    }

    public function findByMerchantProfile($merchantProfile): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.merchantProfile = :merchant')
            ->setParameter('merchant', $merchantProfile)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalByMerchantProfile($merchantProfile): array
    {
        $result = $this->createQueryBuilder('v')
            ->select('
                SUM(v.weightKg) as totalWeight,
                SUM(v.totalAmount) as totalAmount,
                SUM(v.merchantAmount) as merchantAmount,
                SUM(v.reupCommission) as reupCommission
            ')
            ->where('v.merchantProfile = :merchant')
            ->setParameter('merchant', $merchantProfile)
            ->getQuery()
            ->getSingleResult();

        return [
            'totalWeight' => (float) ($result['totalWeight'] ?? 0),
            'totalAmount' => (float) ($result['totalAmount'] ?? 0),
            'merchantAmount' => (float) ($result['merchantAmount'] ?? 0),
            'reupCommission' => (float) ($result['reupCommission'] ?? 0)
        ];
    }
}

