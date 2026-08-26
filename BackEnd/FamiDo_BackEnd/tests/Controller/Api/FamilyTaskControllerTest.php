<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class FamilyTaskControllerTest extends WebTestCase
{
    public function testChefCanCreateAFamily(): void
    {
        $client = static::createClient();
        $chef = $this->createAccount($client, 'ROLE_CHEF');

        $client->request(
            'POST',
            '/api/famille',
            server: $this->jsonServer($chef['token']),
            content: json_encode(['nom' => 'Famille de test'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $response = $this->responseData($client);
        self::assertSame('Famille de test', $response['nom']);
        self::assertNotEmpty($response['codeInvitation']);
        self::assertCount(1, $response['membres']);
    }

    public function testChefCanCreateAFamilyTask(): void
    {
        $client = static::createClient();
        $chef = $this->createAccount($client, 'ROLE_CHEF');
        $this->createFamily($client, $chef['token']);

        $client->request(
            'POST',
            '/api/taches',
            server: $this->jsonServer($chef['token']),
            content: json_encode([
                'titre' => 'Ranger la cuisine',
                'description' => 'Ranger la vaisselle',
                'priorite' => 'normale',
                'dateEcheance' => '2026-08-25',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $response = $this->responseData($client);
        self::assertSame('Ranger la cuisine', $response['titre']);
        self::assertFalse($response['isPrivate']);
        self::assertSame('a_faire', $response['statut']);
    }

    public function testPrivateTaskIsNotVisibleInFamilyTasks(): void
    {
        $client = static::createClient();
        $chef = $this->createAccount($client, 'ROLE_CHEF');
        $this->createFamily($client, $chef['token']);

        $member = $this->createAccount($client, 'ROLE_MEMBRE');
        $family = $this->getFamily($client, $chef['token']);
        $this->joinFamily($client, $member['token'], $family['codeInvitation']);

        $client->request(
            'POST',
            '/api/mes-taches',
            server: $this->jsonServer($member['token']),
            content: json_encode([
                'titre' => 'Tâche personnelle',
                'priorite' => 'normale',
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/taches', server: $this->jsonServer($chef['token']));
        self::assertResponseStatusCodeSame(200);
        self::assertSame([], $this->responseData($client));

        $client->request('GET', '/api/mes-taches', server: $this->jsonServer($member['token']));
        self::assertResponseStatusCodeSame(200);
        $privateTasks = $this->responseData($client);
        self::assertCount(1, $privateTasks);
        self::assertTrue($privateTasks[0]['isPrivate']);
    }

    private function createAccount(KernelBrowser $client, string $role): array
    {
        $email = sprintf('%s-%s@example.com', strtolower($role), bin2hex(random_bytes(4)));
        $client->request(
            'POST',
            '/api/register',
            server: $this->jsonServer(),
            content: json_encode([
                'email' => $email,
                'prenom' => 'Test',
                'password' => 'motdepasse123',
                'role' => $role,
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);

        return $this->responseData($client);
    }

    private function createFamily(KernelBrowser $client, string $token): array
    {
        $client->request(
            'POST',
            '/api/famille',
            server: $this->jsonServer($token),
            content: json_encode(['nom' => 'Famille de test'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);

        return $this->responseData($client);
    }

    private function getFamily(KernelBrowser $client, string $token): array
    {
        $client->request('GET', '/api/famille', server: $this->jsonServer($token));
        self::assertResponseStatusCodeSame(200);

        return $this->responseData($client);
    }

    private function joinFamily(KernelBrowser $client, string $token, string $code): void
    {
        $client->request(
            'POST',
            '/api/famille/rejoindre',
            server: $this->jsonServer($token),
            content: json_encode(['codeInvitation' => $code], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(200);
    }

    private function jsonServer(?string $token = null): array
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        return $server;
    }

    private function responseData(KernelBrowser $client): array
    {
        return json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
