<?php

declare(strict_types=1);

namespace App\Rendering\Application;

final readonly class Generation
{
    public function __construct(public string $id, public string $status, public ?Image $image = null, public ?int $costKopecks = null)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{1,200}$/D', $id) || !in_array($status, ['pending', 'processing', 'completed', 'failed', 'cancelled'], true) || ($status === 'completed' && $image === null) || ($costKopecks !== null && $costKopecks < 0)) {
            throw new \InvalidArgumentException('Invalid generation response.');
        }
    }
}
