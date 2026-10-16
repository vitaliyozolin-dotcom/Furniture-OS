<?php

declare(strict_types=1);

namespace App\Cabinet\Presentation;

use App\Cabinet\Application\ConfigureCabinet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CabinetController
{
    public function __construct(private ConfigureCabinet $configure)
    {
    }
    #[Route('/api/cabinets/preview', methods: ['POST'])]
    public function preview(Request $request): JsonResponse
    {
        return new JsonResponse($this->configure->preview($request->toArray()));
    }
}
