<?php

declare(strict_types=1);

namespace App\Project\Infrastructure;

use App\Project\Domain\CabinetProject;
use App\Project\Domain\Projects;
use App\Project\Infrastructure\Persistence\ProjectRecord;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

final readonly class DoctrineProjects implements Projects
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    public function find(string $id): ?CabinetProject
    {
        return $this->em->find(ProjectRecord::class, $id)?->project();
    }
    public function owned(string $ownerId): array
    {
        return array_map(static fn (ProjectRecord $record): CabinetProject => $record->project(), $this->em->getRepository(ProjectRecord::class)->findBy(['ownerId' => $ownerId], ['updatedAt' => 'DESC']));
    }
    public function save(CabinetProject $project): CabinetProject
    {
        $record = $this->em->find(ProjectRecord::class, $project->id);
        if ($record === null) {
            $record = new ProjectRecord($project);
            $this->em->persist($record);
        } else {
            $project->requireVersion($record->version);
            $record->title = $project->title;
            $record->configuration = $project->configuration;
            $record->updatedAt = $project->updatedAt;
        }
        try {
            $this->em->flush();
        } catch (OptimisticLockException) {
            throw new \DomainException('Проект изменён в другой вкладке. Откройте актуальную версию.');
        }
        return $record->project();
    }
}
