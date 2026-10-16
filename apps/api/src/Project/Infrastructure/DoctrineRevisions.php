<?php

declare(strict_types=1);

namespace App\Project\Infrastructure;

use App\Project\Domain\CabinetRevision;
use App\Project\Domain\Revisions;
use App\Project\Infrastructure\Persistence\RevisionRecord;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRevisions implements Revisions
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    public function find(string $id): ?CabinetRevision
    {
        return $this->em->find(RevisionRecord::class, $id)?->revision();
    }
    public function forVersion(string $projectId, int $version): ?CabinetRevision
    {
        return $this->em->getRepository(RevisionRecord::class)->findOneBy(['projectId' => $projectId, 'version' => $version])?->revision();
    }
    public function save(CabinetRevision $revision): CabinetRevision
    {
        $this->em->persist(new RevisionRecord($revision));
        $this->em->flush();
        return $revision;
    }
}
