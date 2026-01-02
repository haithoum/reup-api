<?php

namespace App\State\Deposit;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Deposit\CreateDepositInput;
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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CreateDepositProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CitizenProfileRepository $citizenProfileRepository,
        private RewardRuleRepository $rewardRuleRepository
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Deposit
    {
        /** @var CreateDepositInput $data */
        $user = $this->security->getUser();

        if (!$user || !in_array('ROLE_MERCHANT', $user->getRoles())) {
            throw new AccessDeniedHttpException('Accès réservé aux commerçants');
        }

        $merchantProfile = $user->getMerchantProfile();
        if (!$merchantProfile || $merchantProfile->getStatus() !== 'APPROVED') {
            throw new AccessDeniedHttpException('Votre compte commerçant doit être approuvé');
        }

        // Trouver le citoyen par QR code
        $citizenProfile = $this->citizenProfileRepository->findOneBy(['publicId' => $data->citizenQrCode]);
        if (!$citizenProfile || !$citizenProfile->getUser()->isActive()) {
            throw new NotFoundHttpException('QR code citoyen invalide ou compte inactif');
        }

        // Créer le dépôt
        $deposit = new Deposit();
        $deposit->setCitizenProfile($citizenProfile);
        $deposit->setCitizenUser($citizenProfile->getUser());
        $deposit->setMerchantProfile($merchantProfile);
        $deposit->setMerchantUser($user);
        $deposit->setWeightKg($data->weightKg);
        $deposit->setStatus('PENDING');

        // Calculer les points selon les règles actives
        $points = $this->calculatePoints($merchantProfile, $data->weightKg);
        $deposit->setPointsEarned($points);

        // Créer/Mettre à jour le wallet citoyen-commerçant
        $wallet = $this->getOrCreateWallet($citizenProfile, $merchantProfile);
        $wallet->setBalance($wallet->getBalance() + $points);

        // Créer la transaction wallet
        $transaction = new WalletTransaction();
        $transaction->setWallet($wallet);
        $transaction->setType('EARNED');
        $transaction->setAmount($points);
        $transaction->setDescription("Dépôt de {$data->weightKg} kg de pain sec");
        $transaction->setRelatedDeposit($deposit);

        // Mettre à jour le stock
        $this->updateStock($merchantProfile, $data->weightKg, $deposit);

        $this->entityManager->persist($deposit);
        $this->entityManager->persist($wallet);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $deposit;
    }

    private function calculatePoints(MerchantProfile $merchant, float $weightKg): int
    {
        // Récupérer la règle de récompense active pour ce commerçant
        $rewardRule = $this->rewardRuleRepository->findActiveRuleForMerchant($merchant);

        if ($rewardRule) {
            return (int) ($weightKg * $rewardRule->getPointsPerKg());
        }

        // Points par défaut : 20 points par kg
        return (int) ($weightKg * 20);
    }

    private function getOrCreateWallet(CitizenProfile $citizen, MerchantProfile $merchant): CitizenMerchantWallet
    {
        $wallet = $this->entityManager->getRepository(CitizenMerchantWallet::class)
            ->findOneBy([
                'citizenProfile' => $citizen,
                'merchantProfile' => $merchant
            ]);

        if (!$wallet) {
            $wallet = new CitizenMerchantWallet();
            $wallet->setCitizenProfile($citizen);
            $wallet->setCitizenUser($citizen->getUser());
            $wallet->setMerchantProfile($merchant);
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

        // Historique
        $history = new StockHistory();
        $history->setStock($stock);
        $history->setType('DEPOSIT_IN');
        $history->setWeightKg($weightKg);
        $history->setNewStockKg($newWeight);
        $history->setRelatedDeposit($deposit);

        $this->entityManager->persist($history);
    }
}

