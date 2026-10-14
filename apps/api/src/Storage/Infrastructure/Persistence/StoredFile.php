<?php

declare(strict_types=1);

namespace App\Storage\Infrastructure\Persistence;

use App\Storage\Domain\FileMetadata;
use App\Storage\Domain\StorageKey;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stored_file')]
class StoredFile
{
    #[ORM\Id, ORM\Column(length: 64)] public string $id;
    #[ORM\Column(length: 128)] public string $owner;
    #[ORM\Column(length: 64)] public string $purpose;
    #[ORM\Column(length: 128)] public string $mime;
    #[ORM\Column] public int $size;
    #[ORM\Column(length: 64)] public string $checksum;
    #[ORM\Column(length: 64, unique: true)] public string $storageKey;
    #[ORM\Column(length: 32)] public string $backend = 'local';
    #[ORM\Column(length: 32)] public string $status;
    #[ORM\Column(type: 'datetime_immutable')] public \DateTimeImmutable $createdAt;
    #[ORM\Column(type: 'datetime_immutable')] public \DateTimeImmutable $updatedAt;
    public function __construct(FileMetadata $file)
    {
        foreach (['id', 'owner', 'purpose', 'mime', 'size', 'checksum', 'status', 'createdAt'] as $field) {
            $this->$field = $file->$field;
        }
        $this->storageKey = $file->key->value;
        $this->updatedAt = $file->createdAt;
    }
    public function toDomain(): FileMetadata
    {
        return new FileMetadata($this->id, $this->owner, $this->purpose, $this->mime, $this->size, $this->checksum, new StorageKey($this->storageKey), $this->createdAt, $this->status);
    }
}
