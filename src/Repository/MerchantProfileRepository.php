<?php

namespace App\Repository;

use App\Entity\MerchantProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MerchantProfile>
 */
class MerchantProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MerchantProfile::class);
    }

    public function findByPublicId(string $publicId): ?MerchantProfile
    {
        return $this->findOneBy(['publicId' => $publicId]);
    }

    public function findApprovedMerchants(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.validationStatus = :status')
            ->setParameter('status', 'APPROVED')
            ->orderBy('m.shopName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingMerchants(): array
    {
        return $this->findBy(['validationStatus' => 'PENDING'], ['createdAt' => 'ASC']);
    }

    public function findNearby(float $latitude, float $longitude, float $radiusKm = 10): array
    {
        // Formule Haversine pour calculer la distance
        $sql = '
            SELECT m.*, 
                (6371 * acos(cos(radians(:lat)) * cos(radians(m.latitude)) * 
                cos(radians(m.longitude) - radians(:lng)) + 
                sin(radians(:lat)) * sin(radians(m.latitude)))) AS distance
            FROM merchant_profiles m
            WHERE m.validation_status = :status
            HAVING distance < :radius
            ORDER BY distance
        ';

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'lat' => $latitude,
            'lng' => $longitude,
            'status' => 'APPROVED',
            'radius' => $radiusKm
        ]);

        return $result->fetchAllAssociative();
    }
}

