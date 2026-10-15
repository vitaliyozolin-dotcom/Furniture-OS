<?php

declare(strict_types=1);

namespace App\Storage\Application;

use App\Storage\Domain\FileMetadata;

interface FileMetadataRepository
{
    public function save(FileMetadata $file): void;
    public function find(string $id): ?FileMetadata;
    /** @return iterable<FileMetadata> */
    public function all(): iterable;
}
