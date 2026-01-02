<?php

namespace App\State\Deposit;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Deposit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ValidateDepositProcessor implements ProcessorInterface
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
            throw new AccessDeniedHttpException('Vous ne pouvez valider que vos propres dépôts');
        }

        if ($deposit->getStatus() !== 'PENDING') {
            throw new AccessDeniedHttpException('Seuls les dépôts en attente peuvent être validés');
        }

        $deposit->setStatus('VALIDATED');
        $deposit->setValidatedAt(new \DateTime());

        $this->entityManager->flush();

        return $deposit;
    }
}

