<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Crée un utilisateur de test pour les authentifications',
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe')
            ->addArgument('role', InputArgument::OPTIONAL, 'Rôle (ROLE_CITIZEN, ROLE_MERCHANT, ROLE_PRO, ROLE_ADMIN)', 'ROLE_CITIZEN')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->warning("L'utilisateur avec l'email '{$email}' existe déjà.");

            // Mettre à jour le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($existingUser, $password);
            $existingUser->setPasswordHash($hashedPassword);
            $existingUser->setRole($role);
            $existingUser->setIsActive(true);

            $this->entityManager->flush();

            $io->success("Mot de passe mis à jour pour l'utilisateur '{$email}'");
            return Command::SUCCESS;
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setUuid(Uuid::v4()->toRfc4122());
        $user->setEmail($email);
        $user->setRole($role);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPasswordHash($hashedPassword);

        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("Utilisateur de test créé avec succès !");
        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['ID', $user->getId()],
                ['UUID', $user->getUuid()],
                ['Email', $user->getEmail()],
                ['Rôle', $user->getRole()],
                ['Actif', $user->isActive() ? 'Oui' : 'Non'],
            ]
        );

        return Command::SUCCESS;
    }
}
