<?php

namespace App\Controller\Api;

use App\Entity\MerchantProfile;
use App\Repository\MerchantProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/merchants')]
class MerchantController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MerchantProfileRepository $merchantProfileRepository
    ) {
    }

    #[Route('', name: 'api_merchants_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $cityId = $request->query->get('cityId');
        $categoryId = $request->query->get('categoryId');
        $hasStock = $request->query->get('hasStock');
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');
        $radius = $request->query->get('radius', 10); // 10km par défaut

        $qb = $this->merchantProfileRepository->createQueryBuilder('m')
            ->where('m.status = :status')
            ->setParameter('status', 'APPROVED');

        if ($cityId) {
            $qb->andWhere('m.addressCity = :cityId')
                ->setParameter('cityId', $cityId);
        }

        if ($categoryId) {
            $qb->andWhere('m.merchantCategory = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($hasStock === 'true') {
            $qb->leftJoin('m.stock', 's')
                ->andWhere('s.currentWeightKg > 0');
        }

        $merchants = $qb->getQuery()->getResult();

        $result = [];
        foreach ($merchants as $merchant) {
            $data = [
                'id' => $merchant->getPublicId(),
                'storeName' => $merchant->getStoreName(),
                'category' => $merchant->getMerchantCategory()?->getName(),
                'address' => $merchant->getAddressStreet(),
                'city' => $merchant->getAddressCity()?->getName(),
                'coordinates' => [
                    'latitude' => $merchant->getLatitude(),
                    'longitude' => $merchant->getLongitude()
                ],
                'stockKg' => $merchant->getStock()?->getCurrentWeightKg() ?? 0
            ];

            // Calculer la distance si lat/long fournis
            if ($latitude && $longitude) {
                $distance = $this->calculateDistance(
                    (float) $latitude,
                    (float) $longitude,
                    $merchant->getLatitude(),
                    $merchant->getLongitude()
                );

                if ($distance <= $radius) {
                    $data['distance'] = round($distance, 1);
                    $result[] = $data;
                }
            } else {
                $result[] = $data;
            }
        }

        // Trier par distance si applicable
        if ($latitude && $longitude) {
            usort($result, fn($a, $b) => ($a['distance'] ?? PHP_FLOAT_MAX) <=> ($b['distance'] ?? PHP_FLOAT_MAX));
        }

        return $this->json(['merchants' => $result]);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

