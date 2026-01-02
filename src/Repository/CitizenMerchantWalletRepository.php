<?php

namespace App\Repository;

use App\Entity\CitizenMerchantWallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CitizenMerchantWallet>
 */
class CitizenMerchantWalletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenMerchantWallet::class);
    }

    public function findByCitizenUser($citizenUser): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.citizenUser = :user')
            ->setParameter('user', $citizenUser)
            ->orderBy('w.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByMerchantUser($merchantUser): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.merchantUser = :user')
            ->setParameter('user', $merchantUser)
            ->orderBy('w.balance', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOrCreate($citizenUser, $merchantUser): CitizenMerchantWallet
    {
        $wallet = $this->findOneBy([
            'citizenUser' => $citizenUser,
            'merchantUser' => $merchantUser
        ]);

        if (!$wallet) {
            $wallet = new CitizenMerchantWallet();
            $wallet->setCitizenUser($citizenUser);
            $wallet->setMerchantUser($merchantUser);
            $wallet->setBalance(0);
        }

        return $wallet;
    }
}

