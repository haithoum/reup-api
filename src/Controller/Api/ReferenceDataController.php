<?php

namespace App\Controller\Api;

use App\Entity\City;
use App\Entity\MerchantCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ReferenceDataController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/cities', name: 'api_cities_list', methods: ['GET'])]
    public function cities(): JsonResponse
    {
        $cities = $this->entityManager->getRepository(City::class)->findAll();

        $result = array_map(fn(City $city) => [
            'id' => $city->getId(),
            'name' => $city->getName(),
            'countryCode' => $city->getCountryCode()
        ], $cities);

        return $this->json(['cities' => $result]);
    }

    #[Route('/merchant-categories', name: 'api_merchant_categories_list', methods: ['GET'])]
    public function merchantCategories(): JsonResponse
    {
        $categories = $this->entityManager->getRepository(MerchantCategory::class)->findAll();

        $result = array_map(fn(MerchantCategory $category) => [
            'id' => $category->getId(),
            'name' => $category->getName()
        ], $categories);

        return $this->json(['categories' => $result]);
    }
}

