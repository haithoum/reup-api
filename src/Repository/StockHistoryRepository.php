<?php

namespace App\Repository;

use App\Entity\StockHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockHistory>
 */
class StockHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockHistory::class);
    }

    public function findByStock($stock): array
    {
        return $this->createQueryBuilder('sh')
            ->where('sh.stock = :stock')
            ->setParameter('stock', $stock)
            ->orderBy('sh.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByMerchantUser($merchantUser, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('sh')
            ->leftJoin('sh.stock', 's')
            ->where('s.merchantUser = :user')
            ->setParameter('user', $merchantUser)
            ->orderBy('sh.createdAt', 'DESC');

        if ($type) {
            $qb->andWhere('sh.type = :type')
                ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }
}

