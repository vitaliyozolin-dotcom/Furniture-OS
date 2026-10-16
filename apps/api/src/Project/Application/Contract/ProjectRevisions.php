<?php

declare(strict_types=1);

namespace App\Project\Application\Contract;

interface ProjectRevisions
{
    public function issue(string $projectId, int $expectedVersion): array;
    public function read(string $revisionId): array;
    public function requireOwnership(string $projectId): string;
}
