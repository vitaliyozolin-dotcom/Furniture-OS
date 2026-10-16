<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use PHPUnit\Framework\Attributes\DataProvider;

final class PilotSecurityTest extends WebTestCase
{
    private function browser(): KernelBrowser
    {
        $client = self::createClient();
        $client->setServerParameter('REMOTE_ADDR', '198.18.'.random_int(0, 255).'.'.random_int(1, 254));
        return $client;
    }
    private function csrf(KernelBrowser $client): array
    {
        $client->request('GET', '/api/auth/session');
        self::assertResponseIsSuccessful();
        return ['HTTP_X_CSRF_TOKEN' => $this->body($client)['csrfToken']];
    }
    private function body(KernelBrowser $client): array
    {
        return json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
    private function login(KernelBrowser $client): array
    {
        $email = 'account-'.bin2hex(random_bytes(8)).'@example.test';
        $csrf = $this->csrf($client);
        $client->jsonRequest('POST', '/api/auth/register', ['name' => 'Покупатель', 'email' => $email, 'password' => 'Password123!'], $csrf);
        self::assertResponseStatusCodeSame(201);
        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => 'Password123!'], $csrf);
        self::assertResponseIsSuccessful();
        return $this->csrf($client);
    }
    private function config(): array
    {
        return ['width' => 1800, 'height' => 2200, 'depth' => 600, 'sections' => [['type' => 'shelves', 'shelves' => 3, 'drawers' => 0]], 'bodyMaterial' => 'sonoma', 'facadeMaterial' => 'chipboard'];
    }
    public static function mutations(): iterable
    {
        yield ['POST', '/api/auth/register'];
        yield ['POST', '/api/auth/login'];
        yield ['POST', '/api/auth/logout'];
        yield ['POST', '/api/projects'];
        yield ['PUT', '/api/projects/unknown'];
        yield ['DELETE', '/api/projects/unknown/publication'];
    }
    #[DataProvider('mutations')]
    public function testAllMutationsRequireCsrf(string $method, string $path): void
    {
        $client = $this->browser();
        $client->jsonRequest($method, $path, []);
        self::assertResponseStatusCodeSame(403);
    }
    public function testAnonymousCannotReadOrCreatePrivateProjects(): void
    {
        $client = $this->browser();
        $csrf = $this->csrf($client);
        foreach (['/api/projects', '/api/projects/unknown'] as $path) {
            $client->request('GET', $path);
            self::assertResponseStatusCodeSame(401);
        }
        $client->jsonRequest('POST', '/api/projects', ['title' => 'Шкаф', 'configuration' => $this->config()], $csrf);
        self::assertResponseStatusCodeSame(401);
        $client->jsonRequest('POST', '/api/cabinets/preview', $this->config());
        self::assertResponseIsSuccessful();
        self::assertSame('prototype-v0', $this->body($client)['rulesetVersion']);
    }
    public function testOwnershipConflictsAndPublicationLifecycle(): void
    {
        $client = $this->browser();
        $csrf = $this->login($client);
        $client->jsonRequest('POST', '/api/projects', ['title' => 'Первый шкаф', 'configuration' => $this->config()], $csrf);
        self::assertResponseStatusCodeSame(201);
        $project = $this->body($client);
        $id = $project['id'];
        $client->request('GET', '/api/projects');
        self::assertCount(1, $this->body($client));
        $client->request('GET', '/api/projects/'.$id);
        self::assertSame($project, $this->body($client));
        $client->jsonRequest('POST', '/api/projects/'.$id.'/publication', ['city' => 'Казань', 'description' => 'Для спальни', 'expectedVersion' => 1], $csrf);
        self::assertResponseIsSuccessful();
        $publication = $this->body($client);
        $client->jsonRequest('POST', '/api/projects/'.$id.'/publication', ['city' => 'Казань', 'description' => 'Для спальни', 'expectedVersion' => 1], $csrf);
        self::assertSame($publication['token'], $this->body($client)['token']);
        $config = $this->config();
        $config['width'] = 1900;
        $client->jsonRequest('PUT', '/api/projects/'.$id, ['title' => 'Второй вариант', 'configuration' => $config, 'expectedVersion' => 1], $csrf);
        self::assertResponseIsSuccessful();
        self::assertSame(2, $this->body($client)['version']);
        $client->jsonRequest('PUT', '/api/projects/'.$id, ['title' => 'Устаревший', 'configuration' => $config, 'expectedVersion' => 1], $csrf);
        self::assertResponseStatusCodeSame(409);
        $client->jsonRequest('POST', '/api/projects/'.$id.'/publication', ['city' => 'Казань', 'description' => '', 'expectedVersion' => 1], $csrf);
        self::assertResponseStatusCodeSame(409);
        $client->request('GET', '/api/publications/'.$publication['token']);
        self::assertSame(1800, $this->body($client)['configuration']['width']);
        $client->jsonRequest('POST', '/api/auth/logout', [], $csrf);
        self::assertResponseStatusCodeSame(204);
        $other = $this->login($client);
        $client->request('GET', '/api/projects');
        self::assertSame([], $this->body($client));
        $client->request('GET', '/api/projects/'.$id);
        self::assertResponseStatusCodeSame(404);
        $client->jsonRequest('PUT', '/api/projects/'.$id, ['title' => 'Чужой', 'configuration' => $config, 'expectedVersion' => 2], $other);
        self::assertResponseStatusCodeSame(404);
        $client->jsonRequest('POST', '/api/projects/'.$id.'/publication', ['city' => 'Казань', 'description' => '', 'expectedVersion' => 2], $other);
        self::assertResponseStatusCodeSame(404);
        $client->jsonRequest('DELETE', '/api/projects/'.$id.'/publication', [], $other);
        self::assertResponseStatusCodeSame(404);
        // Check the public projection stays free of account and ownership fields.
        $client->request('GET', '/api/publications/'.$publication['token']);
        self::assertSame(['token', 'title', 'city', 'description', 'configuration', 'estimate', 'rulesetVersion', 'publishedAt'], array_keys($this->body($client)));
    }
    public function testWithdrawalAndNewPublicationHaveSeparateTokens(): void
    {
        $client = $this->browser();
        $csrf = $this->login($client);
        $client->jsonRequest('POST', '/api/projects', ['title' => 'Проект', 'configuration' => $this->config()], $csrf);
        $id = $this->body($client)['id'];
        $client->jsonRequest('DELETE', '/api/projects/'.$id.'/publication', [], $csrf);
        self::assertResponseStatusCodeSame(204);
        $publish = ['city' => 'Москва', 'description' => '', 'expectedVersion' => 1];
        $client->jsonRequest('POST', '/api/projects/'.$id.'/publication', $publish, $csrf);
        $token = $this->body($client)['token'];
        $client->jsonRequest('DELETE', '/api/projects/'.$id.'/publication', [], $csrf);
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('DELETE', '/api/projects/'.$id.'/publication', [], $csrf);
        self::assertResponseStatusCodeSame(204);
        $client->request('GET', '/api/publications/'.$token);
        self::assertResponseStatusCodeSame(404);
        $client->jsonRequest('POST', '/api/projects/'.$id.'/publication', $publish, $csrf);
        self::assertNotSame($token, $this->body($client)['token']);
        $client->request('GET', '/api/publications/'.str_repeat('0', 32));
        self::assertResponseStatusCodeSame(404);
    }
    public function testRegistrationErrorsNormalizationAndHashPrivacy(): void
    {
        $client = $this->browser();
        $csrf = $this->csrf($client);
        $email = 'Case-'.bin2hex(random_bytes(6)).'@EXAMPLE.TEST';
        $client->jsonRequest('POST', '/api/auth/register', ['name' => 'Имя', 'email' => $email, 'password' => 'Password123!'], $csrf);
        self::assertResponseStatusCodeSame(201);
        self::assertSame(strtolower($email), $this->body($client)['email']);
        self::assertSame(['id', 'email', 'name'], array_keys($this->body($client)));
        $client->jsonRequest('POST', '/api/auth/register', ['name' => 'Имя', 'email' => strtolower($email), 'password' => 'Password123!'], $csrf);
        self::assertResponseStatusCodeSame(409);
        $client->jsonRequest('POST', '/api/auth/register', ['name' => 1], $csrf);
        self::assertResponseStatusCodeSame(422);
        $client->jsonRequest('POST', '/api/auth/register', [], $csrf);
        self::assertResponseStatusCodeSame(422);
        $client->request('POST', '/api/auth/register', server: ['CONTENT_TYPE' => 'application/json', ...$csrf], content: '{broken');
        self::assertResponseStatusCodeSame(400);
        $client->jsonRequest('POST', '/api/auth/register', [], $csrf);
        self::assertResponseStatusCodeSame(429);
    }
    public function testLoginThrottlingAndNoAccountEnumeration(): void
    {
        $client = $this->browser();
        $csrf = $this->csrf($client);
        $email = 'absent-'.bin2hex(random_bytes(6)).'@example.test';
        for ($i = 0; $i < 5; ++$i) {
            $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => 'WrongPass123'], $csrf);
            self::assertResponseStatusCodeSame(401);
            self::assertSame('Неверный email или пароль.', $this->body($client)['detail']);
        }
        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => 'WrongPass123'], $csrf);
        self::assertResponseStatusCodeSame(429);
    }
    public function testLoginRotatesSessionAndInvalidatesPreviousCsrf(): void
    {
        $client = $this->browser();
        $before = $this->csrf($client);
        $cookie = $client->getCookieJar()->all()[0] ?? null;
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        $this->login($client);
        self::assertNotSame($cookie->getValue(), $client->getCookieJar()->get($cookie->getName())->getValue());
        $client->jsonRequest('POST', '/api/projects', ['title' => 'Тест', 'configuration' => $this->config()], $before);
        self::assertResponseStatusCodeSame(403);
    }
}
