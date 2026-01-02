<?php

namespace App\Controller\Api;

use App\Entity\Coupon;
use App\Entity\WalletTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/coupons')]
#[IsGranted('ROLE_MERCHANT')]
class CouponController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/redeem', name: 'api_coupon_redeem', methods: ['POST'])]
    public function redeem(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $couponQrCode = $data['couponQrCode'] ?? null;

        if (!$couponQrCode) {
            return $this->json([
                'error' => 'VALIDATION_ERROR',
                'message' => 'couponQrCode est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $coupon = $this->entityManager->getRepository(Coupon::class)
            ->findOneBy(['qrCodeValue' => $couponQrCode]);

        if (!$coupon) {
            return $this->json([
                'error' => 'NOT_FOUND',
                'message' => 'Coupon introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le coupon est ACTIVE
        if ($coupon->getStatus() !== 'ACTIVE') {
            return $this->json([
                'error' => 'COUPON_ALREADY_USED',
                'message' => 'Ce coupon a déjà été utilisé'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier expiration
        if ($coupon->getExpiresAt() && $coupon->getExpiresAt() < new \DateTime()) {
            return $this->json([
                'error' => 'COUPON_EXPIRED',
                'message' => 'Ce coupon a expiré'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que le coupon appartient au bon commerçant
        $user = $this->getUser();
        $merchantProfile = $user->getMerchantProfile();

        if ($coupon->getMerchantUser() !== $user) {
            return $this->json([
                'error' => 'FORBIDDEN',
                'message' => 'Ce coupon ne peut être utilisé que chez ' . $coupon->getMerchantUser()->getMerchantProfile()->getStoreName()
            ], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que le citoyen a assez de points
        $wallet = $this->entityManager->getRepository(\App\Entity\CitizenMerchantWallet::class)
            ->findOneBy([
                'citizenUser' => $coupon->getCitizenUser(),
                'merchantUser' => $user
            ]);

        $pointsCost = $coupon->getRewardRule()->getPointsCost();

        if (!$wallet || $wallet->getBalance() < $pointsCost) {
            return $this->json([
                'error' => 'INSUFFICIENT_POINTS',
                'message' => 'Points insuffisants pour utiliser ce coupon'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Débiter les points
        $wallet->setBalance($wallet->getBalance() - $pointsCost);

        // Créer la transaction
        $transaction = new WalletTransaction();
        $transaction->setWallet($wallet);
        $transaction->setType('SPENT');
        $transaction->setAmount(-$pointsCost);
        $transaction->setDescription('Utilisation coupon : ' . $coupon->getRewardRule()->getName());
        $transaction->setUsedForCoupon($coupon);

        // Marquer le coupon comme utilisé
        $coupon->setStatus('USED');
        $coupon->setUsedAt(new \DateTime());

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $citizenProfile = $coupon->getCitizenUser()->getCitizenProfile();

        return $this->json([
            'id' => $coupon->getUuid(),
            'rewardRule' => [
                'name' => $coupon->getRewardRule()->getName(),
                'type' => $coupon->getRewardRule()->getType()
            ],
            'citizen' => [
                'firstName' => $citizenProfile->getFirstName(),
                'lastName' => $citizenProfile->getLastName()
            ],
            'pointsDeducted' => $pointsCost,
            'status' => $coupon->getStatus(),
            'usedAt' => $coupon->getUsedAt()->format('c')
        ]);
    }
}

