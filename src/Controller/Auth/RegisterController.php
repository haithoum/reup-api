<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Entity\CitizenProfile;
use App\Entity\MerchantProfile;
use App\Entity\ProProfile;
use App\Service\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth/register')]
class RegisterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private ValidatorInterface $validator,
        private RefreshTokenService $refreshTokenService
    ) {
    }

    #[Route('/citizen', name: 'api_register_citizen', methods: ['POST'])]
    public function registerCitizen(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        $requiredFields = ['email', 'password', 'firstName', 'lastName', 'phone', 'cityId'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'error' => 'MISSING_FIELD',
                    'message' => "Le champ {$field} est requis"
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json([
                'error' => 'DUPLICATE_EMAIL',
                'message' => 'Cet email est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setRole('ROLE_CITIZEN');
        $user->setIsActive(true);

        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Créer le profil citoyen
        $profile = new CitizenProfile();
        $profile->setUser($user);
        $profile->setFirstName($data['firstName']);
        $profile->setLastName($data['lastName']);
        $profile->setPhoneNumber($data['phone']);

        // Valider l'entité
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json([
                'error' => 'VALIDATION_ERROR',
                'message' => 'Données invalides',
                'details' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarder
        $this->entityManager->persist($user);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        // Générer JWT
        $accessToken = $this->jwtManager->create($user);

        // Générer refresh token
        $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ], Response::HTTP_CREATED);
    }

    #[Route('/merchant', name: 'api_register_merchant', methods: ['POST'])]
    public function registerMerchant(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        $requiredFields = ['email', 'password', 'firstName', 'lastName', 'phone', 'businessName', 'siret', 'address', 'cityId', 'categoryId'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'error' => 'MISSING_FIELD',
                    'message' => "Le champ {$field} est requis"
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Vérifier email et SIRET
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json([
                'error' => 'DUPLICATE_EMAIL',
                'message' => 'Cet email est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        $existingMerchant = $this->entityManager->getRepository(MerchantProfile::class)
            ->findOneBy(['siret' => $data['siret']]);

        if ($existingMerchant) {
            return $this->json([
                'error' => 'DUPLICATE_SIRET',
                'message' => 'Ce SIRET est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setRole('ROLE_MERCHANT');
        $user->setIsActive(false); // Requiert validation admin

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Créer le profil commerçant
        $profile = new MerchantProfile();
        $profile->setUser($user);
        $profile->setFirstName($data['firstName']);
        $profile->setLastName($data['lastName']);
        $profile->setShopName($data['businessName']);
        $profile->setSiret($data['siret']);
        $profile->setAddressStreet($data['address']);
        $profile->setAddressPostalCode($data['postalCode'] ?? '00000');
        $profile->setPhoneNumber($data['phone']);
        // TODO: Gérer latitude/longitude et city
        $profile->setValidationStatus('PENDING');

        $this->entityManager->persist($user);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $accessToken = $this->jwtManager->create($user);

        // Générer refresh token
        $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'message' => 'Compte créé. En attente de validation par un administrateur.'
        ], Response::HTTP_CREATED);
    }

    #[Route('/pro', name: 'api_register_pro', methods: ['POST'])]
    public function registerPro(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['email', 'password', 'firstName', 'lastName', 'phone', 'organizationName', 'siret'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'error' => 'MISSING_FIELD',
                    'message' => "Le champ {$field} est requis"
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json([
                'error' => 'DUPLICATE_EMAIL',
                'message' => 'Cet email est déjà utilisé'
            ], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setRole('ROLE_PRO');
        $user->setIsActive(false);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $profile = new ProProfile();
        $profile->setUser($user);
        $profile->setFirstName($data['firstName']);
        $profile->setLastName($data['lastName']);
        $profile->setCompanyName($data['organizationName']);
        $profile->setSiret($data['siret']);
        $profile->setPhoneNumber($data['phone']);
        $profile->setValidationStatus('PENDING');

        $this->entityManager->persist($user);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $accessToken = $this->jwtManager->create($user);

        // Générer refresh token
        $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'message' => 'Compte créé. En attente de validation par un administrateur.'
        ], Response::HTTP_CREATED);
    }
}

