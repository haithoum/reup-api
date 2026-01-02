<?php

namespace App\Controller\Api\Admin;

use App\Entity\MerchantProfile;
use App\Entity\ProProfile;
use App\Entity\User;
use App\Entity\Deposit;
use App\Entity\Recovery;
use App\Entity\Coupon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/dashboard', name: 'api_admin_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        // Compter les utilisateurs par rôle
        $totalCitizens = $this->entityManager->getRepository(User::class)
            ->count(['role' => 'ROLE_CITIZEN', 'isActive' => true]);

        $totalMerchants = $this->entityManager->getRepository(User::class)
            ->count(['role' => 'ROLE_MERCHANT', 'isActive' => true]);

        $totalPros = $this->entityManager->getRepository(User::class)
            ->count(['role' => 'ROLE_PRO', 'isActive' => true]);

        $totalUsers = $totalCitizens + $totalMerchants + $totalPros;

        // Compter les comptes en attente
        $pendingMerchants = $this->entityManager->getRepository(MerchantProfile::class)
            ->count(['validationStatus' => 'PENDING']);

        $pendingPros = $this->entityManager->getRepository(ProProfile::class)
            ->count(['validationStatus' => 'PENDING']);

        // Statistiques de dépôts
        $depositsStats = $this->entityManager->getRepository(Deposit::class)
            ->createQueryBuilder('d')
            ->select('SUM(d.weightKg) as total, COUNT(d.id) as count')
            ->where('d.status = :status')
            ->setParameter('status', 'VALIDATED')
            ->getQuery()
            ->getSingleResult();

        // Statistiques de récupérations
        $recoveriesStats = $this->entityManager->getRepository(Recovery::class)
            ->createQueryBuilder('r')
            ->select('SUM(r.actualQtyKg) as total, COUNT(r.id) as count')
            ->where('r.status = :status')
            ->setParameter('status', 'COMPLETED')
            ->getQuery()
            ->getSingleResult();

        // Statistiques coupons
        $activeCoupons = $this->entityManager->getRepository(Coupon::class)
            ->count(['status' => 'ISSUED']);

        $usedCoupons = $this->entityManager->getRepository(Coupon::class)
            ->count(['status' => 'REDEEMED']);

        // Calcul CO2 économisé (approximatif : 0.5 kg CO2 par kg de pain recyclé)
        $totalCo2SavedKg = ((float) ($depositsStats['total'] ?? 0)) * 0.5;

        return $this->json([
            'totalUsers' => $totalUsers,
            'totalCitizens' => $totalCitizens,
            'totalMerchants' => $totalMerchants,
            'totalPros' => $totalPros,
            'totalDepositsKg' => (float) ($depositsStats['total'] ?? 0),
            'totalDepositsCount' => (int) ($depositsStats['count'] ?? 0),
            'totalRecoveriesKg' => (float) ($recoveriesStats['total'] ?? 0),
            'totalRecoveriesCount' => (int) ($recoveriesStats['count'] ?? 0),
            'totalCo2SavedKg' => round($totalCo2SavedKg, 2),
            'pendingMerchants' => $pendingMerchants,
            'pendingPros' => $pendingPros,
            'activeCoupons' => $activeCoupons,
            'usedCoupons' => $usedCoupons
        ]);
    }

    #[Route('/merchants/{id}/approve', name: 'api_admin_merchant_approve', methods: ['PATCH'])]
    public function approveMerchant(int $id): JsonResponse
    {
        $merchantProfile = $this->entityManager->getRepository(MerchantProfile::class)->find($id);

        if (!$merchantProfile) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        if ($merchantProfile->getValidationStatus() !== 'PENDING') {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Seuls les comptes en attente peuvent être approuvés'
            ], Response::HTTP_BAD_REQUEST);
        }

        $merchantProfile->setValidationStatus('APPROVED');
        $merchantProfile->setValidatedAt(new \DateTime());
        $merchantProfile->setValidatedByUser($this->getUser());

        $this->entityManager->flush();

        return $this->json([
            'id' => $merchantProfile->getPublicId(),
            'status' => $merchantProfile->getValidationStatus(),
            'approvedAt' => $merchantProfile->getValidatedAt()->format('c')
        ]);
    }

    #[Route('/merchants/{id}/reject', name: 'api_admin_merchant_reject', methods: ['PATCH'])]
    public function rejectMerchant(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Non spécifié';

        $merchantProfile = $this->entityManager->getRepository(MerchantProfile::class)->find($id);

        if (!$merchantProfile) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        if ($merchantProfile->getValidationStatus() !== 'PENDING') {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Seuls les comptes en attente peuvent être rejetés'
            ], Response::HTTP_BAD_REQUEST);
        }

        $merchantProfile->setValidationStatus('REJECTED');
        $merchantProfile->setValidatedAt(new \DateTime());
        $merchantProfile->setValidatedByUser($this->getUser());
        $merchantProfile->setRejectionReason($reason);

        $this->entityManager->flush();

        return $this->json([
            'id' => $merchantProfile->getPublicId(),
            'status' => $merchantProfile->getValidationStatus(),
            'rejectedAt' => $merchantProfile->getValidatedAt()->format('c'),
            'rejectionReason' => $merchantProfile->getRejectionReason()
        ]);
    }

    #[Route('/pros/{id}/approve', name: 'api_admin_pro_approve', methods: ['PATCH'])]
    public function approvePro(int $id): JsonResponse
    {
        $proProfile = $this->entityManager->getRepository(ProProfile::class)->find($id);

        if (!$proProfile) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        if ($proProfile->getValidationStatus() !== 'PENDING') {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Seuls les comptes en attente peuvent être approuvés'
            ], Response::HTTP_BAD_REQUEST);
        }

        $proProfile->setValidationStatus('APPROVED');
        $proProfile->setValidatedAt(new \DateTime());
        $proProfile->setValidatedByUser($this->getUser());

        $this->entityManager->flush();

        return $this->json([
            'id' => $proProfile->getPublicId(),
            'status' => $proProfile->getValidationStatus(),
            'approvedAt' => $proProfile->getValidatedAt()->format('c')
        ]);
    }

    #[Route('/pros/{id}/reject', name: 'api_admin_pro_reject', methods: ['PATCH'])]
    public function rejectPro(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Non spécifié';

        $proProfile = $this->entityManager->getRepository(ProProfile::class)->find($id);

        if (!$proProfile) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        if ($proProfile->getValidationStatus() !== 'PENDING') {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Seuls les comptes en attente peuvent être rejetés'
            ], Response::HTTP_BAD_REQUEST);
        }

        $proProfile->setValidationStatus('REJECTED');
        $proProfile->setValidatedAt(new \DateTime());
        $proProfile->setValidatedByUser($this->getUser());
        $proProfile->setRejectionReason($reason);

        $this->entityManager->flush();

        return $this->json([
            'id' => $proProfile->getPublicId(),
            'status' => $proProfile->getValidationStatus(),
            'rejectedAt' => $proProfile->getValidatedAt()->format('c'),
            'rejectionReason' => $proProfile->getRejectionReason()
        ]);
    }

    #[Route('/merchants/pending', name: 'api_admin_merchants_pending', methods: ['GET'])]
    public function pendingMerchants(): JsonResponse
    {
        $merchants = $this->entityManager->getRepository(MerchantProfile::class)
            ->findBy(['validationStatus' => 'PENDING'], ['createdAt' => 'ASC']);

        $result = [];
        foreach ($merchants as $merchant) {
            $result[] = [
                'id' => $merchant->getId(),
                'publicId' => $merchant->getPublicId(),
                'shopName' => $merchant->getShopName(),
                'email' => $merchant->getUser()->getEmail(),
                'siret' => $merchant->getSiret(),
                'address' => $merchant->getAddressStreet(),
                'city' => $merchant->getAddressCity()?->getName(),
                'category' => $merchant->getCategory()?->getName(),
                'createdAt' => $merchant->getCreatedAt()->format('c')
            ];
        }

        return $this->json(['merchants' => $result]);
    }

    #[Route('/pros/pending', name: 'api_admin_pros_pending', methods: ['GET'])]
    public function pendingPros(): JsonResponse
    {
        $pros = $this->entityManager->getRepository(ProProfile::class)
            ->findBy(['validationStatus' => 'PENDING'], ['createdAt' => 'ASC']);

        $result = [];
        foreach ($pros as $pro) {
            $result[] = [
                'id' => $pro->getId(),
                'publicId' => $pro->getPublicId(),
                'organizationName' => $pro->getOrganizationName(),
                'organizationType' => $pro->getOrganizationType(),
                'email' => $pro->getUser()->getEmail(),
                'siret' => $pro->getSiret(),
                'address' => $pro->getAddressStreet(),
                'city' => $pro->getAddressCity()?->getName(),
                'createdAt' => $pro->getCreatedAt()->format('c')
            ];
        }

        return $this->json(['pros' => $result]);
    }
}

