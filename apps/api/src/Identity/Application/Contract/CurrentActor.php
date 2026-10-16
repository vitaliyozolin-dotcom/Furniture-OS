<?php

declare(strict_types=1);

namespace App\Identity\Application\Contract;

interface CurrentActor
{
    public function requireId(): string;
    public function profile(): ?array;
}
