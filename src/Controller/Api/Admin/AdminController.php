<?php

namespace App\Controller\Api\Admin;

use App\Entity\MerchantProfile;
use App\Entity\ProProfile;
use App\Entity\User;
use App\Entity\Deposit;
use App\Entity\Recovery;
use App\Entity\Coupon;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
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
    #[OA\Get(
        path: '/api/admin/dashboard',
        summary: 'Récupère les statistiques du tableau de bord administrateur',
        security: [['bearerAuth' => []]],
        tags: ['Admin']
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistiques globales de la plateforme',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'totalUsers', type: 'integer', example: 150),
                new OA\Property(property: 'totalCitizens', type: 'integer', example: 120),
                new OA\Property(property: 'totalMerchants', type: 'integer', example: 25),
                new OA\Property(property: 'totalPros', type: 'integer', example: 5),
                new OA\Property(property: 'totalDepositsKg', type: 'number', format: 'float', example: 456.75),
                new OA\Property(property: 'totalDepositsCount', type: 'integer', example: 320),
                new OA\Property(property: 'totalRecoveriesKg', type: 'number', format: 'float', example: 400.50),
                new OA\Property(property: 'totalRecoveriesCount', type: 'integer', example: 45),
                new OA\Property(property: 'totalCo2SavedKg', type: 'number', format: 'float', example: 228.38),
                new OA\Property(property: 'pendingMerchants', type: 'integer', example: 3),
                new OA\Property(property: 'pendingPros', type: 'integer', example: 2),
                new OA\Property(property: 'activeCoupons', type: 'integer', example: 45),
                new OA\Property(property: 'usedCoupons', type: 'integer', example: 28)
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé - nécessite le rôle ADMIN')]
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
    #[OA\Patch(
        path: '/api/admin/merchants/{id}/approve',
        summary: 'Approuve un commerçant en attente de validation',
        security: [['bearerAuth' => []]],
        tags: ['Admin']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'ID du profil commerçant',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Commerçant approuvé avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string', example: 'merchant_abc123xyz'),
                new OA\Property(property: 'status', type: 'string', example: 'APPROVED'),
                new OA\Property(property: 'approvedAt', type: 'string', format: 'date-time', example: '2026-01-02T14:30:00+01:00')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Statut invalide - seuls les comptes PENDING peuvent être approuvés')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé - nécessite le rôle ADMIN')]
    #[OA\Response(response: 404, description: 'Profil commerçant introuvable')]
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
    #[OA\Patch(
        path: '/api/admin/merchants/{id}/reject',
        summary: 'Rejette un commerçant en attente de validation',
        security: [['bearerAuth' => []]],
        tags: ['Admin']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'ID du profil commerçant',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'reason',
                    type: 'string',
                    example: 'Documents incomplets ou non conformes',
                    description: 'Raison du rejet (optionnelle, défaut: "Non spécifié")'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Commerçant rejeté avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string', example: 'merchant_abc123xyz'),
                new OA\Property(property: 'status', type: 'string', example: 'REJECTED'),
                new OA\Property(property: 'rejectedAt', type: 'string', format: 'date-time', example: '2026-01-02T14:30:00+01:00'),
                new OA\Property(property: 'rejectionReason', type: 'string', example: 'Documents incomplets ou non conformes')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Statut invalide - seuls les comptes PENDING peuvent être rejetés')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé - nécessite le rôle ADMIN')]
    #[OA\Response(response: 404, description: 'Profil commerçant introuvable')]
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
    #[OA\Patch(
        path: '/api/admin/pros/{id}/approve',
        summary: 'Approuve un acteur pro en attente de validation',
        security: [['bearerAuth' => []]],
        tags: ['Admin']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'ID du profil acteur pro',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Acteur pro approuvé avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string', example: 'pro_xyz789abc'),
                new OA\Property(property: 'status', type: 'string', example: 'APPROVED'),
                new OA\Property(property: 'approvedAt', type: 'string', format: 'date-time', example: '2026-01-02T15:00:00+01:00')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Statut invalide - seuls les comptes PENDING peuvent être approuvés')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé - nécessite le rôle ADMIN')]
    #[OA\Response(response: 404, description: 'Profil acteur pro introuvable')]
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
    #[OA\Patch(
        path: '/api/admin/pros/{id}/reject',
        summary: 'Rejette un acteur pro en attente de validation',
        security: [['bearerAuth' => []]],
        tags: ['Admin']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'ID du profil acteur pro',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'reason',
                    type: 'string',
                    example: 'Organisation non éligible au programme',
                    description: 'Raison du rejet (optionnelle, défaut: "Non spécifié")'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Acteur pro rejeté avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string', example: 'pro_xyz789abc'),
                new OA\Property(property: 'status', type: 'string', example: 'REJECTED'),
                new OA\Property(property: 'rejectedAt', type: 'string', format: 'date-time', example: '2026-01-02T15:00:00+01:00'),
                new OA\Property(property: 'rejectionReason', type: 'string', example: 'Organisation non éligible au programme')
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Statut invalide - seuls les comptes PENDING peuvent être rejetés')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé - nécessite le rôle ADMIN')]
    #[OA\Response(response: 404, description: 'Profil acteur pro introuvable')]
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
    #[OA\Get(
        path: '/api/admin/merchants/pending',
        summary: 'Liste tous les commerçants en attente de validation',
        security: [['bearerAuth' => []]],
        tags: ['Admin']
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des commerçants en attente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'merchants',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 12),
                            new OA\Property(property: 'publicId', type: 'string', example: 'merchant_abc123xyz'),
                            new OA\Property(property: 'shopName', type: 'string', example: 'Boulangerie Martin'),
                            new OA\Property(property: 'email', type: 'string', example: 'martin@example.com'),
                            new OA\Property(property: 'siret', type: 'string', example: '12345678901234'),
                            new OA\Property(property: 'address', type: 'string', example: '15 Rue de la Paix'),
                            new OA\Property(property: 'city', type: 'string', example: 'Paris', nullable: true),
                            new OA\Property(property: 'category', type: 'string', example: 'Boulangerie', nullable: true),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-01T10:30:00+01:00')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé - nécessite le rôle ADMIN')]
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
    #[OA\Get(
        path: '/api/admin/pros/pending',
        summary: 'Liste tous les acteurs pros en attente de validation',
        security: [['bearerAuth' => []]],
        tags: ['Admin']
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des acteurs pros en attente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'pros',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 8),
                            new OA\Property(property: 'publicId', type: 'string', example: 'pro_xyz789abc'),
                            new OA\Property(property: 'organizationName', type: 'string', example: 'Ferme Bio du Soleil'),
                            new OA\Property(property: 'organizationType', type: 'string', example: 'FARM'),
                            new OA\Property(property: 'email', type: 'string', example: 'contact@fermesoleil.fr'),
                            new OA\Property(property: 'siret', type: 'string', example: '98765432109876'),
                            new OA\Property(property: 'address', type: 'string', example: 'Route de Campagne'),
                            new OA\Property(property: 'city', type: 'string', example: 'Lyon', nullable: true),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-01T11:15:00+01:00')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé - nécessite le rôle ADMIN')]
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

