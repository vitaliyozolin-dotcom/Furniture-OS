<?php

declare(strict_types=1);

namespace App\Tests\Rendering;

use App\Rendering\Application\Image;
use App\Rendering\Application\RenderRequest;
use App\Rendering\Infrastructure\PolzaNanoBanana;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PolzaNanoBananaTest extends TestCase
{
    private const MODEL = 'google/gemini-nano-banana-2.1';

    public function testReferenceSubmissionAndPollingDownloadsWithoutLeakingCredentials(): void
    {
        $calls = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): MockResponse {
            $calls[] = [$method, $url];
            self::assertSame('Bearer test-secret', substr($options['normalized_headers']['authorization'][0], 15));
            self::assertSame(0, $options['max_redirects']);
            if ($method === 'POST') {
                $body = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);
                self::assertSame(self::MODEL, $body['model']);
                self::assertTrue($body['async']);
                self::assertSame(1, $body['input']['max_images']);
                self::assertSame('1K', $body['input']['image_resolution']);
                self::assertSame('png', $body['input']['output_format']);
                self::assertSame('base64', $body['input']['images'][0]['type']);
                self::assertSame(RenderingTest::PNG, $body['input']['images'][0]['data']);
                self::assertStringContainsString('Preserve the cabinet outline', $body['input']['prompt']);
                self::assertStringContainsString('Белый дуб', $body['input']['prompt']);
                self::assertSame(['require_parameters' => true, 'allow_fallbacks' => false, 'max_price' => ['image' => 10]], $body['provider']);
                return self::response('completed', ['usage' => ['cost_rub' => 3.141]]);
            }
            self::assertSame('https://polza.ai/api/v1/media/job-1', $url);
            return self::response('completed', ['data' => ['url' => 'https://s3.polza.ai/result.png'], 'usage' => ['cost_rub' => 3.141]]);
        });
        $downloads = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('GET', $method);
            self::assertSame('https://s3.polza.ai/result.png', $url);
            self::assertArrayNotHasKey('authorization', $options['normalized_headers']);
            self::assertSame(0, $options['max_redirects']);
            return new MockResponse(base64_decode(RenderingTest::PNG));
        });
        $adapter = $this->adapter($http, $downloads);
        self::assertSame('nano_banana', $adapter->name());
        self::assertSame(self::MODEL, $adapter->model());
        $started = $adapter->start(new RenderRequest(new Image(base64_decode(RenderingTest::PNG)), 'Белый дуб'), 1000);
        self::assertSame('processing', $started->status);
        self::assertSame(315, $started->costKopecks);
        $result = $adapter->poll($started->id);
        self::assertSame(base64_decode(RenderingTest::PNG), $result->image->bytes);
        self::assertSame(315, $result->costKopecks);
        self::assertCount(2, $calls);
    }

    public static function states(): iterable
    {
        foreach (['pending', 'processing', 'failed', 'cancelled'] as $state) {
            yield $state => [$state];
        }
    }

    #[DataProvider('states')]
    public function testNonCompletedStatesDoNotDownload(string $state): void
    {
        $adapter = $this->adapter(new MockHttpClient(self::response($state)), new MockHttpClient([]));
        self::assertSame($state, $adapter->poll('job-1')->status);
    }

    public static function malformed(): iterable
    {
        yield 'missing id' => [[]];
        yield 'wrong id' => [['id' => 'other', 'status' => 'processing']];
        yield 'non-string status' => [['id' => 'job-1', 'status' => 123]];
        yield 'unknown status' => [['id' => 'job-1', 'status' => 'bogus']];
        yield 'negative cost' => [['id' => 'job-1', 'status' => 'processing', 'usage' => ['cost_rub' => -1]]];
        yield 'non numeric cost' => [['id' => 'job-1', 'status' => 'processing', 'usage' => ['cost_rub' => 'oops']]];
        yield 'unbounded cost' => [['id' => 'job-1', 'status' => 'processing', 'usage' => ['cost_rub' => 1000001]]];
        yield 'missing image' => [['id' => 'job-1', 'status' => 'completed']];
        yield 'invalid url' => [['id' => 'job-1', 'status' => 'completed', 'data' => ['url' => []]]];
    }

    #[DataProvider('malformed')]
    public function testMalformedResponsesAreRejected(array $data): void
    {
        $adapter = $this->adapter(new MockHttpClient(new MockResponse(json_encode($data, JSON_THROW_ON_ERROR))));
        $this->expectException(\Exception::class);
        $adapter->poll('job-1');
    }

    public static function unsafeUrls(): iterable
    {
        foreach (['http://s3.polza.ai/a', 'https://evil.example/a', 'https://s3.polza.ai:443/a', 'https://secret@s3.polza.ai/a', 'https://user:secret@s3.polza.ai/a', 'https://127.0.0.1/a', 'https://s3.polza.ai.evil.example/a', 'https://[broken'] as $url) {
            yield [$url];
        }
    }

    #[DataProvider('unsafeUrls')]
    public function testGeneratedUrlIsRestrictedBeforeAnyDownload(string $url): void
    {
        $adapter = $this->adapter(new MockHttpClient(self::response('completed', ['data' => ['url' => $url]])), new MockHttpClient([]));
        $this->expectExceptionMessage('not allowed');
        $adapter->poll('job-1');
    }

    public static function httpErrors(): iterable
    {
        foreach ([301, 400, 401, 402, 403, 408, 429, 500, 503] as $code) {
            yield [$code];
        }
    }

    #[DataProvider('httpErrors')]
    public function testProviderErrorsAreSanitizedAndNeverRetried(int $code): void
    {
        $requests = 0;
        $http = new MockHttpClient(function () use ($code, &$requests): MockResponse {
            ++$requests;
            return new MockResponse('sensitive provider error', ['http_code' => $code]);
        });
        try {
            $this->adapter($http)->poll('job-1');
            self::fail('Expected an HTTP error');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('HTTP '.$code, $e->getMessage());
            self::assertStringNotContainsString('sensitive', $e->getMessage());
        }
        self::assertSame(1, $requests);
    }

    public function testTransportExceptionAndInvalidJsonAreSanitized(): void
    {
        foreach ([new MockResponse('invalid-json'), static function (): never {
            throw new TransportException('Bearer test-secret');
        }] as $response) {
            try {
                $this->adapter(new MockHttpClient($response))->poll('job-1');
                self::fail('Expected failure');
            } catch (\RuntimeException $e) {
                self::assertSame('Polza transport or JSON failure. Resume the saved job; do not repeat paid submissions.', $e->getMessage());
            }
        }
    }

    public static function badConfig(): iterable
    {
        yield ['', self::MODEL, 30];
        yield ['key', 'unsupported-model', 30];
        yield ['key', self::MODEL, 0];
        yield ['key', self::MODEL, 121];
    }

    #[DataProvider('badConfig')]
    public function testConfigurationCheckedBeforeNetwork(string $key, string $model, int $timeout): void
    {
        $http = new MockHttpClient([]);
        $this->expectException(\RuntimeException::class);
        (new PolzaNanoBanana($http, $http, $key, $model, 's3.polza.ai', $timeout))->start(new RenderRequest(new Image(base64_decode(RenderingTest::PNG)), 'Белый'), 1000);
    }

    public function testInvalidProviderIdNeverMakesHttpRequest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->adapter(new MockHttpClient([]))->poll('../secret');
    }

    public function testMalformedStartIdIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->adapter(new MockHttpClient(new MockResponse('{"id":1,"status":"pending"}')))->start(new RenderRequest(new Image(base64_decode(RenderingTest::PNG)), 'Белый'), 1000);
    }

    public static function brokenDownloads(): iterable
    {
        yield 'redirect' => [new MockResponse('', ['http_code' => 302]), 'Cannot download'];
        yield 'oversized' => [new MockResponse(str_repeat('x', 10485761)), 'exceeds'];
        yield 'not image' => [new MockResponse('<html>error</html>'), 'PNG'];
        yield 'transport' => [static function (): never {
            throw new TransportException('secret');
        }, 'download failed'];
    }

    #[DataProvider('brokenDownloads')]
    public function testFailedDownloadDoesNotSubmitAgain(mixed $download, string $message): void
    {
        $adapter = $this->adapter(new MockHttpClient(self::response('completed', ['data' => ['url' => 'https://s3.polza.ai/result.png']])), new MockHttpClient($download));
        $this->expectExceptionMessage($message);
        $adapter->poll('job-1');
    }

    private function adapter(MockHttpClient $http, ?MockHttpClient $downloads = null): PolzaNanoBanana
    {
        return new PolzaNanoBanana($http, $downloads ?? new MockHttpClient([]), 'test-secret', self::MODEL, 's3.polza.ai');
    }

    private static function response(string $status, array $extra = []): MockResponse
    {
        return new MockResponse(json_encode(['id' => 'job-1', 'status' => $status] + $extra, JSON_THROW_ON_ERROR));
    }
}
