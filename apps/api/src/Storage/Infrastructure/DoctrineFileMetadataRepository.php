<?php

declare(strict_types=1);

namespace App\Storage\Infrastructure;

use App\Storage\Application\FileMetadataRepository;
use App\Storage\Domain\FileMetadata;
use App\Storage\Infrastructure\Persistence\StoredFile;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineFileMetadataRepository implements FileMetadataRepository
{
    public function __construct(private EntityManagerInterface $manager)
    {
    }
    public function save(FileMetadata $file): void
    {
        $existing = $this->manager->find(StoredFile::class, $file->id);
        if ($existing !== null) {
            if ($existing->checksum !== $file->checksum || $existing->storageKey !== $file->key->value) {
                throw new \RuntimeException('Conflicting file metadata.');
            }
            return;
        }
        $this->manager->persist(new StoredFile($file));
        $this->manager->flush();
    }
    public function find(string $id): ?FileMetadata
    {
        return $this->manager->find(StoredFile::class, $id)?->toDomain();
    }
    public function all(): iterable
    {
        foreach ($this->manager->getRepository(StoredFile::class)->createQueryBuilder('file')->getQuery()->toIterable() as $entity) {
            yield $entity->toDomain();
        }
    }
}
