<?php

declare(strict_types=1);

namespace App\Project\Application;

use App\Project\Application\Contract\ProjectRevisions;
use App\Project\Domain\CabinetProject;
use App\Project\Domain\CabinetRevision;
use App\Project\Domain\Projects;
use App\Project\Domain\Revisions;
use App\Identity\Application\Contract\CurrentActor;
use App\Cabinet\Application\Contract\CabinetConfigurator;

final readonly class ProjectWorkspace implements ProjectRevisions
{
    public function __construct(private Projects $projects, private Revisions $revisions, private CurrentActor $actor, private CabinetConfigurator $configurator)
    {
    }
    public function listing(): array
    {
        return array_map(static fn (CabinetProject $project): array => $project->view(), $this->projects->owned($this->actor->requireId()));
    }
    public function get(string $id): array
    {
        return $this->owned($id)->view();
    }
    public function create(string $title, array $configuration): array
    {
        $owner = $this->actor->requireId();
        $preview = $this->configurator->preview($configuration);
        return $this->projects->save(new CabinetProject(bin2hex(random_bytes(16)), $owner, trim($title), $preview['configuration'], 1, new \DateTimeImmutable()))->view();
    }
    public function update(string $id, string $title, array $configuration, int $expectedVersion): array
    {
        $project = $this->owned($id);
        $project->requireVersion($expectedVersion);
        $preview = $this->configurator->preview($configuration);
        return $this->projects->save(new CabinetProject($id, $project->ownerId, trim($title), $preview['configuration'], $project->version, new \DateTimeImmutable()))->view();
    }
    public function requireOwnership(string $projectId): string
    {
        return $this->owned($projectId)->ownerId;
    }
    public function issue(string $projectId, int $expectedVersion): array
    {
        $project = $this->owned($projectId);
        $project->requireVersion($expectedVersion);
        $revision = $this->revisions->forVersion($projectId, $expectedVersion);
        if ($revision === null) {
            $preview = $this->configurator->preview($project->configuration);
            $revision = $this->revisions->save(new CabinetRevision(bin2hex(random_bytes(16)), $projectId, $project->version, $project->title, $preview['configuration'], $preview['estimate'], new \DateTimeImmutable()));
        }
        return $this->revisionView($revision);
    }
    public function read(string $revisionId): array
    {
        $revision = $this->revisions->find($revisionId);
        if ($revision === null) {
            throw new \OutOfBoundsException('Версия шкафа не найдена.');
        }
        return $this->revisionView($revision);
    }
    private function owned(string $id): CabinetProject
    {
        $owner = $this->actor->requireId();
        $project = $this->projects->find($id);
        if ($project === null || $project->ownerId !== $owner) {
            throw new \OutOfBoundsException('Проект не найден.');
        }
        return $project;
    }
    private function revisionView(CabinetRevision $revision): array
    {
        return ['id' => $revision->id, 'title' => $revision->title, 'configuration' => $revision->configuration, 'estimate' => $revision->estimate, 'rulesetVersion' => 'prototype-v0'];
    }
}
