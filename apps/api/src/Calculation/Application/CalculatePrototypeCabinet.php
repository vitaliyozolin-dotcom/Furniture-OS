<?php

declare(strict_types=1);

namespace App\Calculation\Application;

use App\Calculation\Domain\CabinetInput;
use App\Calculation\Domain\PrototypeGeometry;
use App\Calculation\Domain\PrototypePricing;

final readonly class CalculatePrototypeCabinet
{
    public function __construct(private PrototypeGeometry $geometry, private PrototypePricing $pricing)
    {
    }
    public function calculate(array $values): array
    {
        $input = CabinetInput::fromArray($values);
        $result = $this->geometry->calculate($input);
        $result['costs'] = $this->pricing->calculate($input, $result);
        unset($result['area'], $result['shelfCount']);
        return $result;
    }
}
