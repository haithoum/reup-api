<?php

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use App\Entity\CitizenProfile;
use App\Entity\City;
use App\Entity\Coupon;
use App\Entity\Deposit;
use App\Entity\MerchantCategory;
use App\Entity\MerchantProfile;
use App\Entity\Notification;
use App\Entity\ProProfile;
use App\Entity\Recovery;
use App\Entity\RewardRule;
use App\Entity\Stock;
use App\Entity\User;
use App\Service\DashboardStatsService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private DashboardStatsService $statsService
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // IMPORTANT : Ne pas utiliser le système EasyAdmin standard
        // On force le rendu direct de notre template personnalisé

        try {
            // Récupération de toutes les statistiques
            $globalStats = $this->statsService->getGlobalStats();
            $last30DaysStats = $this->statsService->getLast30DaysStats();
            $topMerchants = $this->statsService->getTopMerchantsByWeight(10);
            $topCitizens = $this->statsService->getTopCitizensByWeight(10);
            $merchantsByCategory = $this->statsService->getMerchantsByCategory();
            $environmentalImpact = $this->statsService->getEnvironmentalImpact();

            return $this->render('admin/dashboard_new.html.twig', [
                'globalStats' => $globalStats,
                'last30DaysStats' => $last30DaysStats,
                'topMerchants' => $topMerchants,
                'topCitizens' => $topCitizens,
                'merchantsByCategory' => $merchantsByCategory,
                'environmentalImpact' => $environmentalImpact,
            ]);
        } catch (\Exception $e) {
            // En cas d'erreur, afficher un message simple
            return new Response(
                '<h1>❌ Erreur Dashboard</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('ReUp Admin')
            ->setFaviconPath('favicon.ico')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Profils Citoyens', 'fa fa-user', CitizenProfile::class);
        yield MenuItem::linkToCrud('Profils Commerçants', 'fa fa-store', MerchantProfile::class);
        yield MenuItem::linkToCrud('Profils Acteurs Pro', 'fa fa-truck', ProProfile::class);

        yield MenuItem::section('Transactions');
        yield MenuItem::linkToCrud('Dépôts', 'fa fa-arrow-down', Deposit::class);
        yield MenuItem::linkToCrud('Récupérations', 'fa fa-arrow-up', Recovery::class);
        yield MenuItem::linkToCrud('Stocks', 'fa fa-boxes', Stock::class);

        yield MenuItem::section('Récompenses');
        yield MenuItem::linkToCrud('Coupons', 'fa fa-ticket', Coupon::class);
        yield MenuItem::linkToCrud('Règles de récompense', 'fa fa-gift', RewardRule::class);

        yield MenuItem::section('Paramétrage');
        yield MenuItem::linkToCrud('Villes', 'fa fa-map-marker-alt', City::class);
        yield MenuItem::linkToCrud('Catégories Commerçants', 'fa fa-tags', MerchantCategory::class);

        yield MenuItem::section('Système');
        yield MenuItem::linkToCrud('Notifications', 'fa fa-bell', Notification::class);
        yield MenuItem::linkToCrud('Logs d\'audit', 'fa fa-history', AuditLog::class);

        yield MenuItem::section('Compte');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out-alt');
    }
}

