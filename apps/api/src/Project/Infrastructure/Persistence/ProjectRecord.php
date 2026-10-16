<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence;

use App\Project\Domain\CabinetProject;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'cabinet_project')]
final class ProjectRecord
{
    #[ORM\Id, ORM\Column(length: 32)] public string $id;
    #[ORM\Column(length: 32)] public string $ownerId;
    #[ORM\Column(length: 300)] public string $title;
    #[ORM\Column(type: 'json')] public array $configuration;
    #[ORM\Version, ORM\Column(type: 'integer')] public int $version = 1;
    #[ORM\Column(type: 'datetime_immutable')] public \DateTimeImmutable $updatedAt;
    public function __construct(CabinetProject $project)
    {
        $this->id = $project->id;
        $this->ownerId = $project->ownerId;
        $this->title = $project->title;
        $this->configuration = $project->configuration;
        $this->updatedAt = $project->updatedAt;
    }
    public function project(): CabinetProject
    {
        return new CabinetProject($this->id, $this->ownerId, $this->title, $this->configuration, $this->version, $this->updatedAt);
    }
}
