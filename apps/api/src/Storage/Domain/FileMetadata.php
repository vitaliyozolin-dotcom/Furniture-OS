<?php

declare(strict_types=1);

namespace App\Storage\Domain;

final readonly class FileMetadata
{
    public function __construct(
        public string $id,
        public string $owner,
        public string $purpose,
        public string $mime,
        public int $size,
        public string $checksum,
        public StorageKey $key,
        public \DateTimeImmutable $createdAt,
        public string $status = 'available',
    ) {
        if ($size < 0 || $size > 10485760 || !in_array($mime, ['application/octet-stream', 'text/plain', 'application/json', 'application/pdf'], true) || !preg_match('/^[a-f0-9]{64}$/D', $checksum)) {
            throw new \InvalidArgumentException('Invalid file metadata.');
        }
    }
}
