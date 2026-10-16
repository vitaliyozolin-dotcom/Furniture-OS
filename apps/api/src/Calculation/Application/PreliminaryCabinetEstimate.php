<?php

declare(strict_types=1);

namespace App\Calculation\Application;

use App\Calculation\Application\Contract\CabinetEstimator;

final readonly class PreliminaryCabinetEstimate implements CabinetEstimator
{
    public function __construct(private CalculatePrototypeCabinet $calculation)
    {
    }
    public function estimate(array $configuration): array
    {
        $result = $this->calculation->calculate([...$configuration, 'marginPercent' => 20, 'laborRate' => 800]);
        // The published prototype-v0 contract uses numbers; decimal arithmetic stays internal.
        $result['costs'] = array_map(static fn (string $amount): float => (float) $amount, $result['costs']);
        return $result;
    }
}
