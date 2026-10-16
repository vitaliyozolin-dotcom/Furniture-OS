<?php

declare(strict_types=1);

namespace App\Marketplace\Presentation;

use App\Marketplace\Application\PublishCabinet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PublicationController
{
    public function __construct(private PublishCabinet $publish)
    {
    }
    #[Route('/api/publications/{token}', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        return new JsonResponse($this->publish->publicView($token));
    }
    #[Route('/api/projects/{id}/publication', methods: ['POST'])]
    public function publish(string $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (!isset($data['city'], $data['description'], $data['expectedVersion']) || !is_string($data['city']) || !is_string($data['description']) || !is_int($data['expectedVersion']) || $data['expectedVersion'] < 1) {
            throw new \InvalidArgumentException('Укажите город, описание и актуальную версию.');
        }
        return new JsonResponse($this->publish->publish($id, $data['expectedVersion'], $data['city'], $data['description']));
    }
    #[Route('/api/projects/{id}/publication', methods: ['DELETE'])]
    public function withdraw(string $id): Response
    {
        $this->publish->withdraw($id);
        return new Response('', 204);
    }
}
