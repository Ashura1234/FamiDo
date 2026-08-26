<?php

namespace App\Controller\Api;

use App\Entity\AssignationTache;
use App\Entity\Tache;
use App\Entity\User;
use App\Repository\TacheRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
#[OA\Tag(name: 'Tâches')]
class TacheController extends AbstractController
{
    #[Route('/taches', name: 'api_taches_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste des tâches familiales')]
    #[OA\Response(response: 404, description: 'Famille requise')]
    public function list(TacheRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getFamille()) {
            return $this->json(['error' => 'Famille requise'], 404);
        }

        $tasks = $repository->findBy(
            ['famille' => $user->getFamille(), 'is_private' => false],
            ['dateEcheance' => 'ASC'],
        );

        return $this->json(array_map(fn (Tache $task): array => $this->taskData($task), $tasks));
    }

    #[Route('/taches', name: 'api_taches_create', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['titre', 'priorite'],
        properties: [
            new OA\Property(property: 'titre', type: 'string', example: 'Ranger la cuisine'),
            new OA\Property(property: 'description', type: 'string', example: 'Ranger la vaisselle et nettoyer le plan de travail'),
            new OA\Property(property: 'statut', type: 'string', enum: ['a_faire', 'terminee'], example: 'a_faire'),
            new OA\Property(property: 'priorite', type: 'string', enum: ['basse', 'normale', 'haute'], example: 'normale'),
            new OA\Property(property: 'dateEcheance', type: 'string', format: 'date', example: '2026-08-25'),
        ],
    ))]
    #[OA\Response(response: 201, description: 'Tâche créée')]
    #[OA\Response(response: 403, description: 'Accès réservé au chef')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isGranted('ROLE_CHEF')) {
            return $this->json(['error' => 'Accès réservé au chef de famille'], 403);
        }
        if (!$user->getFamille()) {
            return $this->json(['error' => 'Famille requise'], 400);
        }

        $data = $this->decodeJson($request);
        $titre = trim((string) ($data['titre'] ?? ''));
        if ($titre === '') {
            return $this->json(['error' => 'Le titre est obligatoire'], 400);
        }

        $task = new Tache();
        $task->setTitre($titre);
        $task->setDescription(trim((string) ($data['description'] ?? '')));
        $task->setStatut((string) ($data['statut'] ?? 'a_faire'));
        $task->setPriorite((string) ($data['priorite'] ?? 'normale'));
        $task->setIsPrivate((bool) ($data['isPrivate'] ?? false));
        $task->setFamille($user->getFamille());
        $task->setCreateur($user);
        $task->setDateEcheance($this->parseDate($data['dateEcheance'] ?? null));

        if ($task->isPrivate()) {
            return $this->json(['error' => 'Le chef doit créer une tâche familiale'], 400);
        }

        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json($this->taskData($task), 201);
    }

    #[Route('/taches/{id}', name: 'api_tache_update', methods: ['PUT'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'titre', type: 'string', example: 'Ranger la cuisine'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'statut', type: 'string', enum: ['a_faire', 'terminee']),
        new OA\Property(property: 'priorite', type: 'string', enum: ['basse', 'normale', 'haute']),
        new OA\Property(property: 'dateEcheance', type: 'string', format: 'date'),
    ]))]
    #[OA\Response(response: 200, description: 'Tâche modifiée')]
    #[OA\Response(response: 403, description: 'Accès refusé')]
    public function update(Tache $task, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$this->canManage($user, $task)) {
            return $this->json(['error' => 'Accès réservé au chef de cette famille'], 403);
        }

        $data = $this->decodeJson($request);
        if (array_key_exists('titre', $data)) {
            $task->setTitre(trim((string) $data['titre']));
        }
        if (array_key_exists('description', $data)) {
            $task->setDescription(trim((string) $data['description']));
        }
        if (array_key_exists('statut', $data)) {
            $task->setStatut((string) $data['statut']);
        }
        if (array_key_exists('priorite', $data)) {
            $task->setPriorite((string) $data['priorite']);
        }
        if (array_key_exists('dateEcheance', $data)) {
            $task->setDateEcheance($this->parseDate($data['dateEcheance']));
        }

        $entityManager->flush();

        return $this->json($this->taskData($task));
    }

    #[Route('/taches/{id}', name: 'api_tache_delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)]
    #[OA\Response(response: 200, description: 'Tâche supprimée')]
    #[OA\Response(response: 403, description: 'Accès refusé')]
    public function delete(Tache $task, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->canManage($this->getUser(), $task)) {
            return $this->json(['error' => 'Accès réservé au chef de cette famille'], 403);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return $this->json(['message' => 'Tâche supprimée']);
    }

    #[Route('/mes-taches', name: 'api_private_tasks_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Liste de mes tâches privées')]
    public function privateList(TacheRepository $repository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentification requise'], 401);
        }

        $tasks = $repository->findBy(['createur' => $user, 'is_private' => true], ['dateEcheance' => 'ASC']);

        return $this->json(array_map(fn (Tache $task): array => $this->taskData($task), $tasks));
    }

    #[Route('/mes-taches', name: 'api_private_tasks_create', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['titre', 'priorite'],
        properties: [
            new OA\Property(property: 'titre', type: 'string', example: 'Lire 20 pages'),
            new OA\Property(property: 'description', type: 'string'),
            new OA\Property(property: 'statut', type: 'string', enum: ['a_faire', 'terminee'], example: 'a_faire'),
            new OA\Property(property: 'priorite', type: 'string', enum: ['basse', 'normale', 'haute'], example: 'normale'),
            new OA\Property(property: 'dateEcheance', type: 'string', format: 'date'),
        ],
    ))]
    #[OA\Response(response: 201, description: 'Tâche privée créée')]
    public function privateCreate(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentification requise'], 401);
        }
        if (!$user->getFamille()) {
            return $this->json(['error' => 'Famille requise'], 400);
        }

        $data = $this->decodeJson($request);
        $titre = trim((string) ($data['titre'] ?? ''));
        if ($titre === '') {
            return $this->json(['error' => 'Le titre est obligatoire'], 400);
        }

        $task = new Tache();
        $task->setTitre($titre);
        $task->setDescription(trim((string) ($data['description'] ?? '')));
        $task->setStatut((string) ($data['statut'] ?? 'a_faire'));
        $task->setPriorite((string) ($data['priorite'] ?? 'normale'));
        $task->setIsPrivate(true);
        $task->setCreateur($user);
        $task->setFamille($user->getFamille());
        $task->setDateEcheance($this->parseDate($data['dateEcheance'] ?? null));

        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json($this->taskData($task), 201);
    }

    #[Route('/mes-taches/{id}', name: 'api_private_task_update', methods: ['PUT'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [
        new OA\Property(property: 'titre', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'statut', type: 'string', enum: ['a_faire', 'terminee']),
        new OA\Property(property: 'priorite', type: 'string', enum: ['basse', 'normale', 'haute']),
        new OA\Property(property: 'dateEcheance', type: 'string', format: 'date'),
    ]))]
    #[OA\Response(response: 200, description: 'Tâche privée modifiée')]
    public function privateUpdate(Tache $task, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $task->getCreateur() !== $user || !$task->isPrivate()) {
            return $this->json(['error' => 'Tâche privée introuvable'], 404);
        }

        $data = $this->decodeJson($request);
        if (array_key_exists('titre', $data)) {
            $task->setTitre(trim((string) $data['titre']));
        }
        if (array_key_exists('description', $data)) {
            $task->setDescription(trim((string) $data['description']));
        }
        if (array_key_exists('statut', $data)) {
            $task->setStatut((string) $data['statut']);
        }
        if (array_key_exists('priorite', $data)) {
            $task->setPriorite((string) $data['priorite']);
        }
        if (array_key_exists('dateEcheance', $data)) {
            $task->setDateEcheance($this->parseDate($data['dateEcheance']));
        }

        $entityManager->flush();

        return $this->json($this->taskData($task));
    }

    #[Route('/mes-taches/{id}', name: 'api_private_task_delete', methods: ['DELETE'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)]
    #[OA\Response(response: 200, description: 'Tâche privée supprimée')]
    public function privateDelete(Tache $task, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $task->getCreateur() !== $user || !$task->isPrivate()) {
            return $this->json(['error' => 'Tâche privée introuvable'], 404);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return $this->json(['message' => 'Tâche privée supprimée']);
    }

    #[Route('/taches/{id}/statut', name: 'api_tache_status', methods: ['PATCH'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['statut'],
        properties: [new OA\Property(property: 'statut', type: 'string', enum: ['a_faire', 'terminee'], example: 'terminee')],
    ))]
    #[OA\Response(response: 200, description: 'Statut modifié')]
    public function status(Tache $task, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$this->canManage($user, $task)) {
            return $this->json(['error' => 'Accès réservé au chef de cette famille'], 403);
        }

        $data = $this->decodeJson($request);
        $status = (string) ($data['statut'] ?? '');
        if (!in_array($status, ['a_faire', 'terminee'], true)) {
            return $this->json(['error' => 'Statut invalide'], 400);
        }

        $task->setStatut($status);
        $entityManager->flush();

        return $this->json($this->taskData($task));
    }

    #[Route('/taches/{id}/assignations', name: 'api_tache_assignments', methods: ['PUT'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1)]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['userIds'],
        properties: [new OA\Property(property: 'userIds', type: 'array', items: new OA\Items(type: 'integer'), example: [2, 3])],
    ))]
    #[OA\Response(response: 200, description: 'Assignations remplacées')]
    public function assignments(
        Tache $task,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$this->canManage($user, $task)) {
            return $this->json(['error' => 'Accès réservé au chef de cette famille'], 403);
        }

        $ids = $this->decodeJson($request)['userIds'] ?? null;
        if (!is_array($ids)) {
            return $this->json(['error' => 'userIds doit être un tableau'], 400);
        }

        foreach ($task->getAssignations()->toArray() as $assignment) {
            $entityManager->remove($assignment);
        }

        foreach ($ids as $id) {
            $member = $userRepository->find((int) $id);
            if (!$member || $member->getFamille() !== $task->getFamille()) {
                return $this->json(['error' => 'Un utilisateur ne fait pas partie de cette famille'], 400);
            }

            $assignment = new AssignationTache();
            $assignment->setTache($task);
            $assignment->setUser($member);
            $entityManager->persist($assignment);
        }

        $entityManager->flush();

        return $this->json($this->taskData($task));
    }

    private function canManage(mixed $user, Tache $task): bool
    {
        return $user instanceof User
            && $this->isGranted('ROLE_CHEF')
            && $user->getFamille() === $task->getFamille()
            && !$task->isPrivate();
    }

    private function decodeJson(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    private function parseDate(mixed $value): ?\DateTime
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('!Y-m-d', $value);

        return $date ?: null;
    }

    private function taskData(Tache $task): array
    {
        return [
            'id' => $task->getId(),
            'titre' => $task->getTitre(),
            'description' => $task->getDescription(),
            'statut' => $task->getStatut(),
            'priorite' => $task->getPriorite(),
            'dateEcheance' => $task->getDateEcheance()?->format('Y-m-d'),
            'isPrivate' => $task->isPrivate(),
            'createurId' => $task->getCreateur()?->getId(),
            'assignations' => array_map(static fn (AssignationTache $assignment): int => $assignment->getUser()->getId(), $task->getAssignations()->toArray()),
        ];
    }
}
