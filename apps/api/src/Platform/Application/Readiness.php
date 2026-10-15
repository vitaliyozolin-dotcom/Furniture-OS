<?php

declare(strict_types=1);

namespace App\Platform\Application;

interface Readiness
{
    public function isReady(): bool;
}
