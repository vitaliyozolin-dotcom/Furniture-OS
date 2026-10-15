<?php

declare(strict_types=1);

namespace App\Calculation\Presentation;

use App\Calculation\Application\CalculatePrototypeCabinet;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CalculateController
{
    public function __construct(private CalculatePrototypeCabinet $calculate)
    {
    }
    #[Route('/api/prototype/v0/calculate', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        if (strlen($request->getContent()) > 65536) {
            throw new HttpException(413, 'Request body exceeds 64 KiB.');
        }
        if (strtolower(explode(';', $request->headers->get('Content-Type', ''))[0]) !== 'application/json') {
            throw new HttpException(415, 'Expected application/json.');
        }
        try {
            $data = json_decode($request->getContent(), true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new HttpException(400, 'Malformed JSON.');
        }
        try {
            if (!is_array($data)) {
                throw new \InvalidArgumentException('Expected an object.');
            }
            $result = $this->calculate->calculate($data);
        } catch (\InvalidArgumentException $exception) {
            throw new HttpException(422, $exception->getMessage());
        }
        $result['costs'] = array_map(static fn (string $amount): float => (float) $amount, $result['costs']);
        return new JsonResponse($result);
    }
}
