<?php

namespace App\Repository;

use App\Entity\Recovery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recovery>
 */
class RecoveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recovery::class);
    }

    public function findByUuid(string $uuid): ?Recovery
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findByProUser($proUser): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.proUser = :user')
            ->setParameter('user', $proUser)
            ->orderBy('r.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByMerchantUser($merchantUser): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.merchantUser = :user')
            ->setParameter('user', $merchantUser)
            ->orderBy('r.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

