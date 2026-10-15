<?php

declare(strict_types=1);

namespace App\Storage\Application;

use App\Storage\Domain\FileMetadata;
use App\Storage\Domain\StorageKey;

final readonly class StorageProbe
{
    public function __construct(private FileStorage $storage, private FileMetadataRepository $metadata)
    {
    }
    public function write(string $id): void
    {
        $key = new StorageKey($id);
        $bytes = "Furniture OS storage probe\n";
        $checksum = hash('sha256', $bytes);
        $this->storage->write($key, $bytes, $checksum);
        $this->metadata->save(new FileMetadata($id, 'system', 'migration-probe', 'text/plain', strlen($bytes), $checksum, $key, new \DateTimeImmutable()));
    }
    public function verify(): array
    {
        $failures = [];
        $known = [];
        foreach ($this->metadata->all() as $file) {
            $known[$file->key->value] = true;
            if (!$this->storage->exists($file->key)) {
                $failures[] = $file->id.':missing';
                continue;
            }
            $bytes = $this->storage->read($file->key);
            if (strlen($bytes) !== $file->size || !hash_equals($file->checksum, hash('sha256', $bytes))) {
                $failures[] = $file->id.':checksum';
            }
        }
        foreach ($this->storage->keys() as $key) {
            if (!isset($known[$key->value])) {
                $failures[] = $key->value.':orphan';
            }
        }
        return $failures;
    }
}
