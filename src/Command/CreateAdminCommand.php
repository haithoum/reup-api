<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
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
            ->addArgument('email', InputArgument::OPTIONAL, 'Email de l\'administrateur')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer l'email
        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('Email de l\'administrateur: ');
            $email = $io->askQuestion($question);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->warning('Un utilisateur avec cet email existe déjà.');
            $makeAdmin = $io->confirm('Voulez-vous lui donner les droits admin ?', true);

            if ($makeAdmin) {
                $existingUser->setRole('ROLE_ADMIN');
                $this->entityManager->flush();
                $io->success(sprintf('L\'utilisateur %s a maintenant les droits admin.', $email));
                return Command::SUCCESS;
            }

            return Command::FAILURE;
        }

        // Récupérer le mot de passe
        $password = $input->getArgument('password');
        if (!$password) {
            $question = new Question('Mot de passe: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $io->askQuestion($question);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setRole('ROLE_ADMIN');
        $user->setIsActive(true);
        $user->setCreatedAt(new \DateTimeImmutable());

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Persister
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Administrateur %s créé avec succès !', $email));
        $io->note('Vous pouvez maintenant vous connecter sur http://localhost:8080/admin/login');

        return Command::SUCCESS;
    }
}

