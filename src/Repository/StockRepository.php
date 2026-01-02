<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function findByMerchantUser($merchantUser): ?Stock
    {
        return $this->findOneBy(['merchantUser' => $merchantUser]);
    }

    public function findWithAvailableStock(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.availableKg > 0')
            ->orderBy('s.availableKg', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalAvailableWeight(): float
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(s.availableKg)')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}

