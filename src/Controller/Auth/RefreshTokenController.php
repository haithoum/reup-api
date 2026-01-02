<?php

namespace App\Controller\Auth;

use App\Service\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class RefreshTokenController extends AbstractController
{
    public function __construct(
        private RefreshTokenService $refreshTokenService,
        private JWTTokenManagerInterface $jwtManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Endpoint pour rafraîchir un access token
     *
     * @Route("/refresh", name="api_refresh_token", methods={"POST"})
     */
    #[Route('/refresh', name: 'api_refresh_token', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            $this->logger->warning('Refresh token missing in request', [
                'ip' => $request->getClientIp()
            ]);

            return $this->json([
                'error' => 'MISSING_REFRESH_TOKEN',
                'message' => 'Le refresh token est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le refresh token
        $user = $this->refreshTokenService->validateRefreshToken($refreshToken);

        if (!$user) {
            $this->logger->warning('Invalid or expired refresh token', [
                'ip' => $request->getClientIp()
            ]);

            return $this->json([
                'error' => 'INVALID_REFRESH_TOKEN',
                'message' => 'Refresh token invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Prolonger la durée de vie du refresh token (optionnel)
        $this->refreshTokenService->extendRefreshToken($refreshToken, $request);

        // Générer un nouveau access token
        $newAccessToken = $this->jwtManager->create($user);

        $this->logger->info('Access token refreshed successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ]);

        return $this->json([
            'accessToken' => $newAccessToken,
            'refreshToken' => $refreshToken, // On retourne le même refresh token
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ]
        ]);
    }

    /**
     * Endpoint pour révoquer un refresh token (logout)
     *
     * @Route("/revoke", name="api_revoke_token", methods={"POST"})
     */
    #[Route('/revoke', name: 'api_revoke_token', methods: ['POST'])]
    public function revoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $this->json([
                'error' => 'MISSING_REFRESH_TOKEN',
                'message' => 'Le refresh token est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $revoked = $this->refreshTokenService->revokeRefreshToken($refreshToken);

        if (!$revoked) {
            $this->logger->warning('Attempted to revoke non-existent token', [
                'ip' => $request->getClientIp()
            ]);

            return $this->json([
                'error' => 'TOKEN_NOT_FOUND',
                'message' => 'Refresh token introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->logger->info('Refresh token revoked successfully', [
            'ip' => $request->getClientIp()
        ]);

        return $this->json([
            'message' => 'Token révoqué avec succès'
        ]);
    }

    /**
     * Endpoint pour révoquer tous les refresh tokens d'un utilisateur
     * Utile pour un "logout de tous les appareils"
     *
     * @Route("/revoke-all", name="api_revoke_all_tokens", methods={"POST"})
     */
    #[Route('/revoke-all', name: 'api_revoke_all_tokens', methods: ['POST'])]
    public function revokeAll(Request $request): JsonResponse
    {
        // Cet endpoint nécessite d'être authentifié
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'UNAUTHORIZED',
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $count = $this->refreshTokenService->revokeAllUserTokens($user);

        $this->logger->info('All refresh tokens revoked for user', [
            'user_id' => $user->getId(),
            'count' => $count
        ]);

        return $this->json([
            'message' => 'Tous les tokens ont été révoqués',
            'count' => $count
        ]);
    }
}

