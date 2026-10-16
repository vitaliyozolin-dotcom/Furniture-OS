<?php

declare(strict_types=1);

namespace App\Cabinet\Application;

use App\Cabinet\Application\Contract\CabinetConfigurator;
use App\Cabinet\Domain\CabinetConfiguration;
use App\Calculation\Application\Contract\CabinetEstimator;

final readonly class ConfigureCabinet implements CabinetConfigurator
{
    public function __construct(private CabinetEstimator $estimator)
    {
    }
    public function preview(array $configuration): array
    {
        $configuration = CabinetConfiguration::fromArray($configuration)->values;
        return ['configuration' => $configuration, 'estimate' => $this->estimator->estimate($configuration), 'rulesetVersion' => 'prototype-v0'];
    }
}
