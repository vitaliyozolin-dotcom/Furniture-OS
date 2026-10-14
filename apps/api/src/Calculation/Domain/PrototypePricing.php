<?php

declare(strict_types=1);

namespace App\Calculation\Domain;

final class PrototypePricing
{
    public function calculate(CabinetInput $input, array $geometry): array
    {
        $c = $input->values;
        $decimal = static fn (int|float|string $number): Decimal => new Decimal(is_float($number) ? rtrim(rtrim(sprintf('%.14F', $number), '0'), '.') : (string) $number);
        $bodyPrice = ['sonoma' => 3650, 'cashmere' => 4100, 'graphite' => 4350][$c['bodyMaterial']];
        $facadePrice = ['chipboard' => 1850, 'mdf' => 5600, 'mirror' => 7200][$c['facadeMaterial']];
        $materials = $decimal($geometry['sheets'])->times($bodyPrice)
            ->plus($decimal($geometry['area']['back'])->times(720))
            ->plus($decimal($geometry['area']['facade'])->times($facadePrice))
            ->plus($decimal($geometry['edgeMeters'])->times(38));
        $hardware = $decimal($geometry['hinges'])->times(210)
            ->plus($decimal($geometry['runners'])->times(1450))
            ->plus($decimal($geometry['rods'])->times(850))
            ->plus($decimal(count($c['sections']))->times(480))->plus(1800);
        $hours = $decimal(5)->plus($decimal(count($c['sections']))->times('1.3'))
            ->plus($decimal($geometry['shelfCount'])->times('0.12'))
            ->plus($decimal($geometry['runners'])->times('0.8'));
        $labor = $hours->times($decimal($c['laborRate'])->value);
        $delivery = $decimal(4500);
        $subtotal = $materials->plus($hardware)->plus($labor)->plus($delivery);
        $margin = $subtotal->times($decimal($c['marginPercent'])->value)->dividedBy(100);
        $total = $subtotal->plus($margin);
        return array_map(static fn (Decimal $amount): string => $amount->value, compact('materials', 'hardware', 'labor', 'delivery', 'margin', 'total'));
    }
}
