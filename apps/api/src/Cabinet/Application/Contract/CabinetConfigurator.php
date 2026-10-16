<?php

declare(strict_types=1);

namespace App\Cabinet\Application\Contract;

interface CabinetConfigurator
{
    public function preview(array $configuration): array;
}
