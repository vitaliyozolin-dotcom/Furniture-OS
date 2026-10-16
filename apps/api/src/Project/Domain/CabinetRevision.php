<?php

declare(strict_types=1);

namespace App\Project\Domain;

final readonly class CabinetRevision
{
    public function __construct(public string $id, public string $projectId, public int $version, public string $title, public array $configuration, public array $estimate, public \DateTimeImmutable $createdAt)
    {
    }
}
