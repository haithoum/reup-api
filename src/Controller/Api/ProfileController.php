<?php

namespace App\Controller\Api;

use App\Entity\CitizenMerchantWallet;
use App\Entity\Coupon;
use App\Entity\Deposit;
use App\Entity\Recovery;
use App\Entity\StockHistory;
use App\Entity\ValorizationTransaction;
use App\Entity\WalletTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/me')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/profile', name: 'api_me_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        $role = $user->getRole();

        $data = [
            'id' => $user->getUuid(),
            'email' => $user->getEmail(),
            'role' => $role
        ];

        if ($role === 'ROLE_CITIZEN' && $citizenProfile = $user->getCitizenProfile()) {
            $totalDeposits = $this->entityManager->getRepository(Deposit::class)
                ->createQueryBuilder('d')
                ->select('SUM(d.weightKg) as total, COUNT(d.id) as count')
                ->where('d.citizenUser = :user')
                ->andWhere('d.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', 'VALIDATED')
                ->getQuery()
                ->getSingleResult();

            $data['citizenProfile'] = [
                'firstName' => $citizenProfile->getFirstName(),
                'lastName' => $citizenProfile->getLastName(),
                'phone' => $citizenProfile->getPhoneNumber(),
                'qrCode' => $citizenProfile->getPublicId(),
                'city' => [
                    'id' => $citizenProfile->getAddressCity()?->getId(),
                    'name' => $citizenProfile->getAddressCity()?->getName()
                ],
                'totalDepositsKg' => (float) ($totalDeposits['total'] ?? 0),
                'totalDepositsCount' => (int) ($totalDeposits['count'] ?? 0),
                'createdAt' => $citizenProfile->getCreatedAt()->format('c')
            ];
        } elseif ($role === 'ROLE_MERCHANT' && $merchantProfile = $user->getMerchantProfile()) {
            $totalDeposits = $this->entityManager->getRepository(Deposit::class)
                ->createQueryBuilder('d')
                ->select('SUM(d.weightKg) as total, COUNT(d.id) as count')
                ->where('d.merchantUser = :user')
                ->andWhere('d.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', 'VALIDATED')
                ->getQuery()
                ->getSingleResult();

            $data['merchantProfile'] = [
                'storeName' => $merchantProfile->getStoreName(),
                'qrCode' => $merchantProfile->getPublicId(),
                'address' => $merchantProfile->getAddressStreet(),
                'city' => [
                    'id' => $merchantProfile->getAddressCity()?->getId(),
                    'name' => $merchantProfile->getAddressCity()?->getName()
                ],
                'category' => [
                    'id' => $merchantProfile->getMerchantCategory()?->getId(),
                    'name' => $merchantProfile->getMerchantCategory()?->getName()
                ],
                'coordinates' => [
                    'latitude' => $merchantProfile->getLatitude(),
                    'longitude' => $merchantProfile->getLongitude()
                ],
                'phone' => $merchantProfile->getPhoneNumber(),
                'siret' => $merchantProfile->getSiret(),
                'totalDepositsReceived' => (float) ($totalDeposits['total'] ?? 0),
                'totalDepositsCount' => (int) ($totalDeposits['count'] ?? 0),
                'createdAt' => $merchantProfile->getCreatedAt()->format('c')
            ];
        } elseif ($role === 'ROLE_PRO' && $proProfile = $user->getProProfile()) {
            $data['proProfile'] = [
                'organizationName' => $proProfile->getOrganizationName(),
                'organizationType' => $proProfile->getOrganizationType(),
                'qrCode' => $proProfile->getPublicId(),
                'phone' => $proProfile->getPhoneNumber(),
                'address' => $proProfile->getAddressStreet(),
                'city' => [
                    'id' => $proProfile->getAddressCity()?->getId(),
                    'name' => $proProfile->getAddressCity()?->getName()
                ],
                'siret' => $proProfile->getSiret(),
                'createdAt' => $proProfile->getCreatedAt()->format('c')
            ];
        }

        return $this->json($data);
    }

    #[Route('/wallets', name: 'api_me_wallets', methods: ['GET'])]
    #[IsGranted('ROLE_CITIZEN')]
    public function wallets(): JsonResponse
    {
        $user = $this->getUser();

        $wallets = $this->entityManager->getRepository(CitizenMerchantWallet::class)
            ->findBy(['citizenUser' => $user]);

        $result = [];
        foreach ($wallets as $wallet) {
            $merchantProfile = $wallet->getMerchantUser()->getMerchantProfile();

            $result[] = [
                'id' => $wallet->getId(),
                'merchant' => [
                    'id' => $merchantProfile->getPublicId(),
                    'storeName' => $merchantProfile->getStoreName(),
                    'category' => $merchantProfile->getMerchantCategory()?->getName()
                ],
                'balance' => $wallet->getBalance(),
                'totalEarned' => $wallet->getTotalEarned(),
                'totalSpent' => $wallet->getTotalSpent(),
                'lastActivityAt' => $wallet->getUpdatedAt()->format('c')
            ];
        }

        return $this->json(['wallets' => $result]);
    }

    #[Route('/coupons', name: 'api_me_coupons', methods: ['GET'])]
    #[IsGranted('ROLE_CITIZEN')]
    public function coupons(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $status = $request->query->get('status');

        $qb = $this->entityManager->getRepository(Coupon::class)
            ->createQueryBuilder('c')
            ->where('c.citizenUser = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $status);
        }

        $coupons = $qb->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($coupons as $coupon) {
            $result[] = [
                'id' => $coupon->getUuid(),
                'qrCode' => $coupon->getQrCodeValue(),
                'rewardRule' => [
                    'name' => $coupon->getRewardRule()->getName(),
                    'description' => $coupon->getRewardRule()->getDescription(),
                    'type' => $coupon->getRewardRule()->getType()
                ],
                'merchant' => [
                    'id' => $coupon->getMerchantUser()->getMerchantProfile()->getPublicId(),
                    'storeName' => $coupon->getMerchantUser()->getMerchantProfile()->getStoreName()
                ],
                'status' => $coupon->getStatus(),
                'expiresAt' => $coupon->getExpiresAt()?->format('c'),
                'createdAt' => $coupon->getCreatedAt()->format('c')
            ];
        }

        return $this->json(['coupons' => $result]);
    }

    #[Route('/transactions', name: 'api_me_transactions', methods: ['GET'])]
    #[IsGranted('ROLE_CITIZEN')]
    public function transactions(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $merchantId = $request->query->get('merchantId');
        $type = $request->query->get('type');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $qb = $this->entityManager->getRepository(WalletTransaction::class)
            ->createQueryBuilder('t')
            ->leftJoin('t.wallet', 'w')
            ->where('w.citizenUser = :user')
            ->setParameter('user', $user);

        if ($merchantId) {
            $qb->leftJoin('w.merchantUser', 'mu')
                ->leftJoin('mu.merchantProfile', 'mp')
                ->andWhere('mp.publicId = :merchantId')
                ->setParameter('merchantId', $merchantId);
        }

        if ($type) {
            $qb->andWhere('t.type = :type')
                ->setParameter('type', $type);
        }

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        $transactions = $qb->orderBy('t.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($transactions as $transaction) {
            $merchantProfile = $transaction->getWallet()->getMerchantUser()->getMerchantProfile();

            $item = [
                'id' => $transaction->getId(),
                'type' => $transaction->getType(),
                'amount' => $transaction->getAmount(),
                'description' => $transaction->getDescription(),
                'merchant' => [
                    'id' => $merchantProfile->getPublicId(),
                    'storeName' => $merchantProfile->getStoreName()
                ],
                'createdAt' => $transaction->getCreatedAt()->format('c')
            ];

            if ($transaction->getSourceDeposit()) {
                $item['relatedDeposit'] = ['id' => $transaction->getSourceDeposit()->getUuid()];
            }

            if ($transaction->getUsedForCoupon()) {
                $item['relatedCoupon'] = ['id' => $transaction->getUsedForCoupon()->getUuid()];
            }

            $result[] = $item;
        }

        return $this->json([
            'transactions' => $result,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => (int) ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/deposits', name: 'api_me_deposits', methods: ['GET'])]
    public function deposits(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $status = $request->query->get('status');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $qb = $this->entityManager->getRepository(Deposit::class)
            ->createQueryBuilder('d');

        if (in_array('ROLE_CITIZEN', $user->getRoles())) {
            $qb->where('d.citizenUser = :user');
        } elseif (in_array('ROLE_MERCHANT', $user->getRoles())) {
            $qb->where('d.merchantUser = :user');
        }

        $qb->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $status);
        }

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();

        $deposits = $qb->orderBy('d.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($deposits as $deposit) {
            $merchantProfile = $deposit->getMerchantUser()->getMerchantProfile();

            $result[] = [
                'id' => $deposit->getUuid(),
                'merchant' => [
                    'id' => $merchantProfile->getPublicId(),
                    'storeName' => $merchantProfile->getStoreName()
                ],
                'weightKg' => (float) $deposit->getWeightKg(),
                'status' => $deposit->getStatus(),
                'createdAt' => $deposit->getCreatedAt()->format('c'),
                'validatedAt' => $deposit->getAcceptedAt()?->format('c')
            ];
        }

        return $this->json([
            'deposits' => $result,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => (int) ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/stock', name: 'api_me_stock', methods: ['GET'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function stock(): JsonResponse
    {
        $user = $this->getUser();
        $merchantProfile = $user->getMerchantProfile();
        $stock = $merchantProfile->getStock();

        return $this->json([
            'currentWeightKg' => $stock?->getCurrentWeightKg() ?? 0,
            'lastUpdatedAt' => $stock?->getUpdatedAt()?->format('c')
        ]);
    }

    #[Route('/stock/history', name: 'api_me_stock_history', methods: ['GET'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function stockHistory(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $type = $request->query->get('type'); // DEPOSIT or RECOVERY
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $qb = $this->entityManager->getRepository(StockHistory::class)
            ->createQueryBuilder('sh')
            ->where('sh.merchantUser = :user')
            ->setParameter('user', $user);

        if ($type) {
            $qb->andWhere('sh.operationType = :type')
                ->setParameter('type', $type);
        }

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(sh.id)')->getQuery()->getSingleScalarResult();

        $movements = $qb->orderBy('sh.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($movements as $movement) {
            $item = [
                'id' => $movement->getId(),
                'type' => $movement->getType(),
                'weightKg' => (float) $movement->getWeightKg(),
                'newStockKg' => (float) $movement->getNewStockKg(),
                'createdAt' => $movement->getCreatedAt()->format('c')
            ];

            if ($movement->getRelatedDeposit()) {
                $deposit = $movement->getRelatedDeposit();
                $citizenProfile = $deposit->getCitizenUser()->getCitizenProfile();
                $item['relatedDeposit'] = [
                    'id' => $deposit->getUuid(),
                    'citizen' => $citizenProfile->getFirstName() . ' ' . $citizenProfile->getLastName()
                ];
            }

            if ($movement->getRelatedRecovery()) {
                $recovery = $movement->getRelatedRecovery();
                $proProfile = $recovery->getProUser()->getProProfile();
                $item['relatedRecovery'] = [
                    'id' => $recovery->getUuid(),
                    'pro' => $proProfile->getOrganizationName()
                ];
            }

            $result[] = $item;
        }

        return $this->json([
            'history' => $result,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => (int) ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/recoveries', name: 'api_me_recoveries', methods: ['GET'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function recoveries(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $status = $request->query->get('status'); // PLANNED, IN_PROGRESS, COMPLETED, CANCELLED
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        $qb = $this->entityManager->getRepository(Recovery::class)
            ->createQueryBuilder('r')
            ->where('r.merchantUser = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();

        $recoveries = $qb->orderBy('r.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($recoveries as $recovery) {
            $proProfile = $recovery->getProUser()->getProProfile();

            $result[] = [
                'id' => $recovery->getUuid(),
                'pro' => [
                    'id' => $proProfile->getPublicId(),
                    'organizationName' => $proProfile->getOrganizationName()
                ],
                'plannedWeightKg' => (float) $recovery->getPlannedWeightKg(),
                'actualWeightKg' => $recovery->getActualWeightKg() ? (float) $recovery->getActualWeightKg() : null,
                'status' => $recovery->getStatus(),
                'plannedDate' => $recovery->getPlannedDate()?->format('c'),
                'completedAt' => $recovery->getCompletedAt()?->format('c')
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

    #[Route('/valorization', name: 'api_me_valorization', methods: ['GET'])]
    #[IsGranted('ROLE_MERCHANT')]
    public function valorization(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        // Dates par défaut : mois en cours
        if (!$startDate) {
            $startDate = (new \DateTime('first day of this month'))->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = (new \DateTime('last day of this month'))->format('Y-m-d');
        }

        $qb = $this->entityManager->getRepository(ValorizationTransaction::class)
            ->createQueryBuilder('v')
            ->leftJoin('v.recovery', 'r')
            ->where('r.merchantUser = :user')
            ->setParameter('user', $user)
            ->andWhere('v.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', new \DateTime($startDate . ' 00:00:00'))
            ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));

        $valorizations = $qb->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $totalWeightKg = 0;
        $totalAmountEarned = 0;
        $transactions = [];

        foreach ($valorizations as $valorization) {
            $totalWeightKg += (float) $valorization->getWeightKg();
            $totalAmountEarned += (float) $valorization->getAmountEarned();

            $transactions[] = [
                'id' => $valorization->getUuid(),
                'recovery' => [
                    'id' => $valorization->getRecovery()->getUuid()
                ],
                'weightKg' => (float) $valorization->getWeightKg(),
                'amountEarned' => (float) $valorization->getAmountEarned(),
                'createdAt' => $valorization->getCreatedAt()->format('c')
            ];
        }

        // Calcul de la commission ReUp (10%)
        $reupCommission = $totalAmountEarned * 0.10;
        $netAmountEarned = $totalAmountEarned - $reupCommission;

        // Prix par kg (estimation basée sur les données)
        $pricePerKg = $totalWeightKg > 0 ? $totalAmountEarned / $totalWeightKg : 0;

        return $this->json([
            'period' => [
                'startDate' => $startDate,
                'endDate' => $endDate
            ],
            'totalWeightRecoveredKg' => (float) $totalWeightKg,
            'pricePerKg' => round($pricePerKg, 2),
            'totalAmountEarned' => round($totalAmountEarned, 2),
            'reupCommission' => round($reupCommission, 2),
            'netAmountEarned' => round($netAmountEarned, 2),
            'transactions' => $transactions
        ]);
    }
}
