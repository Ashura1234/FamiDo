<?php

namespace App\Controller\Api;

use App\Entity\Famille;
use App\Entity\User;
use App\Repository\FamilleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/famille')]
#[OA\Tag(name: 'Famille')]
class FamilleController extends AbstractController
{
    #[Route('', name: 'api_famille_create', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['nom'],
        properties: [new OA\Property(property: 'nom', type: 'string', example: 'Famille Dupont')],
    ))]
    #[OA\Response(response: 201, description: 'Famille créée')]
    #[OA\Response(response: 403, description: 'Accès réservé au chef')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isGranted('ROLE_CHEF')) {
            return $this->json(['error' => 'Accès réservé au chef de famille'], 403);
        }

        if ($user->getFamille()) {
            return $this->json(['error' => 'Vous appartenez déjà à une famille'], 409);
        }

        $data = $this->decodeJson($request);
        $nom = trim((string) ($data['nom'] ?? ''));
        if ($nom === '') {
            return $this->json(['error' => 'Le nom de la famille est obligatoire'], 400);
        }

        $famille = new Famille();
        $famille->setNom($nom);
        $famille->setCodeInvitation((string) random_int(100000, 999999));
        $famille->addUser($user);

        $entityManager->persist($famille);
        $entityManager->flush();

        return $this->json($this->familyData($famille), 201);
    }

    #[Route('', name: 'api_famille_show', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Famille récupérée')]
    #[OA\Response(response: 404, description: 'Famille introuvable')]
    public function show(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getFamille()) {
            return $this->json(['error' => 'Vous n’appartenez à aucune famille'], 404);
        }

        return $this->json($this->familyData($user->getFamille()));
    }

    #[Route('/rejoindre', name: 'api_famille_join', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['codeInvitation'],
        properties: [new OA\Property(property: 'codeInvitation', type: 'string', example: '123456')],
    ))]
    #[OA\Response(response: 200, description: 'Famille rejointe')]
    #[OA\Response(response: 404, description: 'Code invalide')]
    public function join(
        Request $request,
        FamilleRepository $familleRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentification requise'], 401);
        }

        if ($user->getFamille()) {
            return $this->json(['error' => 'Vous appartenez déjà à une famille'], 409);
        }

        $data = $this->decodeJson($request);
        $code = trim((string) ($data['codeInvitation'] ?? ''));
        $famille = $familleRepository->findOneBy(['code_invitation' => $code]);

        if (!$famille) {
            return $this->json(['error' => 'Code d’invitation invalide'], 404);
        }

        $famille->addUser($user);
        $entityManager->flush();

        return $this->json($this->familyData($famille));
    }

    #[Route('/membres/{id}', name: 'api_famille_remove_member', methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 2)]
    #[OA\Response(response: 200, description: 'Membre retiré')]
    #[OA\Response(response: 403, description: 'Accès réservé au chef')]
    public function removeMember(
        User $member,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $chef = $this->getUser();
        if (!$chef instanceof User || !$this->isGranted('ROLE_CHEF')) {
            return $this->json(['error' => 'Accès réservé au chef de famille'], 403);
        }

        if (!$chef->getFamille() || $member->getFamille() !== $chef->getFamille()) {
            return $this->json(['error' => 'Ce membre n’appartient pas à votre famille'], 404);
        }

        $chef->getFamille()->removeUser($member);
        $entityManager->flush();

        return $this->json(['message' => 'Membre retiré']);
    }

    private function decodeJson(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    private function familyData(Famille $famille): array
    {
        return [
            'id' => $famille->getId(),
            'nom' => $famille->getNom(),
            'codeInvitation' => $famille->getCodeInvitation(),
            'membres' => array_map(static fn (User $user): array => [
                'id' => $user->getId(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ], $famille->getUsers()->toArray()),
        ];
    }
}
