<?php

declare(strict_types=1);

namespace App\Marketplace\Infrastructure;

use App\Marketplace\Domain\CabinetPublication;
use App\Marketplace\Domain\Publications;
use App\Marketplace\Infrastructure\Persistence\PublicationRecord;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePublications implements Publications
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    public function forProject(string $id): ?CabinetPublication
    {
        return $this->em->find(PublicationRecord::class, $id)?->publication();
    }
    public function byToken(string $token): ?CabinetPublication
    {
        return $this->em->getRepository(PublicationRecord::class)->findOneBy(['token' => $token])?->publication();
    }
    public function save(CabinetPublication $publication): void
    {
        $record = $this->em->find(PublicationRecord::class, $publication->projectId);
        if ($record === null) {
            $record = new PublicationRecord();
        }
        $record->apply($publication);
        $this->em->persist($record);
        $this->em->flush();
    }
}
