<?php

declare(strict_types=1);

namespace App\Storage\Application;

use App\Storage\Domain\StorageKey;

interface FileStorage
{
    /** Idempotent if existing bytes match; conflicting writes fail. Zero bytes allowed. */
    public function write(StorageKey $key, string $bytes, string $sha256): void;
    public function read(StorageKey $key): string;
    public function exists(StorageKey $key): bool;
    /** @return iterable<StorageKey> */
    public function keys(): iterable;
    public function delete(StorageKey $key): void;
}
