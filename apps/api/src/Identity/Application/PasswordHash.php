<?php

declare(strict_types=1);

namespace App\Identity\Application;

interface PasswordHash
{
    public function hash(#[\SensitiveParameter] string $password): string;
}
