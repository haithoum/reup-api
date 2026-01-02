<?php

namespace App\Repository;

use App\Entity\CitizenProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CitizenProfile>
 */
class CitizenProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenProfile::class);
    }

    public function findByPublicId(string $publicId): ?CitizenProfile
    {
        return $this->findOneBy(['publicId' => $publicId]);
    }

    public function findActiveByUser($user): ?CitizenProfile
    {
        return $this->createQueryBuilder('cp')
            ->innerJoin('cp.user', 'u')
            ->where('cp.user = :user')
            ->andWhere('u.isActive = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

