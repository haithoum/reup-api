<?php

namespace App\Controller\Api;

use App\Entity\CitizenProfile;
use App\Entity\Deposit;
use App\Entity\MerchantProfile;
use App\Entity\Stock;
use App\Entity\StockHistory;
use App\Entity\CitizenMerchantWallet;
use App\Entity\WalletTransaction;
use App\Repository\CitizenProfileRepository;
use App\Repository\RewardRuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/deposits')]
#[IsGranted('ROLE_MERCHANT')]
class DepositController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private CitizenProfileRepository $citizenProfileRepository,
        private RewardRuleRepository $rewardRuleRepository
    ) {
    }

    #[Route('', name: 'api_deposit_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $citizenQrCode = $data['citizenQrCode'] ?? null;
        $weightKg = $data['weightKg'] ?? null;

        if (!$citizenQrCode || !$weightKg) {
            return $this->json([
                'error' => 'VALIDATION_ERROR',
                'message' => 'citizenQrCode et weightKg sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($weightKg < 0.1 || $weightKg > 50) {
            return $this->json([
                'error' => 'INVALID_WEIGHT',
                'message' => 'Le poids doit être entre 0.1 et 50 kg'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $merchantProfile = $user->getMerchantProfile();

        if (!$merchantProfile || $merchantProfile->getStatus() !== 'APPROVED') {
            return $this->json([
                'error' => 'ACCOUNT_NOT_APPROVED',
                'message' => 'Votre compte commerçant doit être approuvé'
            ], Response::HTTP_FORBIDDEN);
        }

        // Trouver le citoyen par QR code
        $citizenProfile = $this->citizenProfileRepository->findOneBy(['publicId' => $citizenQrCode]);
        if (!$citizenProfile || !$citizenProfile->getUser()->isActive()) {
            return $this->json([
                'error' => 'INVALID_QR_CODE',
                'message' => 'QR code citoyen invalide ou compte inactif'
            ], Response::HTTP_NOT_FOUND);
        }

        // Créer le dépôt
        $deposit = new Deposit();
        $deposit->setCitizenUser($citizenProfile->getUser());
        $deposit->setMerchantUser($user);
        $deposit->setWeightKg((string) $weightKg);
        $deposit->setStatus('PENDING');

        // Calculer les points
        $points = $this->calculatePoints($merchantProfile, $weightKg);

        // Créer/Mettre à jour le wallet
        $wallet = $this->getOrCreateWallet($citizenProfile, $merchantProfile);
        $wallet->setBalance($wallet->getBalance() + $points);

        // Créer la transaction wallet
        $transaction = new WalletTransaction();
        $transaction->setWallet($wallet);
        $transaction->setType('EARNED');
        $transaction->setAmount($points);
        $transaction->setDescription("Dépôt de {$weightKg} kg de pain sec");
        $transaction->setSourceDeposit($deposit);

        // Mettre à jour le stock
        $this->updateStock($merchantProfile, $weightKg, $deposit);

        $this->entityManager->persist($deposit);
        $this->entityManager->persist($wallet);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $this->json([
            'id' => $deposit->getUuid(),
            'citizen' => [
                'id' => $citizenProfile->getPublicId(),
                'firstName' => $citizenProfile->getFirstName(),
                'lastName' => $citizenProfile->getLastName()
            ],
            'weightKg' => (float) $deposit->getWeightKg(),
            'status' => $deposit->getStatus(),
            'pointsEarned' => $points,
            'createdAt' => $deposit->getCreatedAt()->format('c')
        ], Response::HTTP_CREATED);
    }

    #[Route('/{uuid}/validate', name: 'api_deposit_validate', methods: ['PATCH'])]
    public function validate(string $uuid): JsonResponse
    {
        $deposit = $this->entityManager->getRepository(Deposit::class)->findOneBy(['uuid' => $uuid]);

        if (!$deposit) {
            return $this->json(['error' => 'NOT_FOUND', 'message' => 'Dépôt introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($deposit->getMerchantUser() !== $this->getUser()) {
            return $this->json([
                'error' => 'FORBIDDEN',
                'message' => 'Vous ne pouvez valider que vos propres dépôts'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($deposit->getStatus() !== 'PENDING') {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Seuls les dépôts en attente peuvent être validés'
            ], Response::HTTP_BAD_REQUEST);
        }

        $deposit->setStatus('VALIDATED');
        $deposit->setAcceptedAt(new \DateTime());

        $this->entityManager->flush();

        return $this->json([
            'id' => $deposit->getUuid(),
            'status' => $deposit->getStatus(),
            'validatedAt' => $deposit->getAcceptedAt()->format('c')
        ]);
    }

    #[Route('/{uuid}/refuse', name: 'api_deposit_refuse', methods: ['PATCH'])]
    public function refuse(string $uuid, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Non spécifié';

        $deposit = $this->entityManager->getRepository(Deposit::class)->findOneBy(['uuid' => $uuid]);

        if (!$deposit) {
            return $this->json(['error' => 'NOT_FOUND', 'message' => 'Dépôt introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($deposit->getMerchantUser() !== $this->getUser()) {
            return $this->json([
                'error' => 'FORBIDDEN',
                'message' => 'Vous ne pouvez refuser que vos propres dépôts'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($deposit->getStatus() !== 'PENDING') {
            return $this->json([
                'error' => 'INVALID_STATUS',
                'message' => 'Seuls les dépôts en attente peuvent être refusés'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Annuler la transaction wallet
        $transaction = $this->entityManager->getRepository(WalletTransaction::class)
            ->findOneBy(['sourceDeposit' => $deposit]);

        if ($transaction) {
            $wallet = $transaction->getWallet();
            $wallet->setBalance($wallet->getBalance() - $transaction->getAmount());
            $this->entityManager->remove($transaction);
        }

        // Annuler le stock
        $merchantProfile = $deposit->getMerchantUser()->getMerchantProfile();
        $stock = $merchantProfile->getStock();
        if ($stock) {
            $stock->setCurrentWeightKg($stock->getCurrentWeightKg() - (float) $deposit->getWeightKg());
        }

        $deposit->setStatus('REFUSED');
        $deposit->setRefusedAt(new \DateTime());
        $deposit->setRefusedReason($reason);

        $this->entityManager->flush();

        return $this->json([
            'id' => $deposit->getUuid(),
            'status' => $deposit->getStatus(),
            'refusedAt' => $deposit->getRefusedAt()->format('c'),
            'refusalReason' => $deposit->getRefusedReason()
        ]);
    }

    private function calculatePoints(MerchantProfile $merchant, float $weightKg): int
    {
        // TODO: Implémenter la logique selon les règles de récompense
        return (int) ($weightKg * 20); // 20 points par kg par défaut
    }

    private function getOrCreateWallet(CitizenProfile $citizen, MerchantProfile $merchant): CitizenMerchantWallet
    {
        $wallet = $this->entityManager->getRepository(CitizenMerchantWallet::class)
            ->findOneBy([
                'citizenUser' => $citizen->getUser(),
                'merchantUser' => $merchant->getUser()
            ]);

        if (!$wallet) {
            $wallet = new CitizenMerchantWallet();
            $wallet->setCitizenUser($citizen->getUser());
            $wallet->setMerchantUser($merchant->getUser());
            $wallet->setBalance(0);
        }

        return $wallet;
    }

    private function updateStock(MerchantProfile $merchant, float $weightKg, Deposit $deposit): void
    {
        $stock = $merchant->getStock();
        if (!$stock) {
            $stock = new Stock();
            $stock->setMerchantProfile($merchant);
            $stock->setCurrentWeightKg(0);
            $this->entityManager->persist($stock);
        }

        $oldWeight = $stock->getCurrentWeightKg();
        $newWeight = $oldWeight + $weightKg;
        $stock->setCurrentWeightKg($newWeight);

        $history = new StockHistory();
        $history->setStock($stock);
        $history->setType('DEPOSIT_IN');
        $history->setWeightKg($weightKg);
        $history->setNewStockKg($newWeight);
        $history->setSourceDeposit($deposit);

        $this->entityManager->persist($history);
    }
}

