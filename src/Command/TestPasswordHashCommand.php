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

#[AsCommand(
    name: 'app:test-password-hash',
    description: 'Teste le hashage des mots de passe et vérifie les données en base',
)]
class TestPasswordHashCommand extends Command
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
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe à tester', 'testpassword123')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email de l\'utilisateur à vérifier en base', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $testPassword = $input->getArgument('password');
        $testEmail = $input->getArgument('email');

        $io->title('🔐 Test du hashage des mots de passe');

        // 1. Test du hashage avec un utilisateur temporaire
        $io->section('1. Test du hashage avec UserPasswordHasher');

        $tempUser = new User();
        $tempUser->setEmail('temp@test.com');
        $tempUser->setRole('ROLE_CITIZEN');

        $hashedPassword = $this->passwordHasher->hashPassword($tempUser, $testPassword);

        $io->table(
            ['Propriété', 'Valeur'],
            [
                ['Mot de passe en clair', $testPassword],
                ['Mot de passe hashé', $hashedPassword],
                ['Longueur du hash', strlen($hashedPassword)],
                ['Algorithme détecté', $this->detectHashAlgorithm($hashedPassword)],
                ['Commence par $argon2id$', str_starts_with($hashedPassword, '$argon2id$') ? '✅ Oui' : '❌ Non'],
            ]
        );

        // 2. Test de vérification du mot de passe
        $io->section('2. Test de vérification du mot de passe');

        $isValid = $this->passwordHasher->isPasswordValid($tempUser->setPassword($hashedPassword), $testPassword);
        $isInvalidWrong = $this->passwordHasher->isPasswordValid($tempUser, 'wrongpassword');

        $io->table(
            ['Test', 'Résultat'],
            [
                ['Vérification avec bon mot de passe', $isValid ? '✅ Valide' : '❌ Invalide'],
                ['Vérification avec mauvais mot de passe', $isInvalidWrong ? '❌ Accepté (PROBLÈME!)' : '✅ Rejeté'],
            ]
        );

        // 3. Vérification en base de données si un email est fourni
        if ($testEmail) {
            $io->section('3. Vérification des données en base');

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $testEmail]);

            if ($user) {
                $passwordHash = $user->getPassword();

                $io->table(
                    ['Propriété', 'Valeur'],
                    [
                        ['Email', $user->getEmail()],
                        ['Rôle', $user->getRoles()[0] ?? 'Aucun'],
                        ['Hash en base', substr($passwordHash, 0, 50) . '...'],
                        ['Longueur du hash', strlen($passwordHash ?? '')],
                        ['Type de hash', $this->detectHashAlgorithm($passwordHash)],
                        ['Format Argon2id', str_starts_with($passwordHash ?? '', '$argon2id$') ? '✅ Oui' : '❌ Non'],
                    ]
                );

                // Test de vérification avec le mot de passe fourni
                if ($passwordHash) {
                    $isValidInDb = $this->passwordHasher->isPasswordValid($user, $testPassword);
                    $io->info('Test de vérification du mot de passe "' . $testPassword . '" : ' .
                        ($isValidInDb ? '✅ VALIDE' : '❌ INVALIDE'));
                } else {
                    $io->warning('⚠️  Aucun mot de passe en base (utilisateur OAuth probablement)');
                }
            } else {
                $io->warning("Aucun utilisateur trouvé avec l'email : {$testEmail}");
            }
        }

        // 4. Vérification de tous les utilisateurs en base
        $io->section('4. Audit de tous les utilisateurs en base');

        $users = $this->entityManager->getRepository(User::class)->findAll();
        $stats = [
            'total' => count($users),
            'with_password' => 0,
            'with_argon2id' => 0,
            'with_oauth_only' => 0,
            'with_weak_hash' => 0
        ];

        $usersSample = [];

        foreach ($users as $user) {
            $passwordHash = $user->getPassword();

            if ($passwordHash) {
                $stats['with_password']++;

                if (str_starts_with($passwordHash, '$argon2id$')) {
                    $stats['with_argon2id']++;
                } else {
                    $stats['with_weak_hash']++;
                }
            } else {
                $stats['with_oauth_only']++;
            }

            // Échantillon pour affichage
            if (count($usersSample) < 5) {
                $usersSample[] = [
                    'Email' => $user->getEmail(),
                    'Rôle' => $user->getRole(),
                    'A un mot de passe' => $passwordHash ? '✅' : '❌',
                    'Type de hash' => $this->detectHashAlgorithm($passwordHash),
                    'Créé le' => $user->getCreatedAt()?->format('Y-m-d H:i:s') ?? 'N/A'
                ];
            }
        }

        $io->table(
            ['Statistique', 'Valeur'],
            [
                ['Total utilisateurs', $stats['total']],
                ['Avec mot de passe', $stats['with_password']],
                ['Avec Argon2id (sécurisé)', $stats['with_argon2id']],
                ['OAuth uniquement (pas de mdp)', $stats['with_oauth_only']],
                ['Avec hash faible', $stats['with_weak_hash']],
            ]
        );

        if ($stats['with_weak_hash'] > 0) {
            $io->error('⚠️  ATTENTION: ' . $stats['with_weak_hash'] . ' utilisateur(s) avec un hash non-Argon2id détecté(s) !');
        } else {
            $io->success('✅ Tous les mots de passe utilisent Argon2id (sécurisé)');
        }

        if (!empty($usersSample)) {
            $io->table(
                ['Email', 'Rôle', 'A un mot de passe', 'Type de hash', 'Créé le'],
                array_map(fn($u) => array_values($u), $usersSample)
            );
        }

        return Command::SUCCESS;
    }

    private function detectHashAlgorithm(?string $hash): string
    {
        if (!$hash) {
            return 'Aucun';
        }

        if (str_starts_with($hash, '$argon2id$')) {
            return '🔒 Argon2id (Recommandé)';
        } elseif (str_starts_with($hash, '$argon2i$')) {
            return '🔒 Argon2i';
        } elseif (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$')) {
            return '⚠️  Bcrypt (Acceptable)';
        } elseif (str_starts_with($hash, '$1$')) {
            return '❌ MD5 (Non sécurisé)';
        } elseif (str_starts_with($hash, '$5$')) {
            return '⚠️  SHA-256 (Acceptable)';
        } elseif (str_starts_with($hash, '$6$')) {
            return '⚠️  SHA-512 (Acceptable)';
        } else {
            return '❓ Inconnu (' . substr($hash, 0, 10) . '...)';
        }
    }
}
