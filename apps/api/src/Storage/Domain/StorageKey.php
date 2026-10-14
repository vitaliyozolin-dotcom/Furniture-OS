<?php

declare(strict_types=1);

namespace App\Storage\Domain;

final readonly class StorageKey
{
    public function __construct(public string $value)
    {
        if (str_starts_with($value, 'tmp-') || !preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $value)) {
            throw new \InvalidArgumentException('Invalid opaque storage key.');
        }
    }
}
