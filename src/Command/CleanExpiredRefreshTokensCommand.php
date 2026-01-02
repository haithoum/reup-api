<?php

namespace App\Command;

use App\Service\RefreshTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:refresh-tokens:clean',
    description: 'Nettoie les refresh tokens expirés de la base de données',
)]
class CleanExpiredRefreshTokensCommand extends Command
{
    public function __construct(
        private RefreshTokenService $refreshTokenService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des refresh tokens expirés');

        try {
            $count = $this->refreshTokenService->cleanExpiredTokens();

            if ($count > 0) {
                $io->success(sprintf('%d refresh token(s) expiré(s) supprimé(s)', $count));
            } else {
                $io->info('Aucun refresh token expiré à supprimer');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du nettoyage : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

