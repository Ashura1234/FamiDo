<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    public function testRegisterCreatesAnAccount(): void
    {
        $client = static::createClient();
        $email = sprintf('test-%s@example.com', bin2hex(random_bytes(4)));

        $client->request(
            'POST',
            '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'prenom' => 'Test',
                'password' => 'motdepasse123',
                'role' => 'ROLE_MEMBRE',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $response = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Compte créé', $response['message']);
        self::assertSame($email, $response['user']['email']);
        self::assertSame('Test', $response['user']['prenom']);
        self::assertNotEmpty($response['token']);
    }

    public function testLoginReturnsANewToken(): void
    {
        $client = static::createClient();
        $email = sprintf('login-%s@example.com', bin2hex(random_bytes(4)));

        $this->register($client, $email, 'motdepasse123');
        self::assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => 'motdepasse123',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Connexion réussie', $response['message']);
        self::assertNotEmpty($response['token']);
    }

    public function testLoginRejectsAnInvalidPassword(): void
    {
        $client = static::createClient();
        $email = sprintf('invalid-%s@example.com', bin2hex(random_bytes(4)));

        $this->register($client, $email, 'motdepasse123');
        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => 'mauvaismotdepasse',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testProtectedRouteRejectsMissingToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/famille');

        self::assertResponseStatusCodeSame(401);
    }

    private function register($client, string $email, string $password): void
    {
        $client->request(
            'POST',
            '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'prenom' => 'Test',
                'password' => $password,
                'role' => 'ROLE_MEMBRE',
            ], JSON_THROW_ON_ERROR),
        );
    }
}
