<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence;

use App\Project\Domain\CabinetRevision;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'cabinet_revision')]
#[ORM\UniqueConstraint(name: 'uniq_revision_version', columns: ['project_id', 'version'])]
final class RevisionRecord
{
    #[ORM\Id, ORM\Column(length: 32)] public string $id;
    #[ORM\Column(length: 32)] public string $projectId;
    #[ORM\Column] public int $version;
    #[ORM\Column(length: 300)] public string $title;
    #[ORM\Column(type: 'json')] public array $configuration;
    #[ORM\Column(type: 'json')] public array $estimate;
    #[ORM\Column(type: 'datetime_immutable')] public \DateTimeImmutable $createdAt;
    public function __construct(CabinetRevision $revision)
    {
        foreach (['id', 'projectId', 'version', 'title', 'configuration', 'estimate', 'createdAt'] as $field) {
            $this->$field = $revision->$field;
        }
    }
    public function revision(): CabinetRevision
    {
        return new CabinetRevision($this->id, $this->projectId, $this->version, $this->title, $this->configuration, $this->estimate, $this->createdAt);
    }
}
