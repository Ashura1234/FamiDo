<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'prenom', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'parent@test.fr'),
                new OA\Property(property: 'prenom', type: 'string', example: 'Parent'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'motdepasse123'),
                new OA\Property(property: 'role', type: 'string', enum: ['ROLE_CHEF', 'ROLE_MEMBRE'], example: 'ROLE_CHEF'),
            ],
        ),
    )]
    #[OA\Response(response: 201, description: 'Compte créé')]
    #[OA\Response(response: 400, description: 'Données invalides')]
    #[OA\Response(response: 409, description: 'Email déjà utilisé')]
    public function register(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $prenom = trim((string) ($data['prenom'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role = (string) ($data['role'] ?? 'ROLE_MEMBRE');

        if ($email === '' || $prenom === '' || $password === '') {
            return $this->json(['error' => 'email, prenom et password sont obligatoires'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse email invalide'], 400);
        }

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        if (!in_array($role, ['ROLE_CHEF', 'ROLE_MEMBRE'], true)) {
            return $this->json(['error' => 'Rôle invalide'], 400);
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Cette adresse email est déjà utilisée'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPrenom($prenom);
        $user->setRoles([$role]);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setApiToken(bin2hex(random_bytes(32)));

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'Compte créé',
            'user' => $this->userData($user),
            'token' => $user->getApiToken(),
        ], 201);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'parent@test.fr'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'motdepasse123'),
            ],
        ),
    )]
    #[OA\Response(response: 200, description: 'Connexion réussie')]
    #[OA\Response(response: 401, description: 'Identifiants invalides')]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        $user->setApiToken(bin2hex(random_bytes(32)));
        $entityManager->flush();

        return $this->json([
            'message' => 'Connexion réussie',
            'user' => $this->userData($user),
            'token' => $user->getApiToken(),
        ]);
    }

    private function decodeJson(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'prenom' => $user->getPrenom(),
            'roles' => $user->getRoles(),
        ];
    }
}
