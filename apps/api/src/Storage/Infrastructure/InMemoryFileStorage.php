<?php

declare(strict_types=1);

namespace App\Storage\Infrastructure;

use App\Storage\Application\FileStorage;
use App\Storage\Domain\StorageKey;

final class InMemoryFileStorage implements FileStorage
{
    private array $objects = [];
    public function write(StorageKey $key, string $bytes, string $sha256): void
    {
        if (strlen($bytes) > 10485760 || !hash_equals($sha256, hash('sha256', $bytes))) {
            throw new \InvalidArgumentException('Size or checksum mismatch.');
        }
        if ($this->exists($key) && $this->objects[$key->value] !== $bytes) {
            throw new \RuntimeException('Storage key already contains different bytes.');
        }
        $this->objects[$key->value] = $bytes;
    }
    public function read(StorageKey $key): string
    {
        return $this->objects[$key->value] ?? throw new \RuntimeException('Object missing.');
    }
    public function exists(StorageKey $key): bool
    {
        return array_key_exists($key->value, $this->objects);
    }
    public function keys(): iterable
    {
        return array_map(static fn (string $key): StorageKey => new StorageKey($key), array_keys($this->objects));
    }
    public function delete(StorageKey $key): void
    {
        unset($this->objects[$key->value]);
    }
}
