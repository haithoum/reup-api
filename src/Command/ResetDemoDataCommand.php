<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reset-demo-data',
    description: 'Réinitialise complètement la base de données et recrée les données de démonstration'
)]
class ResetDemoDataCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Ne pas demander de confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔄 Réinitialisation complète de la base de données');

        if (!$input->getOption('force')) {
            $io->warning('Cette commande va :');
            $io->listing([
                'Supprimer TOUTES les données de la base',
                'Recréer le schéma via les migrations',
                'Créer de nouvelles données de démonstration'
            ]);

            if (!$io->confirm('Êtes-vous sûr de vouloir continuer ?', false)) {
                $io->info('Opération annulée.');
                return Command::SUCCESS;
            }
        }

        try {
            // 1. Nettoyer complètement le schéma PostgreSQL
            $io->section('🧹 Étape 1/3 : Nettoyage complet de la base de données');
            $this->connection->executeStatement('DROP SCHEMA public CASCADE');
            $this->connection->executeStatement('CREATE SCHEMA public');
            $this->connection->executeStatement('GRANT ALL ON SCHEMA public TO reup_user');
            $this->connection->executeStatement('GRANT ALL ON SCHEMA public TO public');
            $io->success('Base de données nettoyée');

            // 2. Appliquer les migrations
            $io->section('📝 Étape 2/3 : Application des migrations');
            $migrateCommand = $this->getApplication()->find('doctrine:migrations:migrate');
            $migrateInput = new ArrayInput([
                '--no-interaction' => true,
            ]);
            $returnCode = $migrateCommand->run($migrateInput, $output);

            if ($returnCode !== Command::SUCCESS) {
                throw new \Exception('Échec de l\'application des migrations');
            }

            $io->success('Migrations appliquées avec succès');

            // 3. Créer les données de démonstration
            $io->section('🎭 Étape 3/3 : Création des données de démonstration');
            $demoCommand = $this->getApplication()->find('app:create-demo-data');
            $demoInput = new ArrayInput([]);
            $returnCode = $demoCommand->run($demoInput, $output);

            if ($returnCode !== Command::SUCCESS) {
                throw new \Exception('Échec de la création des données de démonstration');
            }

            $io->newLine(2);
            $io->success('✅ Réinitialisation terminée avec succès !');
            $io->info('🌐 Accès au dashboard : http://localhost:8080/admin');
            $io->info('🔐 Identifiants admin : admin@reup.com / admin123');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la réinitialisation : ' . $e->getMessage());
            $io->note('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

