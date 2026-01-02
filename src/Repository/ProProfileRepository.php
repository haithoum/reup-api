<?php

namespace App\Repository;

use App\Entity\ProProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProProfile>
 */
class ProProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProProfile::class);
    }

    public function findByPublicId(string $publicId): ?ProProfile
    {
        return $this->findOneBy(['publicId' => $publicId]);
    }

    public function findApprovedPros(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.validationStatus = :status')
            ->setParameter('status', 'APPROVED')
            ->orderBy('p.organizationName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingPros(): array
    {
        return $this->findBy(['validationStatus' => 'PENDING'], ['createdAt' => 'ASC']);
    }

    public function findByOrganizationType(string $type): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.organizationType = :type')
            ->andWhere('p.validationStatus = :status')
            ->setParameter('type', $type)
            ->setParameter('status', 'APPROVED')
            ->orderBy('p.organizationName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

