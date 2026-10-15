<?php

declare(strict_types=1);

namespace App\Rendering\Infrastructure;

use App\Rendering\Application\Generation;
use App\Rendering\Application\Image;
use App\Rendering\Application\ImageGenerator;
use App\Rendering\Application\RenderRequest;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PolzaNanoBanana implements ImageGenerator
{
    public function __construct(private HttpClientInterface $http, private HttpClientInterface $downloads, #[\SensitiveParameter] private string $apiKey, private string $modelId, private string $imageHosts, private int $timeoutSeconds = 30)
    {
    }

    public function name(): string
    {
        return 'nano_banana';
    }

    public function model(): string
    {
        return $this->modelId;
    }

    public function assertConfigured(): void
    {
        if (trim($this->apiKey) === '') {
            throw new \RuntimeException('Set POLZA_API_KEY in apps/api/.env.local.');
        }
        if (!in_array($this->modelId, ['google/gemini-nano-banana-2.1', 'google/gemini-3.1-flash-image-preview'], true) || $this->timeoutSeconds < 1 || $this->timeoutSeconds > 120) {
            throw new \RuntimeException('Invalid Nano Banana model or HTTP timeout.');
        }
    }

    public function start(RenderRequest $request, int $maxCostKopecks): Generation
    {
        $this->assertConfigured();
        $data = $this->api('POST', '', [
            'model' => $this->modelId,
            'async' => true,
            'input' => [
                'prompt' => $request->prompt(), 'image_resolution' => $request->resolution,
                'output_format' => 'png', 'max_images' => 1,
                'images' => [['type' => 'base64', 'data' => base64_encode($request->reference->bytes)]],
            ],
            'provider' => ['require_parameters' => true, 'allow_fallbacks' => false, 'max_price' => ['image' => $maxCostKopecks / 100]],
        ]);
        // Persist the provider ID before attempting any download, even if creation finished immediately.
        return $this->generation($data, false);
    }

    public function poll(string $id): Generation
    {
        $this->assertConfigured();
        if (!preg_match('/^[a-zA-Z0-9_-]{1,200}$/D', $id)) {
            throw new \InvalidArgumentException('Invalid provider job ID.');
        }
        $data = $this->api('GET', '/'.$id);
        if (($data['id'] ?? null) !== $id) {
            throw new \RuntimeException('Provider returned a different job ID.');
        }
        return $this->generation($data, true);
    }

    private function api(string $method, string $suffix, ?array $payload = null): array
    {
        try {
            $options = ['auth_bearer' => $this->apiKey, 'timeout' => $this->timeoutSeconds, 'max_duration' => $this->timeoutSeconds, 'max_redirects' => 0];
            if ($payload !== null) {
                $options['json'] = $payload;
            }
            $response = $this->http->request($method, 'https://polza.ai/api/v1/media'.$suffix, $options);
            $status = $response->getStatusCode();
            if ($status !== 200) {
                $response->cancel();
                throw new \RuntimeException('Polza HTTP '.$status.'. Check API key, balance, model access and limits.');
            }
            return $response->toArray(false);
        } catch (\Symfony\Contracts\HttpClient\Exception\ExceptionInterface) {
            // Never expose provider bodies, Authorization headers or base64 input through CLI errors.
            throw new \RuntimeException('Polza transport or JSON failure. Resume the saved job; do not repeat paid submissions.');
        }
    }

    private function generation(array $data, bool $download): Generation
    {
        $id = $data['id'] ?? '';
        $status = $data['status'] ?? '';
        if (!is_string($id) || !is_string($status)) {
            throw new \RuntimeException('Invalid Polza response.');
        }
        $cost = $data['usage']['cost_rub'] ?? null;
        $kopecks = null;
        if ($cost !== null) {
            if (!is_numeric($cost) || !is_finite((float) $cost) || (float) $cost < 0 || (float) $cost > 1000000) {
                throw new \RuntimeException('Invalid Polza usage cost.');
            }
            // Provider supplies fractional RUB. Round up once at the infrastructure boundary.
            $kopecks = (int) ceil((float) $cost * 100 - 0.0000001);
        }
        if ($status === 'completed' && !$download) {
            return new Generation($id, 'processing', costKopecks: $kopecks);
        }
        $image = $status === 'completed' ? $this->download($data['data']['url'] ?? '') : null;
        return new Generation($id, $status, $image, $kopecks);
    }

    private function download(mixed $url): Image
    {
        if (!is_string($url)) {
            throw new \RuntimeException('Invalid Polza image URL.');
        }
        $parts = parse_url($url);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || !in_array($parts['host'] ?? '', explode(',', $this->imageHosts), true) || isset($parts['port']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new \RuntimeException('Polza image host is not allowed. Check AI_POLZA_IMAGE_HOSTS.');
        }
        try {
            // No credentials on CDN requests; no redirects or connections to private network addresses.
            $client = $this->downloads;
            $response = $client->request('GET', $url, ['max_redirects' => 0, 'timeout' => $this->timeoutSeconds, 'max_duration' => $this->timeoutSeconds]);
            if ($response->getStatusCode() !== 200) {
                $response->cancel();
                throw new \RuntimeException('Cannot download generated PNG. Resume the saved job.');
            }
            $bytes = '';
            foreach ($client->stream($response) as $chunk) {
                $bytes .= $chunk->getContent();
                if (strlen($bytes) > 10485760) {
                    $response->cancel();
                    throw new \RuntimeException('Generated PNG exceeds 10 MiB.');
                }
            }
            return new Image($bytes);
        } catch (\Symfony\Contracts\HttpClient\Exception\ExceptionInterface) {
            throw new \RuntimeException('Generated image download failed. Resume the saved job.');
        }
    }
}
