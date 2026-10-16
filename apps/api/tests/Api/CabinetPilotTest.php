<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CabinetPilotTest extends WebTestCase
{
    public function testRegisterCreateSavePublishAndIsolateAnotherAccount(): void
    {
        $client = self::createClient();
        $client->setServerParameter('REMOTE_ADDR', '192.0.2.'.random_int(1, 250));
        $client->request('GET', '/api/auth/session');
        self::assertResponseIsSuccessful();
        $session = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertNull($session['user']);
        $csrf = ['HTTP_X_CSRF_TOKEN' => $session['csrfToken']];
        $email = 'pilot-'.bin2hex(random_bytes(6)).'@example.test';
        $client->jsonRequest('POST', '/api/auth/register', ['name' => 'Мастер', 'email' => $email, 'password' => 'CabinetPass123!'], $csrf);
        self::assertResponseStatusCodeSame(201);
        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => 'CabinetPass123!'], $csrf);
        self::assertResponseIsSuccessful();
        $client->request('GET', '/api/auth/session');
        $csrf = ['HTTP_X_CSRF_TOKEN' => json_decode($client->getResponse()->getContent(), true)['csrfToken']];
        $config = ['width' => 2400, 'height' => 2400, 'depth' => 600, 'sections' => [['type' => 'wardrobe', 'shelves' => 1, 'drawers' => 2]], 'bodyMaterial' => 'sonoma', 'facadeMaterial' => 'chipboard'];
        $client->jsonRequest('POST', '/api/cabinets/preview', $config);
        self::assertResponseIsSuccessful();
        foreach (json_decode($client->getResponse()->getContent(), true)['estimate']['costs'] as $amount) {
            self::assertIsNumeric($amount);
            self::assertFalse(is_string($amount), 'PrototypeCalculationResultV0 requires JSON numbers.');
        }
        $client->jsonRequest('POST', '/api/projects', ['title' => 'Мой шкаф', 'configuration' => $config], $csrf);
        self::assertResponseStatusCodeSame(201);
        $project = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $client->jsonRequest('POST', '/api/projects/'.$project['id'].'/publication', ['city' => 'Москва', 'description' => 'Шкаф в прихожую', 'expectedVersion' => 1], $csrf);
        self::assertResponseIsSuccessful();
        $publication = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertFalse(is_string($publication['estimate']['costs']['total']));
        self::assertArrayNotHasKey('email', $publication);
        $config['width'] = 2600;
        $client->jsonRequest('PUT', '/api/projects/'.$project['id'], ['title' => 'Изменённый шкаф', 'configuration' => $config, 'expectedVersion' => 1], $csrf);
        self::assertResponseIsSuccessful();
        self::assertSame(2, json_decode($client->getResponse()->getContent(), true)['version']);
        $client->jsonRequest('POST', '/api/auth/logout', [], $csrf);
        self::assertResponseStatusCodeSame(204);
        $client->request('GET', '/api/publications/'.$publication['token']);
        self::assertResponseIsSuccessful();
        self::assertSame(2400, json_decode($client->getResponse()->getContent(), true)['configuration']['width']);
        $client->request('GET', '/api/projects/'.$project['id']);
        self::assertResponseStatusCodeSame(401);
    }
}
