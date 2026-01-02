<?php

namespace App\Repository;

use App\Entity\Deposit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deposit>
 */
class DepositRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deposit::class);
    }

    public function findByUuid(string $uuid): ?Deposit
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findByCitizen($citizenUser, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.citizenUser = :user')
            ->setParameter('user', $citizenUser)
            ->orderBy('d.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByMerchant($merchantUser, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.merchantUser = :user')
            ->setParameter('user', $merchantUser)
            ->orderBy('d.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTotalWeightByCitizen($citizenUser): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.weightKg) as total')
            ->where('d.citizenUser = :user')
            ->andWhere('d.status = :status')
            ->setParameter('user', $citizenUser)
            ->setParameter('status', 'VALIDATED')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getTotalWeightByMerchant($merchantUser): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.weightKg) as total')
            ->where('d.merchantUser = :user')
            ->andWhere('d.status = :status')
            ->setParameter('user', $merchantUser)
            ->setParameter('status', 'VALIDATED')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getStatsByPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('d')
            ->select('
                COUNT(d.id) as totalCount,
                SUM(d.weightKg) as totalWeight,
                d.status
            ')
            ->where('d.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('d.status')
            ->getQuery()
            ->getResult();
    }
}

