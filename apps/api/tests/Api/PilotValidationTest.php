<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PilotValidationTest extends WebTestCase
{
    public static function invalidRequests(): iterable
    {
        yield ['POST', '/api/projects', []];
        yield ['POST', '/api/projects', ['title' => [], 'configuration' => []]];
        yield ['POST', '/api/projects', ['title' => 'Шкаф', 'configuration' => 1]];
        foreach ([null, 0, '1'] as $version) {
            yield ['PUT', '/api/projects/missing', ['title' => 'Шкаф', 'configuration' => [], 'expectedVersion' => $version]];
        }
        yield ['POST', '/api/projects/missing/publication', []];
        yield ['POST', '/api/projects/missing/publication', ['city' => 1, 'description' => '', 'expectedVersion' => 1]];
        yield ['POST', '/api/projects/missing/publication', ['city' => 'Город', 'description' => [], 'expectedVersion' => 1]];
        yield ['POST', '/api/projects/missing/publication', ['city' => 'Город', 'description' => '', 'expectedVersion' => 0]];
    }
    #[DataProvider('invalidRequests')]
    public function testInvalidPayloadRejected(string $method, string $path, array $data): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/auth/session');
        $csrf = json_decode($client->getResponse()->getContent(), true)['csrfToken'];
        $client->jsonRequest($method, $path, $data, ['HTTP_X_CSRF_TOKEN' => $csrf]);
        self::assertResponseStatusCodeSame(422);
    }
    public function testMalformedAndOversizedPreview(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/cabinets/preview', server: ['CONTENT_TYPE' => 'application/json'], content: '{bad');
        self::assertResponseStatusCodeSame(400);
        $client->request('POST', '/api/cabinets/preview', server: ['CONTENT_TYPE' => 'application/json'], content: str_repeat('x', 65537));
        self::assertResponseStatusCodeSame(413);
        $client->jsonRequest('POST', '/api/cabinets/preview', []);
        self::assertResponseStatusCodeSame(422);
    }
}
