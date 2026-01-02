<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:clean-demo-data',
    description: 'Supprime toutes les données de démonstration et recrée le schéma'
)]
class CleanDemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
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

        $io->title('Nettoyage des données de démonstration');

        if (!$input->getOption('force')) {
            $io->warning('Cette commande va supprimer TOUTES les données de la base de données !');

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Êtes-vous sûr de vouloir continuer ? (y/N) ', false);

            if (!$helper->ask($input, $output, $question)) {
                $io->info('Opération annulée.');
                return Command::SUCCESS;
            }
        }

        try {
            $io->section('Suppression du schéma de base de données...');

            // Récupérer la connexion
            $connection = $this->entityManager->getConnection();

            // Désactiver les contraintes de clés étrangères
            $connection->executeStatement('SET session_replication_role = replica;');

            // Récupérer toutes les tables
            $schemaManager = $connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();

            $io->text('Tables trouvées : ' . count($tables));

            // Supprimer toutes les tables
            foreach ($tables as $table) {
                if ($table !== 'doctrine_migration_versions') {
                    $connection->executeStatement('DROP TABLE IF EXISTS "' . $table . '" CASCADE');
                    $io->text('  ✓ Table supprimée : ' . $table);
                }
            }

            // Réactiver les contraintes de clés étrangères
            $connection->executeStatement('SET session_replication_role = DEFAULT;');

            $io->success('Schéma de base de données supprimé avec succès');

            $io->section('Recréation du schéma...');
            $io->note('Veuillez exécuter les commandes suivantes pour recréer le schéma :');
            $io->text([
                '  docker exec -it reup_php php bin/console doctrine:schema:create',
                '  docker exec -it reup_php php bin/console doctrine:migrations:migrate --no-interaction',
                '  docker exec -it reup_php php bin/console app:create-demo-data'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du nettoyage : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

