<?php

namespace App\State\Deposit;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Deposit;
use App\Entity\StockHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class RefuseDepositProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Deposit
    {
        /** @var Deposit $deposit */
        $deposit = $data;
        $user = $this->security->getUser();

        if (!$user || $deposit->getMerchantUser() !== $user) {
            throw new AccessDeniedHttpException('Vous ne pouvez refuser que vos propres dépôts');
        }

        if ($deposit->getStatus() !== 'PENDING') {
            throw new AccessDeniedHttpException('Seuls les dépôts en attente peuvent être refusés');
        }

        // Annuler la transaction wallet
        $wallet = $deposit->getWalletTransaction()?->getWallet();
        if ($wallet) {
            $wallet->setBalance($wallet->getBalance() - $deposit->getPointsEarned());

            // Marquer la transaction comme annulée
            if ($transaction = $deposit->getWalletTransaction()) {
                $this->entityManager->remove($transaction);
            }
        }

        // Annuler la mise à jour du stock
        $stock = $deposit->getMerchantProfile()->getStock();
        if ($stock) {
            $stock->setCurrentWeightKg($stock->getCurrentWeightKg() - $deposit->getWeightKg());

            // Supprimer l'historique de stock
            $stockHistory = $this->entityManager->getRepository(StockHistory::class)
                ->findOneBy(['relatedDeposit' => $deposit]);
            if ($stockHistory) {
                $this->entityManager->remove($stockHistory);
            }
        }

        $deposit->setStatus('REFUSED');
        $deposit->setRefusedAt(new \DateTime());

        // Récupérer la raison depuis le context
        if (isset($context['refusalReason'])) {
            $deposit->setRefusalReason($context['refusalReason']);
        }

        $this->entityManager->flush();

        return $deposit;
    }
}

