<?php

namespace App\Controller\Api;

use App\Entity\MerchantProfile;
use App\Entity\ProProfile;
use App\Entity\Recovery;
use App\Entity\StockHistory;
use App\Entity\ValorizationTransaction;
use App\Repository\MerchantProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/recoveries')]
#[IsGranted('ROLE_PRO')]
class RecoveryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MerchantProfileRepository $merchantProfileRepository
    ) {
    }

    #[Route('', name: 'api_recovery_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $merchantQrCode = $data['merchantQrCode'] ?? null;
        $plannedWeightKg = $data['plannedWeightKg'] ?? null;
        $plannedDate = $data['plannedDate'] ?? null;

        if (!$merchantQrCode || !$plannedWeightKg || !$plannedDate) {
            return $this->json([
                'error' => 'VALIDATION_ERROR',
                'message' => 'merchantQrCode, plannedWeightKg et plannedDate sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($plannedWeightKg < 1 || $plannedWeightKg > 100) {
            return $this->json([
                'error' => 'INVALID_WEIGHT',
                'message' => 'Le poids doit être entre 1 et 100 kg'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Trouver le commerçant
        $merchantProfile = $this->merchantProfileRepository->findOneBy(['publicId' => $merchantQrCode]);

        if (!$merchantProfile || $merchantProfile->getStatus() !== 'APPROVED') {
            return $this->json([
                'error' => 'INVALID_QR_CODE',
                'message' => 'QR code commerçant invalide ou compte non approuvé'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier le stock
        $stock = $merchantProfile->getStock();
        if (!$stock || $stock->getCurrentWeightKg() < $plannedWeightKg) {
            return $this->json([
                'error' => 'INSUFFICIENT_STOCK',
                'message' => 'Stock insuffisant chez ce commerçant'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que la date est dans le futur
        $plannedDateTime = new \DateTime($plannedDate);
        if ($plannedDateTime < new \DateTime()) {
            return $this->json([
                'error' => 'INVALID_DATE',
                'message' => 'La date planifiée doit être dans le futur'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $proProfile = $user->getProProfile();

        // Créer la récupération
        $recovery = new Recovery();
        $recovery->setProUser($user);
        $recovery->setMerchantUser($merchantProfile->getUser());
        $recovery->setPlannedWeightKg($plannedWeightKg);
        $recovery->setPlannedDate($plannedDateTime);
        $recovery->setStatus('PLANNED');

        $this->entityManager->persist($recovery);
        $this->entityManager->flush();

        return $this->json([
            'id' => $recovery->getUuid(),
            'merchant' => [
                'id' => $merchantProfile->getPublicId(),
                'storeName' => $merchantProfile->getStoreName(),
                'currentStock' => $stock->getCurrentWeightKg()
            ],
            'plannedWeightKg' => $recovery->getPlannedWeightKg(),
            'status' => $recovery->getStatus(),
            'plannedDate' => $recovery->getPlannedDate()->format('c'),
            'createdAt' => $recovery->getCreatedAt()->format('c')
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_recovery_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $status = $request->query->get('status');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $qb = $this->entityManager->getRepository(Recovery::class)
            ->createQueryBuilder('r')
            ->where('r.proUser = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();

        $recoveries = $qb->orderBy('r.plannedDate', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($recoveries as $recovery) {
            $merchantProfile = $recovery->getMerchantUser()->getMerchantProfile();

            $result[] = [
                'id' => $recovery->getUuid(),
                'merchant' => [
                    'id' => $merchantProfile->getPublicId(),
                    'storeName' => $merchantProfile->getStoreName(),
                    'address' => $merchantProfile->getAddressStreet(),
                    'coordinates' => [
                        'latitude' => $merchantProfile->getLatitude(),
                        'longitude' => $merchantProfile->getLongitude()
                    ]
                ],
                'plannedWeightKg' => $recovery->getPlannedWeightKg(),
                'actualWeightKg' => $recovery->getActualWeightKg(),
                'status' => $recovery->getStatus(),
                'plannedDate' => $recovery->getPlannedDate()->format('c'),
                'completedAt' => $recovery->getCompletedAt()?->format('c'),
                'createdAt' => $recovery->getCreatedAt()->format('c')
            ];
        }

        return $this->json([
            'recoveries' => $result,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => (int) ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{uuid}/start', name: 'api_recovery_start', methods: ['PATCH'])]
    public function start(string $uuid): JsonResponse
    {
        $recovery = $this->entityManager->getRepository(Recovery::class)
            ->findOneBy(['uuid' => $uuid]);

        if (!$recovery) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        if ($recovery->getProUser() !== $this->getUser()) {
            return $this->json(['error' => 'FORBIDDEN'], Response::HTTP_FORBIDDEN);
        }

        if ($recovery->getStatus() !== 'PLANNED') {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Seules les récupérations planifiées peuvent être démarrées'
            ], Response::HTTP_BAD_REQUEST);
        }

        $recovery->setStatus('IN_PROGRESS');
        $recovery->setStartedAt(new \DateTime());

        $this->entityManager->flush();

        return $this->json([
            'id' => $recovery->getUuid(),
            'status' => $recovery->getStatus(),
            'startedAt' => $recovery->getStartedAt()->format('c')
        ]);
    }

    #[Route('/{uuid}/complete', name: 'api_recovery_complete', methods: ['PATCH'])]
    public function complete(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $actualWeightKg = $data['actualWeightKg'] ?? null;

        if (!$actualWeightKg) {
            return $this->json([
                'error' => 'VALIDATION_ERROR',
                'message' => 'actualWeightKg est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $recovery = $this->entityManager->getRepository(Recovery::class)
            ->findOneBy(['uuid' => $uuid]);

        if (!$recovery) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        if ($recovery->getProUser() !== $this->getUser()) {
            return $this->json(['error' => 'FORBIDDEN'], Response::HTTP_FORBIDDEN);
        }

        if ($recovery->getStatus() !== 'IN_PROGRESS') {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Seules les récupérations en cours peuvent être finalisées'
            ], Response::HTTP_BAD_REQUEST);
        }

        $maxWeight = $recovery->getPlannedWeightKg() * 1.1; // +10%
        if ($actualWeightKg < 0.1 || $actualWeightKg > $maxWeight) {
            return $this->json([
                'error' => 'INVALID_WEIGHT',
                'message' => "Le poids doit être entre 0.1 et {$maxWeight} kg"
            ], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour le stock du commerçant
        $merchantProfile = $recovery->getMerchantUser()->getMerchantProfile();
        $stock = $merchantProfile->getStock();

        if ($stock) {
            $oldWeight = $stock->getCurrentWeightKg();
            $newWeight = max(0, $oldWeight - $actualWeightKg);
            $stock->setCurrentWeightKg($newWeight);

            // Historique
            $history = new StockHistory();
            $history->setStock($stock);
            $history->setType('RECOVERY_OUT');
            $history->setWeightKg(-$actualWeightKg);
            $history->setNewStockKg($newWeight);
            $history->setSourceRecovery($recovery);
            $this->entityManager->persist($history);
        }

        // Valorisation
        $pricePerKg = 1.50; // Prix par kg (à paramétrer)
        $totalAmount = $actualWeightKg * $pricePerKg;
        $reupCommission = $totalAmount * 0.10; // 10% commission
        $merchantAmount = $totalAmount - $reupCommission;

        $valorization = new ValorizationTransaction();
        $valorization->setRecovery($recovery);
        $valorization->setMerchantProfile($merchantProfile);
        $valorization->setWeightKg($actualWeightKg);
        $valorization->setPricePerKg($pricePerKg);
        $valorization->setTotalAmount($totalAmount);
        $valorization->setReupCommission($reupCommission);
        $valorization->setMerchantAmount($merchantAmount);

        $recovery->setActualWeightKg($actualWeightKg);
        $recovery->setStatus('COMPLETED');
        $recovery->setCompletedAt(new \DateTime());

        $this->entityManager->persist($valorization);
        $this->entityManager->flush();

        return $this->json([
            'id' => $recovery->getUuid(),
            'actualWeightKg' => $recovery->getActualWeightKg(),
            'status' => $recovery->getStatus(),
            'completedAt' => $recovery->getCompletedAt()->format('c'),
            'valorization' => [
                'merchantAmount' => $merchantAmount,
                'reupCommission' => $reupCommission
            ]
        ]);
    }

    #[Route('/{uuid}/cancel', name: 'api_recovery_cancel', methods: ['PATCH'])]
    public function cancel(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Non spécifié';

        $recovery = $this->entityManager->getRepository(Recovery::class)
            ->findOneBy(['uuid' => $uuid]);

        if (!$recovery) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        if ($recovery->getProUser() !== $this->getUser()) {
            return $this->json(['error' => 'FORBIDDEN'], Response::HTTP_FORBIDDEN);
        }

        if (!in_array($recovery->getStatus(), ['PLANNED', 'IN_PROGRESS'])) {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Cette récupération ne peut plus être annulée'
            ], Response::HTTP_BAD_REQUEST);
        }

        $recovery->setStatus('CANCELLED');
        $recovery->setCancelledAt(new \DateTime());
        $recovery->setCancellationReason($reason);

        $this->entityManager->flush();

        return $this->json([
            'id' => $recovery->getUuid(),
            'status' => $recovery->getStatus(),
            'cancelledAt' => $recovery->getCancelledAt()->format('c'),
            'cancellationReason' => $recovery->getCancellationReason()
        ]);
    }
}

