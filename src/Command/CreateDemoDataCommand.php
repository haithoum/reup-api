<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\CitizenProfile;
use App\Entity\MerchantProfile;
use App\Entity\ProProfile;
use App\Entity\City;
use App\Entity\MerchantCategory;
use App\Entity\Deposit;
use App\Entity\Recovery;
use App\Entity\Stock;
use App\Entity\Coupon;
use App\Entity\RewardRule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-demo-data',
    description: 'Crée des données de démonstration pour le dashboard'
)]
class CreateDemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des données de démonstration');

        try {
            // Vérifier si les données existent déjà
            $existingCity = $this->entityManager->getRepository(City::class)
                ->findOneBy(['name' => 'Casablanca', 'postalCode' => '20000']);

            $existingAdmin = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => 'admin@reup.com']);

            if ($existingCity || $existingAdmin) {
                $io->warning('Des données de démonstration existent déjà.');
                $io->note('Pour recréer les données, supprimez d\'abord les données existantes :');
                $io->text([
                    '  docker exec -it reup_php php bin/console doctrine:schema:drop --force --full-database',
                    '  docker exec -it reup_php php bin/console doctrine:schema:create',
                    '  docker exec -it reup_php php bin/console doctrine:migrations:migrate --no-interaction',
                    '  docker exec -it reup_php php bin/console app:create-demo-data'
                ]);
                return Command::SUCCESS;
            }

            // 1. Créer une ville
            $io->section('Création des villes...');
            $city = $this->createCity('Casablanca', '20000');
            $io->success('Ville créée : Casablanca');

            // 2. Créer une catégorie de commerçant
            $io->section('Création des catégories...');
            $category = $this->createMerchantCategory('Boulangerie');
            $this->entityManager->flush(); // Flush pour générer le slug
            $io->success('Catégorie créée : Boulangerie');

            // 3. Créer des utilisateurs
            $io->section('Création des utilisateurs...');

            // Admin
            $admin = $this->createUser('admin@reup.com', 'admin123', 'ROLE_ADMIN');
            $io->success('Admin créé : admin@reup.com');

            // Citoyens
            $citizen1 = $this->createUser('citizen1@reup.com', 'password', 'ROLE_CITIZEN');
            $this->createCitizenProfile($citizen1, 'Ahmed', 'Bennani', $city);

            $citizen2 = $this->createUser('citizen2@reup.com', 'password', 'ROLE_CITIZEN');
            $this->createCitizenProfile($citizen2, 'Fatima', 'Alaoui', $city);

            $io->success('2 citoyens créés');

            // Commerçants
            $merchant1 = $this->createUser('merchant1@reup.com', 'password', 'ROLE_MERCHANT');
            $merchantProfile1 = $this->createMerchantProfile($merchant1, 'Boulangerie du Coin', $city, $category);

            $merchant2 = $this->createUser('merchant2@reup.com', 'password', 'ROLE_MERCHANT');
            $merchantProfile2 = $this->createMerchantProfile($merchant2, 'Pain Doré', $city, $category);

            $io->success('2 commerçants créés');

            // Acteur Pro
            $pro = $this->createUser('pro@reup.com', 'password', 'ROLE_PRO');
            $this->createProProfile($pro, 'Jean', 'Dupont', 'Transport Pro', $city);

            $io->success('1 acteur pro créé');

            // 4. Créer des stocks
            $io->section('Création des stocks...');
            $this->createStock($merchant1, 15.5);
            $this->createStock($merchant2, 22.3);
            $io->success('Stocks créés');

            // 5. Créer des dépôts
            $io->section('Création des dépôts...');
            $this->createDeposit($citizen1, $merchant1, 5.0, 'ACCEPTED');
            $this->createDeposit($citizen1, $merchant1, 3.5, 'ACCEPTED');
            $this->createDeposit($citizen2, $merchant2, 7.2, 'ACCEPTED');
            $this->createDeposit($citizen2, $merchant1, 4.8, 'ACCEPTED');
            $io->success('4 dépôts créés');

            // 6. Créer des récupérations
            $io->section('Création des récupérations...');
            $this->createRecovery($pro, $merchant1, 10.0);
            $this->createRecovery($pro, $merchant2, 15.0);
            $io->success('2 récupérations créées');

            // 7. Créer des coupons
            $io->section('Création des coupons...');
            $rewardRule = $this->createRewardRule('Coupon 1kg gratuit', 1.0);
            $this->createCoupon($citizen1, $merchant1, $rewardRule, 'ISSUED');
            $this->createCoupon($citizen1, $merchant2, $rewardRule, 'REDEEMED');
            $this->createCoupon($citizen2, $merchant1, $rewardRule, 'ISSUED');
            $io->success('3 coupons créés');

            $this->entityManager->flush();

            $io->success('✅ Toutes les données de démonstration ont été créées avec succès !');
            $io->info('Vous pouvez maintenant accéder au dashboard : http://localhost:8080/admin');
            $io->info('Identifiants admin : admin@reup.com / admin123');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création des données : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createCity(string $name, string $postalCode): City
    {
        $city = new City();
        $city->setName($name);
        $city->setPostalCode($postalCode);
        $this->entityManager->persist($city);
        return $city;
    }

    private function createMerchantCategory(string $name): MerchantCategory
    {
        $category = new MerchantCategory();
        $category->setName($name);
        $category->setDescription('Catégorie ' . $name);
        $this->entityManager->persist($category);
        return $category;
    }

    private function createUser(string $email, string $password, string $role): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRole($role);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPasswordHash($hashedPassword);

        $this->entityManager->persist($user);
        return $user;
    }

    private function createCitizenProfile(User $user, string $firstName, string $lastName, City $city): CitizenProfile
    {
        $profile = new CitizenProfile();
        $profile->setUser($user);
        $profile->setFirstName($firstName);
        $profile->setLastName($lastName);
        $profile->setAddressCity($city);
        $profile->setPhoneNumber('+212600000000');
        $this->entityManager->persist($profile);
        return $profile;
    }

    private function createMerchantProfile(User $user, string $shopName, City $city, MerchantCategory $category): MerchantProfile
    {
        $profile = new MerchantProfile();
        $profile->setUser($user);
        $profile->setShopName($shopName);
        $profile->setFirstName('Propriétaire');
        $profile->setLastName($shopName);
        $profile->setSiret('12345678901234');
        $profile->setAddressStreet('Rue Example');
        $profile->setAddressCity($city);
        $profile->setAddressPostalCode($city->getPostalCode());
        $profile->setLatitude('33.5731104'); // Casablanca
        $profile->setLongitude('-7.5898434'); // Casablanca
        $profile->setCategory($category);
        $profile->setValidationStatus('APPROVED');
        $profile->setPhoneNumber('+212600000001');
        $profile->setMaxStorageCapacityKg('50.00');
        $this->entityManager->persist($profile);
        return $profile;
    }

    private function createProProfile(User $user, string $firstName, string $lastName, string $companyName, City $city): ProProfile
    {
        $profile = new ProProfile();
        $profile->setUser($user);
        $profile->setFirstName($firstName);
        $profile->setLastName($lastName);
        $profile->setCompanyName($companyName);
        $profile->setSiret('98765432109876');
        $profile->setAddressStreet('Rue Industrie');
        $profile->setAddressCity($city);
        $profile->setAddressPostalCode($city->getPostalCode());
        $profile->setPhoneNumber('+212600000002');
        $profile->setVehicleType('VAN');
        $profile->setMaxCapacityKg('500.00');
        $profile->setValidationStatus('APPROVED');
        $this->entityManager->persist($profile);
        return $profile;
    }

    private function createStock(User $merchantUser, float $availableKg): Stock
    {
        $stock = new Stock();
        $stock->setMerchantUser($merchantUser);
        $stock->setAvailableKg((string) $availableKg);
        $stock->setAlertThresholdKg('5.00');
        $this->entityManager->persist($stock);
        return $stock;
    }

    private function createDeposit(User $citizen, User $merchant, float $weightKg, string $status): Deposit
    {
        $deposit = new Deposit();
        $deposit->setCitizenUser($citizen);
        $deposit->setMerchantUser($merchant);
        $deposit->setWeightKg((string) $weightKg);
        $deposit->setStatus($status);
        if ($status === 'ACCEPTED') {
            $deposit->setAcceptedAt(new \DateTime());
        }
        $this->entityManager->persist($deposit);
        return $deposit;
    }

    private function createRecovery(User $pro, User $merchant, float $qtyKg): Recovery
    {
        $recovery = new Recovery();
        $recovery->setProUser($pro);
        $recovery->setMerchantUser($merchant);
        $recovery->setQtyKg((string) $qtyKg);
        $this->entityManager->persist($recovery);
        return $recovery;
    }

    private function createRewardRule(string $name, float $threshold): RewardRule
    {
        $rule = new RewardRule();
        $rule->setName($name);
        $rule->setScope('GLOBAL');
        $rule->setThresholdKg((string) $threshold);
        $rule->setRewardType('FREE_ITEM');
        $rule->setExpiresInDays(30);
        $rule->setIsActive(true);
        $this->entityManager->persist($rule);
        return $rule;
    }

    private function createCoupon(User $citizen, User $merchant, RewardRule $rewardRule, string $status): Coupon
    {
        $coupon = new Coupon();
        $coupon->setCitizenUser($citizen);
        $coupon->setMerchantUser($merchant);
        $coupon->setRewardRule($rewardRule);
        $coupon->setStatus($status);
        $coupon->setExpiresAt(new \DateTime('+30 days'));

        if ($status === 'REDEEMED') {
            $coupon->setRedeemedAt(new \DateTime());
            $coupon->setRedeemedByMerchantUser($merchant);
        }

        $this->entityManager->persist($coupon);
        return $coupon;
    }
}

