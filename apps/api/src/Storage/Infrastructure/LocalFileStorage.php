<?php

declare(strict_types=1);

namespace App\Storage\Infrastructure;

use App\Storage\Application\FileStorage;
use App\Storage\Domain\StorageKey;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

final class LocalFileStorage implements FileStorage
{
    private readonly Filesystem $filesystem;
    public function __construct(private readonly string $root)
    {
        if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) {
            throw new \RuntimeException('Storage is not writable.');
        }
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($root));
    }
    public function write(StorageKey $key, string $bytes, string $sha256): void
    {
        if (strlen($bytes) > 10485760 || !hash_equals($sha256, hash('sha256', $bytes))) {
            throw new \InvalidArgumentException('Size or checksum mismatch.');
        }
        // A single adapter-wide lock also serializes delete/write on the local volume.
        $lock = fopen($this->root.'/.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('Cannot lock storage.');
        }
        $temporary = 'tmp-'.bin2hex(random_bytes(16));
        try {
            if ($this->exists($key)) {
                if (!hash_equals($sha256, hash('sha256', $this->read($key)))) {
                    throw new \RuntimeException('Storage key already contains different bytes.');
                }
                return;
            }
            $this->filesystem->write($temporary, $bytes, ['visibility' => 'private']);
            if (!hash_equals($sha256, hash('sha256', $this->filesystem->read($temporary)))) {
                throw new \RuntimeException('Stored checksum mismatch.');
            }
            $this->filesystem->move($temporary, $key->value); // atomic rename on one persistent filesystem
        } finally {
            $this->filesystem->delete($temporary);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
    public function read(StorageKey $key): string
    {
        return $this->filesystem->read($key->value);
    }
    public function exists(StorageKey $key): bool
    {
        return $this->filesystem->fileExists($key->value);
    }
    public function keys(): iterable
    {
        $keys = [];
        foreach ($this->filesystem->listContents('', false) as $entry) {
            if ($entry->isFile() && $entry->path() !== '.lock' && !str_starts_with($entry->path(), 'tmp-')) {
                $keys[] = new StorageKey($entry->path());
            }
        }
        return $keys;
    }
    public function delete(StorageKey $key): void
    {
        $lock = fopen($this->root.'/.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('Cannot lock storage.');
        }
        try {
            $this->filesystem->delete($key->value);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
