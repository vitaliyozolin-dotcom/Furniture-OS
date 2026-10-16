<?php

declare(strict_types=1);

namespace App\Calculation\Application\Contract;

interface CabinetEstimator
{
    public function estimate(array $configuration): array;
}
