<?php

declare(strict_types=1);

namespace App\Platform\Presentation;

use App\Platform\Application\Readiness;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    public function __construct(private Readiness $readiness)
    {
    }
    #[Route('/health/live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
    #[Route('/health/ready', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        $ready = $this->readiness->isReady();
        return new JsonResponse(['status' => $ready ? 'ready' : 'unavailable'], $ready ? 200 : 503);
    }
}
