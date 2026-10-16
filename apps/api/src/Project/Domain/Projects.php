<?php

declare(strict_types=1);

namespace App\Project\Domain;

interface Projects
{
    public function find(string $id): ?CabinetProject;
    /** @return list<CabinetProject> */
    public function owned(string $ownerId): array;
    public function save(CabinetProject $project): CabinetProject;
}
