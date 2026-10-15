<?php

declare(strict_types=1);

namespace App\Rendering\Infrastructure;

use App\Rendering\Application\Image;
use App\Rendering\Application\RenderJournal;

final readonly class LocalRenderJournal implements RenderJournal
{
    public function __construct(private string $root)
    {
    }

    public function exclusive(callable $operation): mixed
    {
        if (!is_dir($this->root) && !mkdir($this->root, 0700, true) && !is_dir($this->root)) {
            throw new \RuntimeException('Cannot create AI journal directory.');
        }
        $lock = fopen($this->root.'/.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('Cannot lock AI journal.');
        }
        try {
            return $operation();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function records(): array
    {
        $path = $this->root.'/journal.json';
        if (!is_file($path)) {
            return [];
        }
        $records = json_decode($this->read($path), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($records)) {
            throw new \RuntimeException('Invalid AI journal.');
        }
        return $records;
    }

    public function save(string $key, array $record): void
    {
        $this->checkKey($key);
        $records = $this->records();
        $records[$key] = $record;
        $this->write($this->root.'/journal.json', json_encode($records, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    public function saveImage(string $key, Image $image): void
    {
        $this->checkKey($key);
        $this->write($this->root.'/'.$key.'.png', $image->bytes);
    }

    public function image(string $key): Image
    {
        $this->checkKey($key);
        return new Image($this->read($this->root.'/'.$key.'.png'));
    }

    private function checkKey(string $key): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) {
            throw new \InvalidArgumentException('Invalid render key.');
        }
    }

    private function read(string $path): string
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            throw new \RuntimeException('Cannot read AI journal artifact.');
        }
        return $bytes;
    }

    private function write(string $path, string $bytes): void
    {
        $temporary = $path.'.tmp';
        if (file_put_contents($temporary, $bytes) !== strlen($bytes) || !rename($temporary, $path)) {
            throw new \RuntimeException('Cannot persist AI journal artifact.');
        }
    }
}
