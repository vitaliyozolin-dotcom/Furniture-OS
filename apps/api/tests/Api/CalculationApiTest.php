<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CalculationApiTest extends WebTestCase
{
    public function testHealthCalculationAndCorrelation(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health/live');
        self::assertResponseIsSuccessful();
        $client->request('GET', '/health/ready');
        self::assertResponseIsSuccessful();
        $fixtures = json_decode(file_get_contents(dirname(__DIR__, 4).'/contracts/fixtures/prototype-v0.json'), true);
        foreach ($fixtures as $fixture) {
            $client->jsonRequest('POST', '/api/prototype/v0/calculate', $fixture['input'], ['HTTP_X_CORRELATION_ID' => 'fixture-test']);
            self::assertResponseIsSuccessful();
            self::assertResponseHeaderSame('X-Correlation-ID', 'fixture-test');
            $actual = json_decode($client->getResponse()->getContent(), true);
            self::assertEqualsWithDelta($fixture['expected']['costs']['total'], $actual['costs']['total'], 0.000000001);
            self::assertSame($fixture['expected']['warnings'], $actual['warnings']);
        }
    }
    public function testRejectsInvalidPayloadsAndNeverExposesFiles(): void
    {
        $client = self::createClient();
        foreach ([['{', 400], ['{}', 422], ['null', 422]] as [$body, $status]) {
            $client->request('POST', '/api/prototype/v0/calculate', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
            self::assertResponseStatusCodeSame($status);
            self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
            $problem = json_decode($client->getResponse()->getContent(), true);
            self::assertSame($status, $problem['status']);
            self::assertNotEmpty($problem['correlationId']);
        }
        $client->request('POST', '/api/prototype/v0/calculate', [], [], ['CONTENT_TYPE' => 'text/plain'], '{}');
        self::assertResponseStatusCodeSame(415);
        $client->request('POST', '/api/prototype/v0/calculate', [], [], ['CONTENT_TYPE' => 'application/json'], str_repeat('x', 65537));
        self::assertResponseStatusCodeSame(413);
        $client->request('GET', '/api/prototype/v0/calculate');
        self::assertResponseStatusCodeSame(405);
        self::assertResponseHeaderSame('Allow', 'POST');
        $client->request('GET', '/files/migration-probe');
        self::assertResponseStatusCodeSame(404);
    }
}
