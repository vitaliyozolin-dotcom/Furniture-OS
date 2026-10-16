<?php

declare(strict_types=1);

namespace App\Project\Presentation;

use App\Project\Application\ProjectWorkspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ProjectController
{
    public function __construct(private ProjectWorkspace $workspace)
    {
    }
    #[Route('/api/projects', methods: ['GET'])]
    public function listing(): JsonResponse
    {
        return new JsonResponse($this->workspace->listing());
    }
    #[Route('/api/projects/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        return new JsonResponse($this->workspace->get($id));
    }
    #[Route('/api/projects', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->fields($request, false);
        return new JsonResponse($this->workspace->create($data['title'], $data['configuration']), 201);
    }
    #[Route('/api/projects/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = $this->fields($request, true);
        return new JsonResponse($this->workspace->update($id, $data['title'], $data['configuration'], $data['expectedVersion']));
    }
    private function fields(Request $request, bool $version): array
    {
        $data = $request->toArray();
        if (!isset($data['title'], $data['configuration']) || !is_string($data['title']) || !is_array($data['configuration']) || ($version && (!isset($data['expectedVersion']) || !is_int($data['expectedVersion']) || $data['expectedVersion'] < 1))) {
            throw new \InvalidArgumentException('Укажите название, конфигурацию и актуальную версию проекта.');
        }
        return $data;
    }
}
