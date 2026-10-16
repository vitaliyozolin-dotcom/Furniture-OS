<?php

declare(strict_types=1);

namespace App\Project\Domain;

interface Revisions
{
    public function find(string $id): ?CabinetRevision;
    public function forVersion(string $projectId, int $version): ?CabinetRevision;
    public function save(CabinetRevision $revision): CabinetRevision;
}
