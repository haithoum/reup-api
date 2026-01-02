<?php

namespace App\Controller\Api\Admin;

use App\Entity\RewardRule;
use App\Entity\MerchantCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/reward-rules')]
#[IsGranted('ROLE_ADMIN')]
class RewardRuleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_admin_reward_rules_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rules = $this->entityManager->getRepository(RewardRule::class)
            ->findBy([], ['createdAt' => 'DESC']);

        $result = [];
        foreach ($rules as $rule) {
            $result[] = $this->serializeRule($rule);
        }

        return $this->json(['rules' => $result]);
    }

    #[Route('/{id}', name: 'api_admin_reward_rule_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $rule = $this->entityManager->getRepository(RewardRule::class)->find($id);

        if (!$rule) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeRule($rule));
    }

    #[Route('', name: 'api_admin_reward_rule_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $rule = new RewardRule();
        $this->updateRuleFromData($rule, $data);

        $errors = $this->validator->validate($rule);
        if (count($errors) > 0) {
            return $this->json([
                'error' => 'VALIDATION_ERROR',
                'details' => array_map(fn($error) => [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage()
                ], iterator_to_array($errors))
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($rule);
        $this->entityManager->flush();

        return $this->json($this->serializeRule($rule), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_admin_reward_rule_update', methods: ['PATCH', 'PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $rule = $this->entityManager->getRepository(RewardRule::class)->find($id);

        if (!$rule) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $this->updateRuleFromData($rule, $data);

        $errors = $this->validator->validate($rule);
        if (count($errors) > 0) {
            return $this->json([
                'error' => 'VALIDATION_ERROR',
                'details' => array_map(fn($error) => [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage()
                ], iterator_to_array($errors))
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($this->serializeRule($rule));
    }

    #[Route('/{id}', name: 'api_admin_reward_rule_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $rule = $this->entityManager->getRepository(RewardRule::class)->find($id);

        if (!$rule) {
            return $this->json(['error' => 'NOT_FOUND'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si la règle est utilisée par des coupons actifs
        $activeCouponsCount = $this->entityManager->getRepository(\App\Entity\Coupon::class)
            ->count(['rewardRule' => $rule, 'status' => 'ISSUED']);

        if ($activeCouponsCount > 0) {
            return $this->json([
                'error' => 'RULE_IN_USE',
                'message' => "Cette règle est utilisée par {$activeCouponsCount} coupon(s) actif(s)"
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($rule);
        $this->entityManager->flush();

        return $this->json(['message' => 'Règle supprimée avec succès'], Response::HTTP_NO_CONTENT);
    }

    private function updateRuleFromData(RewardRule $rule, array $data): void
    {
        if (isset($data['name'])) {
            $rule->setName($data['name']);
        }

        if (isset($data['description'])) {
            $rule->setDescription($data['description']);
        }

        if (isset($data['type'])) {
            $rule->setType($data['type']);
        }

        if (isset($data['scope'])) {
            $rule->setScope($data['scope']);
        }

        if (isset($data['pointsCost'])) {
            $rule->setPointsCost($data['pointsCost']);
        }

        if (isset($data['pointsPerKg'])) {
            $rule->setPointsPerKg($data['pointsPerKg']);
        }

        if (isset($data['isActive'])) {
            $rule->setIsActive($data['isActive']);
        }

        if (isset($data['validFrom'])) {
            $rule->setValidFrom(new \DateTime($data['validFrom']));
        }

        if (isset($data['validUntil'])) {
            $rule->setValidUntil(new \DateTime($data['validUntil']));
        }

        if (isset($data['categoryId'])) {
            $category = $this->entityManager->getRepository(MerchantCategory::class)
                ->find($data['categoryId']);
            $rule->setMerchantCategory($category);
        }

        if (isset($data['discountPercentage'])) {
            $rule->setDiscountPercentage($data['discountPercentage']);
        }

        if (isset($data['freeItemSku'])) {
            $rule->setFreeItemSku($data['freeItemSku']);
        }

        if (isset($data['maxUsesPerCitizen'])) {
            $rule->setMaxUsesPerCitizen($data['maxUsesPerCitizen']);
        }

        if (isset($data['maxTotalUses'])) {
            $rule->setMaxTotalUses($data['maxTotalUses']);
        }
    }

    private function serializeRule(RewardRule $rule): array
    {
        return [
            'id' => $rule->getId(),
            'uuid' => $rule->getUuid(),
            'name' => $rule->getName(),
            'description' => $rule->getDescription(),
            'type' => $rule->getType(),
            'scope' => $rule->getScope(),
            'pointsCost' => $rule->getPointsCost(),
            'pointsPerKg' => $rule->getPointsPerKg(),
            'isActive' => $rule->isActive(),
            'validFrom' => $rule->getValidFrom()?->format('Y-m-d'),
            'validUntil' => $rule->getValidUntil()?->format('Y-m-d'),
            'category' => $rule->getMerchantCategory() ? [
                'id' => $rule->getMerchantCategory()->getId(),
                'name' => $rule->getMerchantCategory()->getName()
            ] : null,
            'discountPercentage' => $rule->getDiscountPercentage(),
            'freeItemSku' => $rule->getFreeItemSku(),
            'maxUsesPerCitizen' => $rule->getMaxUsesPerCitizen(),
            'maxTotalUses' => $rule->getMaxTotalUses(),
            'currentTotalUses' => $rule->getCurrentTotalUses(),
            'createdAt' => $rule->getCreatedAt()->format('c')
        ];
    }
}

