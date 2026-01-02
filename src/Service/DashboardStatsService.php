<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class DashboardStatsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Récupère les statistiques globales du tableau de bord
     */
    public function getGlobalStats(): array
    {
        $conn = $this->entityManager->getConnection();

        // Total utilisateurs actifs par rôle
        $usersStats = $conn->fetchAllAssociative("
            SELECT 
                role,
                COUNT(*) as count,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active_count
            FROM users
            WHERE deleted_at IS NULL
            GROUP BY role
        ");

        // Statistiques des dépôts
        $depositsStats = $conn->fetchAssociative("
            SELECT 
                COUNT(*) as total_deposits,
                COUNT(CASE WHEN status = 'ACCEPTED' THEN 1 END) as accepted_deposits,
                COALESCE(SUM(CASE WHEN status = 'ACCEPTED' THEN weight_kg END), 0) as total_weight_kg,
                COALESCE(AVG(CASE WHEN status = 'ACCEPTED' THEN weight_kg END), 0) as avg_weight_kg
            FROM deposits
            WHERE deleted_at IS NULL
        ");

        // Statistiques des récupérations
        $recoveriesStats = $conn->fetchAssociative("
            SELECT 
                COUNT(*) as total_recoveries,
                COALESCE(SUM(qty_kg), 0) as total_weight_recovered_kg
            FROM recoveries
            WHERE deleted_at IS NULL
        ");

        // Stock actuel total
        $stockStats = $conn->fetchAssociative("
            SELECT 
                COUNT(*) as merchants_with_stock,
                COALESCE(SUM(available_kg), 0) as total_stock_kg,
                COALESCE(AVG(available_kg), 0) as avg_stock_per_merchant_kg
            FROM stocks
        ");

        // Statistiques des coupons
        $couponsStats = $conn->fetchAssociative("
            SELECT 
                COUNT(*) as total_coupons,
                COUNT(CASE WHEN status = 'AVAILABLE' THEN 1 END) as available_coupons,
                COUNT(CASE WHEN status = 'REDEEMED' THEN 1 END) as redeemed_coupons,
                COUNT(CASE WHEN status = 'EXPIRED' THEN 1 END) as expired_coupons
            FROM coupons
        ");

        // Commerçants en attente de validation
        $pendingMerchants = $conn->fetchOne("
            SELECT COUNT(*) 
            FROM merchant_profiles
            WHERE validation_status = 'PENDING'
        ");

        // Acteurs pro en attente de validation
        $pendingPros = $conn->fetchOne("
            SELECT COUNT(*) 
            FROM pro_profiles
            WHERE validation_status = 'PENDING'
        ");

        return [
            'users' => $this->parseUsersStats($usersStats),
            'deposits' => $depositsStats,
            'recoveries' => $recoveriesStats,
            'stock' => $stockStats,
            'coupons' => $couponsStats,
            'pending_validations' => [
                'merchants' => (int)$pendingMerchants,
                'pros' => (int)$pendingPros,
                'total' => (int)$pendingMerchants + (int)$pendingPros
            ]
        ];
    }

    /**
     * Récupère les statistiques des 30 derniers jours pour les graphiques
     */
    public function getLast30DaysStats(): array
    {
        $conn = $this->entityManager->getConnection();

        // Dépôts par jour (30 derniers jours)
        $depositsPerDay = $conn->fetchAllAssociative("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                COALESCE(SUM(weight_kg), 0) as total_weight_kg
            FROM deposits
            WHERE deleted_at IS NULL
                AND status = 'ACCEPTED'
                AND created_at >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        // Récupérations par jour (30 derniers jours)
        $recoveriesPerDay = $conn->fetchAllAssociative("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                COALESCE(SUM(qty_kg), 0) as total_weight_kg
            FROM recoveries
            WHERE deleted_at IS NULL
                AND created_at >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        // Nouveaux utilisateurs par jour (30 derniers jours)
        $newUsersPerDay = $conn->fetchAllAssociative("
            SELECT 
                DATE(created_at) as date,
                role,
                COUNT(*) as count
            FROM users
            WHERE deleted_at IS NULL
                AND created_at >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY DATE(created_at), role
            ORDER BY date ASC
        ");

        // Coupons utilisés par jour (30 derniers jours)
        $couponsRedeemedPerDay = $conn->fetchAllAssociative("
            SELECT 
                DATE(redeemed_at) as date,
                COUNT(*) as count
            FROM coupons
            WHERE redeemed_at IS NOT NULL
                AND redeemed_at >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY DATE(redeemed_at)
            ORDER BY date ASC
        ");

        return [
            'deposits' => $depositsPerDay,
            'recoveries' => $recoveriesPerDay,
            'new_users' => $this->groupNewUsersByDate($newUsersPerDay),
            'coupons_redeemed' => $couponsRedeemedPerDay
        ];
    }

    /**
     * Top 10 commerçants par poids collecté
     */
    public function getTopMerchantsByWeight(int $limit = 10): array
    {
        $conn = $this->entityManager->getConnection();

        return $conn->fetchAllAssociative("
            SELECT 
                mp.shop_name,
                u.email,
                COALESCE(SUM(d.weight_kg), 0) as total_collected_kg,
                COUNT(d.id) as total_deposits
            FROM merchant_profiles mp
            INNER JOIN users u ON mp.user_id = u.id
            LEFT JOIN deposits d ON d.merchant_user_id = u.id AND d.status = 'ACCEPTED' AND d.deleted_at IS NULL
            WHERE u.deleted_at IS NULL
                AND mp.validation_status = 'APPROVED'
            GROUP BY mp.id, mp.shop_name, u.email
            ORDER BY total_collected_kg DESC
            LIMIT :limit
        ", ['limit' => $limit]);
    }

    /**
     * Top 10 citoyens par poids déposé
     */
    public function getTopCitizensByWeight(int $limit = 10): array
    {
        $conn = $this->entityManager->getConnection();

        return $conn->fetchAllAssociative("
            SELECT 
                cp.first_name,
                cp.last_name,
                u.email,
                COALESCE(SUM(d.weight_kg), 0) as total_deposited_kg,
                COUNT(d.id) as total_deposits
            FROM citizen_profiles cp
            INNER JOIN users u ON cp.user_id = u.id
            LEFT JOIN deposits d ON d.citizen_user_id = u.id AND d.status = 'ACCEPTED' AND d.deleted_at IS NULL
            WHERE u.deleted_at IS NULL
            GROUP BY cp.id, cp.first_name, cp.last_name, u.email
            ORDER BY total_deposited_kg DESC
            LIMIT :limit
        ", ['limit' => $limit]);
    }

    /**
     * Répartition des commerçants par catégorie
     */
    public function getMerchantsByCategory(): array
    {
        $conn = $this->entityManager->getConnection();

        return $conn->fetchAllAssociative("
            SELECT 
                mc.name as category_name,
                COUNT(mp.id) as merchant_count
            FROM merchant_categories mc
            LEFT JOIN merchant_profiles mp ON mp.category_id = mc.id AND mp.validation_status = 'APPROVED'
            GROUP BY mc.id, mc.name
            ORDER BY merchant_count DESC
        ");
    }

    /**
     * Statistiques de valorisation (impact environnemental)
     */
    public function getEnvironmentalImpact(): array
    {
        $conn = $this->entityManager->getConnection();

        $totalWeight = $conn->fetchOne("
            SELECT COALESCE(SUM(weight_kg), 0)
            FROM deposits
            WHERE status = 'ACCEPTED' AND deleted_at IS NULL
        ");

        // Estimations d'impact (à ajuster selon les données réelles)
        $co2Saved = $totalWeight * 2.5; // kg CO2 économisés (exemple: 2.5kg CO2 par kg pain valorisé)
        $mealsEquivalent = floor($totalWeight / 0.5); // 1 repas = ~0.5kg pain

        return [
            'total_weight_kg' => round($totalWeight, 2),
            'co2_saved_kg' => round($co2Saved, 2),
            'meals_equivalent' => $mealsEquivalent,
            'active_citizens' => $conn->fetchOne("
                SELECT COUNT(DISTINCT citizen_user_id)
                FROM deposits
                WHERE status = 'ACCEPTED' AND deleted_at IS NULL
            "),
            'active_merchants' => $conn->fetchOne("
                SELECT COUNT(DISTINCT merchant_user_id)
                FROM deposits
                WHERE status = 'ACCEPTED' AND deleted_at IS NULL
            ")
        ];
    }

    /**
     * Parse les statistiques utilisateurs par rôle
     */
    private function parseUsersStats(array $usersStats): array
    {
        $parsed = [
            'total' => 0,
            'active' => 0,
            'by_role' => []
        ];

        foreach ($usersStats as $stat) {
            $parsed['total'] += $stat['count'];
            $parsed['active'] += $stat['active_count'];
            $parsed['by_role'][$stat['role']] = [
                'total' => $stat['count'],
                'active' => $stat['active_count']
            ];
        }

        return $parsed;
    }

    /**
     * Groupe les nouveaux utilisateurs par date et rôle
     */
    private function groupNewUsersByDate(array $newUsersPerDay): array
    {
        $grouped = [];

        foreach ($newUsersPerDay as $stat) {
            $date = $stat['date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'total' => 0,
                    'by_role' => []
                ];
            }
            $grouped[$date]['total'] += $stat['count'];
            $grouped[$date]['by_role'][$stat['role']] = $stat['count'];
        }

        return array_values($grouped);
    }
}

