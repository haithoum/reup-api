<?php

namespace App\Repository;

use App\Entity\WalletTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletTransaction>
 */
class WalletTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletTransaction::class);
    }

    public function findByWallet($wallet): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCitizenUser($citizenUser): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.wallet', 'w')
            ->where('w.citizenUser = :user')
            ->setParameter('user', $citizenUser)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByMerchantUser($merchantUser): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.wallet', 'w')
            ->where('w.merchantUser = :user')
            ->setParameter('user', $merchantUser)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

