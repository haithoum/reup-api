<?php

namespace App\Repository;

use App\Entity\Coupon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coupon>
 */
class CouponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class);
    }

    public function findByQrCode(string $qrCode): ?Coupon
    {
        return $this->findOneBy(['publicId' => $qrCode]);
    }

    public function findByCitizenUser($citizenUser): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.citizenUser = :user')
            ->setParameter('user', $citizenUser)
            ->orderBy('c.issuedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByCitizen($citizenUser): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.citizenUser = :user')
            ->andWhere('c.status = :status')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $citizenUser)
            ->setParameter('status', 'ISSUED')
            ->setParameter('now', new \DateTime())
            ->orderBy('c.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findExpiringSoon(int $days = 7): array
    {
        $now = new \DateTime();
        $futureDate = (new \DateTime())->modify("+{$days} days");

        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->andWhere('c.expiresAt BETWEEN :now AND :future')
            ->setParameter('status', 'ISSUED')
            ->setParameter('now', $now)
            ->setParameter('future', $futureDate)
            ->orderBy('c.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

