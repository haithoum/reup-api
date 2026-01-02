<?php

namespace App\Repository;

use App\Entity\MerchantCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MerchantCategory>
 */
class MerchantCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MerchantCategory::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('mc')
            ->orderBy('mc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?MerchantCategory
    {
        return $this->findOneBy(['name' => $name]);
    }
}

