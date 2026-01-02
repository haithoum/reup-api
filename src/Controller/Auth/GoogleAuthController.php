<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Entity\CitizenProfile;
use App\Service\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/auth')]
class GoogleAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private HttpClientInterface $httpClient,
        private RefreshTokenService $refreshTokenService
    ) {
    }

    #[Route('/google', name: 'api_google_auth', methods: ['POST'])]
    public function googleAuth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $idToken = $data['idToken'] ?? null;
        $role = $data['role'] ?? 'citizen';

        if (!$idToken) {
            return $this->json([
                'error' => 'MISSING_TOKEN',
                'message' => 'Google ID Token requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le token Google
        try {
            $googleUser = $this->verifyGoogleToken($idToken);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'INVALID_GOOGLE_TOKEN',
                'message' => 'Token Google invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Chercher ou créer l'utilisateur
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $googleUser['email']]);

        $isNewUser = false;

        if (!$user) {
            // Créer un nouveau compte
            $user = new User();
            $user->setEmail($googleUser['email']);
            $user->setFirstName($googleUser['given_name'] ?? '');
            $user->setLastName($googleUser['family_name'] ?? '');
            $user->setGoogleId($googleUser['sub']);

            // Mapper le rôle
            $roleMap = [
                'citizen' => 'ROLE_CITIZEN',
                'merchant' => 'ROLE_MERCHANT',
                'pro' => 'ROLE_PRO'
            ];
            $user->setRole($roleMap[$role] ?? 'ROLE_CITIZEN');
            $user->setIsActive(true);

            // Créer le profil correspondant
            if ($user->getRole() === 'ROLE_CITIZEN') {
                $profile = new CitizenProfile();
                $profile->setUser($user);
                $profile->setTotalPoints(0);
                $profile->setCurrentPoints(0);
                $this->entityManager->persist($profile);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $isNewUser = true;
        } else {
            // Mettre à jour le Google ID si nécessaire
            if (!$user->getGoogleId()) {
                $user->setGoogleId($googleUser['sub']);
                $this->entityManager->flush();
            }
        }

        // Vérifier si le compte est actif
        if (!$user->isActive()) {
            return $this->json([
                'error' => 'ACCOUNT_INACTIVE',
                'message' => 'Votre compte est inactif'
            ], Response::HTTP_FORBIDDEN);
        }

        // Générer JWT
        $accessToken = $this->jwtManager->create($user);

        // Générer refresh token
        $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

        // Récupérer firstName/lastName depuis le profil
        $firstName = '';
        $lastName = '';
        if ($user->getRole() === 'ROLE_CITIZEN' && $user->getCitizenProfile()) {
            $firstName = $user->getCitizenProfile()->getFirstName() ?? '';
            $lastName = $user->getCitizenProfile()->getLastName() ?? '';
        }

        return $this->json([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'firstName' => $firstName,
                'lastName' => $lastName,
                'googleId' => $user->getGoogleId(),
            ],
            'isNewUser' => $isNewUser
        ]);
    }

    /**
     * Valider le Google ID Token avec l'API Google
     */
    private function verifyGoogleToken(string $idToken): array
    {
        $response = $this->httpClient->request('GET',
            'https://oauth2.googleapis.com/tokeninfo',
            [
                'query' => ['id_token' => $idToken]
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Invalid Google token');
        }

        $data = $response->toArray();

        // Vérifier l'audience (client ID)
        $clientId = $this->getParameter('google_client_id');
        if (isset($data['aud']) && $data['aud'] !== $clientId) {
            throw new \Exception('Invalid token audience');
        }

        return $data;
    }
}

